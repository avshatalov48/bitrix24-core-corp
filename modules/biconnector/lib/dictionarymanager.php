<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DictionaryManager
 *
 * @package Bitrix\BIConnector
 **/

class DictionaryManager
{
	public static function validateCache($dictionaryId)
	{
		$dictionaryId = intval($dictionaryId);

		$select = static::getInsertSelect($dictionaryId);
		if (!$select)
		{
			return false;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$curDateSql = new \Bitrix\Main\Type\DateTime();

		$sql = '
			SELECT UPDATE_DATE
			FROM ' . DictionaryCacheTable::getTableName() . '
			WHERE DICTIONARY_ID = ' . $dictionaryId . '
			AND DATE_ADD(UPDATE_DATE, INTERVAL TTL SECOND) > ' . $helper->getCurrentDateTimeFunction() . '
		';
		$result = $connection->query($sql);
		if ($result->fetch())
		{
			return true;
		}

		$insertFields = [
			'DICTIONARY_ID' => $dictionaryId,
			'UPDATE_DATE' => $curDateSql,
			'TTL' => Dictionary::CACHE_TTL,
		];

		$updateFields = [
			'UPDATE_DATE' => $curDateSql,
			'TTL' => Dictionary::CACHE_TTL,
		];

		$queries = $helper->prepareMerge(DictionaryCacheTable::getTableName(), [
			'DICTIONARY_ID',
		], $insertFields, $updateFields);

		$connection->startTransaction();

		foreach ($queries as $query)
		{
			$connection->queryExecute($query);
		}

		DictionaryDataTable::deleteByFilter([
			'=DICTIONARY_ID' => $dictionaryId,
		]);

		DictionaryDataTable::insertSelect($select);

		$connection->commitTransaction();

		return true;
	}

	public static function getInsertSelect($dictionaryId)
	{
		$select = '';
		switch ($dictionaryId)
		{
		case Dictionary::USER_DEPARTMENT:
			$structureIblockId = intval(\Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', 0));
			if (
				$structureIblockId > 0
				&& \Bitrix\Main\Loader::includeModule('intranet')
				&& \Bitrix\Main\Loader::includeModule('iblock')
			)
			{
				$select = '
					select
						' . Dictionary::USER_DEPARTMENT . ' AS DICTIONARY_ID
						,U.ID AS VALUE_ID
						,D.DEPARTMENT_PATH AS VALUE_STR
					from
						b_user U
						inner join (
							select VALUE_ID as USER_ID, min(VALUE_INT) AS USER_DEPARTMENT_ID
							from b_utm_user
							where FIELD_ID = (select ID from b_user_field where ENTITY_ID=\'USER\' and FIELD_NAME=\'UF_DEPARTMENT\')
							group by VALUE_ID
						) UD on UD.USER_ID = U.ID
						inner join (
							select
								c.id DEPARTMENT_ID
								,concat(group_concat(concat(\'[\',p.id,\'] \',p.name) order by p.left_margin separator \' / \'), \' / [\', c.id, \'] \', c.name) DEPARTMENT_PATH
							from
								b_iblock_section c
								inner join b_iblock_section p
									on p.iblock_id = c.iblock_id
									and p.left_margin < c.left_margin
									and p.right_margin > c.right_margin
							where
								c.iblock_id = (select value from b_option where module_id=\'intranet\' and name=\'iblock_structure\')
							group by
								c.id
						) D on D.DEPARTMENT_ID = UD.USER_DEPARTMENT_ID
					';
			}
			break;
		}
		return $select;
	}
}
