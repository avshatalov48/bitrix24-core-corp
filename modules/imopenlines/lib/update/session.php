<?
namespace Bitrix\Imopenlines\Update;

use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;


Loc::loadMessages(__FILE__);

final class Session extends Stepper
{
	const OPTION_NAME = "imopenlines_index_session";
	protected static $moduleId = "imopenlines";

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
				"count" => SessionTable::getCount(array(
					'=CLOSED' => 'Y',
				)),
			);
		}

		if ($params["count"] > 0)
		{
			$result["title"] = Loc::getMessage("IMOL_UPDATE_STATISTIC_INDEX");
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$cursor = SessionTable::getList(array(
				'order' => array('ID' => 'ASC'),
				'filter' => array(
					'>ID' => $params["lastId"],
					'=CLOSED' => 'Y',
				),
				'select' => array('ID'),
				'offset' => 0,
				'limit' => 100
			));

			$found = false;
			while ($row = $cursor->fetch())
			{
				SessionTable::indexRecord($row['ID']);

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