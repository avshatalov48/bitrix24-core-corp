<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Json;

class EventHandler
{
	private const EVENT_NAME = 'onCrmTimelineItemAction';
	private const SIGNED_PARAMS_SALT = 'ConfigurableRestAppEvent';

	public static function signParams(array $params): string
	{
		return (new Signer())->sign(Json::encode($params), self::SIGNED_PARAMS_SALT);
	}

	public static function emitEvent(string $signedParams): Result
	{
		$result = new Result();
		try
		{
			$eventParams = (new Signer())->unsign($signedParams, 'ConfigurableRestAppEvent');
			$eventParams = Json::decode($eventParams);
		}
		catch (BadSignatureException $e)
		{
			$result->addError(new Error($e->getMessage(), 'BAD_SIGNATURE'));

			return $result;
		}

		$appId = $eventParams['APP_ID'] ?? null;
		unset($eventParams['APP_ID']);
		$events = GetModuleEvents('crm', self::EVENT_NAME);
		while($event = $events->Fetch())
		{
			ExecuteModuleEventEx($event, [$eventParams,  ['REST_EVENT_HOLD_EXCEPT_APP' => $appId]]);
		}

		return $result;
	}

	public static function register(array &$bindings): void
	{
		if (!Loader::includeModule('rest'))
		{
			return;
		}

		$bindings[\CRestUtil::EVENTS][self::EVENT_NAME] = [
			'crm',
			self::EVENT_NAME,
			[\Bitrix\Crm\Activity\Entity\ConfigurableRestApp\EventHandler::class, 'onCrmTimelineItemAction'],
			['category' => \Bitrix\Rest\Sqs::CATEGORY_CRM]
		];
	}

	public static function onCrmTimelineItemAction(array $params)
	{
		return $params[0];
	}
}
