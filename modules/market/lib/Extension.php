<?php

namespace Bitrix\Market;

class Extension
{
	public static function getList(): array
	{
		return [
			'ui.fonts.opensans',
			'ui.textcrop',
			'ui.ears',
			'ui.forms',
			'ui.buttons.icons',
			'ui.buttons',
			'ui.hint',
			'ui.info-helper',
			'ui.icon-set.api.vue',
			'ui.icon-set.actions',
			'sidepanel',
			'loader',
			'access',
			'market.favorites',
			'market.market',
			'market.application',
		];
	}
}