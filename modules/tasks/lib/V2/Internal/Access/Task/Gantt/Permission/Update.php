<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Gantt\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AccessUserErrorInterface;
use Bitrix\Tasks\V2\Internal\Access\AccessUserErrorTrait;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Update implements AttributeAccessInterface, AccessUserErrorInterface
{
	use AccessControllerTrait;
	use AccessUserErrorTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		if (!$entity instanceof Entity\Task\GanttLink)
		{
			return false;
		}

		$accessController = $this->getAccessController(Type::Task, $context);

		$result = $accessController->checkByItemId(
			ActionDictionary::ACTION_TASK_CHANGE_GANTT_DEPENDENCE,
			$entity->taskId,
			['dependentId' => $entity->dependentId]
		);

		if (!$result)
		{
			$this->resolveUserError($accessController);
		}

		return $result;
	}
}
