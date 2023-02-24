<?php

namespace Unleash\Enum;

class ContextField
{
    const USER_ID = 'userId';
    const SESSION_ID = 'sessionId';
    const IP_ADDRESS = 'remoteAddress';
    const ENVIRONMENT = 'environment';
    const REMOTE_ADDRESS = self::IP_ADDRESS;
    const HOSTNAME = 'hostname';
    const CURRENT_TIME = 'currentTime';
}
