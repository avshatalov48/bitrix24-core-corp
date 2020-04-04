<?php

use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskBreadcrumbsTreeComponent extends DiskComponent
{
	protected function processActionDefault()
	{
		$proxyType = $this->storage->getProxyType();
		$this->arResult = array(
			'BREADCRUMBS' => $this->getBreadcrumbs(),
			'STORAGE' => array(
				'NAME' => $proxyType->getTitleForCurrentUser(),
				'LINK' => $proxyType->getBaseUrlFolderList(),
				'ID' => $this->storage->getRootObjectId(),
			),
		);

		$this->includeComponentTemplate();
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		foreach($this->arParams['RELATIVE_ITEMS'] as $i => $item)
		{
			if(empty($item))
			{
				continue;
			}
			$crumbs[] = array(
				'ID' => $item['ID'],
				'NAME' => $item['NAME'],
				'LINK' => rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
					'PATH' => implode('/', (array_slice($parts, 0, $i + 1)))?: '',
				)), '/') . '/',
			);
		}
		unset($i, $item);

		return $crumbs;
	}
}