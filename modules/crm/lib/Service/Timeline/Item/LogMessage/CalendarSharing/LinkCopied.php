<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;

class LinkCopied extends LogMessage
{
	use CalendarSharing;

	public function getType(): string
	{
		return 'CalendarSharingLinkCopied';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_LINK_COPIED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::LINK;
	}

	public function getContentBlocks(): ?array
	{
		$linkBlock = null;
		$linkUrl = $this->getLinkUrl();
		$contentBlock =
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_CLIENT_CAN_SET_MEETING')
			)
				->setColor(Text::COLOR_BASE_70)
		;

		if ($linkUrl)
		{
			$linkBlock =
				(new Layout\Body\ContentBlock\Link())
					->setValue($this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_VIEW_SLOTS'))
					->setAction((new Layout\Action\JsEvent($this->getType() . ':OpenPublicPageInNewTab'))
						->addActionParamString('url', $linkUrl)
					)
			;
		}

		$result = [
			'detail' => $contentBlock,
		];

		if ($linkBlock)
		{
			$result['link'] = $linkBlock;
		}

		return $result;
	}
}