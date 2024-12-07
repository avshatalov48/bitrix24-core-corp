<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class DeleteTagAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'delete';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_TAG_ACTION_DELETE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$tagId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardTagGridManager.Instance.deleteTag({$tagId})";

		return parent::getControl($rawFields);
	}
}
