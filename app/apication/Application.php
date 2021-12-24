<?php declare(strict_types=1);

namespace APIcation;

use APIcation\Endpoints\Endpoint;
use Nette;
use Nette\Utils\Arrays;
use APIcation\Request;
use APIcation\Security\SessionManager;
use Nette\Application\Responses\ForwardResponse;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Session;
use Throwable;
use Tracy\Debugger;

/**
 * API entrypoint controller, complementary to Nette\Aplication
 */
class Application
{
	use Nette\SmartObject;

	/** @var array Parameters from config files */
	private array $params;

	// use first letter capital for Objects and Services
	/** @var Nette\Http\Container */
	private Container $Container;

	/** @var Nette\Http\IRequest */
	private IRequest $HttpRequest;

	/** @var Nette\Http\IResponse */
	private IResponse $HttpResponse;

	/** @var SessionManager */
	private SessionManager $SessionManager;

	/** @var Endpoint Current endpoint in use object */
	private Endpoint $Endpoint;

	/** @var array<callable(self): void>  Occurs before the application loads presenter */
	public array $onStartup = [];

	/** @var array<callable(self, ?\Throwable): void>  Occurs before the application shuts down */
	public array $onShutdown = [];

	/** @var array<callable(self, Request): void>  Occurs when a new request is received */
	public array $onRequest = [];

	/** @var array<callable(self, IPresenter): void>  Occurs when a presenter is created */
	public array $onPresenter = [];

	/** @var array<callable(self, Response): void>  Occurs when a new response is ready for dispatch */
	public array $onResponse = [];

	/** @var array<callable(self, \Throwable): void>  Occurs when an unhandled exception occurs in the application */
	public array $onError = [];

	/** @var array<Request> Array of requests to determine cycles */
	private array $requests = [];

	const maxLoop = 20;

	public function __construct(
		array $params,
		Nette\Http\IRequest $HttpRequest,
		Nette\Http\IResponse $HttpResponse,
		Container $Container,
		Session $Session
	)
	{
		$this->params = $params;
		$this->HttpRequest = $HttpRequest;
		$this->HttpResponse = $HttpResponse;
		$this->Container = $Container;

		$Session->start();
		$this->checkSecurityHeaders();
	}

	/**
	 * Startup security checks before the application is created
	 */
	public function checkSecurityHeaders(): void
	{
		$headers = $this->HttpRequest->getHeaders();

		if ((!($headers[SessionManager::HEADER_ACCESS_KEY] ?? false) && !($headers[SessionManager::HEADER_SERVICE_NAME] ?? false)) ||
			empty($headers[SessionManager::HEADER_ACCESS_KEY]) || empty($headers[SessionManager::HEADER_SERVICE_NAME])) {
			// either one must be specified or shut down the application to don't waste power on this request

			bdump($headers);
			bdump($headers[SessionManager::HEADER_ACCESS_KEY] ?? false, SessionManager::HEADER_ACCESS_KEY);
			bdump($headers[SessionManager::HEADER_SERVICE_NAME] ?? false, SessionManager::HEADER_SERVICE_NAME);
			
			http_response_code(403);
			exit;
		}
	}

	/**
	 * Runs security after Request is created and before any endpoint is used or any other action
	 */
	private function checkSessionService(Request $Request): void
	{
		/** @var string */
		$accessKey = $Request->getHeader(SessionManager::HEADER_ACCESS_KEY);
		/** @var string */
		$serviceName = $Request->getHeader(SessionManager::HEADER_SERVICE_NAME);
		/** @var string */
		$serviceKey = $Request->getHeader(SessionManager::HEADER_SERVICE_KEY);

		$this->SessionManager = $this->getSessionManager();

		bdump($accessKey, SessionManager::HEADER_ACCESS_KEY);
		bdump($serviceName, SessionManager::HEADER_SERVICE_NAME);
		bdump($serviceKey, SessionManager::HEADER_SERVICE_KEY);
		bdump(SessionManager::ENDPOINT_NAME);
		// if access token is specified, just find it and unlock it
		if (strpos($_SERVER['REQUEST_URI'], SessionManager::ENDPOINT_NAME)){
			// we're running the security commands
			//$this->SessionManager->initConnection();
			bdump(42);
		}

		exit;
	}

