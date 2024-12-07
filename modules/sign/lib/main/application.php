<?php
namespace Bitrix\Sign\Main;

use Bitrix\Main\Server;
use Bitrix\Main\Type\ParameterDictionary;

/**
 * @deprecated
 */
class Application
{
	/**
	 * Returns main module's APPLICATION instance.
	 * @return \CMain
	 */
	public static function getInstance(): \CMain
	{
		return $GLOBALS['APPLICATION'];
	}

	/**
	 * Returns Application's server instance.
	 * @return Server
	 */
	public static function getServer(): Server
	{
		return \Bitrix\Main\Context::getCurrent()->getServer();
	}

	/**
	 * Returns true if current request within https.
	 * @return bool
	 */
	public static function isHttps(): bool
	{
		return \Bitrix\Main\Context::getCurrent()->getRequest()->isHttps();
	}

	/**
	 * Returns current request's file list.
	 * @return ParameterDictionary
	 */
	public static function getFileList(): ParameterDictionary
	{
		return \Bitrix\Main\Context::getCurrent()->getRequest()->getFileList();
	}
}
