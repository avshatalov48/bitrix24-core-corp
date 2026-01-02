<?php

namespace Bitrix\Crm\Activity\LastCommunication;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;

final class LastCommunicationAvailabilityChecker
{
	use Singleton;

	public function isEnabled(): bool
	{
		return Option::get('crm', 'enable_last_communication_fields', 'Y') === 'Y';
	}
}