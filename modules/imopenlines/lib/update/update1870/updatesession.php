<?
namespace Bitrix\Imopenlines\Update\Update1870;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines\Model\SessionCheckTable;

Loc::loadMessages(__FILE__);

final class UpdateSession extends Stepper
{
	const OPTION_NAME = "imopenlines_new_fields_no_answer_update_session";
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

		if (Loader::includeModule(self::$moduleId) && Loader::includeModule('im'))
		{
			$params = Option::get(self::$moduleId, self::OPTION_NAME, "");
			$params = ($params !== "" ? @unserialize($params, ['allowed_classes' => false]) : []);
			$params = (is_array($params) ? $params : array());
			if (empty($params))
			{
				$params = [
					"lastId" => 0,
					"number" => 0,
					"count" => SessionCheckTable::getCount([
						'=DATE_NO_ANSWER' => null,
						'!=SESSION.CLOSED' => 'Y',
						'=SESSION.CHAT.AUTHOR_ID' => '0',
						'=SESSION.SEND_NO_ANSWER_TEXT' => 'N',
					]),
				];
			}

			if ($params["count"] > 0)
			{
				$result["title"] = Loc::getMessage("IMOL_SESSION_CONFIG_NO_ANSWER");
				$result["progress"] = 1;
				$result["steps"] = "";
				$result["count"] = $params["count"];

				$cursor = SessionCheckTable::getList([
					'order' => ['ID' => 'ASC'],
					'filter' => [
						'>ID' => $params["lastId"],

						'=DATE_NO_ANSWER' => null,
						'!=SESSION.CLOSED' => 'Y',
						'=SESSION.CHAT.AUTHOR_ID' => '0',
						'=SESSION.SEND_NO_ANSWER_TEXT' => 'N',
					],
					'select' => [
						'ID' => 'SESSION_ID',
						'NO_ANSWER_TIME' => 'SESSION.CONFIG.NO_ANSWER_TIME',
						'DATE_CREATE' => 'SESSION.DATE_CREATE',
					],
					'offset' => 0,
					'limit' => 100
				]);

				$found = false;
				while ($row = $cursor->fetch())
				{
					if(!empty($row['NO_ANSWER_TIME']))
					{
						if(!empty($row['DATE_CREATE']) && $row['DATE_CREATE'] instanceof DateTime)
						{
							$noAnswerTime = $row['DATE_CREATE'];
						}
						else
						{
							$noAnswerTime = new DateTime();
						}

						$noAnswerTime->add($row['NO_ANSWER_TIME'] . ' SECONDS');

						SessionCheckTable::update($row['ID'], ['DATE_NO_ANSWER' => $noAnswerTime]);
					}

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

		return $return;
	}
}
?>