<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main;

/**
 * Class SourceFieldTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SourceField_Query query()
 * @method static EO_SourceField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SourceField_Result getById($id)
 * @method static EO_SourceField_Result getList(array $parameters = [])
 * @method static EO_SourceField_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceField createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceField_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceField wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceField_Collection wakeUpCollection($rows)
 */
class SourceFieldTable extends Main\ORM\Data\DataManager
{
	const FIELD_PHONE = 'PHONE';
	const FIELD_EMAIL = 'EMAIL';
	const FIELD_UTM_SOURCE = 'UTM_SOURCE';
	const FIELD_REF_DOMAIN = 'REF_DOMAIN';
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_source_field';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'SOURCE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'CODE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'VALUE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'SOURCE' => [
				'data_type' => SourceTable::class,
				'reference' => ['=this.SOURCE_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * Get field codes.
	 *
	 * @return array
	 */
	public static function getFieldCodes()
	{
		return [
			static::FIELD_EMAIL,
			static::FIELD_PHONE,
			static::FIELD_REF_DOMAIN,
			static::FIELD_UTM_SOURCE,
		];
	}

	/**
	 * Set source fields.
	 *
	 * @param int $sourceId Source ID.
	 * @param string $code Field code.
	 * @param array $values List of values.
	 * @return void
	 */
	public static function setSourceField($sourceId, $code, array $values)
	{
		if (!$sourceId || !$code)
		{
			return;
		}

		$existedList = static::getList([
			'select' => ['ID'],
			'filter' => ['=SOURCE_ID' => $sourceId, '=CODE' => $code]
		]);
		foreach ($existedList as $existed)
		{
			static::delete($existed['ID']);
		}

		foreach ($values as $value)
		{
			if (!$value)
			{
				continue;
			}

			static::add([
				'SOURCE_ID' => $sourceId,
				'CODE' => $code,
				'VALUE' => $value,
			]);
		}
	}

	/**
	 * Get source fields.
	 *
	 * @param int $sourceId Source ID.
	 * @param string $code Field code.
	 * @return array
	 */
	public static function getSourceField($sourceId, $code)
	{
		$fields = static::getSourceFields();
		if (!isset($fields[$sourceId]))
		{
			return [];
		}
		if (!isset($fields[$sourceId][$code]))
		{
			return [];
		}

		return $fields[$sourceId][$code];
	}

	/**
	 * Get source fields defaults.
	 *
	 * @return array
	 */
	public static function getSourceFieldsDefaults()
	{
		$defaults = [];
		foreach (static::getFieldCodes() as $code)
		{
			$defaults[$code] = [];
		}

		return $defaults;
	}

	/**
	 * Get source fields.
	 *
	 * @return array
	 */
	public static function getSourceFields()
	{
		$fields = [];
		$defaults = static::getSourceFieldsDefaults();

		$list = static::getList([
			'cache' => ['ttl' => 3600]
		])->fetchAll();
		foreach ($list as $item)
		{
			if (!isset($fields[$item['SOURCE_ID']]) || !is_array($fields[$item['SOURCE_ID']]))
			{
				$fields[$item['SOURCE_ID']] = $defaults;
			}

			$fields[$item['SOURCE_ID']][$item['CODE']][] = $item['VALUE'];
		}

		return $fields;
	}
}
