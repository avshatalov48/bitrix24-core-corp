<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Factory;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Loader;
use Bitrix\SocialNetwork\Collab\Access\CollabAccessController;
use Bitrix\SocialNetwork\Collab\Access\Model\CollabModel;
use Bitrix\Socialnetwork\Permission\GroupAccessController;
use Bitrix\Socialnetwork\Permission\Model\GroupModel;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Internals\Trait\SingletonTrait;
use Bitrix\Tasks\V2\Internal\Access\Adapter\EntityModelAdapterInterface;
use Bitrix\Tasks\V2\Internal\Access\Adapter\GroupModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\Adapter\ReminderModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\Adapter\ResultModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\Adapter\TaskModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\Adapter\TemplateModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderAccessController;
use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderModel;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Entity\Group;
use Bitrix\Tasks\V2\Internal\Entity\Result;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\Template;

final class ControllerFactory implements ControllerFactoryInterface
{
	use SingletonTrait;

	public function create(Type $type, int $userId): ?AccessibleController
	{
		$class = $this->getClass($type);

		return $this->createByClass($class, $userId);
	}

	public function createByClass(string $class, int $userId): ?AccessibleController
	{
		if (is_subclass_of($class, BaseAccessController::class))
		{
			return $class::getInstance($userId);
		}

		if (is_subclass_of($class, AccessibleController::class))
		{
			return new $class($userId);
		}

		return null;
	}

	public function createAdapter(EntityInterface $entity): ?EntityModelAdapterInterface
	{
		return match ($entity::class) {
			Task::class => new TaskModelAdapter($entity),
			Template::class => new TemplateModelAdapter($entity),
			Group::class => new GroupModelAdapter($entity),
			Result::class => new ResultModelAdapter($entity),
			Task\Reminder::class => new ReminderModelAdapter($entity),
			default => null,
		};
	}

	public function createModel(Type $type): AccessibleItem
	{
		return match ($type) {
			Type::Task => TaskModel::createNew(),
			Type::Template => TemplateModel::createNew(),
			Type::Flow => FlowModel::createFromId(0),
			Type::Group => GroupModel::createFromId(0),
			Type::Collab => CollabModel::createFromId(0),
			Type::Reminder => ReminderModel::createFromId(0),
		};
	}

	private function getClass(Type $type): string
	{
		return match ($type) {
			Type::Task => TaskAccessController::class,
			Type::Template => TemplateAccessController::class,
			Type::Flow => FlowAccessController::class,
			Type::Group => GroupAccessController::class,
			Type::Collab => CollabAccessController::class,
			Type::Reminder => ReminderAccessController::class,
		};
	}

	protected function init(): void
	{
		Loader::requireModule('socialnetwork');
	}
}
