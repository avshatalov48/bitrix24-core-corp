<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\OperationsApi;

class OperationsApiInDeal extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('OPERATIONS_API_IN_DEAL_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return OperationsApi::getInstance();
	}

	public function isEnabled(): bool
	{
		return DealSettings::getCurrent()->isFactoryEnabled();
	}

	public function enable(): void
	{
		DealSettings::getCurrent()->setFactoryEnabled(true);
		$this->logEnabled();
	}

	public function disable(): void
	{
		DealSettings::getCurrent()->setFactoryEnabled(false);
		$this->logDisabled();
	}

	public function getSort(): int
	{
		return 2;
	}
}
