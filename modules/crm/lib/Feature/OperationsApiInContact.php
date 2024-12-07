<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\OperationsApi;

class OperationsApiInContact extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OPERATIONS_API_IN_CONTACT_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return OperationsApi::getInstance();
	}

	public function isEnabled(): bool
	{
		return ContactSettings::getCurrent()->isFactoryEnabled();
	}

	public function enable(): void
	{
		ContactSettings::getCurrent()->setFactoryEnabled(true);
	}

	public function disable(): void
	{
		ContactSettings::getCurrent()->setFactoryEnabled(false);
	}

	public function getSort(): int
	{
		return 3;
	}
}
