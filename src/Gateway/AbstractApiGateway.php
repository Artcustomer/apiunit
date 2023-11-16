<?php

namespace Artcustomer\ApiUnit\Gateway;

use Artcustomer\ApiUnit\Client\AbstractApiClient;
use Artcustomer\ApiUnit\Event\IApiEventHandler;
use Artcustomer\ApiUnit\Factory\ApiClientFactory;
use Artcustomer\ApiUnit\Http\IApiResponse;
use Artcustomer\ApiUnit\Logger\IApiLogger;

/**
 * @author David
 */
abstract class AbstractApiGateway
{

    /**
     * @var ApiClientFactory
     */
    private ApiClientFactory $clientFactory;

    /**
     * @var AbstractApiClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $clients = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Constructor
     *
     * @param string|null $clientClassName
     * @param array $clientArguments
     * @throws \ReflectionException
     */
    public function __construct(string $clientClassName = null, array $clientArguments = [])
    {
        $this->buildDependencies();

        if (null !== $clientClassName) {
            $this->addClient($clientClassName, $clientArguments, true);
        }
    }

    /**
     * @return void
     */
    private function buildDependencies()
    {
        $this->clientFactory = new ApiClientFactory();
    }

    /**
     * Initialize statement
     */
    abstract public function initialize(): void;

    /**
     * Implement a call to test API
     *
     * @return IApiResponse
     */
    abstract public function test(): IApiResponse;

    /**
     * Add client
     *
     * @param string $className
     * @param array $arguments
     * @param bool $setAsDefault
     * @return object|AbstractApiClient|null
     * @throws \ReflectionException
     */
    public function addClient(string $className, array $arguments = [], bool $setAsDefault = true): object
    {
        if ($this->hasClient($className)) {
            throw new \Exception(sprintf('Client %s already exists !', $className));
        }

        $client = $this->clientFactory->create($className, $arguments);

        $this->clients[$className] = $client;

        if ($setAsDefault) {
            $this->setDefaultClient($className);
        }

        return $client;
    }

    /**
     * Get client
     *
     * @param string $className
     * @return object|mixed|null
     */
    public function getClient(string $className): ?object
    {
        $instance = null;

        if ($this->hasClient($className)) {
            $instance = $this->clients[$className];
        }

        return $instance;
    }

    /**
     * Remove client
     *
     * @param string $className
     * @return bool
     */
    public function removeClient(string $className): bool
    {
        $status = false;

        if ($this->hasClient($className)) {
            unset($this->clients[$className]);

            $status = true;
        }

        return $status;
    }

    /**
     * Has client
     *
     * @param string $className
     * @return bool
     */
    public function hasClient(string $className): bool
    {
        return array_key_exists($className, $this->clients);
    }

    /**
     * Set default client
     *
     * @param string $className
     * @return void
     */
    public function setDefaultClient(string $className)
    {
        $instance = $this->getClient($className);

        if (null !== $instance) {
            $this->client = $instance;
        }
    }

    /**
     * Set IApiLogger instance to one or multiple clients
     *
     * @param IApiLogger $apiLogger
     * @param array $classNames
     * @return void
     */
    public function setApiLogger(IApiLogger $apiLogger, array $classNames = []): void
    {
        if (empty($classNames)) {
            $classNames = array_keys($this->clients);
        }

        foreach ($classNames as $className) {
            $client = $this->getClient($className);

            if (null !== $client) {
                $client->setApiLogger($apiLogger);
            }
        }
    }

    /**
     * Set IApiEventHandler instance to one or multiple clients
     *
     * @param IApiEventHandler $eventHandler
     * @param array $classNames
     * @return void
     */
    public function setEventHandler(IApiEventHandler $eventHandler, array $classNames = []): void
    {
        if (empty($classNames)) {
            $classNames = array_keys($this->clients);
        }

        foreach ($classNames as $className) {
            $client = $this->getClient($className);

            if (null !== $client) {
                $client->setEventHandler($eventHandler);
            }
        }
    }

    /**
     * Set configuration to one or multiple clients
     *
     * @param array $clientConfig
     * @param array $classNames
     * @return void
     */
    public function setClientConfig(array $clientConfig = [], array $classNames = []): void
    {
        if (empty($classNames)) {
            $classNames = array_keys($this->clients);
        }

        foreach ($classNames as $className) {
            $client = $this->getClient($className);

            if (null !== $client) {
                $client->setClientConfig($clientConfig);
            }
        }
    }
}
