<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

class CCrmTrackingEntityPathComponent extends CBitrixComponent
{
	public function getEntityPaths($entityTypeId, $entityId)
	{
		static $paths = [];
		static $lastEntityTypeId;
		static $lastEntityId;

		if ($lastEntityTypeId == $entityTypeId && $lastEntityId == $entityId)
		{
			return $paths;
		}

		$lastEntityId = $entityId;
		$lastEntityTypeId = $entityTypeId;

		$paths = Tracking\Entity::getPaths(
			$entityTypeId,
			$entityId,
			$this->arParams['LIMIT'] ?: 4
		);

		return $paths;
	}

	public function executeComponent()
	{
		if (!Loader::includeModule("crm"))
		{
			return;
		}

		$this->arParams['TRACE_ID'] = isset($this->arParams['TRACE_ID']) ? (int) $this->arParams['TRACE_ID'] : null;
		$this->arParams['LIMIT'] = isset($this->arParams['LIMIT']) ? (int) $this->arParams['LIMIT'] : false;
		$this->arParams['ONLY_SOURCE_ICON'] = isset($this->arParams['ONLY_SOURCE_ICON']) ? (bool) $this->arParams['ONLY_SOURCE_ICON'] : false;
		$entityTypeId = (int) $this->arParams['ENTITY_TYPE_ID'];
		$entityId = (int) $this->arParams['ENTITY_ID'];
		if (!$entityTypeId || !$entityId)
		{
			return;
		}

		$paths = $this->getEntityPaths($entityTypeId, $entityId);

		if ($this->arParams['TRACE_ID'])
		{
			$traceId = $this->arParams['TRACE_ID'];
			$pathsById = array_combine(
				array_column($paths, 'TRACE_ID'),
				array_values($paths)
			);
			$paths = isset($pathsById[$traceId]) ? [$pathsById[$traceId]] : [];
		}

		if (empty($paths))
		{
			return;
		}

		$paths = array_column($paths, 'LIST');
		if ($this->arParams['ONLY_SOURCE_ICON'])
		{
			foreach ($paths as $index => $path)
			{
				$path = array_filter(
					$path,
					function ($item)
					{
						return $item['IS_SOURCE'] && $item['SOURCE_ID'];
					}
				);
				if (empty($path))
				{
					unset($paths[$index]);
					continue;
				}
				$paths[$index] = $path;
			}
			if (empty($paths))
			{
				return;
			}
		}

		if ($this->arParams['ONLY_SOURCE_ICON'])
		{
			$paths = [current($paths)];
		}

		$this->arResult['PATHS'] = $paths;

		$this->includeComponentTemplate();
	}
}