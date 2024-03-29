<?php

namespace Artcustomer\ApiUnit\Http;

/**
 * @author David
 */
interface IApiResponse extends IHttpItem
{

    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @return string
     */
    public function getReasonPhrase(): string;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return object
     */
    public function getContent();

    /**
     * @return mixed
     */
    public function getCustomData();

    /**
     * @param $customData
     */
    public function setCustomData($customData): void;
}
