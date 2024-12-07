<?php declare(strict_types=1);

namespace Bitrix\AI\Parameter;

use Bitrix\AI\Exception\ContainerException;
use Bitrix\AI\Container;
use ReflectionParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;

/**
 * This parameter for using in autowired Container
 */
class DefaultParameter extends Parameter
{
	public function __construct()
	{
		parent::__construct(
			'',
			fn ($className) => Container::init()->getItem($className)
		);
	}

	public function match(ReflectionParameter $parameter): bool
	{
		$class = $this->buildReflectionClass($parameter);
		if (!$class)
		{
			return false;
		}

		try
		{
			$this->getContainer()->getItem($class->name);
		}
		catch (ContainerException)
		{
			return false;
		}

		return true;
	}

	protected function getContainer(): Container
	{
		return Container::init();
	}
}
