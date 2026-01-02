<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Task\Attachment\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;
use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Detach implements AttributeAccessInterface
{
	use AccessControllerTrait;

	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		$ids = $parameters['ids'] ?? [];
		if (!is_array($ids) || empty($ids))
		{
			return false;
		}

		$accessController = $this->getAccessController(Type::Task, $context);
		$adapter = $this->getAdapter($entity);

		$model = $adapter->create();

		$attachments = Container::getInstance()->getDiskFileRepository()->getByIds($ids);

		return $accessController->check(ActionDictionary::ACTION_TASK_DETACH_FILE, $model, ['attachments' => $attachments->toArray()]);
	}
}
