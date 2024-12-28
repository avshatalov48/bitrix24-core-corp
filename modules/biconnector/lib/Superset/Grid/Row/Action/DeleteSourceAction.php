<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

class DeleteSourceAction extends BaseAction
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
		return Loc::getMessage('DELETE_SOURCE_ACTION') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$id = (int)$rawFields['ID'];
		$moduleId = CUtil::JSEscape($rawFields['MODULE']);
		if ($moduleId !== 'BI')
		{
			return null;
		}

		$this->onclick = "BX.BIConnector.ExternalSourceManager.Instance.deleteSource({$id}, '{$moduleId}')";

		return parent::getControl($rawFields);
	}
}
