<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Web\Uri;

/**
 * @mixin Configurable
 */
trait PlannedEventContentBlockTrait
{
	use ContactTrait;
	use DateTrait;
	use MessageTrait;

	public function getPlannedEventContentBlock(?int $contactTypeId, ?int $contactId): ContentBlock
	{
		if ($contactTypeId && $contactId)
		{
			$contactName = $this->getContactName($contactTypeId, $contactId);
			$contactUrl = $this->getContactUrl($contactTypeId, $contactId);
		}
		else
		{
			$activity = $this->getAssociatedEntityModel();
			$settings = $activity ? $activity->get('SETTINGS') : [];
			$contactName = !empty($settings['GUEST_NAME'])
				? $settings['GUEST_NAME']
				: $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST')
			;
			$contactUrl = false;
		}

		$managerData = $this->getUserData($this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID'));

		$managerUrl = isset($managerData['SHOW_URL']) ? new Uri($managerData['SHOW_URL']) : null;
		$managerNameAction = $managerUrl ? new Redirect($managerUrl) : null;
		$managerNameBlock = ContentBlockFactory::createTextOrLink( $managerData['FORMATTED_NAME'] ?? '', $managerNameAction);

		return ContentBlockFactory::createLineOfTextFromTemplate(
			$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_MEETING_PLANNED_MSGVER_2'),
			[
				'#MANAGER_NAME#' => $managerNameBlock,
				'#CONTACT#' => ContentBlockFactory::createTextOrLink(
					$contactName, $contactUrl ? new Redirect($contactUrl) : null
				),
			]
		)
			->setTextColor(Text::COLOR_BASE_70)
		;
	}
}