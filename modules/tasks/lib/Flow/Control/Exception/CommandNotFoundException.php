<?php

namespace Bitrix\Tasks\Flow\Control\Exception;

use Psr\Container\NotFoundExceptionInterface;

class CommandNotFoundException extends FlowException implements NotFoundExceptionInterface
{

}