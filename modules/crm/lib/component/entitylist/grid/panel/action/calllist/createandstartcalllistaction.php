<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\CallList;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Action;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class CreateAndStartCallListAction implements Action
{
	public function __construct(private int $entityTypeId)
	{
	}

	public static function getId(): string
	{
		return 'create-and-start-call-list';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}

	public function getControl(): ?array
	{
		$button = new Button();
		$button
			->setId(self::getId())
			->setText(Loc::getMessage('CRM_GRID_PANEL_ACTION_CALL_LIST_CREATE_AND_START'))
			->setTitle(Loc::getMessage('CRM_GRID_PANEL_ACTION_CALL_LIST_CREATE_AND_START'))
		;

		$onchange = new Onchange();
		$onchange->addAction([
			'ACTION' => Actions::CALLBACK,
			'DATA' => [
				[
					'JS' =>
						(new Event('CallList:createAndStartCallList'))
							->addEntityTypeId($this->entityTypeId)
							->buildJsCallback()
					,
				]
			]
		]);

		$button->setOnchange($onchange);

		return $button->toArray();
	}
}
