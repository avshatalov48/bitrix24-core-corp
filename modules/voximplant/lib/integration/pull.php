<?php

namespace Bitrix\Voximplant\Integration;

use Bitrix\Main\Diag\Helper;
use Bitrix\Main\Loader;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\ConfigTable;
use Bitrix\Voximplant\Model\ExternalLineTable;

/**
 * Class for integration with Push & Pull
 * @package Bitrix\Voximplant\Integration
 * @internal
 */
class Pull
{
	const BALANCE_PUSH_TAG = "vi_balance_change";

	public static function sendBalanceUpdate($newBalance, $currency)
	{
		if(!Loader::includeModule("pull"))
		{
			return;
		}

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$balanceFormatted = \CCurrencyLang::CurrencyFormat($newBalance, $currency, false);
		}
		else
		{
			$balanceFormatted = number_format($newBalance, 2);
		}

		\CPullWatch::AddToStack(
			static::BALANCE_PUSH_TAG,
			[
				"module_id" => "voximplant",
				"command" => "balanceUpdate",
				"params" => [
					"balance" => $newBalance,
					"currency" => $currency,
					"balanceFormatted" => $balanceFormatted
				]
			]
		);
	}

	public static function sendDefaultLineId($users, $defaultLineId)
	{
		self::send(
			'changeDefaultLineId',
			$users,
			[
				'defaultLineId' => $defaultLineId,
				'line' => \CVoxImplantConfig::GetLine($defaultLineId)
			],
			null,
			86400
		);
	}

	protected static function send($command, $users, $params, $push, $ttl = 0)
	{
		if(!Loader::includeModule('pull'))
			return false;

		\Bitrix\Pull\Event::add($users, Array(
			'module_id' => 'voximplant',
			'command' => $command,
			'params' => $params,
			'push' => $push,
			'expiry' => $ttl
		));

		return true;
	}
}