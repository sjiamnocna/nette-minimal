<?php
declare(strict_types=1);

namespace APIcation\Endpoints;

use APIcation\Endpoints\CAbstractEndpoint;
use APIcation\MyAuthenticator;
use Nette\Application\Responses\JsonResponse;
use Nette\Security\User;

/**
 * Handle user login/logout, keep login state, keepalive session etc.
 */
class EGetuser extends CAbstractEndpoint
{
    private User $User;

    public function injectDefault(
      MyAuthenticator $myAuthenticator,
      User $User
    ){
        $this->User = $User;
    }

    public function __isLogged(): JsonResponse
    {
        bdump($this->User->isLoggedIn());

        return new JsonResponse(['user_logged' => $this->User->isLoggedIn()]);
    }
}