<?php
namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Recyclebin;

Main\Localization\Loc::loadMessages(__FILE__);

class Contact extends RecyclableEntity
{
	public static function getEntityName()
	{
		return 'crm_contact';
	}

	public static function prepareSurveyInfo()
	{
		return array(
			self::getEntityName() => array(
				'NAME'    => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_CONTACT_ENTITY_NAME'),
				'HANDLER' => self::class
			)
		);
	}

	/**
	 * Recover entity from Recycle Bin.
	 * @param Recyclebin\Internals\Entity $entity
	 * @return boolean
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		return Crm\Recycling\ContactController::getInstance()->recover(
			$entityID,
			array(
				'ID' => $entity->getId(),
				'SLOTS' => self::prepareDataSlots($entity),
				'SLOT_MAP' => self::prepareDataSlotMap($entity),
				'FILES' => $entity->getFiles()
			)
		);
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
			Crm\Recycling\ContactController::getInstance()->erase(
				$entityID,
				array(
					'ID' => $entity->getId(),
					'SLOTS' => self::prepareDataSlots($entity),
					'SLOT_MAP' => self::prepareDataSlotMap($entity),
					'FILES' => $entity->getFiles()
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
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_CONTACT_RESTORED'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_CONTACT_REMOVED')
			),
			'CONFIRM' => array(
				'RESTORE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_CONTACT_RECOVERY_CONFIRMATION'),
				'REMOVE' => Main\Localization\Loc::getMessage('CRM_RECYCLE_BIN_CONTACT_REMOVAL_CONFIRMATION')
			)
		);
	}
}
