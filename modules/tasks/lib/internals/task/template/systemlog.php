<?
namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class TaskTplSyslogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TYPE int mandatory
 * <li> DATA string optional
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

class SystemLogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_task_tpl_syslog';
	}

	/**
	 * Returns entity map definition.
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
				//'title' => Loc::getMessage('TASK_TPL_SYSLOG_ENTITY_ID_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('TASK_TPL_SYSLOG_ENTITY_TYPE_FIELD'),
			),
			'DATA' => array(
				'data_type' => 'text',
				//'title' => Loc::getMessage('TASK_TPL_SYSLOG_ENTITY_DATA_FIELD'),
			),
			'TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('TASK_TPL_SYSLOG_ENTITY_TEMPLATE_ID_FIELD'),
			),
		);
	}
}