<?php

namespace Bitrix\Intranet\Settings;

interface SettingsSubPageProviderInterface extends SettingsPageProviderInterface
{
	public function getParentType(): string;

	/**
	 * @return array Which contains js-extensions
	 * [ 'my-module.ai-example-page', 'crm.integration.intranet.settings-page'];
	 */
	public function getJsExtensions(): array;
}