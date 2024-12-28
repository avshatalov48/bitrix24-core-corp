<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab\Entity;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Collab;
use Bitrix\Socialnetwork\Collab\Entity\CollabEntity;
use Bitrix\Socialnetwork\Collab\Entity\Type\EntityType;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;

if (!Loader::includeModule('socialnetwork'))
{
	return;
}

class TaskEntity extends CollabEntity
{
	protected ?TaskObject $internalObject = null;

	public function __construct(int $id, mixed $internalObject = null)
	{
		if ($internalObject instanceof TaskObject)
		{
			$this->internalObject = $internalObject;
		}

		parent::__construct($id, $internalObject);
	}

	public function getType(): EntityType
	{
		return EntityType::Task;
	}

	public function getData(): array
	{
		return $this->internalObject->toArray();
	}

	protected function fillCollab(): ?Collab
	{
		if (!$this->internalObject->isCollab())
		{
			return null;
		}

		return $this->collabRegistry->get($this->internalObject->getGroupId());
	}

	protected function checkInternalEntity(): bool
	{
		if ($this->internalObject !== null)
		{
			return true;
		}

		$internalObject = TaskRegistry::getInstance()->getObject($this->id, true);
		if (!$internalObject instanceof TaskObject)
		{
			return false;
		}

		$this->internalObject = $internalObject;

		return true;
	}
}