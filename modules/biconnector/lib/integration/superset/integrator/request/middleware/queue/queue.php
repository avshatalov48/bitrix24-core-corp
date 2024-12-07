<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware\Queue;

use Bitrix\BIConnector\Integration\Superset\Integrator\Request\Middleware;

abstract class Queue
{
	/** @var Middleware\Base[] */
	private array $queue = [];

	final public function add(Middleware\Base $middleware): void
	{
		foreach ($this->queue as $i => $queueMiddleware)
		{
			if ($queueMiddleware->getMiddlewareId() === $middleware->getMiddlewareId())
			{
				$this->queue[$i] = $middleware;

				return;
			}
		}

		$this->queue[] = $middleware;
	}

	final public function remove(string $middlewareId): void
	{
		for ($queueSize = count($this->queue), $i = 0; $i < $queueSize; $i++)
		{
			if ($this->queue[$i]->getMiddlewareId() === $middlewareId)
			{
				array_splice($this->queue, $i, 1);

				return;
			}
		}
	}

	protected function getQueue(): array
	{
		return $this->queue;
	}
}
