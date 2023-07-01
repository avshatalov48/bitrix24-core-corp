<?php

namespace Bitrix\Crm\Integration\ImOpenLines;

use Bitrix\Bizproc\Error;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\ImConnector\Connector;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class GoToChat
{
	public const NOTIFICATIONS_MESSAGE_CODE = 'GOTO_CHAT';

	private const TELEGRAM_BOT_CONNECTOR_ID = 'telegrambot';

	public static function isActive(): bool
	{
		return
			Loader::includeModule('imopenlines')
			&& Loader::includeModule('imconnector')
			&& class_exists(\Bitrix\ImOpenLines\Tracker::class)
			&& method_exists(\Bitrix\ImOpenLines\Tracker::class, 'getMessengerLink')
		;
	}

	public static function isValidLineId(string $lineId): bool
	{
		if (!Loader::includeModule('imconnector'))
		{
			return false;
		}
		$connectorData = Connector::infoConnectorsLine($lineId);

		return isset($connectorData[self::TELEGRAM_BOT_CONNECTOR_ID]);
	}

	public function send(
		Channel $channel,
		string $lineId,
		Channel\Correspondents\From $from,
		Channel\Correspondents\To $to
	): Result
	{
		$result = new Result();

		if (!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_MODULE_NOT_INSTALLED')));

			return $result;
		}

		$senderChannelId = $channel->getSender()::getSenderCode();

		if ($senderChannelId === SmsManager::getSenderCode())
		{
			$facilitator = (new \Bitrix\Crm\MessageSender\SendFacilitator\Sms($channel))
				->setMessageBody($this->getSmsText($lineId, $to))
			;
		}
		elseif ($senderChannelId === NotificationsManager::getSenderCode())
		{
			$facilitator = (new \Bitrix\Crm\MessageSender\SendFacilitator\Notifications($channel))
				->setTemplateCode(self::NOTIFICATIONS_MESSAGE_CODE)
				->setPlaceholders([
					'URL' => $this->getUrl($lineId, $to),
				])
			;
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_WRONG_CHANNEL')));

			return $result;
		}

		return $facilitator
			->setFrom($from)
			->setTo($to)
			->send()
		;
	}

	private function getSmsText(string $lineId, Channel\Correspondents\To $to): string
	{
		return Loc::getMessage('CRM_IMOL_INVITATION_TEXT', ['#URL#' => $this->getUrl($lineId, $to)]);
	}

	private function getUrl(string $lineId,Channel\Correspondents\To $to): string
	{
		$bindings = [];
		$bindings[] = $to->getRootSource()->toArray();
		if ($to->getRootSource()->getEntityTypeId() !== $to->getAddressSource()->getEntityTypeId())
		{
			$bindings[] = $to->getRootSource()->toArray();
		}

		$tracker = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('ImOpenLines.Services.Tracker');

		return $tracker->getMessengerLink(
			$lineId,
			self::TELEGRAM_BOT_CONNECTOR_ID,
			$bindings
		)['web'];
	}
}
