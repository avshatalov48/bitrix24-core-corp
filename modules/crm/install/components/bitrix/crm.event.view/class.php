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

	private \Bitrix\Crm\Service\Router $router;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();
	}

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
		$boundEntitiesMap = $this->extractBoundEntitiesMap($events);

		if (empty($boundEntitiesMap))
		{
			return $events;
		}

		foreach ($boundEntitiesMap as $entityTypeId => &$multipleEntitiesInfo)
		{
			\CCrmOwnerType::PrepareEntityInfoBatch(
				$entityTypeId,
				$multipleEntitiesInfo,
			);
		}
		unset($multipleEntitiesInfo);

		foreach ($events as &$singleEvent)
		{
			if ($this->isRelationEvent($singleEvent))
			{
				$singleEvent = $this->enrichSingleEvent($singleEvent, $boundEntitiesMap);
			}
		}

		return $events;
	}

	private function extractBoundEntitiesMap(array $events): array
	{
		/** @var Array<int, int[]> $boundItemsFetchMap entityTypeId => itemId[] */
		$boundItemsFetchMap = [];

		foreach ($events as $singleEvent)
		{
			if ($this->isRelationEvent($singleEvent))
			{
				$boundEntityTypeId = $this->extractBoundEntityTypeId($singleEvent);
				$boundEntityId = $this->extractBoundEntityId($singleEvent);
				if (\CCrmOwnerType::IsDefined($boundEntityTypeId) && $boundEntityId > 0)
				{
					$boundItemsFetchMap[$boundEntityTypeId][$boundEntityId] = [];
				}
			}
		}

		return $boundItemsFetchMap;
	}

	private function enrichSingleEvent(array $singleEvent, array $boundEntitiesMap): array
	{
		$singleEvent['EVENT_TEXT_1'] = '';
		$singleEvent['EVENT_TEXT_2'] = '';

		$boundEntityTypeId = $this->extractBoundEntityTypeId($singleEvent);
		$boundEntityId = $this->extractBoundEntityId($singleEvent);

		$entityInfo = $boundEntitiesMap[$boundEntityTypeId][$boundEntityId] ?? null;

		if ($entityInfo)
		{
			$singleEvent['EVENT_TEXT_1'] = $entityInfo['ENTITY_TYPE_CAPTION'] ?? '';
			if ($boundEntityTypeId === \CCrmOwnerType::Company)
			{
				$isMyCompany = $entityInfo['IS_MY_COMPANY'] ?? false;
				if ($isMyCompany)
				{
					$singleEvent['EVENT_TEXT_1'] = Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID');
				}
			}

			$url = $this->router->getItemDetailUrl($boundEntityTypeId, $boundEntityId);
			if ($url && !empty($entityInfo['TITLE']))
			{
				$singleEvent['EVENT_TEXT_1'] .= " <a href=\"{$url}\">" . htmlspecialcharsbx($entityInfo['TITLE']) . '</a>';
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
}
