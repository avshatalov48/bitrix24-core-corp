<?php
namespace Bitrix\Imopenlines\Update\Update186900;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines\Tools\Correction;

Loc::loadMessages(__FILE__);

final class Correction3 extends Stepper
{
	const OPTION_NAME = "imopenlines_186900_correction_3";
	protected static $moduleId = "imopenlines";

	/**
	 * @inheritdoc
	 *
	 * @param array $result
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function execute(array &$result)
	{
		$return = false;

		if (Loader::includeModule(self::$moduleId))
		{
			$params = Option::get(self::$moduleId, self::OPTION_NAME, "");
			$params = ($params !== "" ? @unserialize($params, ['allowed_classes' => false]) : []);
			$params = (is_array($params) ? $params : []);
			if (empty($params))
			{
				$params = [
					"lastId" => 0,
					"number" => 0,
					"count" => Correction::getCountSessionsNoDateClose(),
				];
			}

			if ($params["count"] > 0)
			{
				$result["title"] = Loc::getMessage("IMOL_UPDATE_REPAIR_SESSIONS_NO_DATE_CLOSE");
				$result["progress"] = 1;
				$result["steps"] = "";
				$result["count"] = $params["count"];

				$resultCorrectionSession = Correction::closeOldSession(true, 30, 100);

				$found = false;
				if(!empty($resultCorrectionSession['CLOSE']) || !empty($resultCorrectionSession['UPDATE']))
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

				$result["progress"] = intval($params["number"] * 100/ $params["count"]);
				$result["steps"] = $params["number"];

				if ($found === false)
				{
					Option::delete(self::$moduleId, ["name" => self::OPTION_NAME]);
				}
			}
		}

		return $return;
	}
}