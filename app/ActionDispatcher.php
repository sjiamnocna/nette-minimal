<?php declare(strict_types=1);

namespace App\Endpoints;

use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Tracy\Debugger;

class ActionDispatcher implements IPresenter
{

    /**
     * @var Container DI Container
     */
    protected ?Container $container = null;

    protected IRequest $httpRequest;

    protected IResponse $httpResponse;

    protected ?Session $session = null;

    protected ?User $user = null;

    /**
     * @var callable[]  Application startup event actions 
     */
    protected array $onStartup = [];

    /**
     * @var callable[]  Application shutdown event actions 
     */
    protected array $onShutdown = [];

    /**
     * Post data got from _POST or STDIN
     */
    protected array $postData = [];

    final public function injectDefault(
		Container $container,
        IRequest $httpRequest,
        IResponse $httpResponse,
        Session $session = null,
        User $user = null
	): void {
		if ($this->container !== null) {
			throw new \Nette\InvalidStateException('Method ' . __METHOD__ . ' is intended for initialization and should not be called more than once.');
		}

		$this->container = $container;
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->session = $session;
		$this->user = $user;
	}

    /**
     * Do something on app startup
     */
    protected function startup(): void
    {
        // allow 
        Arrays::invoke($this->onStartup, $this);

        $headers = $this->httpRequest->getHeaders();

        // correctly get data from Fetch XHR method
        if (($headers['x-requested-with'] ?? false) === 'XMLHttpRequest' && empty($this->httpRequest->getPost()) && $headers['content-type'] === 'application/json'){
            /**
             * @var string  String data from STDIN (Fetch error)
             */
            $fetchSource = file_get_contents('php://input');
            $postData = @json_decode($fetchSource, true);

            if ($postData){
                //$this->httpRequest->setPost($postData)
            }
        }
    }

    /**
     * Allow access and other custom headers
     */
    protected function setAdditionalHeaders(): void
    {
        $origin = $this->httpRequest->getHeader('origin');
		if($origin !== NULL)
		{
            $this->httpResponse->addHeader('Access-Control-Allow-Origin', $origin);
            $this->httpResponse->addHeader('Access-Control-Allow-Credentials', 'true');
            //...PUT, DELETE, PATCH, OPTIONS
            $this->httpResponse->addHeader('Access-Control-Allow-Methods', 'GET, POST');
            $this->httpResponse->addHeader('Access-Control-Allow-Headers', 'origin, content-type, accept, x-tracy-ajax');
            $this->httpResponse->addHeader('Access-Control-Expose-Headers', 'origin, location, content-type, accept, x-tracy-ajax');

            $this->httpResponse->addHeader('Access-Control-Max-Age', "1728000");
        }

    }

    public function run(Request $request): Response
    {
        // startup actions

        $this->startup();
        $this->setAdditionalHeaders();

        if(!isset($request->parameters['res']) || empty($request->parameters['res'])){
            throw new BadRequestException('No resource specified');
        }

        /**
         * @var string full class path to the resource manipulation class, ALL ENDPOINT CLASS START WITH E LETTER
         */
        $module = $request->parameters['res'] ? 'E' . ucfirst($request->parameters['res']) : null;
        $method = $request->parameters['method'] ?? 'default';

        if (!class_exists($module)){
            throw new BadRequestException('No possible response for this request');
        }
        // get service
        $class = $this->container->getByType($module);
        // we need to run this command but it doesn't exist
        if(!method_exists($class, $method)){
            throw new BadRequestException('Action not found');
        }

        // run wanted class method and return it's content
        return call_user_func([$class, 'run'], $request->getPost());
    }
}