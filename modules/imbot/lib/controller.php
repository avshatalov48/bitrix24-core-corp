<?php
namespace Bitrix\ImBot;

/**
 * Class Controller
 *
 * @package Bitrix\ImBot
 */
class Controller
{
	/**
	 * @param string $botName
	 * @param string $command
	 * @param array $params
	 *
	 * @return mixed|null
	 */
	public static function sendToBot($botName, $command, $params)
	{
		$botName = trim(preg_replace("/[^a-z]/", "", mb_strtolower($botName)));
		if ($botName)
		{
			$params = self::prepareParams($params);

			if (!empty($params['BOT_ID']))
			{
				$bot = \Bitrix\Im\Bot::getCache($params['BOT_ID']);
				if ($bot && class_exists($bot['CLASS']) && method_exists($bot['CLASS'], 'onAnswerAdd'))
				{
					return call_user_func_array(array($bot['CLASS'], 'onAnswerAdd'), array($command, $params));
				}
			}

			$className = '\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName);
			if (
				class_exists($className, true) &&
				method_exists($className, 'onAnswerAdd')
			)
			{
				return call_user_func_array([$className, 'onAnswerAdd'], [$command, $params]);
			}
		}

		return null;
	}

	/**
	 * @param string $serviceName
	 * @param string $command
	 * @param array $params
	 *
	 * @return mixed|null
	 */
	public static function sendToService($serviceName, $command, $params)
	{
		$serviceName = trim(preg_replace("/[^a-z]/", "", mb_strtolower($serviceName)));
		if ($serviceName)
		{
			$className = '\\Bitrix\\ImBot\\Service\\'.ucfirst($serviceName);
			if (
				class_exists($className, true) &&
				method_exists($className, 'onReceiveCommand')
			)
			{
				$params = self::prepareParams($params);

				return call_user_func_array([$className, 'onReceiveCommand'], [$command, $params]);
			}
		}

		return null;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	private static function prepareParams($params)
	{
		foreach ($params as $key => $value)
		{
			if ($value === '#ZERO#')
			{
				$value = '0';
			}
			else if ($value === '#EMPTY#')
			{
				$value = '';
			}

			$params[$key] = $value;
		}

		return $params;
	}
}