<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Permission;

use Attribute;
use Bitrix\Main\Access\AccessibleController;
use Bitrix\Tasks\Access\AccessCacheLoader;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Entity\EntityCollectionInterface;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Read implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(EntityInterface|EntityCollectionInterface $entity, Context $context, array $parameters = []): bool
	{
		$accessController = $this->getAccessController(Type::Task, $context);
		if ($entity instanceof EntityInterface)
		{
			return $this->checkEntity($entity, $accessController);
		}

		(new AccessCacheLoader())->preload($context->getUserId(), $entity->getIds());

		foreach ($entity as $item)
		{
			if (!$this->checkEntity($item, $accessController))
			{
				return false;
			}
		}

		return true;
	}

	private function checkEntity(EntityInterface $entity, AccessibleController $accessController): bool
	{
		$adapter = $this->getAdapter($entity);
		$model = $adapter->transform();

		return $accessController->checkByItemId(ActionDictionary::ACTION_TASK_READ, $model?->getId());
	}
}
