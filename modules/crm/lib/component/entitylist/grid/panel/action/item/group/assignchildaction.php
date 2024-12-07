<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class AssignChildAction extends GroupChildAction
{
	public function __construct(private int $entityTypeId)
	{
	}

	public static function getId(): string
	{
		return 'assign';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_ASSIGN');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		// assignment done on frontend via crm.autorun
		return null;
	}

	protected function getOnchange(): Onchange
	{
		$onchange = new Onchange();

		$hiddenInputId = 'action_assigned_by_id';

		$onchange->addAction([
			'ACTION' => Actions::SHOW,
			'DATA' => [
				['ID' => \Bitrix\Main\Grid\Panel\DefaultValue::FOR_ALL_CHECKBOX_ID],
			],
		]);

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::HIDDEN,
					'ID' => $hiddenInputId,
					'NAME' => 'ACTION_ASSIGNED_BY_ID'
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeAssigment'))
											->addEntityTypeId($this->entityTypeId)
											->addValueElementId($hiddenInputId)
											->buildJsCallback()
									,
								]
							],
						]
					]
				]),
			]
		]);

		$onchange->addAction([
			'ACTION' => Actions::CALLBACK,
			'DATA' => [
				[
					'JS' =>
						(new Event('renderUserTagSelector'))
							->addParam('targetElementId', $hiddenInputId)
							->buildJsCallback()
					,
				]
			]
		]);

		return $onchange;
	}
}
