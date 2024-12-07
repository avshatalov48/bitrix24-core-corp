<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SetOpenedChildAction extends GroupChildAction
{
	public function __construct(private int $entityTypeId)
	{
	}

	public static function getId(): string
	{
		return 'set_opened';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_SET_OPENED');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		// action is done on frontend via crm.autorun
		return null;
	}

	protected function getOnchange(): Onchange
	{
		$onchange = new Onchange();

		$onchange->addAction([
			'ACTION' => Actions::SHOW,
			'DATA' => [
				['ID' => \Bitrix\Main\Grid\Panel\DefaultValue::FOR_ALL_CHECKBOX_ID],
			],
		]);

		$dropdownContainerId = 'action_set_opened';
		$dropdownValueId = $dropdownContainerId . '_control';

		Container::getInstance()->getLocalization()->loadMessages();

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::DROPDOWN,
					'ID' => $dropdownContainerId,
					'NAME' => Item::FIELD_NAME_OPENED,
					'MULTIPLE' => 'N',
					'ITEMS' => [
						[
							'NAME' => Loc::getMessage('CRM_COMMON_YES'),
							'VALUE' => 'Y'
						],
						[
							'NAME' => Loc::getMessage('CRM_COMMON_NO'),
							'VALUE' => 'N'
						],
					],
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeSetOpened'))
											->addEntityTypeId($this->entityTypeId)
											->addValueElementId($dropdownValueId)
											->buildJsCallback()
									,
								]
							],
						]
					]
				]),
			]
		]);

		return $onchange;
	}
}
