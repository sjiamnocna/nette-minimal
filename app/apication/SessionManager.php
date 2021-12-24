<?php declare(strict_types = 1);

namespace APIcation\Security;

use DateTime;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Random;

/**
 * Manages security keys authentication and session token
 * 1. Client access endpoint to init Security
 * 2. Server refuses connection with 403, sends access token
 * 3. Client uses the access token encrypted by the access token to "unlock" the session with public or private key
 * 4. Server sends 
 */
class SessionManager
{

    const ENDPOINT_NAME = 'Ch';
    const HEADER_SERVICE_NAME = 'x-service-name';
    const HEADER_SERVICE_KEY = 'x-service-key';
    const HEADER_ACCESS_KEY = 'x-access-key';

    /** @var Session The session service */
    private SessionSection $Section;

    /** @var bool If the current request is verified by session key */
    private bool $unlocked = false;

    /** @var bool If session was unlocked by API key */
    private bool $unlockedAPI = false;

    function __construct(
        Session $Session
    )
    {
        // create or get section from Nette\Session
        $this->Section = $Session->getSection(self::class);
    }

    /**
     * Phases
     * - specifying the serviceName and giving the session token
     * - unlock the service by service key
     */
    public function initConnection(string $serviceName, ?string $serviceKey = null)
    {
        if ($serviceKey){
            // verify the current service key and return new access token
        } else {
            // find service name and return 
        }
    }

    /**
     * Allows to use certain service (with public code or something)
     * 
     * @var string Service ID in Database OR some service API Key
     */
    private function initService(string $service): void
    {
        // check service and get it's data from database
        $this->setServiceName($service);
    }

    /**
     * Verify incoming access token
     */
    public function unlockSession(string $token): bool
    {
        return $this->unlocked = password_verify($token, $this->Section->get('accessToken'));
    }

    /**
     * Insert API key to unlock the service
     * 
     * @var string API Key
     * 
     * @return bool
     */
    public function unlockService(string $APIKey)
    {
        # code...
        // if the key is correct, unlock the service
        $this->Section->set('serviceUnlocked', true);
    }

    /**
     * Start session by setting service name
     * If changing service name, then we need to invalidate the request
     */
    private function setServiceName(string $name): void
    {
        if ($this->Section->get('serviceName') === $name){
            return;
        }

        $this->Section->set('serviceName', $name);
        $this->Section->set('serviceUnlocked', false);
    }

    /**
     *  Set access token to authorize current session
     * can be set only once per session
     * 
     *  @var string Token string to set
     */
    private function setAccessToken(string $token): void
    {        
        $this->Section->set('accessToken', password_hash($token, PASSWORD_DEFAULT));
    }

    /**
     * Service was unlocked?
     * 
     * @return bool
     */
    public function isServiceUnlocked(string $key)
    {
        return $this->Section->get('serviceUnlocked');
    }

    /**
     * Session unlocked with session key?
     * 
     * @return bool
     */
    public function isSessionUnlocked(): bool
    {
        return $this->unlocked;
    }

    /**
     * Override this method to create different session tokens to secure your app
     * 
     * @return string Generated access token
     */
    public function generateAccessToken(): string
    {
        $token = self::generateMd5Token();
        $this->setAccessToken($token);

        return $token;
    }

    /**
     * Generates Md5 hash token
     * 
     * @return string MD5 token
     */
    public static function generateMd5Token(): string
    {
        $tokenString = (new DateTime())->format('Y-m-d-h-i-s') . Random::generate(16);

        return md5($tokenString);
    }
}