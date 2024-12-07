<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class RefreshAccountingDataChildAction extends GroupChildAction
{
	public function __construct(private readonly int $entityTypeId)
	{
	}

	public static function isEntityTypeSupported(int $entityTypeId): bool
	{
		return in_array($entityTypeId, [\CCrmOwnerType::Lead, \CCrmOwnerType::Deal]);
	}

	public static function getId(): string
	{
		return 'refresh_accounting_data';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_REFRESH_ACCOUNTING_DATA');
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

		$callback =
			(new Event('BatchManager:executeRefreshAccountingData'))
				->addEntityTypeId($this->entityTypeId)
				->buildJsCallback()
		;

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' => $callback,
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
