<?php

namespace Bitrix\Intranet\Portal\Settings;

use Bitrix\Intranet\Contract\OptionContract;

abstract class BaseSettings
{
	public function __construct(
		private readonly OptionContract $option
	)
	{}

	abstract protected function getSettingId(): string;

	public function canCurrentUserEdit(): bool
	{
		return \Bitrix\Intranet\CurrentUser::get()->isAdmin();
	}

	protected function getOption(?string $settingId = null): string
	{
		$settingId ??= $this->getSettingId();

		return (string)$this->option->get($settingId, '');
	}

	protected function setOption(string $value, ?string $settingId = null): void
	{
		if ($this->canCurrentUserEdit())
		{
			$settingId ??= $this->getSettingId();
			$value = trim($value);
			$this->option->set($settingId, $value);
		}
	}

	protected function getDefaultOption(): string
	{
		return '';
	}
}