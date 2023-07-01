<?php

namespace Bitrix\Tasks\Integration\Intranet\Internals\Runtime;

use Bitrix\Main\Entity\DataManager;

/**
 * Class UtmUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UtmUser_Query query()
 * @method static EO_UtmUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UtmUser_Result getById($id)
 * @method static EO_UtmUser_Result getList(array $parameters = [])
 * @method static EO_UtmUser_Entity getEntity()
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection createCollection()
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser wakeUpObject($row)
 * @method static \Bitrix\Tasks\Integration\Intranet\Internals\Runtime\EO_UtmUser_Collection wakeUpCollection($rows)
 */
class UtmUserTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_utm_user';
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'VALUE_ID' => [
				'data_type' => 'integer',
			],
			'FIELD_ID' => [
				'data_type' => 'integer',
			],
			'VALUE_INT' => [
				'data_type' => 'integer',
			],
		];
	}
}