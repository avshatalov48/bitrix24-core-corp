<?php
namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class FlowSearchIndexTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FLOW_ID int mandatory
 * <li> SEARCH_INDEX text optional
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowSearchIndex_Query query()
 * @method static EO_FlowSearchIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowSearchIndex_Result getById($id)
 * @method static EO_FlowSearchIndex_Result getList(array $parameters = [])
 * @method static EO_FlowSearchIndex_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowSearchIndex_Collection wakeUpCollection($rows)
 */

class FlowSearchIndexTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_flow_search_index';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		$id = (new IntegerField('ID'))
			->configurePrimary()
			->configureAutocomplete()
		;

		$flowId = (new IntegerField('FLOW_ID'))
			->configureRequired()
		;

		$searchIndex = new TextField('SEARCH_INDEX');

		return [
			$id,
			$flowId,
			$searchIndex,
		];
	}

	/**
	 * @param int $flowId
	 * @param string $searchIndex
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function set(int $flowId, string $searchIndex): bool
	{
		$searchIndex = trim($searchIndex);

		if ($flowId <= 0 || empty($searchIndex))
		{
			return false;
		}

		$searchIndex = Application::getConnection()->getSqlHelper()->forSql($searchIndex);

		$row = static::query()
			->setSelect(['ID', 'SEARCH_INDEX', 'FLOW_ID'])
			->where('FLOW_ID', $flowId)
			->setLimit(1)
			->exec()->fetchObject()
		;

		if (!$row)
		{
			static::add([
				'FLOW_ID' => $flowId,
				'SEARCH_INDEX' => $searchIndex,
			]);

			return true;
		}

		if ($searchIndex === $row->getSearchIndex())
		{
			return true;
		}

		static::update($row->getId(), [
			'SEARCH_INDEX' => $searchIndex,
		]);

		return true;
	}
}