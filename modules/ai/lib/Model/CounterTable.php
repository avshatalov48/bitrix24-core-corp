<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;

/**
 * Class CounterTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Counter_Query query()
 * @method static EO_Counter_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Counter_Result getById($id)
 * @method static EO_Counter_Result getList(array $parameters = [])
 * @method static EO_Counter_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Counter createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Counter_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Counter wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Counter_Collection wakeUpCollection($rows)
 */
class CounterTable extends Entity\DataManager
{
	use MergeTrait;

	public const COUNTER_LAST_REQUEST_IN_BAAS = 'last_request_in_baas';

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_counter';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Entity\StringField('NAME', [
				'required' => true,
			]),
			new Entity\StringField('VALUE'),
		];
	}
}
