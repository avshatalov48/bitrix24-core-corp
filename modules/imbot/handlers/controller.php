<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!(\Bitrix\Main\Loader::includeModule('im') && \Bitrix\Main\Loader::includeModule('imbot')))
{
	return false;
}

use \Bitrix\Main\Web\Json;


/**
 * @global \CMain $APPLICATION
 */
if ($APPLICATION instanceof \CMain)
{
	$APPLICATION->RestartBuffer();
}

if ($_SERVER['REQUEST_METHOD'] === 'HEAD')
{
	echo '{"RESULT":"OK"}';
	return true;
}
elseif ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
	\Bitrix\ImBot\Log::write($_SERVER['REQUEST_METHOD'], "Request type is invalid");

	echo Json::encode([
		'ERROR' => [[
			'code' => 'PERMISSION_DENIED',
			'message' => "You don't have access to this page.",
		]]
	]);
	return false;
}

\Bitrix\ImBot\Log::write($_POST, 'PORTAL HIT');

$params = $_POST;
$hash = $params["BX_HASH"];
unset($params["BX_HASH"]);

// BOT CLOUD HITS

if (isset($params['BX_IFRAME']) && $params['BX_IFRAME'] == 'Y')
{
	if ($params['BX_IFRAME_ACTION'] == 'REGISTER')
	{
		$checkOut = parse_url($params['DOMAIN']);
		if ($checkOut['host'] != $_SERVER['SERVER_NAME'])
		{
			echo "Code: 404";
			die();
		}

		$apps = \Bitrix\Im\App::getListCache();
		if (!$apps[$params['APP_ID']] || $apps[$params['APP_ID']]['BOT_ID'] != $params['BOT_ID'])
		{
			echo "Code: 500";
			die();
		}
		if ($apps[$params['APP_ID']]['REGISTERED'] == 'Y')
		{
			echo "Code: 302";
			die();
		}
		$hash = $apps[$params['APP_ID']]['HASH'];

		if (\Bitrix\Im\App::getUserHash($params['USER_ID'], $hash) != $params['USER_HASH'])
		{
			echo "Code: 403";
			die();
		}

		$apps = \Bitrix\Im\App::update(
			Array('ID' => $params['APP_ID'], 'USER_ID' => $params['USER_ID']),
			Array('REGISTERED' => 'Y')
		);

		echo \Bitrix\Main\Web\Json::encode(Array(
			'DOMAIN_HASH' => $hash
		));
	}
	else if ($params['BX_IFRAME_ACTION'] == 'UNREGISTER')
	{
		$checkOut = parse_url($params['DOMAIN']);
		if ($checkOut['host'] != $_SERVER['SERVER_NAME'])
		{
			echo "Code: 404";
			die();
		}

		$isExists = false;

		$apps = \Bitrix\Im\App::getListCache();
		foreach ($apps as $app)
		{
			if ($app['HASH'] != $params['DOMAIN_HASH'])
			{
				continue;
			}

			$isExists = true;

			break;
		}

		if ($isExists)
		{
			echo "Code: 404";
			die();
		}

		echo \Bitrix\Main\Web\Json::encode(Array(
			'RESULT' => 'SUCCESS'
		));
	}
}
elseif
(
	(
		isset($params['BX_TYPE'])
		&& !empty($hash)
		&& $params['BX_TYPE'] === \Bitrix\ImBot\Http::TYPE_BITRIX24
		&& defined('BX24_HOST_NAME')
		&& \Bitrix\ImBot\Http::requestSign(\Bitrix\ImBot\Http::TYPE_BITRIX24, array_merge($params, [\BX24_HOST_NAME])) === $hash
	)
	||
	(
		isset($params['BX_TYPE'])
		&& !empty($hash)
		&& $params['BX_TYPE'] === \Bitrix\ImBot\Http::TYPE_CP
		&& \Bitrix\ImBot\Http::requestSign(\Bitrix\ImBot\Http::TYPE_CP, $params) === $hash
	)
)
{
	$params = \Bitrix\Main\Text\Encoding::convertEncoding($params, 'UTF-8', SITE_CHARSET);

	if (isset($params['BX_SERVICE_NAME']) && !empty($params['BX_SERVICE_NAME']))
	{
		$result = \Bitrix\ImBot\Controller::sendToService($params['BX_SERVICE_NAME'], $params['BX_COMMAND'], $params);
	}
	else
	{
		$result = \Bitrix\ImBot\Controller::sendToBot($params['BX_BOT_NAME'], $params['BX_COMMAND'], $params);
	}

	if (is_null($result))
	{
		\Bitrix\ImBot\Log::write("-empty-", "EMPTY RESULT");

		echo Json::encode([
			'ERROR' => [[
				'code' => 'PERMISSION_DENIED',
				'message' => "You don't have access to this page.",
			]]
		]);
	}
	elseif ($result instanceof \Bitrix\Main\Result)
	{
		if ($result->isSuccess())
		{
			\Bitrix\ImBot\Log::write($result->getData(), 'RESULT');

			echo Json::encode($result->getData());
		}
		else
		{
			$errors = $result->getErrors();
			$log = [];
			foreach ($errors as $error)
			{
				$log[] = [
					'code' => $error->getCode(),
					'message' => $error->getMessage(),
					'data' => $error->getCustomData(),
				];
			}
			\Bitrix\ImBot\Log::write($log, 'ERROR RESULT');

			// last error
			$lastError = array_pop($errors);

			echo Json::encode([
				'ERROR' => [[
					'code' => $lastError->getCode(),
					'message' => $lastError->getMessage(),
				]]
			]);
		}
	}
	else
	{
		if (
			$result instanceof \Bitrix\ImBot\Error
			|| $result instanceof \Bitrix\Main\Error
		)
		{
			\Bitrix\ImBot\Log::write($result, 'ERROR RESULT');
		}
		echo Json::encode($result);
	}
}
else
{
	\Bitrix\ImBot\Log::write($hash, "Request sign is invalid");

	echo Json::encode([
		'ERROR' => [[
			'code' => 'PERMISSION_DENIED',
			'message' => "You don't have access to this page.",
		]]
	]);
}
