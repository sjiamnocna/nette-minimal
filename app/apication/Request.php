<?php declare(strict_types=1);

namespace APIcation;

use Exception;
use InvalidArgumentException;
use Nette;
use Nette\InvalidStateException;
use Tracy\Debugger;

/**
 * Presenter request.
 *
 * @property string $presenterName
 * @property array $parameters
 * @property array $post
 * @property array $files
 * @property string|null $method
 */
final class Request
{
	use Nette\SmartObject;

	/** @var string URI query string */
	private string $queryString;

	/** @var string[] Endpoint name, method, param */
	private array $path = [];

	/** @var string HTTP method */
	private string $method;

	/** @var bool Using HTPS */
	private bool $https;

	/** @var bool Using Private key? */
	private bool $private;

	/** @var array */
	private array $post;

	/** @var array */
	private array $files;

	/**
	 * @param  string  $name  presenter name (module:module:presenter)
	 */
	public function __construct(
		string $queryString,
		string $method,
		bool $https,
		bool $private,
		array $post,
		array $files
	) {
		$this->queryString = $queryString;
		$this->method = $method;
		$this->processPath($queryString);
		$this->https = $https;
		$this->private = $private;
		$this->post = $post;
		$this->files = $files;
	}

	private function processPath(string $queryString): void
	{
		/**
		 * Path to wanted action
		 * 1. Endpoint
		 * 2. Action
		 */
		$res = explode('/', trim($queryString, '/'));

		if (count($res) < 1){
			throw new InvalidStateException();
		}

		// skip API
		for ($i = 0; $item = $res[$i] ?? false; $i++){
			if ($item === 'api'){
				// because we use NodeJS proxy pass to API when developing on localhost
				// we prepended 'api' this removes it
				continue;
			}
			$this->path[] = $item;
		}

		$this->path[0] = $this->path[0] ? 'E' . ucfirst($this->path[0]) : null;
        $this->path[1] = $this->path[1] ?? 'default';
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
	 * If private key is used
	 */
	public function getPrivate(): bool
	{
		return $this->private;
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