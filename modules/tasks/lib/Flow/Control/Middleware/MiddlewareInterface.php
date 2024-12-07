<?php

namespace Bitrix\Tasks\Flow\Control\Middleware;

use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;

interface MiddlewareInterface
{
	/**
	 * @throws MiddlewareException
	 */
	public function handle(AbstractCommand $request);
	public function setNext(MiddlewareInterface $handler): MiddlewareInterface;

}