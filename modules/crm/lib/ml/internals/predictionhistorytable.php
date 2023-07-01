<?php

namespace Bitrix\Crm\Ml\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class PredictionHistoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PredictionHistory_Query query()
 * @method static EO_PredictionHistory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PredictionHistory_Result getById($id)
 * @method static EO_PredictionHistory_Result getList(array $parameters = [])
 * @method static EO_PredictionHistory_Entity getEntity()
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionHistory createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionHistory_Collection createCollection()
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionHistory wakeUpObject($row)
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionHistory_Collection wakeUpCollection($rows)
 */
class PredictionHistoryTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_ml_prediction_history';
	}

	public static function getMap()
	{
		return [
			new IntegerField("ID", [
				"primary" => true,
				"autocomplete" => true
			]),
			new DatetimeField("CREATED", [
				"required" => true,
				"default_value" => function()
				{
					return new DateTime();
				}
			]),
			new IntegerField("ENTITY_TYPE_ID", [
				"required" => true
			]),
			new IntegerField("ENTITY_ID", [
				"required" => true
			]),
			new StringField("MODEL_NAME", [
				"required" => true
			]),
			new BooleanField("IS_PENDING", [
				"values" => ["N", "Y"]
			]),
			new StringField("ANSWER"),
			new FloatField("SCORE"),
			new FloatField("SCORE_DELTA"),
			new IntegerField("EVENT_TYPE"),
			new IntegerField("ASSOCIATED_ACTIVITY_ID"),
		];
	}

	/**
	 * Simply updates rows using filter.
	 * @param array $fields Fields.
	 * @param array $filter Filter.
	 * @param int $limit Limit.
	 * @return int Returns count of affected rows.
	 */
	public static function updateBatch(array $fields, array $filter, $limit = 0)
	{
		$limit = (int)$limit;
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($tableName, $fields);

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'UPDATE ' . $tableName . ' SET ' . $update[0] . ' WHERE ' . $where;
		if($limit > 0)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		$connection->queryExecute($sql, $update[1]);
		return $connection->getAffectedRowsCount();
	}

	/**
	 * Deletes rows using filter.
	 * @param array $filter Filter.
	 * @param int $limit Limit.
	 * @return int Returns count of affected rows.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteBatch(array $filter, $limit = 0)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		if($limit > 0)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		$connection->queryExecute($sql);
		return $connection->getAffectedRowsCount();
	}
}