<?
namespace Bitrix\Imopenlines\Update\Update1870;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Model\QueueTable,
	\Bitrix\ImOpenLines\Model\ConfigTable;

Loc::loadMessages(__FILE__);

final class UpdateConfig extends Stepper
{
	const OPTION_NAME = "imopenlines_new_fields_no_answer_update_config";
	protected static $moduleId = "imopenlines";

	/**
	 * @param $value
	 * @return int|string|null
	 */
	protected static function getNearestValue($value)
	{
		$availableValues = [60, 180, 300, 600, 900, 1800, 3600, 7200, 10800, 21600, 28800, 43200];
		$set = [];

		foreach($availableValues as $availableValue){
			$set[$availableValue] = abs($availableValue - $value);
		}
		asort($set);

		return key($set);
	}

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
			$params = (is_array($params) ? $params : array());
			if (empty($params))
			{
				$params = [
					"lastId" => 0,
					"number" => 0,
					"count" => ConfigTable::getCount(),
				];
			}

			if ($params["count"] > 0)
			{
				$result["title"] = Loc::getMessage("IMOL_UPDATE_CONFIG_NO_ANSWER");
				$result["progress"] = 1;
				$result["steps"] = "";
				$result["count"] = $params["count"];

				$cursor = ConfigTable::getList([
					'order' => ['ID' => 'ASC'],
					'filter' => [
						'>ID' => $params["lastId"]
					],
					'select' => [
						'ID',
						'QUEUE_TIME',
						'QUEUE_TYPE',
					],
					'offset' => 0,
					'limit' => 100
				]);

				$found = false;
				while ($row = $cursor->fetch())
				{
					$noAnswerTime = 60;

					if($row['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
					{
						$noAnswerTime = $row['QUEUE_TIME'];
					}
					else
					{
						$countOperators = QueueTable::getCount(['=CONFIG_ID' => $row['ID']]);

						$newTime = $countOperators * $row['QUEUE_TIME'];

						$newTime = self::getNearestValue($newTime);

						if(!empty($newTime))
						{
							$noAnswerTime = $newTime;
						}
					}

					ConfigTable::update($row['ID'], ['NO_ANSWER_TIME' => $noAnswerTime]);

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
		}

		if($return === false)
		{
			UpdateSession::bind();
		}

		return $return;
	}
}
?>