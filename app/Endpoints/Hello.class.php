<?php declare(strict_types = 1);

namespace APIcation\Endpoints;

use Nette\Application\Responses\JsonResponse;

/**
 * Very useful and highly configurable endpoint passes Hello string from configuration
 */
class EHello extends AbstractEndpoint
{
    public function default()
    {
        return new JsonResponse([
            'hello' => $this->params['hello']
        ]);
    }
}