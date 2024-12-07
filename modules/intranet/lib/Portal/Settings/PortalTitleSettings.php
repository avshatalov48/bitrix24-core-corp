<?php

namespace Bitrix\Intranet\Portal\Settings;


use Bitrix\Intranet\Service\PortalSettings;
use Bitrix\Main\Context;

class PortalTitleSettings extends BaseSettings
{
	protected function getSettingId(): string
	{
		return 'site_title';
	}

	public function getTitle(): string
	{
		$title = $this->getOption();

		if (empty($title))
		{
			$title = $this->getDefaultOption();
		}

		return $title;
	}

	public function setTitle(string $value): void
	{
		$this->setOption($value);
	}

	protected function getDefaultOption(): string
	{
		$defaultTitle = PortalSettings::getInstance()->nameSettings()->getName();

		if (empty($defaultTitle))
		{
			$defaultTitle = Context::getCurrent()->getServer()->getServerName();
		}

		return $defaultTitle ?? '';
	}
}