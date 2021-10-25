<?php declare(strict_types = 1);

namespace APIcation\Endpoints;

use Nette\Application\Responses\JsonResponse;

class EHello extends AbstractEndpoint
{
    public function default()
    {
        return new JsonResponse('Hello world!');
    }
}