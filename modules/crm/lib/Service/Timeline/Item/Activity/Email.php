<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Settings\WorkTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmActivityDirection;

class Email extends Activity
{
	private const BLOCK_DELIMITER = '&bull;';

	final protected function getActivityTypeId(): string
	{
		return 'Email';
	}

	public function getIconCode(): string
	{
		return 'email';
	}

	public function getTitle(): string
	{
		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');

		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				if ($this->isScheduled())
				{
					return Loc::getMessage('CRM_TIMELINE_TITLE_EMAIL_INCOMING');
				}
				else
				{
					return Loc::getMessage('CRM_TIMELINE_TITLE_EMAIL_INCOMING_DONE');
				}
			case CCrmActivityDirection::Outgoing:
				if ($this->isScheduled())
				{
					return Loc::getMessage('CRM_TIMELINE_TITLE_EMAIL_OUTGOING');
				}
				else
				{
					return Loc::getMessage('CRM_TIMELINE_TITLE_EMAIL_OUTGOING_DONE');
				}
		}

		return Loc::getMessage('CRM_TIMELINE_TITLE_EMAIL_UNKNOWN');
	}

	public function getLogo(): ?Logo
	{
		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		$logo = new Logo('email');
		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				if ($this->isScheduled())
				{
					return $logo->setAdditionalIconCode('arrow-incoming');
				}
				else
				{
					return $logo->setAdditionalIconCode('done');
				}
			case CCrmActivityDirection::Outgoing:
				return $logo->setAdditionalIconCode('arrow-outgoing');
		}
		return $logo;
	}

	protected function getHeader()
	{
		$activityId = $this->getAssociatedEntityModel()->get('ID');

		static $associatedActivityId;
		static $header;

		if (is_null($header) || $activityId !== $associatedActivityId)
		{
			$associatedActivityId = $activityId;
			$header = \Bitrix\Crm\Activity\Mail\Message::getHeader([
				'OWNER_TYPE_ID' => (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID'),
				'OWNER_ID' => (int)$this->getAssociatedEntityModel()->get('OWNER_ID'),
				'ID' => $this->getAssociatedEntityModel()->get('ID'),
				'SETTINGS' => $this->getAssociatedEntityModel()->get('SETTINGS'),
			])->getData();
		}
		return $header;
	}

	private function buildContactBlock($title, $contact): ?ContentBlock
	{
		$name = $contact['name'];
		$email = $contact['email'];
		$isUser = $contact['isUser'];
		$id = (int)$contact['id'];
		$typeNameId = $contact['typeNameId'];

		if ($isUser)
		{
			if ($id !== 0)
			{
				$url = new Uri("/company/personal/user/" . $id . "/");
			}
		}
		else
		{
			$url = new Uri(Service\Container::getInstance()->getRouter()->getItemDetailUrl(
				$typeNameId,
				$id));
		}

		$lineOfTextBlock = new LineOfTextBlocks();

		if ($name)
		{
			$textOrLink = ContentBlockFactory::createTextOrLink($name, $url ? new Redirect($url) : null);
			$lineOfTextBlock->addContentBlock('name',
				($textOrLink)->setFontWeight(Text::FONT_WEIGHT_NORMAL)
					->setColor(ContentBlock\Text::COLOR_BASE_70)
					->setFontSize(Text::FONT_SIZE_SM));
		}

		return (new ContentBlockWithTitle())->setInline()
			->setTitle($title)
			->setContentBlock($lineOfTextBlock->addContentBlock('email',
				(new Text())->setValue($email)
					->setColor(ContentBlock\Text::COLOR_BASE_70)
					->setFontWeight(Text::FONT_WEIGHT_NORMAL)
					->setFontSize(Text::FONT_SIZE_SM))
			);
	}

	private function buildRecipientBlock(): ?ContentBlock
	{
		$header = $this->getHeader() ?? [];
		$to = $header['to'] ?? [];

		if (!isset($to[0]))
		{
			return null;
		}

		$recipient = $to[0];
		
		return $this->buildContactBlock(Loc::getMessage("CRM_TIMELINE_BLOCK_EMAIL_TITLE_RECIPIENT"), $recipient);
	}

	private function buildSenderBlock(): ?ContentBlock
	{
		$header = $this->getHeader() ?? [];
		$from = $header['from'] ?? [];

		if (!isset($from[0]))
		{
			return null;
		}

		$sender = $from[0];

		return $this->buildContactBlock(Loc::getMessage("CRM_TIMELINE_BLOCK_EMAIL_TITLE_SENDER"), $sender);
	}

	private function buildSubjectBlock(): ?ContentBlock
	{
		$subject = (string)$this->getAssociatedEntityModel()->get('SUBJECT');
		if (empty($subject))
		{
			return null;
		}

		$subject = ContentBlockFactory::createTextOrLink($subject, $this->getTitleAction());
		return (new ContentBlockWithTitle())
			->setTitle(Loc::getMessage("CRM_TIMELINE_BLOCK_EMAIL_TITLE_THEME"))
			->setContentBlock($subject)
			->setInline();
	}

	public function getContentBlocks(): array
	{
		$result = [];

		$subjectBlock = $this->buildSubjectBlock();
		if (isset($subjectBlock))
		{
			$result['subject'] = $subjectBlock;
		}

		$recipientBlock = $this->buildRecipientBlock();
		if (isset($recipientBlock))
		{
			$result['recipient'] = $recipientBlock;
		}

		$senderBlock = $this->buildSenderBlock();
		if (isset($senderBlock))
		{
			$result['sender'] = $senderBlock;
		}

		return $result;
	}

	public function getTitleAction(): ?Action
	{
		return ((new JsEvent('Email::OpenMessage'))->addActionParamInt('threadId',
			$this->getActivityId())->addActionParamString('componentTitle',
			Loc::getMessage('CRM_TIMELINE_EMAIL_MESSAGE_COMPONENT_TITLE')));
	}

	public function getButtons(): array
	{
		if ($this->isScheduled())
		{
			$type = Button::TYPE_PRIMARY;
		}
		else
		{
			$type = Button::TYPE_SECONDARY;
		}

		$nearestWorkday = (new WorkTime())->detectNearestWorkDateTime(3, 1);
		$scheduleButton = (new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_EMAIL_SCHEDULE'), Button::TYPE_SECONDARY))
			->setAction((new JsEvent('Email::Schedule'))
				->addActionParamInt('activityId', $this->getActivityId())
				->addActionParamString('scheduleDate', $nearestWorkday->toString())
				->addActionParamInt('scheduleTs', $nearestWorkday->getTimestamp()));

		return [
			'openButton' => (new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_EMAIL_OPEN'), $type))
				->setAction(($this->getTitleAction())),
			'scheduleButton' => $scheduleButton,
		];
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		if ($items['edit'])
		{
			$items['edit']->setScopeWeb();
		}
		if ($items['view'])
		{
			$items['view']->setScopeWeb();
		}

		return $items;
	}

	protected function getDeleteConfirmationText(): string
	{
		$title = $this->getAssociatedEntityModel()->get('SUBJECT') ?? '';
		return Loc::getMessage('CRM_TIMELINE_INCOMING_EMAIL_DELETION_CONFIRM', ['#TITLE#' => $title]);
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
