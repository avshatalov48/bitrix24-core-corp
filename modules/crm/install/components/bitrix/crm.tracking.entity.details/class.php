<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

class CCrmTrackingEntityDetailsComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if (!Loader::includeModule("crm"))
		{
			return;
		}

		if (!Tracking\Manager::isAccessible())
		{
			return;
		}

		if (empty($this->arParams['SHOW_FIELD']))
		{
			$this->showTrackingBanner();
		}
		else
		{
			$this->showTrackingField();
		}
	}

	public function showTrackingBanner()
	{
		$this->arResult['USER_OPTION_NAME'] = 'tracking';
		$this->arResult['USER_OPTION_KEY_NAME'] = $key = 'hide-banner';
		$options = \CUserOptions::GetOption('crm', $this->arResult['USER_OPTION_NAME'], array());
		if (!empty($options[$key]) && $options[$key] === 'Y')
		{
			return;
		}

		if (Tracking\Manager::isConfigured())
		{
			return;
		}

		$this->includeComponentTemplate();
	}

	public function showTrackingField()
	{
		$entityTypeId = (int) $this->arParams['ENTITY_TYPE_ID'];
		$entityId = (int) $this->arParams['ENTITY_ID'];

		$data = [
			'ROW' => [],
			'SOURCE' => [],
			'PAGES' => [],
			'SITE_DOMAIN' => null,
			'IS_MOBILE' => false,
			'SOURCES' => [],
		];


		///// SOURCES //////////////////////////
		static $actualSources = null;
		if ($actualSources === null)
		{
			$actualSources = Tracking\Provider::getActualSources();
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
		}
		$data['SOURCES'] = $actualSources;


		///// ROW //////////////////////////
		static $row = false;
		if ($row === false)
		{
			$row = Tracking\Internals\TraceEntityTable::getRowByEntity($entityTypeId, $entityId);
		}
		$data['ROW'] = $row;

		///// SOURCE //////////////////////////
		$trace = null;
		if ($row)
		{
			$trace = Tracking\Internals\TraceTable::getRow([
				'select' => ['SOURCE_ID', 'PAGES_RAW', 'IS_MOBILE'],
				'filter' => ['=ID' => $row['TRACE_ID']]
			]);
		}
		if ($trace)
		{
			$data['IS_MOBILE'] = $trace['IS_MOBILE'] === 'Y';
			if ($trace['SOURCE_ID'] && isset($actualSources[$trace['SOURCE_ID']]))
			{
				$data['SOURCE'] = $actualSources[$trace['SOURCE_ID']];
			}

			$trace['PAGES_RAW'] = is_array($trace['PAGES_RAW'])
				? array_slice($trace['PAGES_RAW'], 0, 5)
				: [];
			$data['PAGES'] = array_map(
				function ($page)
				{
					$page['DATE_INSERT'] = FormatDate(
						'j F H:i',
						$page['DATE_INSERT']
					);
					return $page;
				},
				$trace['PAGES_RAW']
			);
		}

		///// SITE_DOMAIN //////////////////////////
		$collection = Tracking\Channel\Factory::createCollection($row['TRACE_ID']);
		foreach ($collection as $channel)
		{
			/** @var Tracking\Channel\Base $channel */
			if (!$channel->isSite())
			{
				continue;
			}

			$data['SITE'] = [
				'CAPTION' => $channel->getName(),
				'DOMAIN' => $channel->getDescription()
			];
		}

		$data['IS_EMPTY'] = empty($data['ROW']) || (
			$collection->isEmpty() && empty($data['SOURCE'])
		);
		$this->arResult['DATA'] = $data;
		$this->includeComponentTemplate();
	}
}