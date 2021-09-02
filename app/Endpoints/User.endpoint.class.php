<?php declare(strict_types = 1);

namespace APIcation\Endpoints;

use Exception;
use APIcation\MyAuthenticator;
use Nette\Application\Responses\JsonResponse;
use Nette\InvalidArgumentException;
use Nette\Security\User;
use Tracy\Debugger;

class EUser extends Endpoint
{
    /**
     * User login, session
     */
    protected ?User $User = null;

    public function injectSpecific(
        User $User,
        MyAuthenticator $Authenticator
    ): void
    {
        $this->User = $User;
        $this->User->setAuthenticator($Authenticator);
    }

    /**
     * Login user, no types for parameter, let the method handle data errors
     */
    protected function login(): JsonResponse
    {
        ['username' => $username, 'password' => $password] = $this->Request->getPost();

        $this->User->login($username, $password);

        return new JsonResponse([
            'success' => $this->User->isLoggedIn(),
            'identity' => (array) $this->User->getIdentity()
        ]);
    }

    /**
     * If user is logged or not
     */
    protected function isLogged(): JsonResponse
    {
        return new JsonResponse(['logged_in' => $this->User->isLoggedIn()]);
    }

    protected function getIdentity()
    {
        return new JsonResponse(['identity' => $this->User->getIdentity()]);
    }

    /**
     * Logout user
     */
    protected function logout(): JsonResponse
    {
        if ($this->User->isLoggedIn()){
            $this->User->logout();
            $success = true;
        }
        
        return new JsonResponse(['success' => $success ?? false]);
    }
}