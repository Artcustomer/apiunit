<?php

namespace Artcustomer\ApiUnit\Client;

use Artcustomer\ApiUnit\Enum\ClientConfig;
use Artcustomer\ApiUnit\Event\IApiEventHandler;
use Artcustomer\ApiUnit\Factory\ApiEventFactory;
use Artcustomer\ApiUnit\Factory\ApiRequestFactory;
use Artcustomer\ApiUnit\Factory\ApiResponseFactory;
use Artcustomer\ApiUnit\Http\IApiRequest;
use Artcustomer\ApiUnit\Http\IApiResponse;
use Artcustomer\ApiUnit\Http\IHttpItem;
use Artcustomer\ApiUnit\Logger\IApiLogger;
use Artcustomer\ApiUnit\Mock\IAPIMock;
use Artcustomer\ApiUnit\Normalizer\IResponseNormalizer;

/**
 * @author David
 */
abstract class AbstractApiClient
{

    /**
     * @var array
     */
    protected $apiParams;

    /**
     * @var array
     */
    protected $clientConfig = [];

    /**
     * @var ApiResponseFactory
     */
    protected $responseFactory;

    /**
     * @var ApiRequestFactory
     */
    protected $requestFactory;

    /**
     * @var ApiEventFactory
     */
    protected $eventFactory;

    /**
     * @var IApiEventHandler
     */
    protected $eventHandler;

    /**
     * @var IApiLogger
     */
    protected $apiLogger;

    /**
     * @var string
     */
    protected $responseDecoratorClassName;

    /**
     * @var array
     */
    protected $responseDecoratorArguments = [];

    /**
     * @var string
     */
    protected $requestClassName;

    /**
     * @var array
     */
    protected $requestArguments = [];

    /**
     * @var bool
     */
    protected $enableEvents = false;

    /**
     * @var bool
     */
    protected $enableListeners = false;

    /**
     * @var bool
     */
    protected $enableMocks = true;

    /**
     * @var bool
     */
    protected $isOperational = false;

    /**
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * @var bool
     */
    protected $debugMode = false;

    /**
     * @var array
     */
    private $normalizers = [];

    /**
     * @var array
     */
    private $mocks = [];

    /**
     * Constructor
     * @param array $apiParams
     * @param array $clientConfig
     * @see ClientConfig
     *
     */
    public function __construct(array $apiParams = [], array $clientConfig = [])
    {
        $this->apiParams = $apiParams;
        $this->clientConfig = $clientConfig;
    }

    /**
     * Initialize client
     */
    abstract public function initialize(): void;

    /**
     * Setup Client
     */
    abstract protected function setupClient(): void;

    /**
     * Do sync request
     *
     * @param IApiRequest $request
     * @return IApiResponse
     */
    abstract protected function doRequest(IApiRequest $request): IApiResponse;

    /**
     * Do async request
     *
     * @param IApiRequest $request
     * @return IApiResponse
     */
    abstract protected function doRequestAsync(IApiRequest $request): IApiResponse;

    /**
     * Build and execute sync request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $query
     * @param null $body
     * @param array $headers
     * @param bool $async
     * @param bool $secured
     * @param null $customData
     * @return IApiResponse
     */
    public function request(string $method, string $endpoint, array $query = [], $body = null, array $headers = [], $async = false, $secured = false, $customData = null): IApiResponse
    {
        if (!$this->isOperational) {
            return $this->responseFactory->create(500, 'Error while sending request', 'API is not operational, check parameters and initialization state.');
        }

        if (!$this->isEnabled) {
            return $this->responseFactory->create(500, 'Error while sending request', 'API is not enabled.');
        }

        $this->preBuildRequest($method, $endpoint, $query, $body, $headers);
        $request = $this->buildRequest($method, $endpoint, $query, $body, $headers, $async, $secured, $customData);

        if (null !== $request) {
            if ($async) {
                return $this->doRequestAsync($request);
            }

            return $this->doRequest($request);
        }

        return $this->responseFactory->create(500, 'Error while building request', sprintf('Unable to build %s request for endpoint "%s"', $method, $endpoint));
    }

