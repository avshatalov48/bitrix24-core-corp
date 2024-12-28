<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;

class Filter extends Base
{
	public function configureActions(): array
	{
		return [
			'getSearchBarPresets' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getSearchBarPresetsAction(): array
	{
		return [
			'presets' => [
//				[
//					'id' => 'recent',
//					'name' => 'Недавно измененные',
//					'default' => true,
//					'value' => null,
//				],
//				[
//					'id' => 'public_link',
//					'name' => 'Публичные ссылки',
//					'default' => false,
//					'value' => null,
//				],
//				[
//					'id' => 'shared',
//					'name' => 'Поделились со мной',
//					'default' => false,
//					'value' => null,
//				],
//				[
//					'id' => 'shared_me',
//					'name' => 'Я поделился',
//					'default' => false,
//					'value' => null,
//				],
			],
		];
	}
}