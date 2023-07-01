<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\Orm;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB;

use Bitrix\Crm\Communication;

/**
 * Class PhoneNumberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PhoneNumber_Query query()
 * @method static EO_PhoneNumber_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PhoneNumber_Result getById($id)
 * @method static EO_PhoneNumber_Result getList(array $parameters = [])
 * @method static EO_PhoneNumber_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_PhoneNumber createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_PhoneNumber_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_PhoneNumber wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_PhoneNumber_Collection wakeUpCollection($rows)
 */
class PhoneNumberTable extends Orm\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_phone_number';
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
			'VALUE' => [
				'data_type' => 'string',
			],
			'USE_CNT' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			],
			'DATE_USE' => [
				'data_type' => 'datetime',
			],
		];
	}

	/**
	 * Get using by number.
	 *
	 * @param string $number Phone number.
	 * @return array
	 */
	public static function getUsingByNumber($number)
	{
		static $list = null;
		if ($list === null)
		{
			$list = [];
			$rows = static::getList([
				'select' => ['VALUE', 'SUM_USE_CNT' ,'MAX_DATE_USE'],
				'runtime' => [
					new Orm\Fields\ExpressionField('SUM_USE_CNT', 'SUM(%s)', ['USE_CNT']),
					new Orm\Fields\ExpressionField('MAX_DATE_USE', 'MAX(%s)', ['DATE_USE']),
				],
				'group' => ['VALUE'],
				'cache' => ['ttl' => 1]
			])->fetchAll();
			foreach ($rows as $row)
			{
				$rowNumber = Communication\Normalizer::normalizePhone($row['VALUE']);
				$rowNumber = $rowNumber ?: $row['VALUE'];
				$row['VALUE'] = $rowNumber;
				$list[$rowNumber] = $row;
			}
		}

		return [
			'cnt' => isset($list[$number]) ? $list[$number]['SUM_USE_CNT'] : 0,
			'date' => isset($list[$number])
				? (string) $list[$number]['MAX_DATE_USE']
				: null,
		];
	}

	/**
	 * Append phone number.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	public static function appendNumber($value)
	{
		$row = static::getRow([
			'select' => ['ID'],
			'filter' => ['=VALUE' => $value]
		]);
		if ($row)
		{
			$result = static::update(
				$row['ID'],
				[
					'USE_CNT' => new DB\SqlExpression("?# + ?i", 'USE_CNT', 1),
					'DATE_USE' => new DateTime()
				]
			);

		}
		else
		{
			$result = static::add([
				'VALUE' => $value,
				'USE_CNT' => 1,
				'DATE_USE' => new DateTime()
			]);
		}

		return $result->isSuccess() ? $value : false;
	}

	/**
	 * Handler of VoxImplant call end event.
	 *
	 * @param array $data Event data.
	 * @return void
	 */
	public static function onVoxImplantCallEnd($data)
	{
		//$phoneNumber = !empty($data['PHONE_NUMBER']) ? $data['PHONE_NUMBER'] : null;
		$portalNumber = !empty($data['PORTAL_NUMBER']) ? trim($data['PORTAL_NUMBER']) : null;
		if (!$portalNumber)
		{
			return;
		}

		static::appendNumber($portalNumber);
	}
}