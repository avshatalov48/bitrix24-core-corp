<?
/**
 * This class allow to manage the sale order's statuses from crm module
 */

IncludeModuleLangFile(__FILE__);

class CCrmStatusInvoice extends CCrmStatus
{
	public static function getStatusIds($statusType)
	{
		$result = array();

		if (!in_array($statusType, array('success', 'failed', 'neutral'), true))
			return $result;

		$statuses = self::getStatusList('INVOICE_STATUS');
		if ($statusType === 'success')
		{
			$result[] = 'P';
		}
		else if ($statusType === 'failed')
		{
			$check = false;
			foreach($statuses as $statusId => $statusInfo)
			{
				if ($check)
					$result[] = $statusId;
				if ($statusId === 'P')
					$check = true;
			}
			unset($check);
		}
		else if ($statusType === 'neutral')
		{
			foreach($statuses as $statusId => $statusInfo)
			{
				if ($statusId === 'P')
					break;
				$result[] = $statusId;
			}
		}

		return $result;
	}

	public static function GetList($arSort = array(), $arFilter = Array())
	{
		return static::GetStatus('INVOICE_STATUS');
	}

	public static function isStatusFailed($statusId): bool
	{
		$arStatuses = self::getStatusIds('failed');

		return in_array($statusId, $arStatuses);
	}

	public static function isStatusNeutral($statusId): bool
	{
		$arStatuses = self::getStatusIds('neutral');

		return in_array($statusId, $arStatuses, true);
	}

	public static function isStatusSuccess($statusId): bool
	{
		return $statusId === 'P';
	}

	public static function getByID($statusID): ?array
	{
		return static::GetStatus('INVOICE_STATUS')[$statusID] ?? null;
	}

	public function Add(array $arFields, bool $bCheckStatusId = true)
	{
		$arStatus = [
			'STATUS_ID' => self::getNewId(),
			'ENTITY_ID' => 'INVOICE_STATUS',
			'NAME' => $arFields['NAME']
		];

		if (isset($arFields['SORT']))
			$arStatus['SORT'] = $arFields['SORT'];

		return parent::Add($arStatus, $bCheckStatusId);
	}

	private static function getNewId(): string
	{
		do
		{
			$newId = chr(random_int(65, 90)); //A-Z
		}
		while(self::isIdExist($newId));

		return $newId;
	}

	/**
	 * Checks if status with ID already exist
	 * @param string $statusId
	 * @return bool
	 */
	private static function isIdExist(string $statusId): bool
	{
		$statusList = self::getStatusList('INVOICE_STATUS');

		return isset($statusList[$statusId]);
	}
}