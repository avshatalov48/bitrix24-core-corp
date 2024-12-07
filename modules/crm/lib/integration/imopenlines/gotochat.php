<?php

namespace Bitrix\Crm\Integration\ImOpenLines;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Status;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;

class GoToChat
{
	public const NOTIFICATIONS_MESSAGE_CODE = 'GOTO_CHAT';

	private const TELEGRAM_BOT_CONNECTOR_ID = 'telegrambot';
	private const WHATS_APP_CONNECTOR_ID = 'notifications';

	private string $senderType;
	private string $senderChannelId;
	private ?ItemIdentifier $owner;
	private string $connectorId = self::TELEGRAM_BOT_CONNECTOR_ID;

	public static function isActive(): bool
	{
		return
			Loader::includeModule('imopenlines')
			&& Loader::includeModule('imconnector')
			&& class_exists(\Bitrix\ImOpenLines\Tracker::class)
			&& method_exists(\Bitrix\ImOpenLines\Tracker::class, 'getMessengerLink')
		;
	}

	public function __construct(
		string $senderType = 'bitrix24',
		string $senderChannelId = 'bitrix24'
	)
	{
		$this->senderType = $senderType;
		$this->senderChannelId = $senderChannelId;
	}

	public function setOwner(ItemIdentifier $owner): GoToChat
	{
		$this->owner = $owner;

		return $this;
	}

	public function setConnectorId(string $connectorId): GoToChat
	{
		$availableConnectors = [
			self::TELEGRAM_BOT_CONNECTOR_ID,
			self::WHATS_APP_CONNECTOR_ID,
		];

		if (!in_array($connectorId, $availableConnectors, true))
		{
			throw new ArgumentException('Unknown connectorId: ' . $connectorId, 'CONNECTOR_ID');
		}

		$this->connectorId = $connectorId;

		return $this;
	}

	public function send(
		string $from,
		int $to,
		?int $lineId = null
	): Result
	{
		$result = new Result();

		if (!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_MODULE_NOT_INSTALLED')));

			return $result;
		}

		$this->checkOwner();

		if ($lineId <= 0)
		{
			$lineId = $this->getFirstAvailableLineId();
		}

		if (!$this->isValidLineId($lineId))
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_INVITATION_WRONG_LINE')));

			return $result;
		}

		$channel = $this->createChannel();
		if (!$channel)
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_INVITATION_CHANNEL_NOT_FOUND')));

			return $result;
		}

		$fromCorrespondent = $this->getFromCorrespondent($channel, $from);
		if (!$fromCorrespondent)
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_INVITATION_WRONG_FROM')));

			return $result;
		}

		$toCorrespondent = $this->getToCorrespondent($channel, $to);
		if (!$toCorrespondent)
		{
			$result->addError(new Error(Loc::getMessage('CRM_IMOL_INVITATION_WRONG_TO')));

			return $result;
		}

		return $this->sendViaFacilitator($channel, $lineId, $fromCorrespondent, $toCorrespondent);
	}

	private function checkOwner(): void
	{
		if (!$this->owner)
		{
			throw new ObjectPropertyException('Owner must be set. Call "setOwner" method before');
		}
	}

	private function getFirstAvailableLineId(): ?int
	{
		if (!Loader::includeModule('imconnector'))
		{
			return null;
		}

		$statuses = Status::getInstanceAllLine($this->connectorId);

		foreach ($statuses as $lineId => $status)
		{
			if (!$status->getError() && $status->getRegister() && $status->getActive())
			{
				return $lineId;
			}
		}

		return null;
	}

	private function isValidLineId(?int $lineId): bool
	{
		if (!Loader::includeModule('imconnector') || !$lineId)
		{
			return false;
		}

		$connectorData = Connector::infoConnectorsLine($lineId);

		return isset($connectorData[$this->connectorId]);
	}

	private function createChannel(): ?Channel
	{
		return ChannelRepository::create($this->owner)->getById($this->senderType, $this->senderChannelId);
	}

	private function getFromCorrespondent(Channel $channel, string $from): ?Channel\Correspondents\From
	{
		foreach ($channel->getFromList() as $fromListItem)
		{
			if ($fromListItem->getId() === $from)
			{
				return $fromListItem;
			}
		}

		return null;
	}

	private function getToCorrespondent(Channel $channel, int $to): ?Channel\Correspondents\To
	{
		foreach ($channel->getToList() as $toListItem)
		{
			if ($toListItem->getAddress()->getId() === $to)
			{
				return $toListItem;
			}
		}

		return null;
	}

	private function sendViaFacilitator(
		Channel $channel,
		int $lineId,
		Channel\Correspondents\From $from,
		Channel\Correspondents\To $to
	): Result
	{
		$result = new Result();

		$url = $this->getUrl($lineId, $to);
		$senderChannelId = $channel->getSender()::getSenderCode();

		if ($senderChannelId === SmsManager::getSenderCode())
		{
			$facilitator = (new \Bitrix\Crm\MessageSender\SendFacilitator\Sms($channel))
				->setMessageBody($this->getSmsText($url))
			;
		}
		elseif ($senderChannelId === NotificationsManager::getSenderCode())
		{
			$facilitator = (new \Bitrix\Crm\MessageSender\SendFacilitator\Notifications($channel))
				->setTemplateCode(self::NOTIFICATIONS_MESSAGE_CODE)
				->setPlaceholders([
					'URL' => $url,
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
			->setAdditionalFields([
				'HIGHLIGHT_URL' => $url,
			])
			->send()
		;
	}

	private function getSmsText(string $url): string
	{
		return Loc::getMessage('CRM_IMOL_INVITATION_TEXT', ['#URL#' => $url]);
	}

	private function getUrl(int $lineId, Channel\Correspondents\To $to): string
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
			$this->connectorId,
			$bindings
		)['web'];
	}
}
