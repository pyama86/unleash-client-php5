<?php
namespace Unleash\Configuration;

class UnleashContext
{
    public function __construct(
        $currentUserId = null,
        $ipAddress = null,
        $sessionId = null,
        $hostname = null,
        $environment = null,
        $currentTime = null,
        $customContext = []
    ) {

        $this->currentUserId = $currentUserId;
        $this->ipAddress = $ipAddress;
        $this->sessionId = $sessionId;
        $this->customContext = $customContext;
        $this->setCurrentTime($currentTime);
    }

    public function getCurrentUserId()
    {
        return $this->currentUserId;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getIpAddress()
    {
        return $this->ipAddress ? $this->ipAddress : $_SERVER['REMOTE_ADDR'];
    }

    public function getSessionId()
    {
        return $this->sessionId ? $this->sessionId : session_id();
    }

    public function setCurrentUserId($currentUserId)
    {
        $this->currentUserId = $currentUserId;
        return $this;
    }

    public function setIpAddress($ipAddress) {
        return $this;
    }

    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    public function getHostname()
    {
        return gethostname();
    }

    public function setCurrentTime($time)
    {
        if ($time === null) {
            $this->removeCustomProperty('currentTime');
        } else {
            $value = is_string($time) ? $time : $time->format(DateTimeInterface::ISO8601);
            $this->setCustomProperty('currentTime', $value);
        }

        return $this;
    }


    public function getCustomProperty($name)
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new Exception("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    public function setCustomProperty($name,  $value)
    {
        $this->customContext[$name] = $value ? $value : '';

        return $this;
    }

    public function hasCustomProperty($name)
    {
        return array_key_exists($name, $this->customContext);
    }

    public function removeCustomProperty($name, $silent = true)
    {
        if (!$this->hasCustomProperty($name) && !$silent) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        unset($this->customContext[$name]);

        return $this;
    }
}
