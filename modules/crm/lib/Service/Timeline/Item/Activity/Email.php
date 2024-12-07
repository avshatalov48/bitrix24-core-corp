<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Mail\Message;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Mail\ContactList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmActivityDirection;

class Email extends Activity
{
	private const TIMELINE_SHORT_LIMIT_LENGTH = 57;
	private const TIMELINE_LONG_LIMIT_LENGTH = 155;

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
			$header = Message::getHeader([
				'OWNER_TYPE_ID' => (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID'),
				'OWNER_ID' => (int)$this->getAssociatedEntityModel()->get('OWNER_ID'),
				'ID' => $this->getAssociatedEntityModel()->get('ID'),
				'SETTINGS' => $this->getAssociatedEntityModel()->get('SETTINGS'),
			])->getData();
		}
		return $header;
	}

	private function buildContactBlock($contact): ?LineOfTextBlocks
	{
		$name = $contact['name'];
		$email = $contact['email'];
		$isUser = $contact['isUser'];
		$id = (int)$contact['id'];
		$typeNameId = $contact['typeNameId'];

		$url = null;
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
			$textOrLink->setFontWeight(Text::FONT_WEIGHT_NORMAL);
			if (!$url)
			{
				$textOrLink->setColor(ContentBlock\Text::COLOR_BASE_70);
			}
			$textOrLink->setFontSize(Text::FONT_SIZE_SM);

			$lineOfTextBlock->addContentBlock('name', $textOrLink);
		}

		return $lineOfTextBlock->addContentBlock('email',
				(new Text())->setValue($email)
					->setColor(ContentBlock\Text::COLOR_BASE_70)
					->setFontWeight(Text::FONT_WEIGHT_NORMAL)
					->setFontSize(Text::FONT_SIZE_SM));
	}

	private function buildContactsBlock($title, $contactsListData, $withWrapper = true): ?ContentBlock
	{
		$contactList = new ContactList();

		// @todo To write a list of contacts for the web version, to lead to a single set of parameters
		$contactList->setContactList($contactsListData);
		$contactList->setTitle($title);

		foreach ($contactsListData as $contact)
		{
			$contactList->addListItem($this->buildContactBlock($contact));
		}

		if ($withWrapper)
		{
			return ((new ContentBlockWithTitle())->setInline()->setWordWrap()->setTitle($title)
				->setContentBlock($contactList));
		}
		else
		{
			return $contactList;
		}
	}

	private function buildRecipientBlock($withWrapper = true): ?ContentBlock
	{
		$header = $this->getHeader() ?? [];

		if (empty($header['to']))
		{
			return null;
		}

		return $this->buildContactsBlock(Loc::getMessage("CRM_TIMELINE_BLOCK_EMAIL_TITLE_RECIPIENT"), $header['to'], $withWrapper);
	}

	private function buildSenderBlock($withWrapper = true): ?ContentBlock
	{
		$header = $this->getHeader() ?? [];
		if (empty($header['from']))
		{
			return null;
		}

		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		if (
			$direction === CCrmActivityDirection::Outgoing
			&& count($header['from']) === 1
			&& $header['from'][0]['senderName'])
		{
			$header['from'][0]['name'] = $header['from'][0]['senderName'];
		}

		return $this->buildContactsBlock(Loc::getMessage("CRM_TIMELINE_BLOCK_EMAIL_TITLE_SENDER"), $header['from'], $withWrapper);
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

		// @todo To write a list of contacts for the web version, to lead to a single set of parameters
		$recipientBlock = $this->buildRecipientBlock();
		if (isset($recipientBlock))
		{
			$result['recipient'] = $recipientBlock->setScopeWeb();
		}

		$recipientBlock = $this->buildRecipientBlock(false);
		if (isset($recipientBlock))
		{
			$result['recipientMob'] = $recipientBlock->setScopeMobile();
		}

		$senderBlock = $this->buildSenderBlock();
		if (isset($senderBlock))
		{
			$result['sender'] = $senderBlock->setScopeWeb();
		}

		$senderBlock = $this->buildSenderBlock(false);
		if (isset($senderBlock))
		{
			$result['senderMob'] = $senderBlock->setScopeMobile();
		}

		$shortBodyBlock = $this->buildShortBodyBlock();
		if (isset($shortBodyBlock))
		{
			$result['shortBody'] = $shortBodyBlock;
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

		return [
			'openButton' => (new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_EMAIL_OPEN'), $type))->setAction(($this->getTitleAction())),
			'scheduleButton' => $this->getScheduleButton('Email::Schedule'),
		];
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		if (isset($items['edit']))
		{
			$items['edit']->setScopeWeb();
		}
		if (isset($items['view']))
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

	protected function canMoveTo(): bool
	{
		return $this->isScheduled();
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function getBody()
	{
		$activityId = $this->getAssociatedEntityModel()->get('ID');

		static $associatedActivityId;
		static $messageBody;

		if (is_null($messageBody) || $activityId !== $associatedActivityId)
		{
			$associatedActivityId = $activityId;
			return Message::getMessageBody($this->getAssociatedEntityModel()->get('ID'))->getData();
		}

		return $messageBody;
	}

	private function buildShortBodyBlock(): ?ContentBlock
	{
		$body = $this->getBody();
		$height = ContentBlock\EditableDescription::HEIGHT_SHORT;
		$sanitizer = new \CBXSanitizer();
		$sanitizer->AddTags(['b' => [],]);

		$sanitizer->setDelTagsWithContent(['blockquote', 'style']);
		$sanitizedBody = $sanitizer->SanitizeHtml($body['HTML']);
		$sanitizedBody = strip_tags($sanitizedBody);

		$sanitizedBody = htmlspecialchars_decode($sanitizedBody, ENT_QUOTES);
		$sanitizedBody = trim(str_replace("\r\n", ' ', $sanitizedBody));

		$sanitizedBody = $this->extractImageAboutReading($sanitizedBody);

		if (mb_strlen($sanitizedBody) === 0)
		{
			return null;
		}

		$messageExploded = explode("\r\n", $sanitizedBody);
		$hasManyLines = count($messageExploded) > 1;

		if (!$hasManyLines)
		{
			$messageExploded = explode("\n", $sanitizedBody);
			$hasManyLines = count($messageExploded) > 1;
		}

		if ($hasManyLines)
		{

			$height = ContentBlock\EditableDescription::HEIGHT_LONG;
			$sanitizedBody = '';
			foreach ($messageExploded as $item)
			{
				$item = trim($item);
				if (empty($item))
				{
					continue;
				}

				if (mb_strlen($sanitizedBody) > self::TIMELINE_LONG_LIMIT_LENGTH)
				{
					$sanitizedBody = mb_substr($sanitizedBody, 0, self::TIMELINE_LONG_LIMIT_LENGTH);
					$sanitizedBody = $this->handlePunctuation($sanitizedBody);
					$sanitizedBody .= '...';
					break;
				}

				$sanitizedBody .= $this->handlePunctuation($item);
			}
		}
		elseif (mb_strlen($sanitizedBody) > self::TIMELINE_SHORT_LIMIT_LENGTH)
		{
			$height = ContentBlock\EditableDescription::HEIGHT_LONG;
			if (mb_strlen($sanitizedBody) > self::TIMELINE_LONG_LIMIT_LENGTH)
			{
				$sanitizedBody = $this->handlePunctuation($sanitizedBody);
				$sanitizedBody = mb_substr($sanitizedBody, 0, self::TIMELINE_LONG_LIMIT_LENGTH);
				if (mb_strlen($sanitizedBody) >= self::TIMELINE_LONG_LIMIT_LENGTH)
				{
					$sanitizedBody .= '...';
				}
			}
		}

		return (new ContentBlock\EditableDescription())
			->setText($sanitizedBody)
			->setEditable(false)
			->setHeight($height)
		;
	}

	private function extractImageAboutReading(string $sanitizedBody): string
	{
		return (string)preg_replace('/\[.*\/pub\/mail\/read\.php.*\]/i','', $sanitizedBody);
	}

	public function handlePunctuation(string $item): string
	{
		$result = trim(str_replace(['.', '!', '?', '&nbsp;', "\t"], ['. ', '! ', '? ', '', ' '], $item));
		return (string)preg_replace("/\h{2,}/u", " ", $result) ?? '';
	}
}
