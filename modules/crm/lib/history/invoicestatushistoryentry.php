<?php
namespace Bitrix\Crm\History;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\History\Entity\InvoiceStatusHistoryTable;

class InvoiceStatusHistoryEntry
{
	public static function getLatest($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$subQuery = new Query(InvoiceStatusHistoryTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(ID)'));
		$subQuery->addSelect('MAX_ID');
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query = new Query(InvoiceStatusHistoryTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MAX_ID'),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
	public static function isRegistered($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(InvoiceStatusHistoryTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}
	public static function register($ownerID, array $entityFields = null, array $options = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$time = isset($options['TIME']) ? $options['TIME'] : null;
		if($time === null)
		{
			$time = new DateTime();
		}
		//$month = (int)$time->format('m');
		//$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		//$year = (int)$time->format('Y');

		/** @var Date $date */
		$date = Date::createFromTimestamp($time->getTimestamp());

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmInvoice::GetList(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'STATUS_ID',
					'DATE_INSERT',
					'DATE_UPDATE',
					'DATE_BILL',
					'DATE_PAY_BEFORE',
					'PAY_VOUCHER_DATE',
					'DATE_MARKED',
					'RESPONSIBLE_ID'
				)
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return false;
		}
		$semanticID = \CCrmInvoice::GetSemanticID($statusID);
		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;

		/** @var Date $insertDate */
		$insertDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_INSERT']) ? $entityFields['DATE_INSERT'] : ''
		);
		if($insertDate === null)
		{
			$insertDate = new Date();
		}

		/** @var Date $billDate */
		$billDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_BILL']) ? $entityFields['DATE_BILL'] : ''
		);
		if($billDate === null)
		{
			$billDate = $insertDate;
		}

		/** @var Date $payBeforeDate */
		$payBeforeDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_PAY_BEFORE']) ? $entityFields['DATE_PAY_BEFORE'] : ''
		);
		if($payBeforeDate === null)
		{
			$payBeforeDate = new Date('9999-12-31', 'Y-m-d');
		}

		$isNew = isset($options['IS_NEW']) ? (bool)$options['IS_NEW'] : false;
		$typeID = PhaseSemantics::isFinal($semanticID)
			? HistoryEntryType::FINALIZATION : ($isNew ? HistoryEntryType::CREATION : HistoryEntryType::MODIFICATION);

		$latest = self::getLatest($ownerID);
		if($latest['STATUS_ID'] === $statusID)
		{
			return false;
		}

		$result = InvoiceStatusHistoryTable::add(
			array(
				'TYPE_ID' => $typeID,
				'OWNER_ID' => $ownerID,
				'CREATED_TIME' => $time,
				'CREATED_DATE' =>  $date,
				'BILL_DATE' => $billDate,
				'PAY_BEFORE_DATE' => $payBeforeDate,
				'ACTIVITY_DATE' => $isNew ? $billDate : $date,
				//'PERIOD_YEAR' => $year,
				//'PERIOD_QUARTER' => $quarter,
				//'PERIOD_MONTH' => $month,
				'RESPONSIBLE_ID' => $responsibleID,
				'STATUS_ID' => $statusID,
				'STATUS_SEMANTIC_ID' => $semanticID,
				'IS_NEW' =>  $isNew ? 'Y' : 'N',
				'IS_JUNK' =>  PhaseSemantics::isLost($semanticID) ? 'Y' : 'N'
			)
		);

		if($result->isSuccess()
			&& $result->getId() > 0
			&& is_array($latest)
			&& ((int)$latest['TYPE_ID']) === HistoryEntryType::FINALIZATION)
		{
			InvoiceStatusHistoryTable::delete($latest['ID']);
		}

		//Bitrix\Crm\History\LeadStatusHistoryEntry::unregister($ID);
		return true;
	}
	public static function unregister($ownerID)
	{
		InvoiceStatusHistoryTable::deleteByOwner($ownerID);
	}
	public static function synchronize($ownerID, array $entityFields = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$latest = self::getLatest($ownerID);
		if(!is_array($latest))
		{
			return false;
		}

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmInvoice::GetList(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'STATUS_ID',
					'DATE_INSERT',
					'DATE_UPDATE',
					'DATE_BILL',
					'DATE_PAY_BEFORE',
					'PAY_VOUCHER_DATE',
					'DATE_MARKED',
					'RESPONSIBLE_ID'
				)
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;
		/** @var Date $insertDate */
		$insertDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_INSERT']) ? $entityFields['DATE_INSERT'] : ''
		);
		if($insertDate === null)
		{
			$insertDate = new Date();
		}

		/** @var Date $billDate */
		$billDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_BILL']) ? $entityFields['DATE_BILL'] : ''
		);
		if($billDate === null)
		{
			$billDate = $insertDate;
		}

		/** @var Date $payBeforeDate */
		$payBeforeDate = \CCrmDateTimeHelper::ParseDateString(
			isset($entityFields['DATE_PAY_BEFORE']) ? $entityFields['DATE_PAY_BEFORE'] : ''
		);
		if($payBeforeDate === null)
		{
			$payBeforeDate = new Date('9999-12-31', 'Y-m-d');
		}

		$latestResponsibleID = (int)$latest['RESPONSIBLE_ID'];
		/** @var Date $latestBillDate */
		$latestBillDate = $latest['BILL_DATE'];
		/** @var Date $latestPayBeforeDate */
		$latestPayBeforeDate = $latest['PAY_BEFORE_DATE'];

		if($responsibleID === $latestResponsibleID
			&& $billDate->getTimestamp() === $latestBillDate->getTimestamp()
			&& $payBeforeDate->getTimestamp() === $latestPayBeforeDate->getTimestamp())
		{
			return false;
		}

		InvoiceStatusHistoryTable::synchronize(
			$ownerID,
			array(
				'RESPONSIBLE_ID' => $responsibleID,
				'BILL_DATE' => $billDate,
				'PAY_BEFORE_DATE' => $payBeforeDate
			)
		);
		return true;
	}
}