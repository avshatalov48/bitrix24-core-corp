<?php

namespace Bitrix\Crm\Service\EventHistory\TrackedObject;

use Bitrix\Crm\Service\EventHistory\TrackedObject;

/**
 * Class Contact
 * @property \Bitrix\Crm\Contact $objectBeforeSave
 * @property \Bitrix\Crm\Contact $object
 */
class Contact extends TrackedObject
{
	protected static function getEntityTitleMethod(): string
	{
		return 'getFormattedName';
	}

	protected function getTrackedRegularFieldNames(): array
	{
		return [];
	}
}