<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Imopenlines;
use Bitrix\Im;

Loc::loadMessages(__FILE__);

class OpenLineManager
{
	/** @var bool|null  */
	private static $isEnabled = null;
	private static $supportedTypes = array(
		'IM' => array(
			'IMOL' => true,
			'OPENLINE' => true,
			'BITRIX24' => true,
			'FACEBOOK' => true,
			'TELEGRAM' => true,
			'VK' => true,
			'VIBER' => true,
			'INSTAGRAM' => true
		)
	);

	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		if(self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('imopenlines')
				&& Loader::includeModule('imopenlines');
		}
		return self::$isEnabled;
	}

	public static function prepareMultiFieldLinkAttributes($typeName, $valueTypeID, $value)
	{
		if(!(isset(self::$supportedTypes[$typeName]) && isset(self::$supportedTypes[$typeName][$valueTypeID])))
		{
			return null;
		}

		$items = explode('|', $value);
		if(!(is_array($items) && count($items) > 2 && $items[0] === 'imol'))
		{
			return null;
		}

		$typeID = $items[1];
		$suffix = strtoupper(preg_replace('/[^a-z0-9]/i', '', $typeID));
		$text = Loc::getMessage("CRM_OPEN_LINE_{$suffix}");
		if($text === null)
		{
			$text = Loc::getMessage('CRM_OPEN_LINE_SEND_MESSAGE');
		}

		return array(
			'HREF' => '#',
			'ONCLICK' => "if(typeof(top.BXIM)!=='undefined') top.BXIM.openMessengerSlider('{$value}', {RECENT: 'N', MENU: 'N'}); return BX.PreventDefault(event);",
			'TEXT' => $text,
			'TITLE' => $text
		);
	}

	public static function getSessionMessages($sessionID, $limit = 20)
	{
		if(
			!Loader::includeModule('im')
			|| !Loader::includeModule('imopenlines')
		)
		{
			return array();
		}

		$sessionID = intval($sessionID);

		if($limit <= 0)
		{
			$limit = 20;
		}

		$query = "
			SELECT MESSAGE, AUTHOR_ID
			FROM
			   b_imopenlines_session S
			   INNER JOIN b_im_message M ON 
				  M.CHAT_ID = S.CHAT_ID 
				  AND M.ID+0 >= S.START_ID 
				  AND ( M.ID+0 <= S.END_ID OR S.END_ID = 0 ) 
				  AND M.AUTHOR_ID > 0
			WHERE S.ID = ".$sessionID."
			ORDER BY M.ID+0 ASC
		";

		$connection = \Bitrix\Main\Application::getConnection();
		$dbResult = $connection->query(
			$connection->getSqlHelper()->getTopSql($query, $limit)
		);

		$results = array();
		while ($messageFields = $dbResult->fetch())
		{
			$messageFields['MESSAGE'] = Im\Text::removeBbCodes($messageFields['MESSAGE']);
			$messageFields['IS_EXTERNAL'] = Im\User::getInstance($messageFields['AUTHOR_ID'])->isConnector();

			$results[] = $messageFields;
		}

		return $results;
	}
}