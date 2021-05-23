<?
namespace Bitrix\Wiki\Update;

use \Bitrix\Main\Update\Stepper;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Wiki\Integration;
use \Bitrix\Socialnetwork\Item\LogIndex;
use \Bitrix\Socialnetwork\LogTable;
use \Bitrix\Socialnetwork\LogIndexTable;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class LivefeedIndexLog extends Stepper
{
	protected static $moduleId = "wiki";

	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule("wiki")
			&& Loader::includeModule("socialnetwork")
			&& Option::get('wiki', 'needLivefeedIndexLog', 'Y') == 'Y'
		))
		{
			return false;
		}

		$return = false;

		$params = Option::get("wiki", "livefeedindexlog", "");
		$params = ($params !== "" ? @unserialize($params, ["allowed_classes" => false]) : array());
		$params = (is_array($params) ? $params : array());
		if (empty($params))
		{
			$params = array(
				"lastId" => 0,
				"number" => 0,
				"count" => LogTable::getCount(
					array(
						'@EVENT_ID' => Integration\Socialnetwork\Log::getEventIdList(),
						'!SOURCE_ID' => false
					)
				)
			);
		}

		if ($params["count"] > 0)
		{
			$result["title"] = Loc::getMessage("FUPD_LF_WIKI_LOG_EVENT_INDEX_TITLE");
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$res = LogTable::getList(array(
				'order' => array('ID' => 'ASC'),
				'filter' => array(
					'>ID' => $params["lastId"],
					'@EVENT_ID' => Integration\Socialnetwork\Log::getEventIdList(),
					'!SOURCE_ID' => false
				),
				'select' => array('ID', 'EVENT_ID', 'SOURCE_ID'),
				'offset' => 0,
				'limit' => 100
			));

			$found = false;
			while ($record = $res->fetch())
			{
				LogIndex::setIndex(array(
					'itemType' => LogIndexTable::ITEM_TYPE_LOG,
					'itemId' => $record['ID'],
					'fields' => $record
				));

				$params["lastId"] = $record['ID'];
				$params["number"]++;
				$found = true;
			}

			if ($found)
			{
				Option::set("wiki", "livefeedindexlog", serialize($params));
				$return = true;
			}

			$result["progress"] = intval($params["number"] * 100/ $params["count"]);
			$result["steps"] = $params["number"];

			if ($found === false)
			{
				Option::delete("wiki", array("name" => "livefeedindexlog"));
				Option::set('wiki', 'needLivefeedIndexLog', 'N');
			}
		}
		return $return;
	}
}
?>