<?php

namespace Bitrix\Intranet\Portal\Settings;

class LogoSettings extends BaseSettings
{
	private const LOGO_RETINA_OPTION_ID = 'client_logo_retina';

	protected function getSettingId(): string
	{
		return 'client_logo';
	}

	public function getLogoId(): int
	{
		return (int)$this->getOption();
	}

	public function getLogoRetinaId(): int
	{
		return (int)$this->getOption(self::LOGO_RETINA_OPTION_ID);
	}

	public function setLogoId(int $value): void
	{
		$this->setOption($value);
	}

	public function setLogoRetinaId(int $value): void
	{
		$this->setOption($value, self::LOGO_RETINA_OPTION_ID);
	}
}