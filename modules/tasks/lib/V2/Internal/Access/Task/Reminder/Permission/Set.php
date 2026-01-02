<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Reminder\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Set implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		if (!$entity instanceof Entity\Task)
		{
			return false;
		}

		$accessController = $this->getAccessController(Type::Task, $context);
		$adapter = $this->getAdapter($entity);

		$model = $adapter->create();

		$reminders = [];
		if (!$entity->reminders?->isEmpty())
		{
			$mapper = Container::getInstance()->getReminderMapper();

			$reminders = $mapper->mapFromCollection($entity->reminders);
		}

		return $accessController->check(ActionDictionary::ACTION_TASK_REMINDER, $model, $reminders);
	}
}