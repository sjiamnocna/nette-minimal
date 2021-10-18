<?php declare(strict_types = 1);

namespace APIcation\Endpoints;

use APIcation\Request;
use Nette\Application\Response;
use Exception;
use Nette\Utils\Reflection;
use Tracy\Debugger;

/**
 * Base class for all Endpoint
 */
abstract class Endpoint
{
    /**
     * Name services with capital letter first to distinguish
     * 
     * @var APIcation\Request
     */
    protected Request $Request;
    
    /**
     * Global run method
     * Expects all methods to return any of Nette\Application\Response objects
     * 
     * @param \APICation\Request
     * 
     * @return Nette\Application\Response
     */
    public function run(Request $Request): Response
    {
        $this->Request = $Request;

        if (!method_exists($this, $Request->getAction())){
            throw new Exception('Action doesn\'t exist');
        }

        // allow specific action for REST methods
        $method = strtolower($this->Request->getMethod());
        $prefixedAction = $method . \ucfirst($this->Request->getAction());

        if (method_exists($this, $prefixedAction)){
            return call_user_func([$this, $prefixedAction]);
        }

        return call_user_func([$this, $this->Request->getAction()]);
    }
}