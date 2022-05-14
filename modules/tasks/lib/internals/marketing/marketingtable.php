<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class MarketingTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Marketing_Query query()
 * @method static EO_Marketing_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Marketing_Result getById($id)
 * @method static EO_Marketing_Result getList(array $parameters = [])
 * @method static EO_Marketing_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Marketing\EO_Marketing_Collection wakeUpCollection($rows)
 */
class MarketingTable extends TaskDataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_marketing';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'EVENT' => [
				'data_type' => 'string',
				'required' => true,
			],
			'DATE_CREATED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATE_SHEDULED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATE_EXECUTED' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'PARAMS' => [
				'data_type' => 'text',
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
		];
	}
}