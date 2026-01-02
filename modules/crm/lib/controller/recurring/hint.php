<?php

namespace Bitrix\Crm\Controller\Recurring;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\DealRecurTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Crm\Recurring\Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Localization\Loc;

class Hint extends \Bitrix\Crm\Controller\Base
{
	public function getAction(ItemIdentifier $itemIdentifier): array
	{
		$entityTypeId = $itemIdentifier->getEntityTypeId();
		$entityId = $itemIdentifier->getEntityId();

		if (Container::getInstance()->getUserPermissions()->item()->canRead($entityTypeId, $entityId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return [];
		}

		if (!Manager::isAvailableEntityTypeId($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError());

			return [];
		}

		$entityId = $itemIdentifier->getEntityId();
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$hint = $this->getDealHint($entityId);
		}
		else
		{
			$hint = $this->getDynamicHint($entityTypeId, $entityId);
		}

		return [
			'hint' => $hint,
		];
	}

	private function getDealHint(int $dealId): string
	{
		$dealDataRaw = DealRecurTable::getlist(
			[
				'filter' => [
					'LOGIC' => 'OR',
					[
						'=DEAL_ID' => $dealId,
						'!=NEXT_EXECUTION' => null,
					],
					'=BASED_ID' => $dealId,
				],
				'select' => ['NEXT_EXECUTION', 'DEAL_ID', 'BASED_ID'],
			],
		);

		$dealIdList = [];
		while ($dealData = $dealDataRaw->fetch())
		{
			if ((int)$dealData['DEAL_ID'] === $dealId)
			{
				$hint = Loc::getMessage(
					'CRM_RECURRING_HINT_NEXT_EXECUTION_DEAL_HINT',
					['#DATE_EXECUTION#' => $dealData['NEXT_EXECUTION']],
				);

				break;
			}

			if ((int)$dealData['BASED_ID'] === $dealId)
			{
				$dealIdList[] = $dealData['DEAL_ID'];
			}
		}

		if (!empty($dealIdList))
		{
			if (count($dealIdList) === 1)
			{
				$hint = GetMessage('NEXT_BASED_ON_DEAL_ONCE', ['#ID#' => $dealIdList[0]]);
			}
			else
			{
				$idLine = '';
				foreach ($dealIdList as $id)
				{
					$idLine .= Loc::getMessage('CRM_RECURRING_HINT_SIGN_NUM_WITH_DEAL_ID', ['#DEAL_ID#' => $id]) . ', ';
				}
				$idLine = mb_substr($idLine, 0, -2);
				$hint = Loc::getMessage('CRM_RECURRING_HINT_NEXT_BASED_ON_DEAL_MULTI', ['#ID_LINE#' => $idLine]);
			}
		}

		if (empty($hint))
		{
			$hint = Loc::getMessage('CRM_RECURRING_HINT_NEXT_DEAL_EMPTY');
		}

		return $hint;
	}

	private function getDynamicHint(int $entityTypeId, int $entityId): string
	{
		$dataRaw = RecurringTable::getlist([
			'filter' => [
				'LOGIC' => 'OR',
				[
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ITEM_ID' => $entityId,
					'!=NEXT_EXECUTION' => null,
				],
				'=BASED_ID' => $entityId,
			],
			'select' => ['NEXT_EXECUTION', 'ITEM_ID', 'BASED_ID'],
		]);

		while ($dataItem = $dataRaw->fetch())
		{
			if ((int)$dataItem['ITEM_ID'] === $entityId)
			{
				$hint = Loc::getMessage(
					'CRM_RECURRING_HINT_NEXT_EXECUTION_ITEM_HINT',
					["#DATE_EXECUTION#" => $dataItem['NEXT_EXECUTION']],
				);

				break;
			}

			if ((int)$dataItem['BASED_ID'] === $entityId)
			{
				$itemIdList[] = $dataItem['ITEM_ID'];
			}
		}

		if (!empty($itemIdList))
		{
			if (count($itemIdList) === 1)
			{
				$hint = Loc::getMessage('CRM_RECURRING_HINT_NEXT_BASED_ON_ITEM_ONCE', ['#ID#' => $itemIdList[0]]);
			}
			else
			{
				$idLine = '';
				foreach ($itemIdList as $id)
				{
					$idLine .= Loc::getMessage('CRM_RECURRING_HINT_SIGN_NUM_WITH_ITEM_ID', ['#ITEM_ID#' => $id]) . ', ';
				}
				$idLine = mb_substr($idLine, 0, -2);
				$hint = Loc::getMessage('CRM_RECURRING_HINT_NEXT_BASED_ON_ITEM_MULTI', ['#ID_LINE#' => $idLine]);
			}
		}

		if (empty($hint))
		{
			$hint = Loc::getMessage('CRM_RECURRING_HINT_NEXT_ITEM_EMPTY');
		}

		return $hint;
	}

	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}
}
