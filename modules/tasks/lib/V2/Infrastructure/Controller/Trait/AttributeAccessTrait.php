<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Trait;

use Bitrix\Main\Engine\Controller;
use Bitrix\Tasks\V2\Internal\Access\AccessUserErrorInterface;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Access\Context\AccessContextTrait;
use ReflectionMethod;

trait AttributeAccessTrait
{
	use ActionTrait;
	use AccessErrorTrait;
	use AccessContextTrait;

	protected function getWithAccess(Controller $controller, string $parameterName, EntityInterface $entity): ?EntityInterface
	{
			$accessAttributes = $this->getArgumentAccessAttributes($controller, $parameterName);
			foreach ($accessAttributes as $accessAttribute)
			{
				/** @var AttributeAccessInterface $accessAttribute */
				if (!$accessAttribute->check($entity, $this->getAccessContext()))
				{
					if ($accessAttribute instanceof AccessUserErrorInterface)
					{
						return $this->buildForbiddenResponse($accessAttribute->getUserError());
					}

					return $this->buildForbiddenResponse();
				}
			}

			return $entity;
	}

	protected function getArgumentAccessAttributes(Controller $controller, string $parameterName): array
	{
		$action = $this->getControllerAction($controller);
		$reflector = new ReflectionMethod($controller, $action);

		$args = $reflector->getParameters();

		$accessAttributes = [];
		foreach ($args as $arg)
		{
			if ($arg->getName() === $parameterName)
			{
				$attributes = $arg->getAttributes();
				foreach ($attributes as $attribute)
				{
					$instance = $attribute->newInstance();
					if ($instance instanceof AttributeAccessInterface)
					{
						$accessAttributes[] = $instance;
					}
				}
			}
		}

		return $accessAttributes;
	}
}
