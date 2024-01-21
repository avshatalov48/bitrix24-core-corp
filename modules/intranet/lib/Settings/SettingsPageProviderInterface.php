<?php

namespace Bitrix\Intranet\Settings;


interface SettingsPageProviderInterface
{
	public function getType(): string;

	public function getSort(): int;

	public function getTitle(): string;

	public function getDataManager(array $data = []): SettingsInterface;
}