    /**
     * Execute pre-built request
     *
     * @param IApiRequest|null $request
     * @return IApiResponse
     */
    public function executeRequest(?IApiRequest $request): IApiResponse
    {
        if (!$this->isOperational) {
            return $this->responseFactory->create(500, 'Error while building request', 'API is not operational, check parameters and initialization state.');
        }

        if (!$this->isEnabled) {
            return $this->responseFactory->create(500, 'Error while sending request', 'API is not enabled.');
        }

        if (null !== $request) {
            if ($request->isAsync()) {
                return $this->doRequestAsync($request);
            }

            return $this->doRequest($request);
        }

        return $this->responseFactory->create(500, 'Error while sending request', 'Request is not well formed');
    }

    /**
     * Register normalizer
     *
     * @param string $className
     * @param array $params
     * @throws \ReflectionException
     */
    public function registerNormalizer(string $className, array $params = []): void
    {
        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceArgs($params);
        $rule = $instance->getRule();

        if (empty($rule)) {
            throw new \Exception('Cannot register a Normalizer with empty property "rule"');
        }

        if (array_key_exists($rule, $this->normalizers)) {
            throw new \Exception(sprintf('A Normalizer is already registered with the rule "%s"', $rule));
        }

        $this->normalizers[$rule] = $instance;
    }

    /**
     * Unregister normalizer
     *
     * @param string $rule
     * @return bool
     */
    public function unregisterNormalizer(string $rule): bool
    {
        if (!empty($rule)) {
            if (array_key_exists($rule, $this->normalizers)) {
                unset($this->normalizers[$rule]);

                return true;
            }
        }

        return false;
    }

    /**
     * Add Mock
     *
     * @param string $className
     * @param array $params
     * @throws \ReflectionException
     */
    public function addMock(string $className, array $params = []): void
    {
        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceArgs($params);
        $name = $instance->getName();

        if (empty($name)) {
            throw new \Exception('Cannot add a Mock with empty property "name"');
        }

        if (array_key_exists($name, $this->mocks)) {
            throw new \Exception(sprintf('A Mock is already added with the name "%s"', $name));
        }

        $instance->build();

        $this->mocks[$name] = $instance;
    }

    /**
     * Remove mock
     *
     * @param string $name
     * @return bool
     */
    public function removeMock(string $name): bool
    {
        if (!empty($name)) {
            if (array_key_exists($name, $this->mocks)) {
                unset($this->mocks[$name]);

                return true;
            }
        }

        return false;
    }

    /**
     * Trigger listener
     *
     * @param string $listener
     * @param IHttpItem $httpItem
     * @return void
     */
    protected function triggerListener(string $listener, IHttpItem $httpItem): void
    {
        if ($this->enableListeners) {
            if (method_exists($httpItem, $listener)) {
                call_user_func([$httpItem, $listener]);
            }
        }
    }

    /**
     * Trigger event
     *
     * @param string $eventType
     * @param IApiRequest $request
     * @param IApiResponse|null $response
     */
    protected function triggerEvent(string $eventType, IApiRequest $request, IApiResponse $response = null): void
    {
        if ($this->enableEvents) {
            $this->onEvent($eventType, $request, $response);
        }
    }

    /**
     * Trigger external event
     *
     * @param string $eventName
     * @param IApiRequest|null $request
     * @param IApiResponse|null $response
     */
    protected function triggerExternalEvent(string $eventName, IApiRequest $request = null, IApiResponse $response = null)
    {
        if ($this->enableEvents) {
            if (null !== $this->eventHandler) {
                $event = $this->eventFactory->create(ApiEventFactory::TYPE_EXTERNAL, $eventName, $request, $response);

                if (null !== $event) {
                    $this->eventHandler->handleEvent($event);
                }
            }
        }
    }

