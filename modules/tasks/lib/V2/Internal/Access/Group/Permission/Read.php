<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Group\Permission;

use Attribute;
use Bitrix\Main\Loader;
use Bitrix\SocialNetwork\Collab\Access\CollabDictionary;
use Bitrix\Socialnetwork\Permission\GroupAccessController;
use Bitrix\Socialnetwork\Permission\GroupDictionary;
use Bitrix\Tasks\V2\Internal\Access\Adapter\GroupModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Read implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$type = Container::getInstance()->getGroupRepository()->getType($entity->getId()) === 'collab'
			? Type::Collab
			: Type::Group;

		$action = $type === Type::Collab ? CollabDictionary::VIEW : GroupDictionary::VIEW;

		return $this->getAccessController($type, $context)->checkByItemId($action, $entity->getId());
	}
}