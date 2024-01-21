<?php

namespace Bitrix\BiConnector\Settings\Buttons;

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;

class Implementation extends Buttons\Button
{
	protected function getDefaultParameters(): array
	{
		return [
			'text' => Loc::getMessage('BICONNECTOR_SETTINGS_BUTTONS_IMPLEMENTATION_TITLE'),
			'color' => Buttons\Color::LIGHT_BORDER,
			'dataset' => [
				'toolbar-collapsed-icon' => Buttons\Icon::INFO,
			],
			'click' => new Buttons\JsCode(
				'top.BX.UI.InfoHelper.show(\'info_implementation_request\');'
			),
			'id' => 'order-implementation-button-id',
		];
	}
}
