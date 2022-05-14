<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Main\Result;

abstract class Decorator extends CriterionRegistrar
{
	/** @var CriterionRegistrar */
	private $wrappee;

	public function __construct(CriterionRegistrar $wrappee)
	{
		$this->wrappee = $wrappee;
	}

	final public function register(Data $data): Result
	{
		$wrapResult = $this->wrapRegister($data);

		$baseResult = $this->wrappee->register($data);
		if (!$baseResult->isSuccess())
		{
			$wrapResult->addErrors($baseResult->getErrors());
		}

		return $wrapResult;
	}

	final public function update(Data $data): Result
	{
		$wrapResult = $this->wrapUpdate($data);

		$baseResult = $this->wrappee->update($data);
		if (!$baseResult->isSuccess())
		{
			$wrapResult->addErrors($baseResult->getErrors());
		}

		return $wrapResult;
	}

	final public function unregister(Data $data): Result
	{
		$wrapResult = $this->wrapUnregister($data);

		$baseResult = $this->wrappee->unregister($data);
		if (!$baseResult->isSuccess())
		{
			$wrapResult->addErrors($baseResult->getErrors());
		}

		return $wrapResult;
	}

	final public function isNull(): bool
	{
		// we suppose that a decorator does something meaningful
		// therefore, a decorated registrar is always not null
		return false;
	}

	abstract protected function wrapRegister(Data $data): Result;

	abstract protected function wrapUpdate(Data $data): Result;

	abstract protected function wrapUnregister(Data $data): Result;
}
