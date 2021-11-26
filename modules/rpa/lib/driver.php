<?php

namespace Bitrix\Rpa;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;
use Bitrix\Rpa\Controller\Comment;
use Bitrix\Rpa\Integration\Bitrix24Manager;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;
use Bitrix\Rpa\Integration\Disk\Connector;
use Bitrix\Rpa\Integration\PullManager;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Model\TypeTable;

final class Driver
{
	public const MODULE_ID = 'rpa';

	/** @var  Driver */
	private static $instance;
	protected $factory;
	protected $userPermissionsClassName;
	protected $usersPermissions = [];
	protected $urlManager;
	protected $director;
	protected $taskManager;
	protected $pullManager;
	protected $bitrix24Manager;
	protected $types;

	private function __construct()
	{
		$this->initObjects();
		$this->types = [];
	}

	private function __clone()
	{
	}

	public static function getInstance(): Driver
	{
		if (!isset(self::$instance))
		{
			self::$instance = new Driver;
		}

		return self::$instance;
	}

	public function isEnabled(): bool
	{
		return true;
	}

	protected function collectClasses(): array
	{
		$classes = [];

		$event = new Event(static::MODULE_ID, 'onDriverCollectClasses');
		EventManager::getInstance()->send($event);
		foreach($event->getResults() as $result)
		{
			if($result->getType() === EventResult::SUCCESS && is_array($result->getParameters()))
			{
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$classes = array_merge($classes, $result->getParameters());
			}
		}

		return $classes;
	}

	protected function getDefaultClasses(): array
	{
		return [
			'factory' => Factory::class,
			'urlManager' => UrlManager::class,
			'director' => Director::class,
			'pullManager' => PullManager::class,
			'taskManager' => TaskManager::class,
			'userPermissions' => UserPermissions::class,
		];
	}

	protected function initObjects(): void
	{
		$classes = $this->collectClasses();
		foreach($this->getDefaultClasses() as $name => $className)
		{
			if(
				isset($classes[$name])
				&& is_string($classes[$name])
				&& is_a($classes[$name], $className, true))
			{
				$className = $classes[$name];
			}

			if($name === 'taskManager')
			{
				if($this->isAutomationEnabled())
				{
					$this->taskManager = new $className();
				}
			}
			elseif($name === 'userPermissions')
			{
				$this->userPermissionsClassName = $className;
			}
			else
			{
				$this->$name = new $className();
			}
		}

		// there is no need to change it
		$this->bitrix24Manager = new Bitrix24Manager();
	}

	public function getFactory(): Factory
	{
		return $this->factory;
	}

	public function getUserId(): int
	{
		global $USER;
		if(is_object($USER))
		{
			return (int) CurrentUser::get()->getId();
		}

		return 0;
	}

	public function getType(int $typeId): ?Type
	{
		if(isset($this->types[$typeId]))
		{
			return $this->types[$typeId];
		}

		$type = $this->getFactory()->getTypeDataClass()::getById($typeId)->fetchObject();
		if($type)
		{
			$this->types[$typeId] = $type;
			return $type;
		}

		return null;
	}

	public function getUserPermissions(int $userId = null): UserPermissions
	{
		if($userId === null)
		{
			$userId = $this->getUserId();
		}

		if(!isset($this->usersPermissions[$userId]))
		{
			$className = $this->userPermissionsClassName;
			$this->usersPermissions[$userId] = new $className($userId);
		}

		return $this->usersPermissions[$userId];
	}

	public function deleteAllData(): Result
	{
		$allResult = new Result();

		$types = TypeTable::getList();
		while($type = $types->fetchObject())
		{
			$typeResult = new Result();
			$items = $type->getItems();
			foreach($items as $item)
			{
				$deleteResult = $item->delete();
				if(!$deleteResult)
				{
					$typeResult->addErrors($deleteResult->getErrors());
				}
			}
			if($typeResult->isSuccess())
			{
				$typeResult = $type->delete();
			}
			if(!$typeResult->isSuccess())
			{
				$allResult->addErrors($typeResult->getErrors());
			}
		}

		return $allResult;
	}

	public function getUrlManager(): UrlManager
	{
		return $this->urlManager;
	}

	public function getDirector(): Director
	{
		return $this->director;
	}

	public function getTaskManager(): ?TaskManager
	{
		return $this->taskManager;
	}

	public function getPullManager(): PullManager
	{
		return $this->pullManager;
	}

	public function isAutomationEnabled(): bool
	{
		return \Bitrix\Rpa\Integration\Bizproc\Automation\Factory::canUseAutomation();
	}

	public function getBitrix24Manager(): Bitrix24Manager
	{
		return $this->bitrix24Manager;
	}

	//region events
	public static function onGetDependentModule(): array
	{
		return [
			'MODULE_ID' => static::MODULE_ID,
			'USE' => ['PUBLIC_SECTION'],
		];
	}

	public static function onGetTypeDataClassList(): array
	{
		return [
			static::getInstance()->getFactory(),
		];
	}

	public static function onDiskBuildConnectorList(): EventResult
	{
		return new EventResult(EventResult::SUCCESS, [
			'TASK' => [
				'ENTITY_TYPE' => Comment::USER_FIELD_ENTITY_ID,
				'MODULE_ID' => static::MODULE_ID,
				'CLASS' => Connector::class,
			]
		]);
	}

	public static function onRestServiceBuildDescription(): array
	{
		return [
			static::MODULE_ID => [
				static::MODULE_ID.'.stub' => [],
			],
		];
	}
	//endregion
}
