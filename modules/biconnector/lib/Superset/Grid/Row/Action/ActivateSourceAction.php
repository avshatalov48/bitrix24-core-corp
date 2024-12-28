<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

final class ActivateSourceAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'activate';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_SOURCE_GRID_ACTION_ACTIVATE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$id = (int)$rawFields['ID'];
		$moduleId = CUtil::JSEscape($rawFields['MODULE']);

		if ($rawFields['ACTIVE'] === 'N')
		{
			$this->onclick = "BX.BIConnector.ExternalSourceManager.Instance.changeActivitySource({$id}, '{$moduleId}')";

			return parent::getControl($rawFields);
		}

		return null;
	}
}
