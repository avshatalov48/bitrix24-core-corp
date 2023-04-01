<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

class CCrmTrackingEntityDetailsComponent extends CBitrixComponent
{
	private $componentResult = ['SELECTED_SOURCE_ID' => null];

	public function executeComponent()
	{
		if (!Loader::includeModule("crm"))
		{
			return $this->componentResult;
		}

		if (!Tracking\Manager::isAccessible())
		{
			return $this->componentResult;
		}

		if (empty($this->arParams['SHOW_FIELD']))
		{
			$this->showTrackingBanner();
		}
		else
		{
			$this->showTrackingField();
		}

		return $this->componentResult;
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

		///// SOURCES //////////////////////////
		static $actualSources = null;
		if ($actualSources === null)
		{
			$actualSources = Tracking\Provider::getActualSources();
			array_unshift($actualSources, [
				'ID' => null,
				'NAME' => Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_EMPTY_OPTION'),
			]);
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
		}

		///// TRACES //////////////////////////
		$traces = Tracking\Internals\TraceTable::getList([
			'select' => ['ID', 'SOURCE_ID', 'PAGES_RAW', 'IS_MOBILE'],
			'filter' => [
				'=ENTITY.ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY.ENTITY_ID' => $entityId,
			],
			'order' => ['DATE_CREATE' => 'DESC'],
			'limit' => 5
		])->fetchAll();

		$traces = array_filter(
			$traces,
			function ($trace, $index) use ($actualSources)
			{
				// old: skip traces without source, except first
				if (/*$index > 0 && */!$trace['SOURCE_ID'])
				{
					return true;
				}

				return isset($actualSources[$trace['SOURCE_ID']]);
			},
			ARRAY_FILTER_USE_BOTH
		);

		$traces = array_combine(
			array_column($traces, 'ID'),
			array_values($traces)
		);

		foreach ($traces as $traceId => $trace)
		{
			///// SITE_DOMAIN
			$site = null;
			$collection = Tracking\Channel\Factory::createCollection($traceId);
			foreach ($collection as $channel)
			{
				/** @var Tracking\Channel\Base $channel */
				if (!$channel->isSite())
				{
					continue;
				}

				$site = [
					'CAPTION' => $channel->getName(),
					'DOMAIN' => $channel->getDescription()
				];
			}

			///// SOURCE
			$source = ($trace['SOURCE_ID'] && isset($actualSources[$trace['SOURCE_ID']]))
				? $actualSources[$trace['SOURCE_ID']]
				: null;

			if (empty($site) && !$source)
			{
				$traces[$traceId] = [
					'ROW' => $trace,
				];

				continue;
			}

			$trace['PAGES_RAW'] = is_array($trace['PAGES_RAW'])
				? array_slice($trace['PAGES_RAW'], 0, 5)
				: [];

			$traces[$traceId] = [
				'ROW' => $trace,
				'SOURCE' => $source,
				'SITE' => $site,
				'IS_MOBILE' => $trace['IS_MOBILE'] === 'Y',
				'PAGES' => array_map(
					function ($page)
					{
						$ts = $page['DATE_INSERT'];
						if ($ts)
						{
							$ts = DateTime::createFromTimestamp($ts)
								->toUserTime()
								->getTimestamp();
						}
						$page['DATE_INSERT'] = FormatDate('j F H:i', $ts);
						return $page;
					},
					$trace['PAGES_RAW']
				)
			];
		}

		$this->arResult['TRACES'] = $traces;
		$this->arResult['SOURCES'] = $actualSources;
		$trace = current($traces);
		$traceSource = $trace['SOURCE'] ?? null;
		$this->componentResult['SELECTED_SOURCE_ID'] = $this->arResult['SELECTED_SOURCE_ID'] = (
			(is_array($traceSource) && array_key_exists('ID', $traceSource))
				? $trace['SOURCE']['ID']
				: (($this->arParams['IS_REQUIRED'] ?? null) === true ? null : 0)
		);
		$this->includeComponentTemplate();
	}
}