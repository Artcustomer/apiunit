<?php

namespace Artcustomer\ApiUnit\Normalizer;

use Artcustomer\ApiUnit\Http\ApiResponse;
use Artcustomer\ApiUnit\Http\IApiResponse;

/**
 * @author David
 */
abstract class AbstractApiResponseNormalizer implements IResponseNormalizer
{

    /**
     * @var string
     */
    protected $rule;

    /**
     * @var string
     */
    protected $pattern;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Normalize response data
     *
     * @param ApiResponse $response
     * @return IApiResponse
     */
    abstract public function normalize(ApiResponse &$response): IApiResponse;

    /**
     * Check regex match
     *
     * @param string $endpoint
     * @return bool
     */
    public function match(string $endpoint): bool
    {
        return 1 === preg_match($this->pattern, $endpoint);
    }

    /**
     * Get rule
     *
     * @return string
     */
    public function getRule(): string
    {
        return $this->rule;
    }
}
