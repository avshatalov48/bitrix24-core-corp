<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Tasks\Internals\Task\Template\CheckListTable;

/**
 * Class TemplateCheckListConverter
 *
 * @package Bitrix\Tasks\Update
 */
class TemplateCheckListConverter extends TaskCheckListConverter
{
	public static $needOptionName = "needTemplateCheckListConversion";
	protected static $paramsOptionName = "templateCheckListConversion";

	protected static $entityIdName = "TEMPLATE_ID";
	protected static $entityItemsTableName = "b_tasks_template_chl_item";
	protected static $entityItemsTreeTableName = "b_tasks_template_chl_item_tree";

	/** @var DataManager $entityItemsDataController */
	protected static $entityItemsDataController = CheckListTable::class;

	/**
	 * @return array
	 * @throws SqlQueryException
	 */
	protected static function getEntitiesIdsToConvert()
	{
		$ids = [];

		$connection = Application::getConnection();
		$templatesRes = $connection->query("
			SELECT I.TEMPLATE_ID
			FROM b_tasks_template_chl_item I
			WHERE I.TITLE = '==='
			GROUP BY I.TEMPLATE_ID
			ORDER BY I.TEMPLATE_ID DESC
			LIMIT 10
		");

		while ($template = $templatesRes->fetch())
		{
			$ids[] = $template['TEMPLATE_ID'];
		}

		return $ids;
	}

	/**
	 * @param $templateId
	 * @param $items
	 * @throws SqlQueryException
	 */
	protected static function insertCheckListRootItems($templateId, $items)
	{
		// insert BX_CHECKLIST_#NUM# items
		$itemsCount = count($items);
		$connection = Application::getConnection();

		$sql = "INSERT INTO b_tasks_template_chl_item (TEMPLATE_ID, TITLE, SORT)
				VALUES ";

		for ($i = 0; $i < $itemsCount; $i++)
		{
			if ($i)
			{
				$sql .= ",";
			}

			$sql.= "(" . $templateId . ",'BX_CHECKLIST_" . ($i + 1) . "'," . $i . ")";
		}

		$connection->query($sql);
	}
}