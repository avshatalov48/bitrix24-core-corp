<?php

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Integration\Sale\Payment\LocHelper;
use Bitrix\CrmMobile\Terminal\ListSearchPreset;
use Bitrix\Main\Localization\Loc;

LocHelper::loadMessages();

class GetSearchDataAction extends Action
{
	public function run(string $entityTypeName, ?int $categoryId = null): array
	{
		return [
			'presets' => [

				[
					'default' => true,
					'id' => ListSearchPreset::FILTER_MY,
					'name' => Loc::getMessage('M_CRM_TL_MY_PAYMENTS_PRESET'),
				],
				[
					'default' => false,
					'id' => ListSearchPreset::FILTER_ALL,
					'name' => Loc::getMessage('M_CRM_TL_ALL_PAYMENTS_PRESET'),
				],
			],
		];
	}
}
