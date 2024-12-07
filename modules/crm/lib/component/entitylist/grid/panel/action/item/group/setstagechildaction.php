<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SetStageChildAction extends GroupChildAction
{
	public function __construct(
		private Factory $factory,
		private ?int $categoryId = null,
	)
	{
		if (!$this->factory->isStagesEnabled())
		{
			throw new InvalidOperationException('SetStage action is not available for types without stages');
		}
	}

	public static function getId(): string
	{
		return 'set_stage';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_SET_STAGE');
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

		$dropdownContainerId = 'action_set_stage';
		$dropdownValueId = $dropdownContainerId . '_control';

		$stagesList = [];
		foreach ($this->factory->getStages($this->categoryId) as $stage)
		{
			// lead can move to the final stage only after conversion. there is a separate action for it in lead grid
			if (
				$this->factory->getEntityTypeId() === \CCrmOwnerType::Lead
				&& PhaseSemantics::isSuccess($stage->getSemantics())
			)
			{
				continue;
			}

			$stagesList[] = [
				'NAME' => $stage->getName(),
				'VALUE' => $stage->getStatusId(),
				'SEMANTICS' => $stage->getSemantics(),
			];
		}

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::DROPDOWN,
					'ID' => $dropdownContainerId,
					'NAME' => Item::FIELD_NAME_STAGE_ID,
					'MULTIPLE' => 'N',
					'ITEMS' => $stagesList,
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeSetStage'))
											->addEntityTypeId($this->factory->getEntityTypeId())
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
