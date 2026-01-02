<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Reminder\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderAction;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Read implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		if (!$entity instanceof Entity\Task\Reminder)
		{
			return false;
		}

		$accessController = $this->getAccessController(Type::Reminder, $context);
		$adapter = $this->getAdapter($entity);

		$model = $adapter->create();

		return $accessController->check(ReminderAction::Read, $model);
	}
}