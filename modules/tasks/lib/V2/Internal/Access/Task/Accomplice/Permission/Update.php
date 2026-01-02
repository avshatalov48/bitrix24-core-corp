<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Accomplice\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Update implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		$accessController = $this->getAccessController(Type::Task, $context);
		$adapter = $this->getAdapter($entity);

		$before = $adapter->create();
		$current = Container::getInstance()->getTaskRepository()->getById($entity->getId());
		if ($current === null)
		{
			return false;
		}

		$after = $adapter->transform($current);

		return $accessController->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $before, $after);
	}
}