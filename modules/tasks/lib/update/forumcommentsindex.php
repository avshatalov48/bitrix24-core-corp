<?
namespace Bitrix\Tasks\Update;
use \Bitrix\Main\Update\Stepper;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

final class ForumCommentsIndex extends Stepper {
	protected static $moduleId = "tasks";
	protected $deleteFile = true;

	public function execute(array &$result)
	{
		if (!(\Bitrix\Main\Loader::includeModule("tasks") &&
			\CTasksTools::getForumIdForIntranet() > 0 &&
			\Bitrix\Main\Loader::includeModule("forum") &&
			\Bitrix\Main\Loader::includeModule("search")
		))
			return false;

		$params = \Bitrix\Main\Config\Option::get("tasks", "update1758", "");
		$params = ($params !== "" ? @unserialize($params) : array());
		$params = (is_array($params) ? $params : array());
		if (empty($params))
		{
			$params = array(
				"lastMessageId" => 0,
				"number" => 0,
				"count" => \Bitrix\Forum\MessageTable::getCount(
					array(
						"FORUM_ID" => \CTasksTools::getForumIdForIntranet(),
						"APPROVED" => "Y",
						"=%XML_ID" => "TASK_%"
					)
				)
			);
		}
		$return = false;

		if ($params["count"] > 0)
		{
			$result["count"] = $params["count"];
			$result["progress"] = 1;
			$result["steps"] = "";

			$NS = array(
				"CLEAR" => "N",
				"MODULE" => "forum",
				"ID" => $params["lastMessageId"] + 1,
				"CNT" => 0,
				"SESS_ID" => md5(uniqid("")),
				"TO_MODULE_ID" => "forum",
				"FILTER" => array(
					"F.ID=".\CTasksTools::getForumIdForIntranet(),
					"FM.XML_ID LIKE 'TASK_%'"
				)
			);

			$oCallBack = new \CSearchCallback;
			$oCallBack->MODULE = $NS["TO_MODULE_ID"];
			$oCallBack->CNT = 0;
			$oCallBack->SESS_ID = $NS["SESS_ID"];

			$cnt = intval(\Bitrix\Main\Config\Option::get("forum", "search_message_count", 0));
			\Bitrix\Main\Config\Option::set("forum", "search_message_count", 15);

			$params["lastMessageId"] = \CForumNew::reindex($NS, $oCallBack, "Index");
			$params["number"] += $oCallBack->CNT;

			if (isset($NS["SKIPPED"]) && is_array($NS["SKIPPED"]))
			{
				while($id = array_pop($NS["SKIPPED"]))
				{
					\CSearch::DeleteIndex("forum", $id);
					$params["number"]++;
				}
			}

			if ($params["lastMessageId"] > 0)
			{
				$params = array(
					"lastMessageId" => $params["lastMessageId"],
					"number" => $params["number"],
					"count" => $params["count"]
				);

				\Bitrix\Main\Config\Option::set("tasks", "update1758", serialize($params));
				\CSearch::DeleteOld($NS["SESS_ID"], $NS["TO_MODULE_ID"]);
				$return = true;
			}

			if ($cnt > 0)
				\Bitrix\Main\Config\Option::set("forum", "search_message_count", $cnt);
			else
				\Bitrix\Main\Config\Option::delete("forum", array("name" => "search_message_count"));

			$result["steps"] = $params["number"];
			if ($return === false)
				\Bitrix\Main\Config\Option::delete("tasks", array("name" => "update1758"));
		}
		return $return;
	}
}
?>