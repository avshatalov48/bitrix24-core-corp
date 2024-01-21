<?php

namespace Bitrix\Crm\Controller\Integration;

use Bitrix\Crm\Controller\Base;
use Bitrix\Intranet\ContactCenter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class OpenLines extends Base
{
	public function getItemsAction(): ?array
	{
		if (!Loader::includeModule('intranet'))
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CIO_INTRANET_NOT_INSTALLED')
				)
			);

			return null;
		}

		$itemsList = (new ContactCenter())->imopenlinesGetItems()->getData();

		$result = [];
		foreach ($itemsList as $itemCode => $item)
		{
			$result[$itemCode] = [
				'selected' => $item['SELECTED'],
				'url' => $item['LINK'],
				'name' => $item['NAME'],
			];
		}

		return $result;
	}
}