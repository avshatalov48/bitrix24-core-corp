<?
namespace Bitrix\Voximplant\Update;

use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Voximplant\StatisticTable;


Loc::loadMessages(__FILE__);

final class Statistic extends Stepper
{
	const OPTION_NAME = "voximplant_index_statistic";
	protected static $moduleId = "voximplant";

	/**
	 * @inheritdoc
	 */
	public function execute(array &$result)
	{
		if (!Loader::includeModule(self::$moduleId))
			return false;

		$return = false;

		$params = Option::get(self::$moduleId, self::OPTION_NAME, "");
		$params = ($params !== "" ? @unserialize($params) : array());
		$params = (is_array($params) ? $params : array());
		if (empty($params))
		{
			$params = array(
				"lastId" => 0,
				"number" => 0,
				"count" => StatisticTable::getCount(),
			);
		}

		if ($params["count"] > 0)
		{
			$result["title"] = Loc::getMessage("VI_UPDATE_STATISTIC_INDEX");
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$cursor = StatisticTable::getList(array(
				'order' => array('ID' => 'ASC'),
				'filter' => array(
					'>ID' => $params["lastId"],
				),
				'select' => array('ID'),
				'offset' => 0,
				'limit' => 100
			));

			$found = false;
			while ($row = $cursor->fetch())
			{
				StatisticTable::indexRecord($row['ID']);

				$params["lastId"] = $row['ID'];
				$params["number"]++;
				$found = true;
			}

			if ($found)
			{
				Option::set(self::$moduleId, self::OPTION_NAME, serialize($params));
				$return = true;
			}

			$result["progress"] = intval($params["number"] * 100/ $params["count"]);
			$result["steps"] = $params["number"];

			if ($found === false)
			{
				Option::delete(self::$moduleId, array("name" => self::OPTION_NAME));
			}
		}
		return $return;
	}
}
?>