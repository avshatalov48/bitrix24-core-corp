<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\Dashboard;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class ShowGroupDetailPopupAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'show-group-detail-popup';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_SETTINGS') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		if (empty($rawFields['ENTITY_TYPE']) || $rawFields['ENTITY_TYPE'] !== DashboardGroupRepository::TYPE_GROUP)
		{
			return null;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_GROUP_MODIFY))
		{
			return null;
		}

		$id = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.showGroupSettingsPopup('G{$id}');";

		return parent::getControl($rawFields);
	}
}
