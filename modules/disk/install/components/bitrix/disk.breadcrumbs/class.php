<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskBreadcrumbsComponent extends \Bitrix\Disk\Internals\BaseComponent
{
	protected function prepareParams()
	{
		parent::prepareParams();
		if(isset($this->arParams['BREADCRUMBS_ID']) && $this->arParams['BREADCRUMBS_ID'] !== '')
		{
			$this->arParams['BREADCRUMBS_ID'] = preg_replace('/[^a-z0-9_]/i', '', $this->arParams['BREADCRUMBS_ID']);
		}
		else
		{
			$this->arParams['BREADCRUMBS_ID'] = 'breadcrumbs_' .(mb_strtolower(randString(5)));
		}

		if(!isset($this->arParams['SHOW_ONLY_DELETED']))
		{
			$this->arParams['SHOW_ONLY_DELETED'] = false;
		}

		$this->arParams['CLASS_NAME'] = $this->arParams['CLASS_NAME'] ?? '';

		if(!isset($this->arParams['BREADCRUMBS']))
		{
			$this->arParams['BREADCRUMBS'] = array();
		}

		if(!isset($this->arParams['MAX_BREADCRUMBS_TO_SHOW']))
		{
			$this->arParams['MAX_BREADCRUMBS_TO_SHOW'] = 20;
		}

		if(!isset($this->arParams['ENABLE_DROPDOWN']))
		{
			$this->arParams['ENABLE_DROPDOWN'] = true;
		}

		if(isset($this->arParams['ENABLE_SHORT_MODE']))
		{
			$this->arParams['ENABLE_SHORT_MODE'] = (bool)$this->arParams['ENABLE_SHORT_MODE'];
		}
		else
		{
			$this->arParams['ENABLE_SHORT_MODE'] = false;
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'STORAGE_ID' => $this->arParams['STORAGE_ID'],
			'BREADCRUMBS_ID' => $this->arParams['BREADCRUMBS_ID'],
			'BREADCRUMBS_ROOT' => array(
				'ID' => $this->arParams['BREADCRUMBS_ROOT']['ID'],
				'NAME' => $this->arParams['BREADCRUMBS_ROOT']['NAME'],
				'LINK' => $this->arParams['BREADCRUMBS_ROOT']['LINK'],
				'ENCODED_LINK' => $this->encodeUrn($this->arParams['BREADCRUMBS_ROOT']['LINK']),
			),
			'BREADCRUMBS' => $this->processCrumbs($this->arParams['BREADCRUMBS']),
			'SHOW_ONLY_DELETED' => $this->arParams['SHOW_ONLY_DELETED'],
		);

		if ($this->arParams['ENABLE_SHORT_MODE'])
		{
			$this->includeComponentTemplate('short_template');
		}
		else
		{
			$this->includeComponentTemplate();
		}
	}

	private function processCrumbs(array $crumbs): array
	{
		foreach ($crumbs as $i => $crumb)
		{
			$crumbs[$i]['ENCODED_LINK'] = $crumb['ENCODED_LINK'] ?? self::encodeUrn($crumb['LINK']);
		}

		return $crumbs;
	}
}