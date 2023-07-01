<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB;

use Bitrix\Crm\Communication;

/**
 * Class PoolTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Pool_Query query()
 * @method static EO_Pool_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Pool_Result getById($id)
 * @method static EO_Pool_Result getList(array $parameters = [])
 * @method static EO_Pool_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Pool createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Pool_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Pool wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Pool_Collection wakeUpCollection($rows)
 */
class PoolTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_pool';
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
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			],
			'TYPE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'VALUE' => [
				'data_type' => 'string',
			],
			/*
			'PHONE_NUMBER' => [
				'data_type' => PhoneNumberTable::class,
				'reference' => [
					'=this.TYPE_ID' => new DB\SqlExpression('?', Communication\Type::PHONE),
					'=this.VALUE' => 'ref.VALUE',
				],
			],
			*/
		];
	}

	/**
	 * Append item to pool.
	 *
	 * @param int $typeId Type ID.
	 * @param string $value Value.
	 * @return bool
	 */
	public static function appendPoolItem($typeId, $value)
	{
		$value = Communication\Normalizer::normalize($value, $typeId);
		if (!Communication\Validator::validate($value, $typeId))
		{
			return false;
		}

		$row = static::getRow([
			'select' => ['ID'],
			'filter' => ['=TYPE_ID' => $typeId, '=VALUE' => $value]
		]);
		if ($row)
		{
			return true;
		}

		$result = static::add(['TYPE_ID' => $typeId, 'VALUE' => $value]);
		return $result->isSuccess() ? $value : false;
	}

	/**
	 * Remove item to pool.
	 *
	 * @param int $typeId Type ID.
	 * @param string $value Value.
	 * @return bool
	 */
	public static function removePoolItem($typeId, $value)
	{
		$normalizedValue = Communication\Normalizer::normalize($value, $typeId);
		if (!Communication\Validator::validate($normalizedValue, $typeId))
		{
			return false;
		}

		$row = static::getRow([
			'select' => ['ID'],
			'filter' => ['=TYPE_ID' => $typeId, '=VALUE' => [$value, $normalizedValue]]
		]);
		if (!$row)
		{
			return true;
		}

		return static::delete($row['ID'])->isSuccess();
	}

	/**
	 * Get pool items by type ID.
	 *
	 * @param int $typeId Type ID.
	 * @return array
	 */
	public static function getPoolItemsByTypeId($typeId)
	{
		$list = static::getList([
			'select' => ['VALUE'],
			'filter' => ['=TYPE_ID' => $typeId]
		])->fetchAll();

		return array_column($list, 'VALUE');
	}
}