<?php declare(strict_types=1);

namespace APIcation;

use APIcation\Security\SessionManager;
use Exception;
use Nette;
use Nette\InvalidStateException;

/**
 * Presenter request.
 *
 * @property string $presenterName
 * @property array $parameters
 * @property array $post
 * @property array $files
 * @property string|null $method
 */
class Request
{
	use Nette\SmartObject;

	private SessionManager $Security;

	/** @var string URI query string */
	private string $queryString;

	/** @var string[] Endpoint name, method, param */
	private array $path = [];

	/** @var string HTTP method */
	private string $method;

	/** @var bool Using Private key? */
	private bool $private;

	/** @var array */
	private array $post;

	/** @var array */
	private array $files;

	/** @var array */
	private array $headers;

	/**
	 * @param  string  $name  presenter name (module:module:presenter)
	 */
	public function __construct(
		string $queryString,
		string $method,
		array $post,
		array $files,
		array $headers
	) {
		$this->queryString = $queryString;
		$this->method = $method;
		$this->post = $post;
		$this->files = $files;
		$this->headers = $headers;

		$this->processPath($queryString);
	}

	public static function breakPath(string $queryString): array
	{
		if (empty($queryString)){
			throw new Exception('Querystring mustnot be empty');
		}
		/**
		 * Path to wanted action
		 * 1. Endpoint
		 * 2. Action
		 */
		$res = explode('/', trim($queryString, '/'));

		// skip API
		if ($res[0] === 'api'){
			array_shift($res);
		}

		return $res;
	}

	private function processPath(string $queryString): void
	{
		$res = self::breakPath($queryString);

		$this->path = [
			$res[0] ? 'E' . ucfirst($res[0]) : null,
			$res[1] ?? 'default'
		];
	}

	/**
	 * Get Endpoint name
	 */
	public function getEndpoint(): string
	{
		return $this->path[0];
	}

	/**
	 * Get Endpoint name
	 */
	public function getEndpointPath(): string
	{
		// add full namespace path
		return 'APIcation\Endpoints\\' . $this->path[0];
	}

	/**
	 * Get action name
	 */
	public function getAction(): string
	{
		return $this->path[1];
	}

	/**
	 * Returns a variable provided to the presenter via POST.
	 * If no key is passed, returns the entire array.
	 * @return mixed
	 */
	public function getPost(string $key = null)
	{
		return func_num_args() === 0
			? $this->post
			: ($this->post[$key] ?? null);
	}

	/**
	 * Returns all uploaded files.
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	/**
	 * Returns current method
	 */
	public function getMethod(): ?string
	{
		return $this->method;
	}

	/**
	 * Get all headers or one of them
	 * 
	 * @param string Header name if you want specific one
	 */
	public function getHeader(?string $headerName = null)
	{
		return $headerName ?
			($this->headers[$headerName] ?? false) : $this->headers;
	}

	/**
	 * Sets the flag.
	 * @return static
	 */
	public function setFlag(string $flag, bool $value = true)
	{
		$this->flags[$flag] = $value;
		return $this;
	}

	/**
	 * Checks the flag.
	 */
	public function hasFlag(string $flag): bool
	{
		return !empty($this->flags[$flag]);
	}

	public function toArray(): array
	{
		$params = $this->params;
		$params['presenter'] = $this->name;
		return $params;
	}
}