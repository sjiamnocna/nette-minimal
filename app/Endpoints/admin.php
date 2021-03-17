<?php declare(strict_types=1);

namespace App\Endpoints;

use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Request;

class MyEndpoint implements IPresenter
{
    public function run(Request $request): Response
    {
        return new TextResponse(var_export($request, true));
    }
}