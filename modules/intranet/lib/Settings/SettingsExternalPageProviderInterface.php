<?php

namespace Bitrix\Intranet\Settings;


interface SettingsExternalPageProviderInterface extends SettingsPageProviderInterface
{
	public function getJsExtensions(): array;
}