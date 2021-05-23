<?php

use Bitrix\Disk\Internals\DiskComponent;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskFolderViewComponent extends DiskComponent
{
	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->findFolder();

		return true;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if(!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),
			'BREADCRUMBS' => $this->getBreadcrumbs(),
		);

		$this->includeComponentTemplate();
	}

	protected function findFolder()
	{
		$this->folder = \Bitrix\Disk\Folder::loadById($this->arParams['FOLDER_ID']);

		if(!$this->folder)
		{
			throw new \Bitrix\Main\SystemException("Invalid folder.");
		}
		return $this;
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', '/' . trim($this->arParams['RELATIVE_PATH'], '/'));
		foreach ($parts as $i => $part)
		{
			$crumbs[] = array(
				'NAME' => $part,
				'LINK' => rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
					'PATH' => implode('/', (array_slice($parts, 0, $i + 1))),
				)), '/') . '/',
			);
		}
		unset($i, $part);

		return $crumbs;
	}
}