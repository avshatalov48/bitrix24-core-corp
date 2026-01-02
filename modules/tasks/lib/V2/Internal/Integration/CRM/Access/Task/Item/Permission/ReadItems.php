<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Access\Task\Item\Permission;

use Attribute;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission\Update;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Entity\Task;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ReadItems implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		if (!$entity instanceof Task)
		{
			return false;
		}

		$canUpdate = (new Update())->check($entity, $context, $parameters);
		if (!$canUpdate)
		{
			return false;
		}

		$accessService = Container::getInstance()->getCrmAccessService();

		$beforeCheck = (array)$entity->crmItemIds;
		$afterCheck = $accessService->filterCrmItemsWithAccess($beforeCheck, $context->getUserId());

		return count($beforeCheck) === count($afterCheck);
	}
}