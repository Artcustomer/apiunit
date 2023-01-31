<?php

namespace Artcustomer\ApiUnit\Event;

interface IApiEventHandler {

    /**
     * @param IApiEvent $event
     */
    public function handleEvent(IApiEvent $event): void;
}
