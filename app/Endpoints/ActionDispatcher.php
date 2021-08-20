<?php declare(strict_types=1);

namespace App\Endpoints;

use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Tracy\Debugger;

class ActionDispatcher implements IPresenter
{
    protected function startup(): void
    {}

    public function blabla(Request $request)
    {
        Debugger::barDump($request);
    }

    public function run(Request $request): Response
    {
        $this->startup();

        Debugger::barDump($request->parameters);

        return new JsonResponse(json_encode(['42']));
    }
}