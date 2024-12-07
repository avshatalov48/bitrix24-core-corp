<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\Configurable;

/**
 * @mixin Configurable
 */

trait ModelDataTrait
{
	public function hasContact(): bool
	{
		return
			($this->getModel()->getSettings()['CONTACT_ID'] ?? null)
			&& ($this->getModel()->getSettings()['CONTACT_TYPE_ID'] ?? null)
		;
	}

	public function getContactId(): ?int
	{
		return $this->getModel()->getSettings()['CONTACT_ID'] ?? null;
	}

	public function getContactTypeId(): ?int
	{
		return $this->getModel()->getSettings()['CONTACT_TYPE_ID'] ?? null;
	}

	public function getLinkHash(): ?string
	{
		$linkHash = $this->getModel()->getSettings()['LINK_HASH'] ?? null;

		if (!$linkHash)
		{
			$historyItemModel = $this->getModel()->getHistoryItemModel();
			if ($historyItemModel)
			{
				$linkHash = $historyItemModel->get('LINK_HASH');
			}
		}

		return $linkHash;
	}

	public function getTimestamp(): ?int
	{
		$timestamp = $this->getModel()->getSettings()['TIMESTAMP'] ?? null;

		return $timestamp ? (int)$timestamp : null;
	}

	public function getLinkRule(): array
	{
		$linkRuleArray = $this->getModel()->getSettings()['LINK_RULE'] ?? null;

		if (!$linkRuleArray)
		{
			$historyItemModel = $this->getModel()->getHistoryItemModel();
			if ($historyItemModel)
			{
				$linkRuleArray = $historyItemModel->get('LINK_RULE');
			}
		}

		// for compatibility
		if (!$linkRuleArray)
		{
			$linkRuleArray = [
				'ranges' => [
					[
						'from' => 540,
						'to' => 1080,
						'weekdays' => [1, 2, 3, 4, 5],
						'weekdaysTitle' => $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_WORKDAYS_MSGVER_1'),
					],
				],
				'slotSize' => 60,
			];
		}

		return $linkRuleArray;
	}
}