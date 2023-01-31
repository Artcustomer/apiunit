<?php

namespace Artcustomer\ApiUnit\Event;

use Artcustomer\ApiUnit\Http\IApiRequest;
use Artcustomer\ApiUnit\Http\IApiResponse;

interface IApiEvent {

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return IApiRequest
     */
    public function getRequest(): IApiRequest;

    /**
     * @return IApiResponse
     */
    public function getResponse(): IApiResponse;
}
