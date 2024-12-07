<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\RemoveAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class DeleteAction extends RemoveAction
{
	public function __construct(private int $entityTypeId)
	{
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		// deletion done on frontend via crm.autorun
		return null;
	}

	public function getControl(): ?array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$removeButton = new Button();
		$removeButton
			->setClass(DefaultValue::REMOVE_BUTTON_CLASS)
			->setId(DefaultValue::REMOVE_BUTTON_ID)
			->setText(Loc::getMessage('CRM_COMMON_ACTION_DELETE'))
			->setTitle(Loc::getMessage('CRM_COMMON_ACTION_DELETE'))
		;

		$onchange = new Onchange();
		$onchange->addAction([
			'ACTION' => Actions::CALLBACK,
			'DATA' => [
				[
					'JS' =>
						(new Event('BatchManager:executeDeletion'))
							->addEntityTypeId($this->entityTypeId)
							->buildJsCallback()
					,
				]
			]
		]);

		$removeButton->setOnchange($onchange);

		return $removeButton->toArray();
	}
}
