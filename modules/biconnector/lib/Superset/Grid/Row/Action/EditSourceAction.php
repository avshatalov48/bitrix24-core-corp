<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

final class EditSourceAction extends BaseAction
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
		return Loc::getMessage('BICONNECTOR_SOURCE_GRID_ACTION_EDIT') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$id = (int)$rawFields['ID'];
		$moduleId = CUtil::JSEscape($rawFields['MODULE']);

		$this->onclick = "BX.BIConnector.ExternalSourceManager.Instance.openSourceDetail({$id}, '{$moduleId}')";

		return parent::getControl($rawFields);
	}
}