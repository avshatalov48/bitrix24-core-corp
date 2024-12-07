<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Service\MicroService\BaseReceiver;
use Bitrix\Call\Error;
use Bitrix\Im\Call\Call;
use Bitrix\Im\V2\Call\CallFactory;


class CallController extends BaseReceiver
{
	/**
	 * @restMethod call.CallController.finishCall
	 */
	public function finishCallAction(string $callUuid): ?array
	{
		Loader::includeModule('im');

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $callUuid);

		if (!isset($call))
 		{
			$this->addError(new Error(Error::CALL_NOT_FOUND));

			return null;
		}

		$isSuccess = $call->getSignaling()->sendFinish();

		if (!$isSuccess)
		{
			$this->addError(new Error(Error::SEND_PULL_ERROR));

			return null;
		}

		return ['result' => true];
	}

	/**
	 * @restMethod call.CallController.disconnectUser
	 */
	public function disconnectUserAction(string $callUuid, int $userId): ?array
	{
		Loader::includeModule('im');

		$call = CallFactory::searchActiveByUuid(Call::PROVIDER_BITRIX, $callUuid);

		if (!isset($call))
		{
			$this->addError(new Error(Error::CALL_NOT_FOUND));

			return null;
		}

		$isSuccess = $call->getSignaling()->sendHangup($userId, $call->getUsers(), null);

		if (!$isSuccess)
		{
			$this->addError(new Error(Error::SEND_PULL_ERROR));

			return null;
		}

		return ['result' => true];
	}
}