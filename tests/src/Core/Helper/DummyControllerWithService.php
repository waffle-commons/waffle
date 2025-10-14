<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Core\BaseController;
use Waffle\Core\View;

/**
 * A dummy controller that requires a service in its action method.
 * This is used to test the service injection capabilities of the Response class.
 */
final class DummyControllerWithService extends BaseController
{
    public function index(DummyService $service): View
    {
        return new View(data: $service->getServiceData());
    }
}
