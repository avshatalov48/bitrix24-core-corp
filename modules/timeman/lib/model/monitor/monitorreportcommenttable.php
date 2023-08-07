<?php
namespace Bitrix\Timeman\Model\Monitor;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DateField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class MonitorReportCommentTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_LOG date mandatory
 * <li> USER_ID int mandatory
 * <li> DESKTOP_CODE string(32) optional
 * <li> COMMENT text optional
 * </ul>
 *
 * @package Bitrix\Timeman\Model\Monitor
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MonitorReportComment_Query query()
 * @method static EO_MonitorReportComment_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_MonitorReportComment_Result getById($id)
 * @method static EO_MonitorReportComment_Result getList(array $parameters = array())
 * @method static EO_MonitorReportComment_Entity getEntity()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment createObject($setDefaultValues = true)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection createCollection()
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment wakeUpObject($row)
 * @method static \Bitrix\Timeman\Model\Monitor\EO_MonitorReportComment_Collection wakeUpCollection($rows)
 */

class MonitorReportCommentTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_timeman_monitor_report_comment';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('MONITOR_REPORT_COMMENT_ENTITY_ID_FIELD')
				]
			),
			new DateField(
				'DATE_LOG',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_REPORT_COMMENT_ENTITY_DATE_LOG_FIELD')
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MONITOR_REPORT_COMMENT_ENTITY_USER_ID_FIELD')
				]
			),
			new StringField(
				'DESKTOP_CODE',
				[
					'validation' => [__CLASS__, 'validateDesktopCode'],
					'title' => Loc::getMessage('MONITOR_REPORT_COMMENT_ENTITY_DESKTOP_CODE_FIELD')
				]
			),
			new TextField(
				'COMMENT',
				[
					'title' => Loc::getMessage('MONITOR_REPORT_COMMENT_ENTITY_COMMENT_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for DESKTOP_CODE field.
	 *
	 * @return array
	 */
	public static function validateDesktopCode()
	{
		return [
			new LengthValidator(null, 32),
		];
	}
}