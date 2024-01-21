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
			$agentName = 'Bitrix\ImOpenLines\Session\Agent::replacementUserAgent(' . $replaceId . ', ' . $id . ');';

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

	public static function validateUserCode(string $userCode)
	{
		if (!\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			return false;
		}

		$result = \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($userCode);


		$user = \Bitrix\Main\UserTable::getByPrimary($result['connectorUserId'], [
			'select' => [
				'ID',
				'EXTERNAL_AUTH_ID',
				'XML_ID'
			]
		])->fetch();
		if (!$user)
		{
			return false;
		}

		if ($user['EXTERNAL_AUTH_ID'] !== Library::NAME_EXTERNAL_USER)
		{
			return false;
		}

		if ($user['XML_ID'] !== $result['connectorId'].'|'.$result['connectorChatId'])
		{
			return false;
		}

		return true;
	}
}