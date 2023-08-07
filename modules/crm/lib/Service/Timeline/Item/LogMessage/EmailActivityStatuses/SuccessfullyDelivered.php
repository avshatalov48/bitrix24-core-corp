<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\EmailActivityStatuses;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Activity\Mail\Message;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class SuccessfullyDelivered extends LogMessage
{
	public function getIconCode(): ?string
	{
		return Icon::COMPLETE;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_SUCCESSFULLY_DELIVERED');
	}

	public function getType(): string
	{
		return 'EmailActivitySuccessfullyDelivered';
	}

	private function getActivityId(): int
	{
		return (int) $this->getModel()->getAssociatedEntityId();
	}

	public function getTitleAction(): ?Action
	{
		return ((new JsEvent('Email::OpenMessage'))->addActionParamInt('threadId',
			$this->getActivityId())->addActionParamString('componentTitle',
			Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_MESSAGE_COMPONENT_TITLE')));
	}

	public function getContentBlocks(): ?array
	{
		$activityId = $this->getActivityId();
		$messageSubject = Message::getSubjectById($activityId);
		$subjectLink = ContentBlockFactory::createTextOrLink($messageSubject, $this->getTitleAction());

		$titleTheme = new Text();
		$titleTheme->setValue(Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_TITLE_THEME'))
			->setColor(Text::COLOR_BASE_70);

		$lineOfTextBlock = new LineOfTextBlocks();
		$lineOfTextBlock->addContentBlock('titile-subject', $titleTheme)
			->addContentBlock('subject', $subjectLink->setFontWeight(Text::FONT_WEIGHT_NORMAL)
				->setColor(ContentBlock\Text::COLOR_BASE_70)
				->setFontSize(Text::FONT_SIZE_SM));

		return [
			'activityInfo' => $lineOfTextBlock,
		];
	}
}