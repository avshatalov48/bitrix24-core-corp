<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action\UnusedElements;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DeleteAction extends BaseAction
{
	public static function getId(): ?string
	{
		return 'delete';
	}

	public function getControl(array $rawFields): ?array
	{
		$owners = $rawFields['OWNERS'];
		if (!in_array((int)CurrentUser::get()->getId(), $owners, true))
		{
			$deleteAllElementsPermission = AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DELETE_ALL_UNUSED_ELEMENTS);
			if (!$deleteAllElementsPermission)
			{
				return null;
			}
		}

		$id = (int)$rawFields['EXTERNAL_ID'];
		$type = (string)$rawFields['TYPE'];

		$this->onclick = "BX.BIConnector.UnusedElementsGridManager.Instance.deleteElement([{elementId: {$id}, elementType: '{$type}'}])";

		return parent::getControl($rawFields);
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_ROW_ACTION_DELETE') ?? '';
	}
}
