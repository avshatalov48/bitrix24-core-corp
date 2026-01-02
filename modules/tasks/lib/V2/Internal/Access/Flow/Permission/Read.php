<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Flow\Permission;

use Attribute;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Read implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		return $this->getAccessController(Type::Flow, $context)->checkByItemId(FlowAction::READ, $entity->getId());
	}
}
