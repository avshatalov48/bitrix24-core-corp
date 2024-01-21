<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Exclusion\Entity;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;

use Bitrix\Crm\Communication;

/**
 * Class ExclusionTable
 *
 * @package Bitrix\Crm\Exclusion\Entity
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Exclusion_Query query()
 * @method static EO_Exclusion_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Exclusion_Result getById($id)
 * @method static EO_Exclusion_Result getList(array $parameters = [])
 * @method static EO_Exclusion_Entity getEntity()
 * @method static \Bitrix\Crm\Exclusion\Entity\EO_Exclusion createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Exclusion\Entity\EO_Exclusion_Collection createCollection()
 * @method static \Bitrix\Crm\Exclusion\Entity\EO_Exclusion wakeUpObject($row)
 * @method static \Bitrix\Crm\Exclusion\Entity\EO_Exclusion_Collection wakeUpCollection($rows)
 */
class ExclusionTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_exclusion';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
				'required' => true,
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'default_value' => Communication\Type::EMAIL,
				'required' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'COMMENT' => array(
				'data_type' => 'string',
			),
		);
	}

	/**
	 * Add exclusion batch.
	 *
	 * @param array $list List.
	 * @return void
	 */
	public static function addExclusionBatch(array $list)
	{
		$updateList = array();
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$dateInsert = new DateTime();

		sort($list);
		foreach ($list as $index => $item)
		{
			$item = explode(';', $item);
			TrimArr($item);
			if (!$item[0])
			{
				continue;
			}

			$code = $item[0];
			$name = isset($item[1]) ? $item[1] : null;
			$name = is_string($name) ? trim($name) : null;
			$name = Encoding::convertEncodingToCurrent($name);

			$typeId = Communication\Type::detect($code);
			if (!$typeId)
			{
				continue;
			}

			$code = Communication\Normalizer::normalize($code, $typeId);
			if (!$code)
			{
				continue;
			}

			$updateItem = array(
				'TYPE_ID' => $typeId,
				'CODE' => $code,
				'COMMENT' => $name,
				'DATE_INSERT' => $dateInsert,
			);
			$updateList[] = $updateItem;
		}

		if (count($updateList) === 0)
		{
			return;
		}
		$sqlHelper = Application::getConnection()->getSqlHelper();
		foreach (static::divideList($updateList) as $list)
		{
			$keys = implode(', ', array_keys(current($list)));
			$values = [];
			foreach ($list as $item)
			{
				$values[] = implode(
					", ",
					[
						(int) $item['TYPE_ID'],
						"'" . $sqlHelper->forSql($item['CODE']) . "'",
						$item['COMMENT'] ? "'" . $sqlHelper->forSql($item['COMMENT'], 255) . "'" : 'NULL',
						$sqlHelper->convertToDbDateTime($item['DATE_INSERT']),
					]
				);
			}
			$values = implode('), (', $values);

			$tableName = static::getTableName();
			$sql = $sqlHelper->getInsertIgnore($tableName, "($keys)", "VALUES($values)");
			Application::getConnection()->query($sql);
		}
	}

	protected static function divideList(array $list, $limit = 300)
	{
		$length = count($list);
		if ($length < $limit)
		{
			return array($list);
		}

		$result = array();
		$partsCount = ceil($length / $limit);
		for ($index = 0; $index < $partsCount; $index++)
		{
			$result[$index] = array_slice($list, $limit * $index, $limit);
		}

		return $result;
	}
}