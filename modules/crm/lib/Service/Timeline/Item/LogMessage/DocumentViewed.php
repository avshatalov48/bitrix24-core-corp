<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Main\Localization\Loc;

Container::getInstance()->getLocalization()->loadMessages();

final class DocumentViewed extends LogMessage
{
	use Mixin\Document;

	public function getType(): string
	{
		return 'DocumentViewed';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_DOCUMENT_VIEWED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::VIEW;
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];

		$blocks['title'] =
			(new Link())
				->setValue($this->getDocument()->getTitle())
				->setAction($this->getOpenDocumentAction())
		;

		return $blocks;
	}
}
