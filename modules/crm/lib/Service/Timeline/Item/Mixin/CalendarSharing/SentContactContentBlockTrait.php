<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;

/**
 * @mixin Configurable
 */
trait SentContactContentBlockTrait
{
	use ContactTrait;
	use DateTrait;

	public function getSentContactContentBlock(?int $contactTypeId, ?int $contactId, ?int $timestamp): ContentBlock
	{
		$result = new LineOfTextBlocks();

		$result->addContentBlock(
			'title',
			ContentBlockFactory::createTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_SENT')
			)
				->setColor(Text::COLOR_BASE_70)
		);

		$contactName = $this->getContactName($contactTypeId, $contactId);
		$contactUrl = $this->getContactUrl($contactTypeId, $contactId);

		$result->addContentBlock(
			'contactInfo',
			ContentBlockFactory::createTextOrLink(
				$contactName, $contactUrl ? new Redirect($contactUrl) : null
			)
		);

		$date = $this->getDateContent($timestamp);
		$result->addContentBlock(
			'linkCreationDate',
			(new Date())
				->setDate($date)
				->setColor(Text::COLOR_BASE_90)
		);

		return $result;
	}
}