<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller;

final class IconController extends EntityEditorController
{
	private const CONTROLLER_TYPE_NAME = 'iconController';

	protected function getType(): string
	{
		return self::CONTROLLER_TYPE_NAME;
	}
}
