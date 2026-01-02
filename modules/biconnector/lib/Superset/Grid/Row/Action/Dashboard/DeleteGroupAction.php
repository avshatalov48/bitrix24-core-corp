<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\Dashboard;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class DeleteGroupAction extends BaseAction
{
	public static function getId(): ?string
	{
		return 'deleteGroup';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_ACTION_DELETE') ?? '';
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

		if (empty($rawFields['TYPE']) || $rawFields['TYPE'] === SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM)
		{
			return null;
		}

		$groupId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardGridManager.Instance.deleteGroup({$groupId})";

		return parent::getControl($rawFields);
	}
}
