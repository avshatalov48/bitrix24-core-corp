<?php
IncludeModuleLangFile(__FILE__);

class CCrmQuote extends CAllCrmQuote
{
	const TABLE_NAME = 'b_crm_quote';
	const ELEMENT_TABLE_NAME = 'b_crm_quote_elem';
	const DB_TYPE = 'MYSQL';

	public static function DoSaveElementIDs($ID, $storageTypeID, $arElementIDs)
	{
		global $APPLICATION, $DB;

		$ID = intval($ID);
		$storageTypeID = intval($storageTypeID);
		if($ID <= 0 || !\Bitrix\Crm\Integration\StorageType::isDefined($storageTypeID) || !is_array($arElementIDs))
		{
			$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_INVALID_PARAMS'));
			return false;
		}

		$DB->Query('DELETE FROM '.self::ELEMENT_TABLE_NAME.' WHERE QUOTE_ID = '.$ID);

		if(empty($arElementIDs))
		{
			return true;
		}

		$arRows = [];
		foreach($arElementIDs as $elementID)
		{
			$arRows[] = [
				'QUOTE_ID'=> $ID,
				'STORAGE_TYPE_ID' => $storageTypeID,
				'ELEMENT_ID' => $elementID,
			];
		}

		$bulkColumns = '';
		$bulkValues = [];

		foreach($arRows as &$row)
		{
			$data = $DB->PrepareInsert(self::ELEMENT_TABLE_NAME, $row);
			if($bulkColumns === '')
			{
				$bulkColumns = '('. $data[0] . ')';
			}

			$bulkValues[] = $data[1];
		}
		unset($row);

		$query = '';
		foreach($bulkValues as &$value)
		{
			$query .= ($query !== '' ? ',' : '').'('.$value.')';
		}

		if($query !== '')
		{
			$helper = \Bitrix\Main\Application::getConnection()->getSqlHelper();
			$sql = $helper->getInsertIgnore(self::ELEMENT_TABLE_NAME, $bulkColumns, ' VALUES ' . $query);

			$DB->Query($sql);
		}

		return true;
	}
}
