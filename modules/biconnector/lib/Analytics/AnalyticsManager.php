<?php

namespace Bitrix\BIConnector\Analytics;

use Bitrix\Main\Analytics\AnalyticsEvent;

final class AnalyticsManager
{
	private const TOOL = 'BI_Builder';
	private const PERMISSIONS_CATEGORY = 'permissions';

	public const GROUP_GRID_SECTION = 'grid';
	public const GROUP_PERMISSION_SECTION = 'permissions_slider';

	private const PERMISSIONS_EDIT_ACTION = 'permissions_edited';

	static function sendSavePermissionsAnalytics(string $source): void
	{
		$event = new AnalyticsEvent(
			self::PERMISSIONS_EDIT_ACTION,
			self::TOOL,
			self::PERMISSIONS_CATEGORY
		);

		$event
			->setSubSection($source)
			->send()
		;
	}
}
