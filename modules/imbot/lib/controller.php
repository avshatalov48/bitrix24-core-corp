<?php
namespace Bitrix\ImBot;

class Controller
{
	public static function sendToBot($botName, $command, $params)
	{
		$result = null;

		$botName = trim(preg_replace("/[^a-z]/", "", mb_strtolower($botName)));
		if (!$botName)
			return $result;

		foreach ($params as $key => $value)
		{
			if ($value == '#ZERO#')
				$value = '0';
			else if ($value == '#EMPTY#')
				$value = '';

			$params[$key] = $value;
		}

		if ($params['BOT_ID'])
		{
			$bot = \Bitrix\Im\Bot::getCache($params['BOT_ID']);
			if ($bot && class_exists($bot['CLASS']) && method_exists($bot['CLASS'], 'onAnswerAdd'))
			{
				return call_user_func_array(array($bot['CLASS'], 'onAnswerAdd'), Array($command, $params));
			}
		}

		if (
			class_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName))
			&& method_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName), 'onAnswerAdd')
		)
		{
			return call_user_func_array(array('\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName), 'onAnswerAdd'), Array($command, $params));
		}
		return $result;
	}

	public static function sendToService($serviceName, $command, $params)
	{
		$result = null;

		$serviceName = trim(preg_replace("/[^a-z]/", "", mb_strtolower($serviceName)));
		if (!$serviceName)
			return $result;

		foreach ($params as $key => $value)
		{
			if ($value == '#ZERO#')
				$value = '0';
			else if ($value == '#EMPTY#')
				$value = '';

			$params[$key] = $value;
		}

		if (class_exists('\\Bitrix\\ImBot\\Service\\'.ucfirst($serviceName)) && method_exists('\\Bitrix\\ImBot\\Service\\'.ucfirst($serviceName), 'onReceiveCommand'))
		{
			return call_user_func_array(array('\\Bitrix\\ImBot\\Service\\'.ucfirst($serviceName), 'onReceiveCommand'), Array($command, $params));
		}
		return $result;
	}
}