<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ObserversChildAction extends GroupChildAction
{
	public function __construct(private readonly int $entityTypeId)
	{
	}

	public static function isEntityTypeSupported(Factory $factory): bool
	{
		return $factory->isObserversEnabled();
	}

	public static function isChangeObserverPermitted(int $entityTypeId, UserPermissions $userPermissions, ?int $categoryId = 0): bool
	{
		return $userPermissions->checkUpdatePermissions($entityTypeId, 0, $categoryId);
	}

	public static function getId(): string
	{
		return 'set_observer';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_OBSERVERS');
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
				['ID' => DefaultValue::FOR_ALL_CHECKBOX_ID],
			],
		]);

		$hiddenInputId = 'action_observers_by_id';

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::HIDDEN,
					'ID' => $hiddenInputId,
					'NAME' => 'ACTION_OBSERVERS_BY_ID'
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeObservers'))
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
						(new Event('renderUserTagMultipleSelector'))
							->addParam('targetElementId', $hiddenInputId)
							->addParam('multipleSelect', true)
							->buildJsCallback()
					,
				]
			]
		]);

		return $onchange;
	}
}