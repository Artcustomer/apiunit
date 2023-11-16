<?php

namespace Artcustomer\ApiUnit\Client;

use Artcustomer\ApiUnit\Enum\ClientConfig;
use Artcustomer\ApiUnit\Http\CurlApiRequest;
use Artcustomer\ApiUnit\Http\IApiRequest;
use Artcustomer\ApiUnit\Http\IApiResponse;
use Artcustomer\ApiUnit\Mock\CurlApiMock;
use Artcustomer\ApiUnit\Mock\IApiMock;
use Artcustomer\ApiUnit\Utils\ApiEventTypes;
use Artcustomer\ApiUnit\Utils\ApiListenerTypes;
use Artcustomer\ApiUnit\Utils\ApiMethodTypes;

/**
 * @author David
 */
class CurlApiClient extends AbstractApiClient
{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Constructor
     *
     * @param array $params
     * @param array $clientConfig
     */
    public function __construct(array $params, array $clientConfig = [])
    {
        parent::__construct($params, $clientConfig);

        $this->requestClassName = CurlApiRequest::class;
        $this->config = [
            ClientConfig::ENABLE_PROXY => false
        ];
        $this->options = [
            CURLOPT_VERBOSE => 0
        ];
    }

    /**
     * Initialize Client
     */
    public function initialize(): void
    {

    }

    /**
     * Setup Curl Client
     */
    protected function setupClient(): void
    {

    }

    /**
     * Do sync request
     *
     * @param IApiRequest $request
     * @return IApiResponse
     */
    protected function doRequest(IApiRequest $request): IApiResponse
    {
        $this->triggerEvent(ApiEventTypes::PRE_EXECUTE, $request);
        $this->triggerExternalEvent(ApiEventTypes::PRE_EXECUTE, $request);
        $this->triggerListener(ApiListenerTypes::PRE_EXECUTE, $request);

        /** @var CurlApiMock $mock */
        $mock = $this->applyMock($request);

        if (null !== $mock) {
            $result = [
                'status' => $mock->getStatus(),
                'result' => $mock->getContent(),
                'error' => false,
                'message' => ''
            ];
        } else {
            $result = $this->executeCurl($request);
        }

        if (false === $result['error']) {
            $statusCode = $result['status'] !== 0 ? $result['status'] : 500;
            $content = $result['result'] ?? null;
            $response = $this->responseFactory->create($statusCode, '', '', $content, $request->getCustomData());

            $this->applyNormalizer($request, $response);
            $this->triggerEvent(ApiEventTypes::EXECUTION_SUCCESS, $request, $response);
            $this->triggerExternalEvent(ApiEventTypes::N_SUCCESS, $request, $response);
        } else {
            $response = $this->responseFactory->create($result['status'], '', $result['message'], null, $request->getCustomData());

            $this->triggerEvent(ApiEventTypes::EXECUTION_ERROR, $request, $response);
            $this->triggerExternalEvent(ApiEventTypes::N_ERROR, $request, $response);
        }

        $this->triggerEvent(ApiEventTypes::POST_EXECUTE, $request, $response);
        $this->triggerExternalEvent(ApiEventTypes::POST_EXECUTE, $request, $response);
        $this->triggerListener(ApiListenerTypes::POST_EXECUTE, $request);

        return $response;
    }

    /**
     * Do async request
     *
     * @param IApiRequest $request
     * @return IApiResponse
     */
    protected function doRequestAsync(IApiRequest $request): IApiResponse
    {
        // Not implemented yet...

        $response = $this->responseFactory->create(500);

        return $response;
    }

    /**
     * Apply mock if available
     *
     * @param IApiRequest $request
     * @return null|IApiMock
     */
    protected function applyMock(IApiRequest $request): ?IApiMock
    {
        if (true === $this->enableMocks) {
            /** @var IApiMock $mock */
            $mock = $this->getAvailableMock($request);

            if (null !== $mock && $mock instanceof CurlApiMock) {
                return $mock;
            }
        }

        return null;
    }

    /**
     * Execute curl
     *
     * @param CurlApiRequest $request
     * @return array
     */
    private function executeCurl(CurlApiRequest $request)
    {
        $curlOptions = array_replace($this->options, $request->getOptions());
        $curlResult = [
            'status' => 500,
            'result' => null,
            'error' => true,
            'message' => ''
        ];
        $result = false;
        $ch = $request->getCurlResource();

        curl_setopt($ch, CURLOPT_URL, $request->getUri());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($ch, CURLOPT_HEADER, true);

        switch ($request->getMethod()) {
            case ApiMethodTypes::GET:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;

            case ApiMethodTypes::POST:
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ApiMethodTypes::POST);

                if (null !== $request->getBody()) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getBody());
                }
                break;

            case ApiMethodTypes::PUT:
                if (null !== $request->getBody()) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getBody());
                }
                break;

            default:
                break;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->prepareHeader($request->getHeaders()));

        if (true === $this->config['enableProxy']) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy']);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['proxyuserpwd']);
        }

        foreach ($curlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        try {
            $result = curl_exec($ch);
        } catch (\Exception $e) {
            $curlResult['status'] = 500;
            $curlResult['result'] = null;
            $curlResult['error'] = true;
            $curlResult['message'] = $e->getMessage();
        }

        if (false !== $result) {
            $curlResult['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlResult['result'] = $result;
            $curlResult['error'] = false;
        } else {
            $curlError = curl_error($ch);
            $errorMessage = 'Unknown';

            if ('' !== $curlError) {
                $errorMessage = $curlError;
            }

            $curlResult['status'] = 500;
            $curlResult['result'] = null;
            $curlResult['error'] = true;
            $curlResult['message'] = sprintf('Curl error : %s', $errorMessage);
        }

        $curlResult['info'] = curl_getinfo($ch);

        curl_close($ch);

        return $curlResult;
    }

    /**
     * Prepare header
     *
     * @param array $headers
     * @return array
     */
    private function prepareHeader(array $headers): array
    {
        $header = [];

        foreach ($headers as $key => $value) {
            $header[] = sprintf('%s:%s', $key, $value);
        }

        return $header;
    }
}
