<?php
namespace Bitrix\ImConnector;

class User
{
	/**
	 * @param int $replaceId
	 * @param array $searchIds
	 */
	public static function addUniqueReplacementAgent(int $replaceId, array $searchIds): void
	{
		$moduleId = 'imopenlines';

		foreach ($searchIds as $id)
		{
			$agentName = '\Bitrix\ImOpenLines\Session\Agent::replacementUserAgent(' . $replaceId . ', ' . $id . ');';

			$isAgent = \CAgent::getList([], [
				'MODULE_ID' => $moduleId,
				'NAME' => $agentName
			])->fetch();

			if(!$isAgent)
			{
				\CAgent::AddAgent($agentName, $moduleId, 'N', 60);
			}
		}
	}
}