	/**
     * Allow access and other custom headers
     */
    protected function setAdditionalHeaders(): void
    {
        $origin = $this->HttpRequest->getHeader('origin');
		if($origin !== NULL){
            $this->HttpResponse->addHeader('Access-Control-Allow-Origin', $origin);
            $this->HttpResponse->addHeader('Access-Control-Allow-Credentials', 'true');
            //...PUT, DELETE, PATCH, OPTIONS
            $this->HttpResponse->addHeader('Access-Control-Allow-Methods', 'GET, POST');
            $this->HttpResponse->addHeader('Access-Control-Allow-Headers', 'origin, content-type, accept, x-tracy-ajax');
            $this->HttpResponse->addHeader('Access-Control-Expose-Headers', 'origin, location, content-type, accept, x-tracy-ajax');

            $this->HttpResponse->addHeader('Access-Control-Max-Age', "1728000");
        }
    }

	/**
	 * Runs whole application based on request
	 * 
	 * @param Request
	 */
	public function processRequest(Request $Request): void
	{
		$Request->getHeader(SessionManager::HEADER_ACCESS_KEY);
		
		$this->checkSessionService($Request);
		$this->setAdditionalHeaders();

		process:
		if (count($this->requests) > self::maxLoop) {
			throw new ApplicationException('Too many loops detected in application life cycle.');
		}

		$this->requests[] = $Request;
		Arrays::invoke($this->onRequest, $this, $Request);

		try {
			// get Endpoint class full path
			$endpointName = $Request->getEndpoint();
			$endpointPath = $Request->getEndpointPath();

			if (!class_exists($endpointPath)){
				throw new BadRequestException('No possible response for this request');
			}

			// get service
			$Endpoint = $this->Container->createInstance($endpointPath);
			$this->Container->addService($endpointName, $Endpoint);

			// we need to run this command but it doesn't exist
			if(!method_exists($Endpoint, 'run')){
				throw new BadRequestException('Unknown action');
			}
			
			// inject dependencies
			$this->Container->callInjects($Endpoint);
			// pass config parameters
			$Endpoint->setParams($this->params);
	
			// run wanted class method and return it's content
			$Response = call_user_func([$Endpoint, 'run'], $this->params, $Request, $this->HttpResponse);
		} catch (Throwable $e) {
			throw count($this->requests) > 1
				? $e
				: new BadRequestException($e->getMessage(), 0, $e);
		}

		if ($Response instanceof ForwardResponse) {
			$Request = $Response->getRequest();
			goto process;
		}

		Arrays::invoke($this->onResponse, $this, $Response);
		$Response->send($this->HttpRequest, $this->HttpResponse);
	}

	/**
	 * Get session manager
	 */
	public function getSessionManager(): SessionManager
	{
		if ($this->Container->hasService('SessionManager')){
			return $this->Container->getService('SessionManager');
		}

		$SessionManager = $this->Container->createInstance(SessionManager::class);
		$this->Container->addService('SessionManager', $SessionManager);

		return $SessionManager;
	}

	/**
	 * Create initial request object
	 * 
	 * @return Request
	 */
	public function createInitialRequest(): Request
	{
		$postData = $this->HttpRequest->getPost();
		$headers = $this->HttpRequest->getHeaders();

        // correctly get data from Fetch XHR method
        if (($headers['x-requested-with'] ?? false) === 'XMLHttpRequest' && empty($this->HttpRequest->getPost()) && $headers['content-type'] === 'application/json'){
            /**
             * @var string  String data from STDIN (Fetch error)
             */
            $fetchSource = file_get_contents('php://input');
            $postData = @json_decode($fetchSource, true);
        }
		
		// finally create and return request
		return new Request(
			$_SERVER['REQUEST_URI'], // URI query string
			$this->HttpRequest->getMethod(), // HTTP2 method
			$postData ?? [],
			$this->HttpRequest->getFiles() ?? [],
			$headers,
			$this->getSessionManager()
		);
	}

	/**
	 * Returns all processed requests.
	 * @return Request[]
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}

	/**
	 * Dispatch a HTTP request to a front controller.
	 */
	public function run(): void
	{
		try {
			Arrays::invoke($this->onStartup, $this);
			$this->processRequest($this->createInitialRequest());
			Arrays::invoke($this->onShutdown, $this);
		} catch (\Throwable $e) {
			Arrays::invoke($this->onError, $this, $e);
			Arrays::invoke($this->onShutdown, $this, $e);
			throw $e;
		}
	}
}