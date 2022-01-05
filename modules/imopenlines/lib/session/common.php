<?php
namespace Bitrix\ImOpenLines\Session;

use Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenLines\Model\SessionTable;

Loc::loadMessages(__FILE__);

class Common
{
	public const URL_IM_HISTORY = '/online/?IM_HISTORY=imol|#ID#';

	/**
	 * Parses custom code to the components of the data.
	 *
	 * @param string $userCode
	 * @return array
	 */
	public static function parseUserCode(string $userCode): array
	{
		//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId
		[$connectorId, $configId, $externalChatId, $connectorUserId] = explode('|', $userCode);

		return [
			'CONNECTOR_ID' => $connectorId,
			'CONFIG_ID' => $configId,
			'EXTERNAL_CHAT_ID' => $externalChatId,
			'CONNECTOR_USER_ID' => $connectorUserId,
		];
	}

	/**
	 *Create custom code from an array of data.
	 *
	 * @param array $userCode
	 * @return string
	 */
	public static function combineUserCode(array $userCode): string
	{
		return $userCode['CONNECTOR_ID'] . '|' . $userCode['CONFIG_ID'] . '|' . $userCode['EXTERNAL_CHAT_ID'] . '|' . $userCode['CONNECTOR_USER_ID'];
	}

	/**
	 * @return array
	 */
	public static function getAgreementFields(): array
	{
		return [
			Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_NAME'),
			Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_EMAIL'),
			Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_PHONE'),
			Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_PHOTO'),
		];
	}

	/**
	 * Try to configId of the active session on $chatId.
	 *
	 * @param int $chatId
	 * @return int|bool
	 */
	public static function getConfigIdByChatId($chatId)
	{
		$result = false;

		$session = SessionTable::getList(array(
			'select' => Array(
				'CONFIG_ID',
			),
			'filter' => array(
				'=CHAT_ID' => $chatId,
				'=CLOSED' => 'N'
			),
			'order' => array(
				'ID' => 'DESC',
			)
		))->fetch();

		if($session && $session['CONFIG_ID'] > 0)
		{
			$result = $session['CONFIG_ID'];
		}

		return $result;
	}

	/**
	 * @param $sessionId
	 * @return mixed
	 */
	public static function getUrlImHistory($sessionId)
	{
		return str_replace('#ID#', $sessionId, self::URL_IM_HISTORY);
	}

	/**
	 * @param $sessionId
	 * @param $textUrl
	 * @return string
	 */
	public static function getUrlImHistoryBbCode($sessionId, $textUrl)
	{
		return '[URL=' . self::getUrlImHistory($sessionId) . ']' . $textUrl . '[/URL]';
	}
}