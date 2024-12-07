<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

use Bitrix\Catalog\Config\State;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class CrmProduct extends Base
{
	protected const TYPE = 'crm-product';

	public function __construct(string $id, string $title = null)
	{
		parent::__construct($id, $title);
		$this->setPayload([
			'isExternalCatalog' => Loader::includeModule('catalog') && State::isExternalCatalog(),
		]);
	}

	protected function getDefaultTitle(): string
	{
		return Loc::getMessage('M_UI_TAB_CRM_PRODUCT_DEFAULT_TITLE');
	}
}
