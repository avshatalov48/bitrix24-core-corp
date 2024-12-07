<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class EditTagAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'edit';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DASHBOARD_GRID_TAG_ACTION_RENAME') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$tagId = (int)$rawFields['ID'];
		$this->onclick = "BX.BIConnector.SupersetDashboardTagGridManager.Instance.renameTag({$tagId}, 'grid_menu')";

		return parent::getControl($rawFields);
	}
}