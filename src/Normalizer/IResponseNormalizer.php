<?php

namespace Artcustomer\ApiUnit\Normalizer;

use Artcustomer\ApiUnit\Http\ApiResponse;
use Artcustomer\ApiUnit\Http\IApiResponse;

interface IResponseNormalizer {

    /**
     * @param string $endpoint
     * @return bool
     */
    public function match(string $endpoint): bool;

    /**
     * @param ApiResponse $response
     * @return IApiResponse
     */
    public function normalize(ApiResponse &$response): IApiResponse;
}
