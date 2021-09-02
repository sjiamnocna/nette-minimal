<?php declare(strict_types = 1);

namespace APIcation;

use Nette;
use Nette\Security\SimpleIdentity;

class MyAuthenticator implements Nette\Security\Authenticator
{
	public function authenticate(string $username, string $password): SimpleIdentity
	{
		if ($password !== '12345') {
			throw new Nette\Security\AuthenticationException('Invalid password.');
		}

		return new SimpleIdentity(
			$username,
			'admin', // or array of roles
			['name' => $username]
		);
	}
}
