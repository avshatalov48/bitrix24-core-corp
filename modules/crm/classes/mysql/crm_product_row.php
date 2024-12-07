<?php

use Bitrix\Crm\Reservation\Compatibility\ProductRowReserves;

class CCrmProductRow extends CAllCrmProductRow
{
	const TABLE_NAME = 'b_crm_product_row';
	const CONFIG_TABLE_NAME = 'b_crm_product_row_cfg';
	const DB_TYPE = 'MYSQL';

	/** @var bool */
	static $perRowInsert = false;

	/** @var array */
	static $originalRows = [];

	// Contract -->
	public static function DeleteByOwner($ownerType, $ownerID)
	{
		$ownerType = (string)($ownerType);
		$ownerID = (int)($ownerID);

		global $DB;
		$ownerType = $DB->ForSql($ownerType);

		$tableName = self::TABLE_NAME;

		$reservationTableName = \Bitrix\Crm\Reservation\Internals\ProductRowReservationTable::getTableName();
		$DB->Query(
			"DELETE FROM {$reservationTableName} WHERE ROW_ID IN (
					SELECT ID FROM {$tableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}
			)",
			true
		);

		$DB->Query(
			"DELETE FROM {$tableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}");
	}

	public static function SaveRows($ownerType, $ownerID, $arRows, $accountContext = null, $checkPerms = true, $regEvent = true, $syncOwner = true, $totalInfo = array())
	{
		if (!CCrmSaleHelper::isProcessInventoryManagement())
		{
			return parent::SaveRows($ownerType, $ownerID, $arRows, $accountContext, $checkPerms, $regEvent, $syncOwner, $totalInfo);
		}

		self::setPerRowInsert(true);

		$result = parent::SaveRows($ownerType, $ownerID, $arRows, $accountContext, $checkPerms, $regEvent, $syncOwner, $totalInfo);

		if ($result)
		{
			ProductRowReserves::processRows((string)$ownerType, (int)$ownerID, $arRows);
		}

		return $result;
	}

	public static function DoSaveRows($ownerType, $ownerID, array $arRows)
	{
		global $DB;

		static::$originalRows = $arRows;

		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		$insertRows = array();
		$updateRows = array();
		$deleteRows = array();
		foreach($arRows as $index => $row)
		{
			if(isset($row['ID']) && $row['ID'] > 0)
			{
				$updateRows[$row['ID']] = $row;
			}
			else
			{
				$row['ORIGINAL_INDEX'] = $index;
				$insertRows[] = $row;
			}
		}

		$dbResult = self::GetList(
			array('ID'=>'ASC'),
			array('=OWNER_TYPE' => $ownerType, '=OWNER_ID' => $ownerID)
		);
		if(is_object($dbResult))
		{
			while($row = $dbResult->Fetch())
			{
				$ID = $row['ID'];
				if(!isset($updateRows[$ID]))
				{
					$deleteRows[] = $ID;
				}
				elseif(!self::NeedForUpdate($row, $updateRows[$ID]))
				{
					unset($updateRows[$ID]);
				}
			}
		}

		$tableName = self::TABLE_NAME;

		if(!empty($deleteRows))
		{
			$scriptValues = implode(',', $deleteRows);
			$DB->Query("DELETE FROM {$tableName} WHERE ID IN ({$scriptValues})");

			$reservationTableName = \Bitrix\Crm\Reservation\Internals\ProductRowReservationTable::getTableName();
			$DB->Query(
				"DELETE FROM {$reservationTableName} WHERE ROW_ID IN ({$scriptValues})",
				true
			);
		}
		if(!empty($updateRows))
		{
			foreach($updateRows as $ID => $row)
			{
				unset($row['ID'], $row['OWNER_TYPE'], $row['OWNER_ID']);
				if ($row['TAX_RATE'] === null)
				{
					$row['TAX_RATE'] = false;
				}
				$scriptValues = $DB->PrepareUpdate($tableName, $row);

				$DB->Query("UPDATE {$tableName} SET {$scriptValues} WHERE ID = {$ID}");
			}
		}
		if(!empty($insertRows))
		{
			if (self::$perRowInsert)
			{
				foreach($insertRows as $row)
				{
					unset($row['ID']);

					$row['OWNER_TYPE'] = $ownerType;
					$row['OWNER_ID'] = $ownerID;
					if ($row['TAX_RATE'] === null)
					{
						$row['TAX_RATE'] = false;
					}

					if ($row['XML_ID'] === null)
					{
						$row['XML_ID'] = false;
					}

					$data = $DB->PrepareInsert($tableName, $row);

					$DB->Query(
						"INSERT INTO {$tableName}({$data[0]}) VALUES ({$data[1]})"
					);

					static::$originalRows[$row['ORIGINAL_INDEX']]['ID'] = (int)$DB->LastID();
				}
			}
			else
			{
				$scriptColumns = '';
				$scriptValues = '';
				foreach($insertRows as $row)
				{
					unset($row['ID']);

					$row['OWNER_TYPE'] = $ownerType;
					$row['OWNER_ID'] = $ownerID;
					if ($row['TAX_RATE'] === null)
					{
						$row['TAX_RATE'] = false;
					}

					if ($row['XML_ID'] === null)
					{
						$row['XML_ID'] = false;
					}
					$data = $DB->PrepareInsert($tableName, $row);

					if($scriptColumns === '')
					{
						$scriptColumns = $data[0];
					}

					if($scriptValues !== '')
					{
						$scriptValues .= ",({$data[1]})";
					}
					else
					{
						$scriptValues = "({$data[1]})";
					}
				}

				$DB->Query(
					"INSERT INTO {$tableName}({$scriptColumns}) VALUES {$scriptValues}"
				);
			}

		}

		return true;
	}

	public static function LoadSettings($ownerType, $ownerID)
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		global $DB;
		$tableName = self::CONFIG_TABLE_NAME;
		$ownerType = $DB->ForSql($ownerType);
		$dbResult = $DB->Query("SELECT SETTINGS FROM {$tableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}");
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		$s = is_array($fields) && isset($fields['SETTINGS']) ? $fields['SETTINGS'] : '';
		if($s === '')
		{
			return array();
		}

		return unserialize($s, ['allowed_classes' => false]);
	}

	public static function SaveSettings($ownerType, $ownerID, $settings)
	{
		$ownerType = $ownerType;
		$ownerID = intval($ownerID);

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$insert = [
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE' => $ownerType,
			'SETTINGS' => serialize($settings),
		];
		$update = [
			'SETTINGS' => $insert['SETTINGS'],
		];
		$merge = $helper->prepareMerge(self::CONFIG_TABLE_NAME, ['OWNER_ID', 'OWNER_TYPE'], $insert, $update);
		$connection->query($merge[0]);
	}

	/**
	 * @param bool $perRowInsert
	 */
	public static function setPerRowInsert(bool $perRowInsert): void
	{
		self::$perRowInsert = $perRowInsert;
	}

	/**
	 * @return array
	 */
	public static function getOriginalRows(): array
	{
		return self::$originalRows;
	}

	// <-- Contract
}
