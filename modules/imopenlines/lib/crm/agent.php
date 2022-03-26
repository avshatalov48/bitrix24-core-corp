<?php
namespace Bitrix\ImOpenLines\Crm;

use Bitrix\Main\Config\Option;

/**
 * Class Agent
 *
 * @package Bitrix\ImOpenLines\Crm
 */
class Agent
{
	/**
	 * @param string $oldUserCode
	 * @param string $newUserCode
	 */
	public static function addUniqueReplacementUserCodeAgent(string $oldUserCode, string $newUserCode): void
	{
		$moduleId = 'imopenlines';

		$agentName = '\Bitrix\ImOpenLines\Crm\ReplacementUserCode::bind(0, [\'' . $oldUserCode . '\',\'' . $newUserCode . '\']);';

		$isAgent = \CAgent::getList([], [
			'MODULE_ID' => $moduleId,
			'NAME' => $agentName
		])->fetch();

		if(!$isAgent)
		{
			$optionName = ReplacementUserCode::OPTION_NAME . md5($oldUserCode . $newUserCode);
			$params = Option::get(ReplacementUserCode::$moduleId, $optionName, '');

			if (empty($params))
			{
				\CAgent::AddAgent($agentName, $moduleId, 'N', 60);
			}
		}
	}
}