<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Crm\Integration\MailManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender;
use Bitrix\Calendar\Sharing;
use Bitrix\Main;

class MailService extends AbstractService
{
	public static function canSendMessage(ItemIdentifier $entity): bool
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);

		$channels = $repo->getListBySender(MailManager::getSenderCode());
		foreach ($channels as $channel)
		{
			if ($channel->checkChannel()->isSuccess())
			{
				return true;
			}
		}

		return false;
	}

	public function sendCrmSharingInvited(): bool
	{
		if ($this->crmDealLink === null)
		{
			return false;
		}

		$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $this->crmDealLink->getEntityId());
		$channel = $this->getEntityChannel($entity);
		if (is_null($channel))
		{
			return false;
		}

		$from = $this->getFromEntity($channel, $this->crmDealLink->getSenderId());
		$to = $this->getToEntity($channel, $this->crmDealLink->getContactId(), $this->crmDealLink->getContactType());
		if (!$from || !$to)
		{
			return false;
		}

		Sharing\Helper::setSiteLanguage();

		$sharingMail = new Sharing\Notification\Mail();

		$subject = $sharingMail->getInviteLinkSubject();
		$fields = $sharingMail->getInviteLinkParams($this->crmDealLink);

		$lid = SITE_ID;

		$mainEvent = [
			'EVENT_NAME' => 'CALENDAR_SHARING',
			'C_FIELDS' => $fields,
			'FIELDS' => $fields,
			'LID' => $lid,
			'DUPLICATE' => 'Y',
			'MESSAGE_ID' => '',
			'DATE_INSERT' => (new Main\Type\DateTime())->toString(),
			'FILE' => [],
			'LANGUAGE_ID' => LANGUAGE_ID,
			'ID' => 0,
			'FILES_CONTENT' => [],
		];

		$sites = explode(',', $lid);
		if (empty($sites))
		{
			return false;
		}

		$eventMessageFilter = [
			'=ACTIVE' => 'Y',
			'=EVENT_NAME' => $mainEvent['EVENT_NAME'],
			'=EVENT_MESSAGE_SITE.SITE_ID' => $sites,
			[
				'LOGIC' => 'OR',
				[
					'=LANGUAGE_ID' => $mainEvent['LANGUAGE_ID'],
				],
				[
					'=LANGUAGE_ID' => null,
				],
			],
		];

		$messageIdRow = Main\Mail\Internal\EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => $eventMessageFilter,
			'group' => ['ID'],
		])->fetch();

		if (!$messageIdRow)
		{
			return false;
		}

		$eventMessage = Main\Mail\Internal\EventMessageTable::getRowById($messageIdRow['ID']);
		$message = Main\Mail\EventMessageCompiler::createInstance([
			'EVENT' => $mainEvent,
			'FIELDS' => $fields,
			'MESSAGE' => $eventMessage,
			'SITE' => $sites,
			'CHARSET' => '',
		]);
		$message->compile();

		return (new MessageSender\SendFacilitator\Mail($channel))
			->setTo($to)
			->setFrom($from)
			->setMessageSubject($subject)
			->setMessageBody($message->getMailBody())
			->setAddEmailSignature(false)
			->send()
			->isSuccess()
		;
	}

	public function sendCrmSharingAutoAccepted(): bool
	{
		$to = $this->getEmailAddress();
		if (is_null($to))
		{
			return false;
		}

		return (new Sharing\Notification\Mail())
			->setEventLink($this->eventLink)
			->setEvent($this->event)
			->notifyAboutMeetingCreated($to)
		;
	}

	public function sendCrmSharingCancelled(): bool
	{
		$to = $this->getEmailAddress();
		if (is_null($to))
		{
			return false;
		}

		return (new Sharing\Notification\Mail())
			->setEventLink($this->eventLink)
			->setEvent($this->event)
			->notifyAboutMeetingCancelled($to)
		;
	}

	public function sendCrmSharingEdited(): bool
	{
		$to = $this->getEmailAddress();
		if (is_null($to))
		{
			return false;
		}

		return (new Sharing\Notification\Mail())
			->setEventLink($this->eventLink)
			->setEvent($this->event)
			->setOldEvent($this->oldEvent)
			->notifyAboutSharingEventEdit($to)
		;
	}

	protected function getEmailAddress(): ?string
	{
		if ($this->crmDealLink === null)
		{
			return null;
		}

		$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $this->crmDealLink->getEntityId());
		$channel = $this->getEntityChannel($entity);
		if (is_null($channel))
		{
			return null;
		}

		$to = $this->getToEntity($channel, $this->crmDealLink->getContactId(), $this->crmDealLink->getContactType());
		if (!$to)
		{
			return null;
		}

		return $to->getAddress()->getValue();
	}

	protected function getEntityChannel(ItemIdentifier $entity): ?MessageSender\Channel
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);
		$channel = $repo->getById(MailManager::getSenderCode(), $this->crmDealLink->getChannelId());
		if (is_null($channel))
		{
			return null;
		}

		return $channel;
	}
}
