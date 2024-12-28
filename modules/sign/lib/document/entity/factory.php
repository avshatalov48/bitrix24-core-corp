<?php

namespace Bitrix\Sign\Document\Entity;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Sign\Document\Result\Entity\Factory\SaveNewEntityResult;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;

class Factory
{
	/** @var array<Type\Document\EntityType::*, class-string<Dummy>> */
	private const ENTITY_TYPE_TO_ENTITY_CLASS = [
		Type\Document\EntityType::SMART => \Bitrix\Sign\Document\Entity\Smart::class,
		Type\Document\EntityType::SMART_B2E => \Bitrix\Sign\Document\Entity\SmartB2e::class,
	];

	public function getByDocument(Item\Document $document): ?Dummy
	{
		if ($document->entityType === null || $document->entityId === null)
		{
			return null;
		}

		return $this->create($document->entityType, $document->entityId);
	}

	/**
	 * @param Type\Document\EntityType::* $type
	 *
	 * @return class-string<Dummy>|null
	 */
	public function getEntityClassNameByType(string $type): ?string
	{
		return self::ENTITY_TYPE_TO_ENTITY_CLASS[$type] ?? null;
	}

	public function create(string $code, int $entityId): ?Dummy
	{
		$entityClass = self::ENTITY_TYPE_TO_ENTITY_CLASS[$code] ?? null;
		if ($entityClass === null)
		{
			return null;
		}

		return new $entityClass($entityId);
	}

	final public function createNewEntity(Item\Document $document, bool $checkPermission = true): SaveNewEntityResult
	{
		$result = new SaveNewEntityResult();
		if (!Loader::includeModule('crm'))
		{
			return $result->addError(new Error('CRM module is not installed'));
		}

		$entityType = $document->entityType;

		if ($entityType === null)
		{
			return $result->addError(new Main\Error("Document doesnt contains entity type"));
		}

		$entityClass = self::ENTITY_TYPE_TO_ENTITY_CLASS[$entityType] ?? null;

		if ($entityClass === null)
		{
			return $result->addError(new Main\Error("Entity type: `$entityType` is not exist"));
		}

		$resultEntityId = $entityClass::create($document, $checkPermission);
		if ($resultEntityId === null)
		{
			return $result->addError(new Main\Error("Can't create new crm entity"));
		}

		return $result->setId($resultEntityId);
	}
}
