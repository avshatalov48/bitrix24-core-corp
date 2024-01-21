<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity\Mail\Message;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;

trait MailMessage
{
	public function getOpenMessageAction(int $activityId): ?Action
	{
		return (new JsEvent('Email::OpenMessage'))
			->addActionParamInt('threadId', $activityId)
			->addActionParamString('componentTitle', Loc::getMessage('CRM_TIMELINE_MAIL_MESSAGE_COMPONENT_TITLE'))
		;
	}

	public function getSubjectContentBlock(int $activityId): ?ContentBlock
	{
		$messageSubject = Message::getSubjectById($activityId);
		$subjectLink = ContentBlockFactory::createTextOrLink($messageSubject, $this->getOpenMessageAction($activityId));

		$titleTheme = new Text();
		$titleTheme
			->setValue(Loc::getMessage('CRM_TIMELINE_MAIL_MESSAGE_COMPONENT_SUBJECT_BLOCK'))
			->setColor(Text::COLOR_BASE_70)
		;

		$subjectLink
			->setFontWeight(Text::FONT_WEIGHT_NORMAL)
			->setColor(ContentBlock\Text::COLOR_BASE_70)
			->setFontSize(Text::FONT_SIZE_SM)
		;

		return (new LineOfTextBlocks())
			->addContentBlock('titile-subject', $titleTheme)
			->addContentBlock('subject', $subjectLink)
		;
	}
}