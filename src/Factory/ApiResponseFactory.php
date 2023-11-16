<?php

namespace Artcustomer\ApiUnit\Factory;

use Artcustomer\ApiUnit\Http\ApiResponse;
use Artcustomer\ApiUnit\Http\IApiResponse;

/**
 * @author David
 */
class ApiResponseFactory
{

    /**
     * @var ApiResponse
     */
    private $responseDecorator;

    /**
     * Constructor
     *
     * @param ApiResponse|null $responseDecorator
     */
    public function __construct(ApiResponse $responseDecorator = null)
    {
        $this->responseDecorator = $responseDecorator;
    }

    /**
     * Create ApiResponse instance
     *
     * @param int $statusCode
     * @param string $reasonPhrase
     * @param string $message
     * @param null $content
     * @param null $customData
     * @return IApiResponse
     */
    public function create(int $statusCode, string $reasonPhrase = '', string $message = '', $content = null, $customData = null): IApiResponse
    {
        $response = new ApiResponse();

        if (null !== $this->responseDecorator) {
            $response = $this->responseDecorator;
        }

        $response->setStatusCode($statusCode);
        $response->setReasonPhrase($reasonPhrase);
        $response->setMessage($message);
        $response->setContent($content);
        $response->setCustomData($customData);

        return $response;
    }
}
