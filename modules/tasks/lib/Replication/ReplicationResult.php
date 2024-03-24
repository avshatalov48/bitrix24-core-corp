<?php

namespace Bitrix\Tasks\Replication;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Replication\AbstractReplicator;
use Throwable;

class ReplicationResult extends Result
{
	public function __construct(private AbstractReplicator $replicator)
	{
		parent::__construct();
	}

	public function writeToLog(): static
	{
		if (!$this->replicator->isDebug())
		{
			return $this;
		}

		if ($this->isSuccess())
		{
			return $this;
		}

		$errors = $this->getErrorCollection()->toArray();
		(new Log())->collect($this->replicator::class . ' Debug: ' . var_export($errors, true));
		return $this;
	}

	public function merge(Result ...$results): static
	{
		foreach ($results as $result)
		{
			if (!$result->isSuccess)
			{
				$this->addErrors($result->getErrors());
			}
			$this->setData(array_merge($this->getData(), $result->getData()));
		}

		return $this;
	}

	public function getPayload()
	{
		return $this->getData()[$this->replicator->getPayloadKey()] ?? null;
	}

	public function convertFromResult(Result $result): static
	{
		return $this->setData($result->getData())->addErrors($result->getErrors());
	}

	public function addThrowable(Throwable $throwable): static
	{
		$this->addError(Error::createFromThrowable($throwable));
		return $this;
	}
}