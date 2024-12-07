<?php

namespace Bitrix\Intranet\Portal\Settings;

class PortalNameSettings extends BaseSettings
{
	protected function getSettingId(): string
	{
		return 'site_name';
	}

	public function setName(string $value): void
	{
		$this->setOption($value);
	}

	public function getName(): string
	{
		return $this->getOption();
	}
}