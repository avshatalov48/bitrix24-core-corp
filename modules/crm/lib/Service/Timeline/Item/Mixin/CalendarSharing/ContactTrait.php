<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Web\Uri;

trait ContactTrait
{
	use MessageTrait;

	private function getContactName(int $contactTypeId, int $contactId): string
	{
		$contactData = Container::getInstance()
			->getEntityBroker($contactTypeId)
			?->getById($contactId)
		;

		$result = false;
		if ($contactData)
		{
			if ($contactTypeId === \CCrmOwnerType::Contact)
			{
				$result = $contactData->getFullName();
			}
			else if ($contactTypeId === \CCrmOwnerType::Company)
			{
				$result = $contactData->getTitle();
			}
		}

		return is_string($result)
			? $result
			: $this->getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_NOT_FOUND')
		;
	}

	private function getContactUrl(int $contactTypeId, int $contactId): ?Uri
	{
		$result = null;

		$detailUrl = Container::getInstance()
			->getRouter()
			->getItemDetailUrl(
				$contactTypeId,
				$contactId
			)
		;
		if ($detailUrl)
		{
			$result = new Uri($detailUrl);
		}

		return $result;
	}
}
