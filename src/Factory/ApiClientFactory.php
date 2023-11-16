<?php

namespace Artcustomer\ApiUnit\Factory;

use Artcustomer\ApiUnit\Client\AbstractApiClient;

/**
 * @author David
 */
class ApiClientFactory
{

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Create Client
     *
     * @param string $className
     * @param array $arguments
     * @return AbstractApiClient|null
     * @throws \ReflectionException
     */
    public function create(string $className, array $arguments = []): ?AbstractApiClient
    {
        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceArgs($arguments);

        return $instance;
    }
}
