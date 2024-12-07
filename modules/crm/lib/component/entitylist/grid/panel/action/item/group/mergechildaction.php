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
use Bitrix\Main\Web\Uri;

final class MergeChildAction extends GroupChildAction
{
	public function __construct(
		private readonly int $entityTypeId,
		private readonly ?Uri $mergerUrl = null,
	)
	{
	}

	public static function getId(): string
	{
		return 'merge';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_MERGE');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}

	protected function getOnchange(): Onchange
	{
		$onchange = new Onchange();

		$onchange->addAction([
			'ACTION' => Actions::HIDE,
			'DATA' => [
				['ID' => \Bitrix\Main\Grid\Panel\DefaultValue::FOR_ALL_CHECKBOX_ID],
			],
		]);

		$applyButton = (new Snippet())->getApplyButton([
			'ONCHANGE' => [
				[
					'ACTION' => Actions::CALLBACK,
					'DATA' => [
						[
							'JS' =>
								(new Event('BatchManager:executeMerge'))
									->addEntityTypeId($this->entityTypeId)
									->addParam('mergerUrl', $this->mergerUrl)
									->buildJsCallback()
							,
						]
					],
				]
			],
		]);
		$applyButton['SETTINGS']['minSelectedRows'] = 2;
		$applyButton['SETTINGS']['buttonId'] = $applyButton['ID'];

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				$applyButton
			],
		]);

		return $onchange;
	}
}
