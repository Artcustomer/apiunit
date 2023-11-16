<?php

namespace Artcustomer\ApiUnit\Connector;

use Artcustomer\ApiUnit\Client\AbstractApiClient;

/**
 * @author David
 */
abstract class AbstractConnector
{

    protected AbstractApiClient $client;

    /**
     * Constructor
     *
     * @param AbstractApiClient $client
     * @param bool $initializeClient
     */
    public function __construct(AbstractApiClient $client, bool $initializeClient = true)
    {
        $this->client = $client;

        if ($initializeClient) {
            $this->client->initialize();
        }
    }

    /**
     * @return AbstractApiClient
     */
    public function getClient(): AbstractApiClient
    {
        return $this->client;
    }
}
