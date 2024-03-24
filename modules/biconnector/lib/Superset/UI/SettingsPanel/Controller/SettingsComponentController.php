<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller;

final class SettingsComponentController extends EntityEditorController
{
	private const CONTROLLER_TYPE_NAME = 'settingComponentController';

	protected function getType(): string
	{
		return self::CONTROLLER_TYPE_NAME;
	}
}
