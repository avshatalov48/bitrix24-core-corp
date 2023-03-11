<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Imopenlines\Widget;

use Bitrix\ImOpenLines\BasicError;
use Bitrix\Main\Localization\Loc;

class Config
{
	static private $error = null;

	/**
	 * @param $code
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getByCode($code)
	{
		self::clearError();

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			self::setError(__METHOD__, 'IM_NOT_FOUND', Loc::getMessage('IMOL_WIDGET_CONFIG_IM_NOT_FOUND'));
			return false;
		}
		if (!\Bitrix\Main\Loader::includeModule('imconnector'))
		{
			self::setError(__METHOD__, 'IMCONNECTOR_NOT_FOUND', Loc::getMessage('IMOL_WIDGET_CONFIG_IMCONNECTOR_NOT_FOUND'));
			return false;
		}

		$result = \Bitrix\ImOpenLines\Model\LivechatTable::getList([
			'select' => ['CONFIG_ID', 'TEXT_PHRASES', 'SHOW_SESSION_ID'],
			'filter' => ['=URL_CODE' => $code]
		])->fetch();
		if (!$result)
		{
			self::setError(__METHOD__, 'CONFIG_ERROR', Loc::getMessage('IMOL_WIDGET_CONFIG_CONFIG_ERROR'));
			return false;
		}

		$configManager = new \Bitrix\ImOpenLines\Config();
		$config = $configManager->get($result['CONFIG_ID']);

		$queue = [];
		$operatorDataType = \Bitrix\ImOpenLines\Config::operatorDataConfig($result['CONFIG_ID']);

		foreach ($config['QUEUE'] as $userId)
		{
			$userArray = \Bitrix\ImOpenLines\Queue::getUserData($result['CONFIG_ID'], $userId);
			if (!$userArray)
			{
				continue;
			}

			$queue[] = $userArray;

			if ($operatorDataType == \Bitrix\ImOpenLines\Config::OPERATOR_DATA_HIDE)
			{
				break;
			}
		}

		$connectors = [];
		$activeConnectors = \Bitrix\ImConnector\Connector::infoConnectorsLine($result['CONFIG_ID']);

		$classMap = \Bitrix\ImConnector\Connector::getIconClassMap();
		foreach ($activeConnectors as $code => $params)
		{
			if (\Bitrix\ImOpenLines\Connector::isLiveChat($code) || empty($params['url']))
				continue;

			$connectors[] = [
				'TITLE' => $params['name']? $params['name']:'',
				'CODE' => $code,
				'ICON' => $classMap[$code],
				'LINK' => $params['url_im']? $params['url_im']: $params['url'],
			];
		}

		$maxFileSize = \CUtil::Unformat(ini_get("post_max_size"));
		if ($maxFileSize > 5242880)
		{
			$maxFileSize = 5242880;
		}

		$result['TEXT_PHRASES'] = !empty($result['TEXT_PHRASES']) && is_array($result['TEXT_PHRASES']) ? $result['TEXT_PHRASES'] : array();
		foreach ($result['TEXT_PHRASES'] as &$phrase)
		{
			$phrase = (string)$phrase;
		}

		return [
			'CONFIG_ID' => (int)$config['ID'],
			'CONFIG_NAME' => $config['LINE_NAME'],
			'VOTE_ENABLE' => $config['VOTE_MESSAGE'] === 'Y', //TODO - remove in next version
			'CONSENT_URL' => $config['AGREEMENT_ID'] && $config['AGREEMENT_MESSAGE'] == 'Y'? \Bitrix\ImOpenLines\Common::getAgreementLink($config['AGREEMENT_ID'], $config['LANGUAGE_ID'], true): '',
			'OPERATORS' => $queue,
			'ONLINE' => $config['QUEUE_ONLINE'] === 'Y',
			'CONNECTORS' => $connectors,
			'DISK' => [
				'ENABLED' => \Bitrix\Main\ModuleManager::isModuleInstalled('disk'),
				'MAX_FILE_SIZE' => $maxFileSize
			],
			'VOTE' => [
				'ENABLE' => $config['VOTE_MESSAGE'] === 'Y',
				'BEFORE_FINISH' => $config['VOTE_BEFORE_FINISH'] === 'Y',
				'MESSAGE_TEXT' => (string)$config['VOTE_MESSAGE_1_TEXT'],
				'MESSAGE_LIKE' => (string)$config['VOTE_MESSAGE_1_LIKE'],
				'MESSAGE_DISLIKE' => (string)$config['VOTE_MESSAGE_1_DISLIKE'],
			],
			'TEXT_MESSAGES' => $result['TEXT_PHRASES'],
			'WATCH_TYPING' => $config['WATCH_TYPING'] === 'Y',
			'SHOW_SESSION_ID' => $result['SHOW_SESSION_ID'] === 'Y',
			'CRM_FORMS_SETTINGS' => [
				'USE_WELCOME_FORM' => $config['USE_WELCOME_FORM'],
				'WELCOME_FORM_ID' => $config['WELCOME_FORM_ID'],
				'WELCOME_FORM_DELAY' => $config['WELCOME_FORM_DELAY']
			]
		];
	}


	/**
	 * @return BasicError
	 */
	public static function getError()
	{
		if (is_null(static::$error))
		{
			self::clearError();
		}

		return static::$error;
	}

	/**
	 * @param $method
	 * @param $code
	 * @param $msg
	 * @param array $params
	 * @return bool
	 */
	private static function setError($method, $code, $msg, $params = Array())
	{
		static::$error = new BasicError($method, $code, $msg, $params);
		return true;
	}

	private static function clearError()
	{
		static::$error = new BasicError(null, '', '');
		return true;
	}
}
