<?php

namespace Bitrix\Crm\MessageSender\MassWhatsApp;

use Bitrix\Crm\Activity\Provider\Sms\MessageDto;
use Bitrix\Crm\Activity\Provider\Sms\Sender;
use Bitrix\Crm\Activity\Provider\Sms\SenderExtra;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel\ErrorCode;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SendItem
{
	use Singleton;

	public const DEFAULT_PROVIDER = 'ednaru';

	private MessageDataRepo $messageDataRepo;

	private TemplateCreator $templateCreator;

	public function __construct()
	{
		$this->messageDataRepo = MessageDataRepo::getInstance();
		$this->templateCreator = TemplateCreator::getInstance();
	}

	public function execute(Item $item, TemplateParams $params): Result
	{
		$result = new Result();

		$messageTo = $this->messageDataRepo->getMessageTo(
			new ItemIdentifier($item->getEntityTypeId(), $item->getId())
		);
		if (empty($messageTo))
		{
			$result->addError(new Error(Loc::getMessage('CRM_MASS_WHATSAPP_SENDITEM_NO_PHONE')));

			return $result;
		}

		$messageFrom = $this->getFromNumber($params);

		if (empty($messageFrom))
		{
			$result->addError(new Error(ErrorCode::getNotConnectedError()->getMessage()));

			return $result;
		}

		$tplResult = $this->templateCreator->prepareTemplate(
			new ItemIdentifier($item->getEntityTypeId(), $item->getId()),
			$params->messageBody,
			$params->messageTemplate
		);

		if (empty($tplResult->preparedTemplate))
		{
			$result->addError(new Error(ErrorCode::getUnknownChannelError()->getMessage()));

			return $result;
		}

		$message = new MessageDto([
			'senderId' => self::DEFAULT_PROVIDER,
			'from' => $messageFrom,
			'to' => $messageTo,
			'body' => $tplResult->messageBody,
			'template' => $tplResult->preparedTemplate,
		]);

		$senderExtra = new SenderExtra(
			sentMessageTag: SenderExtra::SENT_MESSAGE_TAG_GROUP_WHATSAPP_MESSAGE,
		);

		$owner = new ItemIdentifier($item->getEntityTypeId(), $item->getId());

		$sender = (new Sender($owner, $message, $senderExtra));
		$sender->setEntityIdentifier($owner);

		return $sender->send();
	}

	private function getFromNumber(TemplateParams $params): string
	{
		if (empty($params->fromPhone))
		{
			return $this->messageDataRepo->getFirstMessageFrom();
		}

		if (!$this->checkIsFromPhoneAvailable($params->fromPhone))
		{
			return $this->messageDataRepo->getFirstMessageFrom();
		}

		return $params->fromPhone;
	}

	private function checkIsFromPhoneAvailable(string $fromPhone): bool
	{
		$fromList = $this->messageDataRepo->getFromListByProviderId(self::DEFAULT_PROVIDER);
		$fromListIds = array_column($fromList, 'id');

		return in_array($fromPhone, $fromListIds, true);
	}
}