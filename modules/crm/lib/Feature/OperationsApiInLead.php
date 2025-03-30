<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\OperationsApi;

class OperationsApiInLead extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OPERATIONS_API_IN_LEAD_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return OperationsApi::getInstance();
	}

	public function isEnabled(): bool
	{
		return LeadSettings::getCurrent()->isFactoryEnabled();
	}

	public function enable(): void
	{
		LeadSettings::getCurrent()->setFactoryEnabled(true);
		$this->logEnabled();
	}

	public function disable(): void
	{
		LeadSettings::getCurrent()->setFactoryEnabled(false);
		$this->logDisabled();
	}

	public function getSort(): int
	{
		return 1;
	}
}
