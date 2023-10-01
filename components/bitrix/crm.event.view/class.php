<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Service\EventHistory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('crm');

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

final class CrmEventViewComponent extends \Bitrix\Crm\Component\Base
{
	protected const FILTER_VALUE_RELATIONS = 'relations';

	protected function compileEventDesc(array $event): string
	{
		if (mb_strlen($event['EVENT_TEXT_1']) > 255 || mb_strlen($event['EVENT_TEXT_2']) > 255)
		{
			$desc =
				'<div id="event_desc_short_'
				. (int)$event['ID']
				. '"><a href="#more" onclick="crm_event_desc('
				. (int)$event['ID']
				. '); return false;">'
				. GetMessage('CRM_EVENT_DESC_MORE').'</a></div>'
			;

			$desc .=
				'<div id="event_desc_full_'
				. (int)$event['ID']
				. '" style="display: none"><b>'
				. GetMessage('CRM_EVENT_DESC_BEFORE')
				. '</b>:<br>'
				. ($event['EVENT_TEXT_1'])
				. '<br><br><b>'
				. GetMessage('CRM_EVENT_DESC_AFTER')
				. '</b>:<br>'
				. ($event['EVENT_TEXT_2'])
				. '</div>'
			;
		}
		elseif ($event['EVENT_TEXT_1'] !== '' && $event['EVENT_TEXT_2'] !== '')
		{
			$desc = ($event['EVENT_TEXT_1']) . ' <span>&rarr;</span> ' . ($event['EVENT_TEXT_2']);
		}
		else
		{
			$desc = !empty($event['EVENT_TEXT_1']) ? ($event['EVENT_TEXT_1']) : '';
		}

		return nl2br($desc);
	}

	protected function enrichRelationEvents(array $events): array
	{
		$eventRelationIdToBoundItemInfo = $this->prepareBoundItemsInfo($events);

		foreach ($events as &$singleEvent)
		{
			if ($this->isRelationEvent($singleEvent))
			{
				$singleEvent = $this->enrichSingleEvent(
					$singleEvent,
					$eventRelationIdToBoundItemInfo[$this->extractEventRelationId($singleEvent)] ?? null
				);
			}
		}

		return $events;
	}

	private function prepareBoundItemsInfo(array $events): array
	{
		$eventRelationIdToBoundItem = [];
		$allEventRelationIds = [];

		$eventIdsToFetch = [];
		foreach ($events as $singleEvent)
		{
			if ($this->isRelationEvent($singleEvent))
			{
				$eventRelationId = $this->extractEventRelationId($singleEvent);
				$allEventRelationIds[$eventRelationId] = $eventRelationId;

				$boundEntityTypeId = $this->extractBoundEntityTypeId($singleEvent);
				$boundEntityId = $this->extractBoundEntityId($singleEvent);

				if (\CCrmOwnerType::IsDefined($boundEntityTypeId) && $boundEntityId > 0)
				{
					$eventRelationIdToBoundItem[$eventRelationId] = [$boundEntityTypeId, $boundEntityId];
				}
				else
				{
					$eventId = $this->extractEventId($singleEvent);

					$eventIdsToFetch[$eventId] = $eventId;
				}
			}
		}

		if (!empty($eventIdsToFetch))
		{
			$eventsObjects = \Bitrix\Crm\EventTable::query()
				->setSelect(['ID', 'EVENT_RELATION.ID', 'EVENT_RELATION.ENTITY_TYPE', 'EVENT_RELATION.ENTITY_ID'])
				->whereIn('ID', $eventIdsToFetch)
				->fetchCollection()
			;

			foreach ($eventsObjects as $eventObject)
			{
				$relations = $eventObject->requireEventRelation()->getAll();
				if (count($relations) < 2)
				{
					continue;
				}

				[$leftRelation, $rightRelation] = array_values($relations);

				if (
					isset($allEventRelationIds[$leftRelation->getId()])
					&& !isset($eventRelationIdToBoundItem[$leftRelation->getId()])
				)
				{
					$eventRelationIdToBoundItem[$leftRelation->getId()] = [
						\CCrmOwnerType::ResolveID($rightRelation->requireEntityType()),
						$rightRelation->requireEntityId(),
					];
				}

				if (
					isset($allEventRelationIds[$rightRelation->getId()])
					&& !isset($eventRelationIdToBoundItem[$rightRelation->getId()])
				)
				{
					$eventRelationIdToBoundItem[$rightRelation->getId()] = [
						\CCrmOwnerType::ResolveID($leftRelation->requireEntityType()),
						$leftRelation->requireEntityId(),
					];
				}
			}
		}

		$infos = [];
		foreach ($eventRelationIdToBoundItem as [$boundEntityTypeId, $boundItemId])
		{
			$infos[$boundEntityTypeId][$boundItemId] = [];
		}

		foreach ($infos as $boundEntityTypeId => &$multipleEntitiesInfo)
		{
			\CCrmOwnerType::PrepareEntityInfoBatch(
				$boundEntityTypeId,
				$multipleEntitiesInfo,
			);
		}
		unset($multipleEntitiesInfo);

		$result = [];
		foreach ($eventRelationIdToBoundItem as $eventRelationId => [$boundEntityTypeId, $boundItemId])
		{
			$result[$eventRelationId] = $infos[$boundEntityTypeId][$boundItemId] ?? null;
		}

		return $result;
	}

	private function enrichSingleEvent(array $singleEvent, ?array $boundItemInfo): array
	{
		$singleEvent['EVENT_TEXT_1'] = '';
		$singleEvent['EVENT_TEXT_2'] = '';

		if ($boundItemInfo)
		{
			$singleEvent['EVENT_TEXT_1'] = $boundItemInfo['ENTITY_TYPE_CAPTION'] ?? '';
			$isMyCompany = $boundItemInfo['IS_MY_COMPANY'] ?? false;
			if ($isMyCompany)
			{
				$singleEvent['EVENT_TEXT_1'] = Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID');
			}

			if (!empty($boundItemInfo['SHOW_URL']) && !empty($boundItemInfo['TITLE']))
			{
				$singleEvent['EVENT_TEXT_1'] .=
					' <a href="' . htmlspecialcharsbx($boundItemInfo['SHOW_URL']) . '">'
					. htmlspecialcharsbx($boundItemInfo['TITLE'])
					. '</a>'
				;
			}
		}

		$singleEvent['EVENT_DESC'] = $this->compileEventDesc($singleEvent);

		return $singleEvent;
	}

	private function isRelationEvent(array $event): bool
	{
		return (
			isset($event['EVENT_TYPE'])
			&& (
				(int)$event['EVENT_TYPE'] === EventHistory::EVENT_TYPE_LINK
				|| (int)$event['EVENT_TYPE'] === EventHistory::EVENT_TYPE_UNLINK
			)
		);
	}

	private function extractBoundEntityTypeId(array $event): int
	{
		return \CCrmOwnerType::ResolveID($event['~EVENT_TEXT_1'] ?? '');
	}

	private function extractBoundEntityId(array $event): int
	{
		return (int)($event['~EVENT_TEXT_2'] ?? 0);
	}

	/**
	 * Returns ID of a row in b_crm_event table
	 *
	 * @param array $event
	 * @return int
	 */
	private function extractEventId(array $event): int
	{
		return (int)($event['EVENT_REL_ID'] ?? 0);
	}

	/**
	 * Returns ID of a row in b_crm_event_relation table
	 *
	 * @param array $event
	 * @return int
	 */
	private function extractEventRelationId(array $event): int
	{
		return (int)($event['ID'] ?? 0);
	}
}
