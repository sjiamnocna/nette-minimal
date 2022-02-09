<?php declare(strict_types=1);

namespace APIcation;

use APIcation\Endpoints\Endpoint;
use Nette;
use Nette\Utils\Arrays;
use APIcation\Request;
use Exception;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

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

	/** @var Endpoint Current endpoint in use object */
	private Endpoint $Endpoint;

	/** @var CSecurity */
	private CSecurity $Security;

	/** @var array<callable(self): void>  Occurs before the application loads presenter */
	public array $onStartup = [];

	/** @var array<callable(self, ?\Throwable): void>  Occurs before the application shuts down */
	public array $onShutdown = [];

	/** @var array<callable(self, Request): void>  Occurs when a new request is received */
	public array $onRequest = [];

	/** @var array<callable(self, Response): void>  Occurs when a new response is ready for dispatch */
	public array $onResponse = [];

	/** @var array<callable(self, \Throwable): void>  Occurs when an unhandled exception occurs in the application */
	public array $onError = [];

	public function __construct(
		array $params,
		Nette\Http\IRequest $HttpRequest,
		Nette\Http\IResponse $HttpResponse,
		Container $Container
	)
	{
		$this->params = $params;
		$this->HttpRequest = $HttpRequest;
		$this->HttpResponse = $HttpResponse;
		$this->Container = $Container;
		$this->Security = $this->Container
			->createInstance(CSecurity::class, [$this->params['service']]);
		$this->Container->addService('CSecurity', $this->Security);

		// call security before anything else
		$this->Security->run();
	}

	/**
	 * Runs whole application based on request
	 * 
	 * @param Request
	 */
	public function processRequest(Request $Request): void
	{
		Arrays::invoke($this->onRequest, $this, $Request);

		// get Endpoint class full path
		$endpointName = $Request->getEndpoint();
		$endpointPath = $Request->getEndpointPath();

		if (!class_exists($endpointPath)){
			throw new Exception('No possible response for this request', 404);
		}

		// get service
		$Endpoint = $this->Container->createInstance($endpointPath);
		$this->Container->addService($endpointName, $Endpoint);

		// we need to run this command but it doesn't exist
		if(!method_exists($Endpoint, 'run')){
			throw new Exception('Unknown action', 404);
		}
		
		// pass config parameters
		$Endpoint->setParams($this->params);

		// run wanted class method and return it's content
		$Response = call_user_func([$Endpoint, 'run'], $this->params, $Request, $this->HttpResponse);

		Arrays::invoke($this->onResponse, $this, $Response);
		$Response->send($this->HttpRequest, $this->HttpResponse);
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

        // correctly get data from Fetch
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
			$this->Security->isServiceAuthorized()
		);
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