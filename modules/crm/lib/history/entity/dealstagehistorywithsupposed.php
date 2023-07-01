<?php
namespace Bitrix\Crm\History\Entity;


use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\Date;

/**
 * Class DealStageHistoryWithSupposedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DealStageHistoryWithSupposed_Query query()
 * @method static EO_DealStageHistoryWithSupposed_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DealStageHistoryWithSupposed_Result getById($id)
 * @method static EO_DealStageHistoryWithSupposed_Result getList(array $parameters = [])
 * @method static EO_DealStageHistoryWithSupposed_Entity getEntity()
 * @method static \Bitrix\Crm\History\Entity\EO_DealStageHistoryWithSupposed createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\History\Entity\EO_DealStageHistoryWithSupposed_Collection createCollection()
 * @method static \Bitrix\Crm\History\Entity\EO_DealStageHistoryWithSupposed wakeUpObject($row)
 * @method static \Bitrix\Crm\History\Entity\EO_DealStageHistoryWithSupposed_Collection wakeUpCollection($rows)
 */
class DealStageHistoryWithSupposedTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_deal_stage_history_with_supposed';
	}


	public static function getMap()
	{
		$map = [
			new IntegerField('ID', ['primary' => true]),
			new IntegerField('OWNER_ID'),
			new DatetimeField('CREATED_TIME'),
			new DateField('CREATED_DATE'),
			new IntegerField('CATEGORY_ID'),
			new StringField('STAGE_SEMANTIC_ID'),
			new StringField('STAGE_ID'),
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
