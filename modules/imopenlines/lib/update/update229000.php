<?php
namespace Bitrix\Imopenlines\Update;

use \Bitrix\ImOpenLines\Tools\Correction;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__DIR__. '/update186900/correction4.php');

final class Update229000 extends Stepper
{
	const OPTION_NAME = 'imopenlines_chat_session_id';
	protected static $moduleId = 'imopenlines';

	/**
	 * @inheritdoc
	 *
	 * @param array $option
	 * @return bool
	 */
	public function execute(array &$option): bool
	{
		$return = false;

		if (Loader::includeModule(self::$moduleId))
		{
			$params = Option::get(self::$moduleId, self::OPTION_NAME, '');
			$params = ($params !== '' ? @unserialize($params, ['allowed_classes' => false]) : []);
			$params = (is_array($params) ? $params : []);
			if (empty($params))
			{
				$params = [
					"lastId" => 0,
					"number" => 0,
					"count" => Correction::getCountChatSessionId(),
				];
			}

			if ($params["count"] > 0)
			{
				$option["title"] = Loc::getMessage("IMOL_UPDATE_REPAIR_STATUS_CLOSED_SESSIONS");
				$option["progress"] = 1;
				$option["steps"] = "";
				$option["count"] = $params["count"];

				$resultCorrectionSession = Correction::restoreChatSessionId(true, 100);

				$found = false;
				if(!empty($resultCorrectionSession))
				{
					$params["number"]++;
					$params["lastId"] = $params["number"] * 100;
					$found = true;
				}

				if ($found)
				{
					Option::set(self::$moduleId, self::OPTION_NAME, serialize($params));
					$return = true;
				}

				$option["progress"] = intval($params["number"] * 100/ $params["count"]);
				$option["steps"] = $params["number"];

				if ($found === false)
				{
					Option::delete(self::$moduleId, ["name" => self::OPTION_NAME]);
				}
			}
		}

		return $return;
	}
}