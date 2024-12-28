<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab\Entity\CheckList;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Collab;
use Bitrix\Socialnetwork\Collab\Entity\CollabEntity;
use Bitrix\Socialnetwork\Collab\Entity\CollabEntityFactory;
use Bitrix\Socialnetwork\Collab\Entity\Type\EntityType;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;

if (!Loader::includeModule('socialnetwork'))
{
	return;
}

class TaskCheckListEntity extends CollabEntity
{
	protected ?array $internalData = null;

	public function __construct(int $id, mixed $internalObject = null)
	{
		if (is_array($internalObject) && isset($internalObject['ID'], $internalObject['TASK_ID']))
		{
			$this->internalData = $internalObject;
		}

		parent::__construct($id, $internalObject);
	}

	public function getType(): EntityType
	{
		return EntityType::TaskCheckList;
	}

	public function getData(): array
	{
		return $this->internalData;
	}

	protected function fillCollab(): ?Collab
	{
		$taskId = $this->internalData['TASK_ID'];

		return $this->getLinkedCollabEntity($taskId)?->getCollab();
	}

	protected function checkInternalEntity(): bool
	{
		$internalData = TaskCheckListFacade::getList(
			select: ['ID', 'TASK_ID'], filter: ['ID' => $this->id]
		);

		$internalData = $internalData[$this->id] ?? null;

		if ($internalData === null)
		{
			return false;
		}

		if (!isset($internalData['TASK_ID']))
		{
			return false;
		}

		if ((int)$internalData['TASK_ID'] <= 0)
		{
			return false;
		}

		$this->internalData = [
			'ID' => $this->id,
			'TASK_ID' => (int)$internalData['TASK_ID'],
		];

		return true;
	}

	protected function getLinkedCollabEntity(int $entityId): ?CollabEntity
	{
		return CollabEntityFactory::getById($entityId, EntityType::Task);
	}
}