<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Sender\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loader::requireModule('sender');

final class AddItemsToSegmentChildAction extends GroupChildAction
{
	public function __construct(private int $entityTypeId)
	{
	}

	public static function getId(): string
	{
		return 'sender_segment_add';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_SENDER_ADD_ITEMS_TO_SEGMENT');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
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

		$dropdownContainerId = 'action_sender_segment_add';
		$dropdownValueId = $dropdownContainerId . '_control';

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::DROPDOWN,
					'ID' => $dropdownContainerId,
					'NAME' => 'SENDER_SEGMENT_ID',
					'ITEMS' => \Bitrix\Crm\Integration\Sender\GridPanel::getSegmentsDropdownItems()
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('Sender:addItemsToSegment'))
											->addEntityTypeId($this->entityTypeId)
											->addValueElementId($dropdownValueId)
											->buildJsCallback()
									,
								]
							],
						]
					],
				]),
			]
		]);

		return $onchange;
	}
}
