<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\Integration\Pull;
use Bitrix\MessageService\MessageStatus;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;

class Sms extends Base
{
	protected function getActivityTypeId(): string
	{
		return 'Sms';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_SMS_TITLE');
	}

	public function getTags(): ?array
	{
		$smsInfo = $this->getAssociatedEntityModel()->get('SMS_INFO');
		$smsInfo = $smsInfo ?? [];

		$statusId = $smsInfo['statusId'] ?? null;
		if (is_null($statusId))
		{
			return null;
		}

		$semantics = MessageStatus::getSemantics();
		$statusSemantic = $semantics[$statusId] ?? null;
		if (is_null($statusSemantic))
		{
			return null;
		}

		$timelineStatusSemanticsMap = [
			MessageStatus::SEMANTIC_FAILURE => Tag::TYPE_FAILURE,
			MessageStatus::SEMANTIC_PROCESS => Tag::TYPE_WARNING,
			MessageStatus::SEMANTIC_SUCCESS => Tag::TYPE_SUCCESS,
		];
		$timelineStatusSemantic = $timelineStatusSemanticsMap[$statusSemantic] ?? null;
		if (is_null($timelineStatusSemantic))
		{
			return null;
		}

		$statusDescriptions = MessageStatus::getDescriptions();

		$statusTag = new Tag(
			$statusDescriptions[$statusId] ?? '',
			$timelineStatusSemantic
		);

		if (isset($smsInfo['errorText']))
		{
			$statusTag->setHint($smsInfo['errorText']);
		}

		return [
			'status' => $statusTag,
		];
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo('comment'))->setInCircle();
	}

	protected function getMessageId(): ?int
	{
		return $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
	}

	protected function getMessageText(): ?string
	{
		return $this->getAssociatedEntityModel()->get('DESCRIPTION_RAW');
	}

	protected function getMessageSentViaContentBlock(): ?Layout\Body\ContentBlock
	{
		$smsInfo = $this->getAssociatedEntityModel()->get('SMS_INFO');
		$smsInfo = $smsInfo ?? [];

		$senderId = $smsInfo['senderId'] ?? '';
		$senderName = $smsInfo['senderShortName'] ?? '';
		$fromName = $smsInfo['fromName'] ?? '';

		if ($senderId === 'rest' && $fromName)
		{
			$senderName = $fromName;
		}
		$providerParams = $this->getAssociatedEntityModel()->get('PROVIDER_PARAMS') ?? [];
		$sentByRobot = ($providerParams['sender'] ?? '')  === 'robot';

		$message = Loc::getMessage(
			$sentByRobot
				? 'CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_BY_ROBOT_VIA_SERVICE'
				: 'CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_VIA_SERVICE',
			[
				'#SERVICE_NAME#' => $senderName,
			]
		);
		if ($senderId !== 'rest' && $fromName)
		{
			$message = Loc::getMessage($sentByRobot
					? 'CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_BY_ROBOT_VIA_SERVICE_FULL'
					: 'CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_VIA_SERVICE_FULL',
				[
					'#SERVICE_NAME#' => $senderName,
					'#PHONE_NUMBER#' => $fromName,
				]
			);
		}

		return
			(new Layout\Body\ContentBlock\Text())
				->setValue($message)
				->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_60)
		;
	}

	protected function getPullModuleId(): string
	{
		return 'messageservice';
	}

	protected function getPullCommand(): string
	{
		if (!Loader::includeModule('messageservice'))
		{
			return '';
		}

		return Pull::COMMAND;
	}

	protected function getPullTagName(): string
	{
		if (!Loader::includeModule('messageservice'))
		{
			return '';
		}

		return Pull::TAG;
	}



	protected function buildUserContentBlock(): ?ContentBlock
	{
		$providerParams = $this->getAssociatedEntityModel()->get('PROVIDER_PARAMS') ?? [];
		$recipientUserId = (int)($providerParams['recipient_user_id'] ?? 0);
		if (!$recipientUserId)
		{
			return null;
		}
		$communication = $this->getAssociatedEntityModel()->get('COMMUNICATION') ?? [];
		$phone = $communication['FORMATTED_VALUE'] ?? null;
		if (!$phone)
		{
			return null;
		}
		$userInfo = Container::getInstance()->getUserBroker()->getById($recipientUserId);
		$userName = $userInfo['FORMATTED_NAME'] ?? null;
		$userDetailsUrl = $userInfo['SHOW_URL'] ?? null;
		if (!$userName)
		{
			return null;
		}
		$textOrLink = ContentBlockFactory::createTextOrLink($userName . ' ' . $phone, $userDetailsUrl ? new Redirect($userDetailsUrl) : null);

		return (new LineOfTextBlocks())
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SMS_RECIPIENT'))
			)
			->addContentBlock('data', $textOrLink->setIsBold(isset($userDetailsUrl))->setColor(Text::COLOR_BASE_90))
		;
	}

}
