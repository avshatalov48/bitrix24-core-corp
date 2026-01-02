<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Baas;
use \Bitrix\Main;

abstract class ExceptionFromError extends Baas\UseCase\External\Exception\UseCaseException
{
	protected ?Main\Error $error = null;

	public function setError(?Main\Error $error): static
	{
		$this->error = $error;

		return $this;
	}

	public function getCustomData(): ?array
	{
		return $this->error && is_array($this->error->getCustomData()) ? $this->error->getCustomData() : null;
	}
}
