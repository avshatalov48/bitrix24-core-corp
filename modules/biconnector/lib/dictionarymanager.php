<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\EO_User;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DictionaryManager
 *
 * @package Bitrix\BIConnector
 **/

class DictionaryManager
{
	protected static ?EO_User $userBefore = null;

	/**
	 * Returns user object by its idintifier.
	 *
	 * @param int $userId User identifier.
	 * @return EO_User
	 */
	protected static function getUser($userId)
	{
		return static::$userBefore = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'UF_DEPARTMENT'],
			'filter' => [
				'=ID' => $userId,
				'!=EXTERNAL_AUTH_ID' => \Bitrix\Main\UserTable::getExternalUserTypes(),
			],
			'limit' => 1,
		])->fetchObject();
	}

	/**
	 * Returns sorted and concatenated array values.
	 *
	 * @param array $a An array.
	 * @return string
	 */
	protected static function arrayToKey($a)
	{
		if (is_array($a))
		{
			sort($a);
			$key = implode(',', $a);
		}
		else
		{
			$key = '';
		}
		return $key;
	}

	/**
	 * Event OnBeforeUserUpdate handler.
	 * Invalidates user departments cache.
	 *
	 * @param array &$userFields CUser fields.
	 *
	 * @return void
	 */
	public static function onBeforeUserUpdateHandler(&$userFields)
	{
		if (array_key_exists('UF_DEPARTMENT', $userFields))
		{
			static::$userBefore = static::getUser($userFields['ID']);
		}
	}

	/**
	 * Event OnAfterUserUpdate handler.
	 * Invalidates user departments cache.
	 *
	 * @param array &$userFields CUser fields.
	 *
	 * @return void
	 */
	public static function onAfterUserUpdateHandler(&$userFields)
	{
		if (
			$userFields['RESULT']
			&& isset($userFields['UF_DEPARTMENT'])
			&& (int)$userFields['ID'] === static::$userBefore?->getId()
		)
		{
			$departmentBefore = static::$userBefore->getUfDepartment();
			$departmentAfter = $userFields['UF_DEPARTMENT'];
			if (static::arrayToKey($departmentBefore) !== static::arrayToKey($departmentAfter))
			{
				static::invalidateCache(Dictionary::USER_DEPARTMENT);
				static::$userBefore = null;
			}
		}
	}

	protected static $available = [];

	public static function isAvailable($dictionaryId)
	{
		if (!array_key_exists($dictionaryId, static::$available))
		{
			static::$available[$dictionaryId] = (static::getInsertSelect($dictionaryId) !== '');
		}
		return static::$available[$dictionaryId];
	}

	/**
	 * invalidateCache
	 *
	 * @param int $dictionaryId Data dictionary cache identifier.
	 *
	 * @return void
	 */
	public static function invalidateCache($dictionaryId)
	{
		DictionaryCacheTable::delete($dictionaryId);
	}

	protected static $validated = [];

	/**
	 * validateCache
	 *
	 * @param int $dictionaryId Data dictionary cache identifier.
	 *
	 * @return bool
	 */
	public static function validateCache($dictionaryId)
	{
		$dictionaryId = intval($dictionaryId);
		$manager = Manager::getInstance();
		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		if (isset(static::$validated[$dictionaryId]))
		{
			return static::$validated[$dictionaryId];
		}

		$select = static::getInsertSelect($dictionaryId);
		if (!$select)
		{
			static::$validated[$dictionaryId] = false;

			return false;
		}

		$sql = '
			SELECT UPDATE_DATE
			FROM ' . DictionaryCacheTable::getTableName() . '
			WHERE DICTIONARY_ID = ' . $dictionaryId . '
			AND DATE_ADD(UPDATE_DATE, INTERVAL TTL SECOND) > ' . $helper->getCurrentDateTimeFunction() . '
		';
		$updateDate = $connection->queryScalar($sql);
		if ($updateDate)
		{
			static::$validated[$dictionaryId] = true;

			return true;
		}

		$now = new \Bitrix\Main\Type\DateTime();
		$insertFields = [
			'DICTIONARY_ID' => $dictionaryId,
			'UPDATE_DATE' => $now,
			'TTL' => Dictionary::CACHE_TTL,
		];

		$updateFields = [
			'UPDATE_DATE' => $now,
			'TTL' => Dictionary::CACHE_TTL,
		];

		$queries = $helper->prepareMerge(DictionaryCacheTable::getTableName(), [
			'DICTIONARY_ID',
		], $insertFields, $updateFields);

		foreach ($queries as $query)
		{
			$connection->queryExecute($query);
		}

		try
		{
			DictionaryDataTable::deleteByFilter([
				'=DICTIONARY_ID' => $dictionaryId,
			]);

			DictionaryDataTable::insertSelect($select);
		}
		catch (\Exception $exception)
		{
			static::$validated[$dictionaryId] = false;

			return false;
		}

		static::$validated[$dictionaryId] = true;

		return true;
	}

	/**
	 * getInsertSelect
	 *
	 * @param  mixed $dictionaryId Data dictionary cache identifier.
	 *
	 * @return string
	 */
	public static function getInsertSelect($dictionaryId)
	{
		$select = '';

		if ($dictionaryId == Dictionary::USER_DEPARTMENT)
		{
			$manager = Manager::getInstance();
			$connection = $manager->getDatabaseConnection();
			$structureIblockId = (int)$connection->queryScalar("
				select value
				from b_option
				where module_id = 'intranet' and name = 'iblock_structure'
			");

			if (
				$structureIblockId > 0
				&& $connection->isTableExists('b_utm_user')
				&& $connection->isTableExists('b_iblock_section')
			)
			{
				$select = '
					select
						' . Dictionary::USER_DEPARTMENT . " AS DICTIONARY_ID
						,U.ID AS VALUE_ID
						,D.DEPARTMENT_PATH AS VALUE_STR
					from
						b_user U
						inner join (
							select VALUE_ID as USER_ID, min(VALUE_INT) AS USER_DEPARTMENT_ID
							from b_utm_user
							where FIELD_ID = (select ID from b_user_field where ENTITY_ID='USER' and FIELD_NAME='UF_DEPARTMENT')
							group by VALUE_ID
						) UD on UD.USER_ID = U.ID
						inner join (
							select
								c.id DEPARTMENT_ID
								,case
									when p.id is not null
									then concat(
										group_concat(concat('[',p.id,'] ',p.name) order by p.left_margin separator ' / '),
										' / [', c.id, '] ',
										c.name)
									else concat('[', c.id, '] ', c.name)
								end DEPARTMENT_PATH
							from
								b_iblock_section c
								left join b_iblock_section p
									on p.iblock_id = c.iblock_id
									and p.left_margin < c.left_margin
									and p.right_margin > c.right_margin
							where
								c.iblock_id = " . $structureIblockId . "
							group by
								c.id
						) D on D.DEPARTMENT_ID = UD.USER_DEPARTMENT_ID
					where
						U.EXTERNAL_AUTH_ID NOT IN ('" . implode("', '", \Bitrix\Main\UserTable::getExternalUserTypes()) . "') OR U.EXTERNAL_AUTH_ID IS NULL
					";
			}
		}

		return $select;
	}
}
