<?php

namespace Bitrix\Crm\Service\EventHistory\TrackedObject;

use Bitrix\Crm\Service\EventHistory\TrackedObject;

/**
 * Class Contact
 *
 * @property \Bitrix\Crm\EO_Company $objectBeforeSave
 * @property \Bitrix\Crm\EO_Company $object
 */
final class Company extends TrackedObject
{
	protected static function getEntityTitleMethod(): string
	{
		return 'getTitle';
	}

	protected function getTrackedRegularFieldNames(): array
	{
		return [];
	}
}
