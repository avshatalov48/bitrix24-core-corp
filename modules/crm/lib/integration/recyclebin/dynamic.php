<?php

namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Recyclebin;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;

Main\Localization\Loc::loadMessages(__FILE__);

class Dynamic extends RecyclableEntity
{
	protected const SHORT_PREFIX = 'crm_';
	protected const PREFIX = 'crm_dynamic_';
	protected const MODULE_ID = 'crm';

	public static function createRecycleBinEntity($entityId, ?int $entityTypeId = null): Recyclebin\Internals\Entity
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		if(!$entityTypeId)
		{
			throw new Main\ArgumentException("EntityTypeId must be setted");
		}

		return new Recyclebin\Internals\Entity($entityId, static::getEntityName($entityTypeId), 'crm');
	}

	public static function getEntityName($entityTypeId = null): string
	{
		return self::SHORT_PREFIX . mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
	}

	public static function prepareSurveyInfo(): array
	{
		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false
		]);

		$data = [];
		foreach($typesMap->getTypes() as $type)
		{
			$data[static::getEntityName($type->getEntityTypeId())] = [
				'NAME' => $type->getTitle(),
				'HANDLER' => __CLASS__,
			];
		}

		return array_merge(self::getSurveyInfoFromRecyclebin(), $data);
	}

	public static function getSurveyInfoFromRecyclebin(): array
	{
		$list = RecyclebinTable::getList([
			'select' => [
				'ENTITY_TYPE_NAME'
			],
			'runtime' => [
				new Main\Entity\ExpressionField(
					'ENTITY_TYPE_NAME',
					'DISTINCT(%s)',
					['ENTITY_TYPE']
				),
			],
			'filter' => [
				'=%ENTITY_TYPE' => self::PREFIX.'%'
			]
		]);

		$result = [];
		foreach($list as $item)
		{
			$result[$item['ENTITY_TYPE_NAME']] = [
				'NAME' => $item['ENTITY_TYPE_NAME'],
				'HANDLER' => __CLASS__
			];
		}

		return $result;
	}

	/**
	 * @param Recyclebin\Internals\Entity $entity
	 * @return bool|void
	 */
	public static function moveFromRecyclebin(Recyclebin\Internals\Entity $entity)
	{
		$entityTypeName = $entity->getEntityType();
		if(strpos($entityTypeName, self::SHORT_PREFIX) === 0)
		{
			$entityTypeName = strtoupper(
				str_replace(self::SHORT_PREFIX, '', $entityTypeName)
			);
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		if(!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return false;
		}

		$entityId = (int)$entity->getEntityId();
		if($entityId <= 0)
		{
			return false;
		}

		return Crm\Recycling\DynamicController::getInstance($entityTypeId)->recover(
			$entityId,
			[
				'ID' => $entity->getId(),
				'SLOTS' => self::prepareDataSlots($entity),
				'SLOT_MAP' => self::prepareDataSlotMap($entity),
				'FILES' => $entity->getFiles(),
			]
		);
	}

	/**
	 * @param Recyclebin\Internals\Entity $entity
	 * @param array $params
	 *
	 * @return Main\Result|void
	 */
	public static function removeFromRecyclebin(Recyclebin\Internals\Entity $entity, array $params = []): Main\Result
	{
		$entityID = (int)$entity->getEntityId();
		if($entityID <= 0)
		{
			return (new Main\Result())->addError(
				new Main\Error('Entity ID must be greater than zero.')
			);
		}

		try
		{
			Crm\Recycling\DynamicController::getInstance(
				self::resolveEntityTypeId($entity->getEntityType())
			)->erase(
				$entityID,
				[
					'ID' => $entity->getId(),
					'SLOTS' => self::prepareDataSlots($entity),
					'SLOT_MAP' => self::prepareDataSlotMap($entity),
					'FILES' => $entity->getFiles()
				]
			);
		}
		catch(\Exception $e)
		{
			return (new Main\Result())->addError(
				new Main\Error($e->getMessage(), $e->getCode())
			);
		}
		return (new Main\Result());
	}

	/**
	 * @param string $entityType
	 * @return int
	 */
	public static function resolveEntityTypeId(string $entityType): int
	{
		$entityTypeName = str_replace(self::SHORT_PREFIX, '', $entityType);

		return \CCrmOwnerType::ResolveID($entityTypeName);
	}

	public static function getNotifyMessages(): array
	{
		return [
			'NOTIFY' => [
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_DYNAMIC_RESTORED'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_DYNAMIC_REMOVED')
			],
			'CONFIRM' => [
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_DYNAMIC_RECOVERY_CONFIRMATION'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_DYNAMIC_REMOVAL_CONFIRMATION')
			]
		];
	}
}
