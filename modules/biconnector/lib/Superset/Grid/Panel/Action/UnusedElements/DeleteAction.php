<?php

namespace Bitrix\BIConnector\Superset\Grid\Panel\Action\UnusedElements;

use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DeleteAction implements Panel\Action\Action
{
	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}

	public static function getId(): string
	{
		return 'delete';
	}

	public function getControl(): ?array
	{
		$onchange = new Panel\Snippet\Onchange();
		$onchange->addAction([
			'ACTION' => Panel\Actions::CALLBACK,
			'DATA' => [
				[
					'JS' => 'BX.BIConnector.UnusedElementsGridManager.Instance.deleteSelectedElements();',
				],
			],
		]);
		$deleteButton = new Panel\Snippet\Button();
		$deleteButton->setClass(Panel\DefaultValue::REMOVE_BUTTON_CLASS);
		$deleteButton->setId(Panel\DefaultValue::REMOVE_BUTTON_ID);
		$deleteButton->setOnchange($onchange);
		$deleteButton->setText(Loc::getMessage('BI_UNUSED_ELEMENTS_GRID_BULK_DELETE_BUTTON'));

		return $deleteButton->toArray();
	}
}
