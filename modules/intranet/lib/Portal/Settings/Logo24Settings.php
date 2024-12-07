<?php

namespace Bitrix\Intranet\Portal\Settings;

class Logo24Settings extends BaseSettings
{
	protected function getSettingId(): string
	{
		return 'logo24show';
	}

	public function getLogo24(): string
	{
		return $this->getOption() === 'N' ? '' : '24';
	}

	public function setLogo24(string $value): void
	{
		if (empty($value) || $value === 'N')
		{
			$this->setOption('N');
		}
		else
		{
			$this->setOption('Y');
		}
	}

	protected function getDefaultOption(): string
	{
		return 'Y';
	}
}