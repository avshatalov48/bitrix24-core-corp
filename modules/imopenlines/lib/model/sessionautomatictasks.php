<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main\ORM\Query\Join,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\Relations\Reference;

/**
 * Class SessionAutomaticTasksTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SessionAutomaticTasks_Query query()
 * @method static EO_SessionAutomaticTasks_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_SessionAutomaticTasks_Result getById($id)
 * @method static EO_SessionAutomaticTasks_Result getList(array $parameters = array())
 * @method static EO_SessionAutomaticTasks_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_SessionAutomaticTasks_Collection wakeUpCollection($rows)
 */
class SessionAutomaticTasksTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_session_automatic_tasks';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true
				]
			),
			new IntegerField(
				'CONFIG_AUTOMATIC_MESSAGE_ID',
				[
					'required' => true
				]
			),
			new IntegerField(
				'SESSION_ID',
				[
					'required' => true
				]
			),
			new DatetimeField(
				'DATE_TASK',
				[
					'required' => true
				]
			),
			new Reference(
				'SESSION',
				SessionTable::class,
				Join::on('this.SESSION_ID', 'ref.ID')
			),
			new Reference(
				'CONFIG_AUTOMATIC_MESSAGE',
				ConfigAutomaticMessagesTable::class,
				Join::on('this.CONFIG_AUTOMATIC_MESSAGE_ID', 'ref.ID')
			)
		];
	}
}