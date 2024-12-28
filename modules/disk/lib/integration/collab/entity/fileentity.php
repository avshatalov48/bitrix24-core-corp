<?php

declare(strict_types=1);

namespace Bitrix\Disk\Integration\Collab\Entity;

use Bitrix\Disk\File;
use Bitrix\Disk\Integration\Collab\CollabService;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Collab;
use Bitrix\Socialnetwork\Collab\Entity\CollabEntity;
use Bitrix\Socialnetwork\Collab\Entity\Type\EntityType;

if (!Loader::includeModule('socialnetwork'))
{
	return;
}

class FileEntity extends CollabEntity
{
	protected ?File $internalObject = null;
	private CollabService $collabService;

	public function __construct(int $id, mixed $internalObject = null)
	{
		if ($internalObject instanceof File)
		{
			$this->internalObject = $internalObject;
		}

		$this->collabService = new CollabService();
		parent::__construct($id, $internalObject);
	}

	public function getType(): EntityType
	{
		return EntityType::File;
	}

	public function getData(): array
	{
		return $this->internalObject->toArray();
	}

	protected function fillCollab(): ?Collab
	{
		$storage = $this->internalObject->getStorage();
		if ($storage === null)
		{
			return null;
		}

		return $this->collabService->getCollabByStorage($storage);
	}

	protected function checkInternalEntity(): bool
	{
		if ($this->internalObject !== null)
		{
			return true;
		}

		$internalObject = File::getById($this->id);
		if ($internalObject === null)
		{
			return false;
		}

		$this->internalObject = $internalObject;

		return true;
	}
}