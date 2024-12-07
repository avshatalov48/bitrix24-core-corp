<?php

namespace Bitrix\Tasks\Flow\Control\Middleware;

use Bitrix\Tasks\AbstractCommand;

abstract class AbstractMiddleware implements MiddlewareInterface
{
	protected ?MiddlewareInterface $nextHandler = null;

	public function setNext(MiddlewareInterface $handler): MiddlewareInterface
	{
		$this->nextHandler = $handler;
		return $this->nextHandler;
	}

	public function handle(AbstractCommand $request)
	{
		return $this->nextHandler?->handle($request);
	}
}