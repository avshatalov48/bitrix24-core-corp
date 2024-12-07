<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class OpenAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'open';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_OPEN') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$dashboardId = $rawFields['ID'];
		if (!$dashboardId)
		{
			return parent::getControl($rawFields);
		}

		if (!empty($rawFields['HAS_ZONE_URL_PARAMS']))
		{
			return null;
		}

		$this->href = $rawFields['DETAIL_URL'];

		return parent::getControl($rawFields);
	}
}