    /**
     * Event callback
     *
     * @param string $eventType
     * @param IApiRequest $request
     * @param IApiResponse|null $response
     */
    protected function onEvent(string $eventType, IApiRequest $request, IApiResponse $response = null): void
    {
        // Override it only if you need it
        // Set 'enableEvents' to true to get the callback
    }

    /**
     * Callback before building request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $query
     * @param $body
     * @param array $headers
     */
    protected function preBuildRequest(string $method, string $endpoint, array $query = [], $body = null, array $headers = []): void
    {
        // Override it only if you need it
    }

    /**
     * Initialize client
     * Call this method after params setup
     */
    protected function init()
    {
        $this->isOperational = true;

        $this->setupClientConfig();
        $this->setupClient();
        $this->buildDependencies();
    }

    /**
     * Build request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $query
     * @param null $body
     * @param array $headers
     * @param bool $async
     * @param bool $secured
     * @param null $customData
     * @return null|IApiRequest
     */
    protected function buildRequest(string $method, string $endpoint, array $query = [], $body = null, array $headers = [], $async = false, $secured = false, $customData = null): ?IApiRequest
    {
        return $this->requestFactory->create($this->requestClassName, $this->requestArguments, $method, $endpoint, $query, $body, $headers, $async, $secured, $customData);
    }

    /**
     * Apply normalizer
     *
     * @param IApiRequest $request
     * @param IApiResponse $response
     * @return IApiResponse
     */
    protected function applyNormalizer(IApiRequest $request, IApiResponse &$response): IApiResponse
    {
        /** @var IResponseNormalizer $normalizer */
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->match($request->getEndpoint())) {
                return $normalizer->normalize($response);
            }
        }

        return $response;
    }

    /**
     * Get available mock for request
     *
     * @param IApiRequest $request
     * @return IAPIMock
     */
    protected function getAvailableMock(IApiRequest $request): ?IApiMock
    {
        /** @var IApiMock $mock */
        foreach ($this->mocks as $mock) {
            if ($mock->match($request->getEndpoint())) {
                return $mock;
            }
        }

        return null;
    }

    /**
     * Build client dependencies
     *
     * @throws \ReflectionException
     */
    private function buildDependencies()
    {
        $decorator = null;

        if (null !== $this->responseDecoratorClassName) {
            $reflection = new \ReflectionClass($this->responseDecoratorClassName);
            $decorator = $reflection->newInstanceArgs($this->responseDecoratorArguments);
        }

        $this->responseFactory = new ApiResponseFactory($decorator);
        $this->requestFactory = new ApiRequestFactory($this->apiParams);
        $this->eventFactory = new ApiEventFactory();
    }

    /**
     * @return void
     */
    private function setupClientConfig()
    {
        $this->enableListeners = $this->clientConfig[ClientConfig::ENABLE_LISTENERS] ?? true;
        $this->enableEvents = $this->clientConfig[ClientConfig::ENABLE_EVENTS] ?? false;
        $this->enableMocks = $this->clientConfig[ClientConfig::ENABLE_MOCKS] ?? false;
        $this->debugMode = $this->clientConfig[ClientConfig::DEBUG_MODE] ?? false;
    }

    /**
     * @param IApiLogger $apiLogger
     */
    public function setApiLogger(IApiLogger $apiLogger): void
    {
        $this->apiLogger = $apiLogger;
    }

    /**
     * @param IApiEventHandler $eventHandler
     */
    public function setEventHandler(IApiEventHandler $eventHandler): void
    {
        $this->eventHandler = $eventHandler;
    }

    /**
     * @param array $clientConfig
     * @return void
     */
    public function setClientConfig(array $clientConfig = []): void
    {
        $this->clientConfig = $clientConfig;
    }

    /**
     * @return ApiResponseFactory
     */
    public function getResponseFactory(): ApiResponseFactory
    {
        return $this->responseFactory;
    }

    /**
     * @return ApiRequestFactory
     */
    public function getRequestFactory(): ApiRequestFactory
    {
        return $this->requestFactory;
    }
}
