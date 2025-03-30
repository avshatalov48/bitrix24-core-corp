<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\OperationsApi;

class OperationsApiInCompany extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OPERATIONS_API_IN_COMPANY_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return OperationsApi::getInstance();
	}

	public function isEnabled(): bool
	{
		return CompanySettings::getCurrent()->isFactoryEnabled();
	}

	public function enable(): void
	{
		CompanySettings::getCurrent()->setFactoryEnabled(true);
		$this->logEnabled();
	}

	public function disable(): void
	{
		CompanySettings::getCurrent()->setFactoryEnabled(false);
		$this->logDisabled();
	}

	public function getSort(): int
	{
		return 4;
	}
}
