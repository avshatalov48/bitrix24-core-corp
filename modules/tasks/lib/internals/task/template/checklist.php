<?
/**
 * Class CheckListTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Assert;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class CheckListTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CheckList_Query query()
 * @method static EO_CheckList_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CheckList_Result getById($id)
 * @method static EO_CheckList_Result getList(array $parameters = [])
 * @method static EO_CheckList_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_CheckList_Collection wakeUpCollection($rows)
 */
class CheckListTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template_chl_item';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	public static function getUfId()
	{
		return 'TASKS_TASK_TEMPLATE_CHECKLIST';
	}

	public static function add(array $data)
	{
		$data = static::normalizeColumns($data);

		if(!isset($data['SORT']))
		{
			$data['TEMPLATE_ID'] = intval($data['TEMPLATE_ID']);
			if (!$data['TEMPLATE_ID'] && intval($data['fields']['TEMPLATE_ID']))
			{
				$data['TEMPLATE_ID'] = intval($data['fields']['TEMPLATE_ID']);
			}

			if($data['TEMPLATE_ID'] && !array_key_exists('SORT', $data))
			{
				$item = static::getList(array(
					'runtime' => array(
						'MAX_SORT' => array(
							'dat_type' => 'integer',
							'expression' => array(
								'MAX(SORT)'
							)
						)
					),
					'filter' => array(
						'=TEMPLATE_ID' => $data['TEMPLATE_ID']
					),
					'select' => array(
						'MAX_SORT'
					)
				))->fetch();

				if(intval($item['MAX_SORT']))
				{
					$data['SORT'] = intval($item['MAX_SORT']) + 1;
				}
				else
				{
					$data['SORT'] = 1;
				}
				$data['fields']['SORT'] = $data['SORT'];
			}
		}

		return parent::add($data);
	}

	public static function update($primary, array $data)
	{
		$data = static::normalizeColumns($data);
		return parent::update($primary, $data);
	}

	public static function getListByTemplateDependency($templateId, $parameters)
	{
		$templateId = (int)$templateId;
		if (!$templateId) // getter should not throw any exception on bad parameters
		{
			return new \Bitrix\Main\DB\ArrayResult([]);
		}

		if (!is_array($parameters))
		{
			$parameters = [];
		}
		if (!is_array($parameters['filter'] ?? null))
		{
			$parameters['filter'] = [];
		}

		$parameters['filter']['@TEMPLATE_ID'] = new \Bitrix\Main\DB\SqlExpression(
			\Bitrix\Tasks\Internals\Task\Template\DependenceTable::getSubTreeSql($templateId)
		);

		return static::getList($parameters);
	}

	/**
	 * Update list items for a certain template: add new, update passed and delete absent.
	 * This function is low-level, i.e. it disrespects any events\callbacks
	 *
	 * @param integer $templateId
	 * @param mixed[] $items
	 * @return mixed[]
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function updateForTemplate($templateId, $items = array())
	{
		$templateId = 	Assert::expectIntegerPositive($templateId, '$templateId');
		$items = 		Assert::expectArray($items, '$items');

		$existed = array();
		$res = static::getList(array(
			'filter' => array('=TEMPLATE_ID' => $templateId),
			'select' => array('ID')
		));
		while($item = $res->fetch())
		{
			$existed[$item['ID']] = true;
		}

		$results = array();
		foreach($items as $item)
		{
			$item = Assert::expectArray($item, '$items[]');
			$item['TEMPLATE_ID'] = $templateId;

			$item['TITLE'] = trim($item['TITLE']);
			/*
			if((string) $item['TITLE'] == '')
			{
				continue;
			}
			*/

			if(intval($item['ID']))
			{
				$id = $item['ID'];

				unset($item['ID']);
				unset($existed[$id]);

				$results[$id] = static::update($id, $item);
			}
			else
			{
				$addResult = static::add($item);
				if($addResult->isSuccess())
					$results[$addResult->getId()] = $addResult;
				else
					$results[] = $addResult;
			}
		}

		foreach($existed as $id => $flag)
		{
			$results[$id] = static::delete($id);
		}

		return $results;
	}

	/**
	 * Move item after other item.
	 * This function is low-level, i.e. it disrespects any events\callbacks.
	 *
	 * @param integer $selectedItemId
	 * @param integer $insertAfterItemId
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function moveAfterItem($templateId, $selectedItemId, $insertAfterItemId)
	{
		$templateId = 			Assert::expectIntegerPositive($templateId, '$templateId');
		$selectedItemId = 		Assert::expectIntegerPositive($selectedItemId, '$selectedItemId');
		$insertAfterItemId = 	Assert::expectIntegerPositive($insertAfterItemId, '$insertAfterItemId');

		$res = static::getList(array('filter' => array(
			'=TEMPLATE_ID' => $templateId
		), 'order' => array(
			'SORT' => 'asc',
			'ID' => 'asc'
		), 'select' => array(
			'ID',
			'SORT'
		)));

		$items = array($selectedItemId => 0);	// by default to first position
		$prevItemId = 0;
		$sortIndex = 1;
		while($item = $res->fetch())
		{
			if ($insertAfterItemId == $prevItemId)
				$items[$selectedItemId] = $sortIndex++;

			if ($item['ID'] != $selectedItemId)
				$items[$item['ID']] = $sortIndex++;

			$prevItemId = $item['ID'];
		}

		if ($insertAfterItemId == $prevItemId)
			$items[$selectedItemId] = $sortIndex;

		if (!empty($items))
		{
			$sql = "
				UPDATE ".static::getTableName()."
					SET
						SORT = CASE ";

			foreach ($items as $id => $sortIndex)
				$sql .= " WHEN ID = '".intval($id)."' THEN '".intval($sortIndex)."'";

			$sql .= " END

				WHERE TEMPLATE_ID = '".intval($templateId)."'";

			\Bitrix\Main\HttpApplication::getConnection()->query($sql);
		}
	}

	/**
	 * Removes all checklist's items for given template.
	 * This function is low-level, i.e. it disrespects any events\callbacks
	 *
	 * @param integer $templateId
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function deleteByTemplateId($templateId)
	{
		$templateId = Assert::expectIntegerPositive($templateId, '$templateId');

		\Bitrix\Main\HttpApplication::getConnection()->query("DELETE FROM ".static::getTableName()." WHERE TEMPLATE_ID = '".$templateId."'");
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
			),
			'TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_TEMPLATE_ID_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'TITLE' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_TITLE_FIELD'),
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			),
			'CHECKED' => array(
				'data_type' => 'integer',
			),
			'IS_IMPORTANT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),

			// for compatibility
			'IS_COMPLETE' => array(
				'data_type' => 'string',
				'expression' => array(
					"CASE WHEN %s = '1' THEN 'Y' ELSE 'N' END",
					"CHECKED"
				)
			),
			'SORT_INDEX' => array(
				'data_type' => 'integer',
				'expression' => array(
					"%s",
					"SORT"
				)
			),
		);
	}

	/**
	 * @return string
	 */
	public static function getSortColumnName()
	{
		return 'SORT';
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	private static function normalizeColumns($data)
	{
		if (array_key_exists('SORT_INDEX', $data))
		{
			$data['SORT'] = $data['SORT_INDEX'];
			unset($data['SORT_INDEX']);
		}

		if (array_key_exists('IS_COMPLETE', $data))
		{
			$data['CHECKED'] = $data['IS_COMPLETE'];
			unset($data['IS_COMPLETE']);
		}

		return $data;
	}

	/**
	 * @param string $ids - string of type (1,2,...,7)
	 */
	public static function deleteByCheckListsIds($ids)
	{
		global $DB;

		$tableName = static::getTableName();

		$DB->Query("
			DELETE FROM {$tableName}
			WHERE ID IN {$ids} 
		");
	}
}