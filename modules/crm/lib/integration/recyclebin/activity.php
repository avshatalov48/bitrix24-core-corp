<?php

namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Crm;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Main;
use Bitrix\Recyclebin;

Main\Localization\Loc::loadMessages(__FILE__);

class Activity extends RecyclableEntity
{
	public static function getEntityName()
	{
		return 'crm_activity';
	}

	public static function prepareSurveyInfo()
	{
		return array(
			self::getEntityName() => array(
				'NAME'    => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_ACTIVITY_ENTITY_NAME'),
				'HANDLER' => self::class
			)
		);
	}

	/**
	 * Recover entity from Recycle Bin.
	 *
	 * @param Recyclebin\Internals\Entity $entity
	 * @return Main\Result | bool | int | null
	 */
	public static function moveFromRecyclebin(Recyclebin\Internals\Entity $entity)
	{
		if($entity->getEntityType() !== self::getEntityName())
		{
			return false;
		}

		$entityID = (int)$entity->getEntityId();
		if($entityID <= 0)
		{
			return false;
		}

		try
		{
			return Crm\Recycling\ActivityController::getInstance()->recover(
				$entityID,
				[
					'ID' => $entity->getId(),
					'ENTITY' => $entity,
					'SLOTS' => self::prepareDataSlots($entity),
					'SLOT_MAP' => self::prepareDataSlotMap($entity),
					'FILES' => $entity->getFiles(),
				]
			);
		}
		catch (Main\InvalidOperationException $exception)
		{
			return (new Main\Result())->addError(new Main\Error($exception->getMessage()));
		}
	}

	/**
	 * Erase entity from Recycle Bin.
	 *
	 * @param Recyclebin\Internals\Entity $entity
	 * @param array $params
	 *
	 * @return Main\Result
	 */
	public static function removeFromRecyclebin(Recyclebin\Internals\Entity $entity, array $params = [])
	{
		if($entity->getEntityType() !== self::getEntityName())
		{
			return (new Main\Result())->addError(new Main\Error('Entity type mismatch.'));
		}

		$entityID = (int)$entity->getEntityId();
		if($entityID <= 0)
		{
			return (new Main\Result())->addError(new Main\Error('Entity ID must be greater than zero.'));
		}

		try
		{
			Crm\Recycling\ActivityController::getInstance()->erase(
				$entityID,
				array(
					'ID' => $entity->getId(),
					'SLOTS' => self::prepareDataSlots($entity),
					'SLOT_MAP' => self::prepareDataSlotMap($entity),
					'FILES' => $entity->getFiles(),
					'SKIP_TASKS' => ActivitySettings::getValue(ActivitySettings::KEEP_UNBOUND_TASKS)
				)
			);
		}
		catch (\Exception $e)
		{
			return (new Main\Result())->addError(new Main\Error($e->getMessage(), $e->getCode()));
		}
		return (new Main\Result());
	}

	/**
	 * Get message array for Recycle Bin action's notification
	 * @return array
	 */
	public static function getNotifyMessages(): array
	{
		return array(
			'NOTIFY'=> array(
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_ACTIVITY_RESTORED'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_ACTIVITY_REMOVED')
			),
			'CONFIRM' => array(
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_ACTIVITY_RECOVERY_CONFIRMATION'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_ACTIVITY_REMOVAL_CONFIRMATION')
			)
		);
	}
}
