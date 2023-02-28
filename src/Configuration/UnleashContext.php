<?php
namespace Unleash\Configuration;
use Unleash\Enum\ContextField;
use Unleash\Enum\Stickiness;
use Exception;
use Datetime;
use Unleash\Exception\InvalidValueException;
class UnleashContext
{
    public function __construct(
        $currentUserId = null,
        $ipAddress = null,
        $sessionId = null,
        $customContext = [],
        $hostname = null,
        $environment = null,
        $currentTime = null
    ) {

        $this->currentUserId = $currentUserId;
        $this->ipAddress = $ipAddress;
        $this->sessionId = $sessionId;
        $this->customContext = $customContext;
        $this->setHostname($hostname);
        $this->environment = $environment;
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
        return !is_null($this->ipAddress) ? $this->ipAddress : $_SERVER['REMOTE_ADDR'];
    }

    public function getSessionId()
    {
        return !is_null($this->sessionId) ? $this->sessionId : (session_id() ?: null);
    }

    public function setCurrentUserId($currentUserId)
    {
        $this->currentUserId = $currentUserId;
        return $this;
    }

    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
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
        $v = $this->findContextValue(ContextField::HOSTNAME);
        return !is_null($v) ? $v : (gethostname() ?: null);
    }

    public function setHostname($hostname)
    {
        if ($hostname === null) {
            $this->removeCustomProperty(ContextField::HOSTNAME);
        } else {
            $this->setCustomProperty(ContextField::HOSTNAME, $hostname);
        }

        return $this;
    }

    public function setCurrentTime($time)
    {
        if ($time === null) {
            $this->removeCustomProperty('currentTime');
        } else {
            $value = is_string($time) ? $time : $time->format(DateTime::ISO8601);
            $this->setCustomProperty('currentTime', $value);
        }

        return $this;
    }

    public function findContextValue($fieldName)
    {
        switch($fieldName) {
            case ContextField::USER_ID:
            case Stickiness::USER_ID:
                return $this->getCurrentUserId();
                break;
            case ContextField::SESSION_ID:
            case Stickiness::SESSION_ID:
                return $this->getSessionId();
                break;
            case ContextField::IP_ADDRESS:
                return $this->getIpAddress();
                break;
            case ContextField::ENVIRONMENT:
                return $this->getEnvironment();
                break;
            case ContextField::CURRENT_TIME:
                return $this->getCurrentTime()->format(DateTime::ISO8601);
                break;
            default:
                return !is_null($this->customContext[$fieldName]) ? $this->customContext[$fieldName] : null;
                break;
        }
    }

    public function getCurrentTime()
    {
        if (!$this->hasCustomProperty('currentTime')) {
            return new DateTime();
        }

        return new DateTime($this->getCustomProperty('currentTime'));
    }

    public function getCustomProperty($name)
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    public function setCustomProperty($name,  $value)
    {
        $this->customContext[$name] = !is_null($value) ? $value : '';

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

    public function hasMatchingFieldValue($fieldName, $values)
    {
        $fieldValue = $this->findContextValue($fieldName);
        if ($fieldValue === null) {
            return false;
        }

        return in_array($fieldValue, $values, true);
    }

}
