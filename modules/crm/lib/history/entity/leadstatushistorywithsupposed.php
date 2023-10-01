<?php
namespace Bitrix\Crm\History\Entity;


use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Type\Date;

/**
 * Class LeadStatusHistoryWithSupposedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_LeadStatusHistoryWithSupposed_Query query()
 * @method static EO_LeadStatusHistoryWithSupposed_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_LeadStatusHistoryWithSupposed_Result getById($id)
 * @method static EO_LeadStatusHistoryWithSupposed_Result getList(array $parameters = [])
 * @method static EO_LeadStatusHistoryWithSupposed_Entity getEntity()
 * @method static \Bitrix\Crm\History\Entity\EO_LeadStatusHistoryWithSupposed createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\History\Entity\EO_LeadStatusHistoryWithSupposed_Collection createCollection()
 * @method static \Bitrix\Crm\History\Entity\EO_LeadStatusHistoryWithSupposed wakeUpObject($row)
 * @method static \Bitrix\Crm\History\Entity\EO_LeadStatusHistoryWithSupposed_Collection wakeUpCollection($rows)
 */
class LeadStatusHistoryWithSupposedTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_lead_status_history_with_supposed';
	}


	public static function getMap()
	{
		$map = [
			new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
			new IntegerField('OWNER_ID'),
			new DatetimeField('CREATED_TIME'),
			new DateField('CREATED_DATE'),
			new StringField('STATUS_SEMANTIC_ID'),
			new StringField('STATUS_ID'),
			new StringField('IS_LOST'),
			new StringField('IS_SUPPOSED'),
			new DateField('LAST_UPDATE_DATE'),
			new DateField('CLOSE_DATE'),
			new IntegerField('SPENT_TIME'),
		];
		return $map;
	}

	public static function clean()
	{
		$tableName = self::getTableName();
		global $DB;
		$DB->Query('TRUNCATE TABLE ' . $tableName . ';');
	}

	/**
	 * @param $ownerId
	 * @param Date $closeDate
	 *
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function updateCloseDateByOwnerId($ownerId, $closeDate)
	{
		$helper = Application::getConnection()->getSqlHelper();
		Application::getConnection()->queryExecute("UPDATE " . self::getTableName() . " SET CLOSE_DATE={$helper->convertToDb($closeDate, new \Bitrix\Main\ORM\Fields\DateField('D'))} " . " WHERE OWNER_ID={$ownerId}");
	}

	public static function deleteByOwnerId($dealId)
	{
		Application::getConnection()->queryExecute("DELETE FROM " . self::getTableName() . " WHERE OWNER_ID = {$dealId}");
	}

}
