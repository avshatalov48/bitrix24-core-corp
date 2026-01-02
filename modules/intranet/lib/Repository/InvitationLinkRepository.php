<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\InvitationLink;
use Bitrix\Intranet\Enum\LinkEntityType;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\Internal\Model\InvitationLink as InvitationLinkModel;
use Bitrix\Intranet\Contract\Repository\InvitationLinkRepository as InvitationLinkRepositoryContract;
use Bitrix\Intranet\Table\InvitationLinkTable;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

class InvitationLinkRepository implements InvitationLinkRepositoryContract
{
	private static array $entityCache = [];

	public function getByEntity(LinkEntityType $entityType, int $entityId): ?InvitationLink
	{
		$cacheKey = $entityType->value.$entityId;
		if (isset(static::$entityCache[$cacheKey]) && static::$entityCache[$cacheKey] instanceof InvitationLink)
		{
			return static::$entityCache[$cacheKey];
		}
		$model = InvitationLinkTable::query()
			->where('ENTITY_TYPE', $entityType->value)
			->where('ENTITY_ID', $entityId)
			->setLimit(1)
			->fetchObject();

		if ($model)
		{
			static::$entityCache[$cacheKey] = $this->makeEntityByModel($model);

			return static::$entityCache[$cacheKey];
		}

		return null;
	}

	public function getActualByEntity(LinkEntityType $entityType, int $entityId): ?InvitationLink
	{
		$subFilter = Query::filter()
			->logic('or')
			->where('EXPIRED_AT', '>', new DateTime())
			->whereNull('EXPIRED_AT');

		$model = InvitationLinkTable::query()
			->where('ENTITY_TYPE', $entityType->value)
			->where('ENTITY_ID', $entityId)
			->where($subFilter)
			->setLimit(1)
			->fetchObject();

		if ($model)
		{
			return $this->makeEntityByModel($model);
		}

		return null;
	}

	public function create(InvitationLink $entity): InvitationLink
	{
		$oldEntity = $this->getByEntity($entity->getEntityType(), $entity->getEntityId());
		if ($oldEntity)
		{
			throw new DuplicateEntryException(
				'Entity "'.$entity->getEntityType()->value.'" and id "'.$entity->getEntityId().'" is exists'
			);
		}
		$model = $this->makeModelByEntity($entity);
		$result = $model->save();

		if (!$result->isSuccess())
		{
			throw new CreationFailedException($result->getErrorCollection());
		}

		return $this->makeEntityByModel($model);
	}

	public function delete(int $id): bool
	{
		$result = InvitationLinkTable::delete($id);

		static::$entityCache = [];

		return $result->isSuccess();
	}

	private function makeEntityByModel(InvitationLinkModel $model): InvitationLink
	{
		return new InvitationLink(
			$model->getEntityId(),
			LinkEntityType::from($model->getEntityType()),
			$model->getCode(),
			$model->getId(),
			$model->getCreatedBy(),
			$model->getCreatedAt(),
			$model->getExpiredAt(),
		);
	}

	private function makeModelByEntity(InvitationLink $entity): InvitationLinkModel
	{
		$model = InvitationLinkTable::getEntity()->createObject();

		$createdBy = $entity->getCreatedBy() !== null
			? $entity->getCreatedBy()
			: (
				CurrentUser::get()->getId() > 0
				? CurrentUser::get()->getId()
				: null
			)
		;
		$createdAt = $entity->getCreatedAt() !== null ? $entity->getCreatedAt() : new DateTime();

		$model->setEntityId($entity->getEntityId())
			->setEntityType($entity->getEntityType()->value)
			->setCode($entity->getCode())
			->setCreatedBy($createdBy)
			->setCreatedAt($createdAt)
			->setExpiredAt($entity->getExpiredAt());
		 if ((int)$entity->getId() > 0)
		 {
			 $model->setId($entity->getId());
		 }

		 return $model;
	}
}