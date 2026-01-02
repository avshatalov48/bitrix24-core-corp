<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Trait;

use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Tasks\DI\Container;
use Closure;

trait AttributeAutowireTrait
{

	protected function getLocatorParameter(string $className, string $idOrClass): Parameter
	{
		return new Parameter(
			$className,
			$this->getLocatorClosure($idOrClass),
		);
	}

	protected function getInjectionParameter(string $className): Parameter
	{
		return new Parameter(
			$className,
			$this->getInjectionClosure($className),
		);
	}

	protected function getLocatorClosure(string $idOrClass): Closure
	{
		return static fn () => Container::getInstance()->getRegisteredObject($idOrClass);
	}

	protected function getInjectionClosure(string $className): Closure
	{
		return static fn () => Container::getInstance()->getRuntimeObjectWithDi($className);
	}
}