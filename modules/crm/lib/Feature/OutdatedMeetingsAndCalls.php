<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Activities;

class OutdatedMeetingsAndCalls extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OUTDATED_MEETINGS_AND_CALLS_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Activities::getInstance();
	}

	public function allowSwitchBySecretLink(): bool
	{
		return false;
	}

	protected function getOptionName(): string
	{
		return 'use_outdated_calendar_activities';
	}
}
