<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Main;
use Bitrix\Crm;

trait RequisiteControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Suspend entity requisites.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\Db\SqlQueryException
	 */
	protected function suspendRequisites($entityID, $recyclingEntityID)
	{
		$requisite = new Crm\EntityRequisite();
		$dbResult = $requisite->getList(
			array(
				'filter' => array('=ENTITY_TYPE_ID' => $this->getEntityTypeID(), '=ENTITY_ID' => $entityID),
				'select' => array('ID')
			)
		);

		$requisiteIDs = array();
		while($fields = $dbResult->fetch())
		{
			$requisiteIDs[] = (int)$fields['ID'];
		}

		$requisite->transferOwnership(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID
		);

		$bankingDetail = new Crm\EntityBankDetail();
		foreach($requisiteIDs as $requisiteID)
		{
			$bankingDetail->transferOwnership(
				\CCrmOwnerType::Requisite,
				$requisiteID,
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID
			);

			Crm\EntityAddress::rebind(
				\CCrmOwnerType::Requisite,
				$requisiteID,
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID
			);
		}

		Crm\Integrity\DuplicateRequisiteCriterion::unregister($this->getEntityTypeID(), $entityID);
		Crm\Integrity\DuplicateBankDetailCriterion::unregister($this->getEntityTypeID(), $entityID);

		//region Register volatile duplicate criterion fields
		DuplicateVolatileCriterion::register(
			$this->getEntityTypeID(),
			$entityID,
			[FieldCategory::REQUISITE, FieldCategory::BANK_DETAIL]
		);
		//endregion Register volatile duplicate criterion fields
	}

	/**
	 * Recover entity requisites.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\Db\SqlQueryException
	 */
	protected function recoverRequisites($recyclingEntityID, $newEntityID)
	{
		$requisite = new Crm\EntityRequisite();
		$dbResult = $requisite->getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => $this->getSuspendedEntityTypeID(),
					'=ENTITY_ID' => $recyclingEntityID
				),
				'select' => array('ID')
			)
		);

		$requisiteIDs = array();
		while($fields = $dbResult->fetch())
		{
			$requisiteIDs[] = (int)$fields['ID'];
		}

		$requisite->transferOwnership(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			$this->getEntityTypeID(),
			$newEntityID
		);

		$bankingDetail = new Crm\EntityBankDetail();
		foreach($requisiteIDs as $requisiteID)
		{
			$bankingDetail->transferOwnership(
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID,
				\CCrmOwnerType::Requisite,
				$requisiteID
			);

			Crm\EntityAddress::rebind(
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID,
				\CCrmOwnerType::Requisite,
				$requisiteID
			);
		}

		Crm\Integrity\DuplicateRequisiteCriterion::registerByEntity($this->getEntityTypeID(), $newEntityID);
		Crm\Integrity\DuplicateBankDetailCriterion::registerByEntity($this->getEntityTypeID(), $newEntityID);

		//region Register volatile duplicate criterion fields
		DuplicateVolatileCriterion::register(
			$this->getEntityTypeID(),
			$newEntityID,
			[FieldCategory::REQUISITE, FieldCategory::BANK_DETAIL]
		);
		//endregion Register volatile duplicate criterion fields
	}

	/**
	 * Erase Suspended Entity requisites.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function eraseSuspendedRequisites($recyclingEntityID)
	{
		$requisite = new Crm\EntityRequisite();
		$dbResult = $requisite->getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => $this->getSuspendedEntityTypeID(),
					'=ENTITY_ID' => $recyclingEntityID
				),
				'select' => array('ID')
			)
		);

		$requisiteIDs = array();
		while($fields = $dbResult->fetch())
		{
			$requisiteIDs[] = (int)$fields['ID'];
		}

		//Disabling type check is required
		$requisite->deleteByEntity(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			array('enableCheck' => false)
		);

		$bankDetail = new Crm\EntityBankDetail();
		foreach($requisiteIDs as $requisiteID)
		{
			Crm\EntityAddress::deleteByEntity(
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID
			);

			//Disabling type check is required
			$bankDetail->deleteByEntity(
				\CCrmOwnerType::SuspendedRequisite,
				$requisiteID,
				array('enableCheck' => false)
			);
		}
	}
}