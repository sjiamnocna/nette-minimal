<?php declare(strict_types=1);

namespace APIcation;

use APIcation\Endpoints\Endpoint;
use Nette;
use Nette\Utils\Arrays;
use Nette\Application\Response;
use APIcation\Request;
use Nette\Application\IPresenter;
use Nette\Application\Responses\ForwardResponse;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Throwable;

/**
 * API entrypoint controller, complementary to Nette\Aplication
 */
final class Application
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

	/** @var Endpoint Endpoint object */
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
		Container $Container
	)
	{
		$this->params = $params;
		$this->HttpRequest = $HttpRequest;
		$this->HttpResponse = $HttpResponse;
		$this->Container = $Container;
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
			$Response = call_user_func([$Endpoint, 'run'], $Request);
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
			((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443), // using HTTPS
			false, // using private key
			$postData ?? [],
			$this->HttpRequest->getFiles() ?? []
		);
	}

	/**
	 * Returns all processed requests.
	 * @return Request[]
	 */
	final public function getRequests(): array
	{
		return $this->requests;
	}
}