<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

class CCrmTrackingEntityPathComponent extends CBitrixComponent
{
	public function getEntityPath($entityTypeId, $entityId)
	{
		static $path = [];
		static $lastEntityTypeId;
		static $lastEntityId;

		if ($lastEntityTypeId == $entityTypeId && $lastEntityId == $entityId)
		{
			return $path;
		}

		$path = [];
		$lastEntityId = $entityId;
		$lastEntityTypeId = $entityTypeId;

		$row = Tracking\Internals\TraceEntityTable::getRowByEntity($entityTypeId, $entityId);
		if (!$row)
		{
			return $path;
		}

		static $actualSources = null;
		if ($actualSources === null)
		{
			$actualSources = Tracking\Provider::getActualSources();
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
		}

		$trace = Tracking\Internals\TraceTable::getRow([
			'select' => ['SOURCE_ID'],
			'filter' => ['=ID' => $row['TRACE_ID']]
		]);
		if ($trace && $trace['SOURCE_ID'] && isset($actualSources[$trace['SOURCE_ID']]))
		{
			$source = $actualSources[$trace['SOURCE_ID']];
			$path[] = [
				'NAME' => $source['NAME'],
				'DESC' => $source['DESCRIPTION'],
				'ICON' => $source['ICON_CLASS'],
				'ICON_COLOR' => $source['ICON_COLOR'],
				'IS_SOURCE' => true,
			];
		}


		$collection = Tracking\Channel\Factory::createCollection($row['TRACE_ID']);
		foreach ($collection as $channel)
		{
			/** @var Tracking\Channel\Base $channel */
			$path[] = [
				'NAME' => $channel->getName(),
				'DESC' => $channel->getDescription(),
				'ICON' => null,
				'ICON_COLOR' => null,
				'IS_SOURCE' => false,
			];
		}

		return $path;
	}

	public function executeComponent()
	{
		if (!Loader::includeModule("crm"))
		{
			return;
		}

		$onlySourceIcon = isset($this->arParams['ONLY_SOURCE_ICON']) ? (bool) $this->arParams['ONLY_SOURCE_ICON'] : false;
		$entityTypeId = $this->arParams['ENTITY_TYPE_ID'];
		$entityId = $this->arParams['ENTITY_ID'];
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		$path = $this->getEntityPath($entityTypeId, $entityId);
		if ($onlySourceIcon)
		{
			$path = array_filter(
				$path,
				function ($item)
				{
					return $item['IS_SOURCE'];
				}
			);
		}
		$this->arResult['PATH'] = $path;

		$this->includeComponentTemplate();
	}
}