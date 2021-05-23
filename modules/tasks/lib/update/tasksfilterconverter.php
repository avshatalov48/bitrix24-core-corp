<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Blog\Integration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Ui\Filter\Convert;

Loc::loadMessages(__FILE__);

final class TasksFilterConverter extends Stepper
{
	protected static $moduleId = "tasks";
	private $countAtHit = 100;

	public function execute(array &$result)
	{
		global $DB;

		if (!Loader::includeModule("tasks") || Option::get('tasks', 'tasksFilterNeedConvert', 'Y') == 'N')
		{
			return false;
		}
		$return = false;

		$params = Option::get("tasks", "tasksfilterconvert", "");
		$params = ($params !== "" ? @unserialize($params, ['allowed_classes' => false]) : array());
		$params = (is_array($params) ? $params : array());

		if (empty($params))
		{
			$filterRes = $DB->Query('SELECT COUNT(*) as CNT FROM b_tasks_filters');
			if (!$filterRes)
			{
				$count = 0;
			}
			else
			{
				$filter = $filterRes->Fetch();
				$count = $filter['CNT'];
			}
			$params = array(
				"lastId" => 0,
				"number" => 0,
				"count" => $count
			);
		}

		if ($params["count"] > 0)
		{
			$found = false;
			$res = $DB->Query(
				"
				SELECT ID, NAME, PARENT, SERIALIZED_FILTER, USER_ID
				FROM b_tasks_filters 
				WHERE ID > ".(int)$params["lastId"]."
				LIMIT {$this->countAtHit}"
			);

			$userFilters = array();

			while ($arData = $res->Fetch())
			{
				$serializedFilter = unserialize($arData['SERIALIZED_FILTER'], ['allowed_classes' => false]);
				if(!is_array($serializedFilter))
				{
					$serializedFilter = array();
				}

				$fields = \Bitrix\Tasks\Ui\Filter\Convert\Filter::prepareFilter($serializedFilter);
				if ($fields)
				{
					$userFilters[$arData['USER_ID']]['exported_filter_'.microtime(true).'_'.$arData['ID']] = array(
						'name' => $arData['NAME'],
						'fields' => $fields,
						'sort'=> 100,
						'filter_rows' => join(',', array_keys($fields))
					);
				}

				$params["lastId"] = $arData['ID'];
				$params["number"]++;
				$found = true;
			}

			foreach(array_keys($userFilters) as $userId)
			{
				$filters = \CUserOptions::getOption("main.ui.filter", 'TASKS_GRID_ROLE_ID_4096_0_ADVANCED_N', array(), $userId);
				if(!$filters)
				{
					$filters = array();
					$filters["use_pin_preset"] = true;
					$filters["default_presets"] = \Bitrix\Tasks\Helper\Filter::getPresets();
					$filters["default"] = \Bitrix\Main\UI\Filter\Options::findDefaultPresetId(
						$filters["default_presets"]
					);
					$filters["filter"] = $filters["default"];
					$filters["filters"] = $filters["default_presets"];
				}

				$filters['filters'] = array_merge((array)$filters['filters'], $userFilters[$userId]);

				\CUserOptions::SetOption(
					'main.ui.filter',
					'TASKS_GRID_ROLE_ID_4096_0_ADVANCED_N',
					$filters,
					false,
					$userId
				);
			}

			$result["steps"] = $params["number"];
			$result["count"] = $params["count"];

			if ($found)
			{
				Option::set("tasks", "tasksfilterconvert", serialize($params));
				$return = true;
			}
			else
			{
				Option::delete("tasks", array("name" => "tasksfilterconvert"));
				Option::set('tasks', 'tasksFilterNeedConvert', 'N');
			}
		}

		return $return;
	}
}

?>
