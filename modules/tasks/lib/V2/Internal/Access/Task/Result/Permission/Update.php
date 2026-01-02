<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Result\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\ResultModel;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Update implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		$accessController = $this->getAccessController(Type::Task, $context);
		$adapter = $this->getAdapter($entity);

		/** @var ?ResultModel $model */
		$model = $adapter->transform();

		return $accessController->checkByItemId(ActionDictionary::ACTION_TASK_RESULT_EDIT, $model?->getTaskId());
	}
}
