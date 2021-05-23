<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	return;
}

class CDiskLastFilesComponent extends DiskComponent
{
	protected function prepareParams()
	{
		if(!$this->getUser() || !$this->getUser()->getId())
		{
			return;
		}

		parent::prepareParams();

		if(isset($this->arParams['MAX_COUNT_FILES']))
		{
			$this->arParams['MAX_COUNT_FILES'] = (int)$this->arParams['MAX_COUNT_FILES'];
		}
		else
		{
			$this->arParams['MAX_COUNT_FILES'] = 5;
		}

	}

	/**
	 * Processes operations before runs action.
	 *
	 * @param string $actionName Action name which will run.
	 * @return bool
	 */
	protected function processBeforeAction($actionName)
	{
		if(!$this->getUser() || !$this->getUser()->getId())
		{
			return false;
		}

		return parent::processBeforeAction($actionName);
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'FILES' => $this->getLastFiles(),
		);

		$this->includeComponentTemplate();
	}

	private function getLastFiles()
	{
		$parameters = array(
			'filter' => array(
				'STORAGE_ID' => $this->storage->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE,
				'DELETED_TYPE' => \Bitrix\Disk\Internals\ObjectTable::DELETED_TYPE_NONE,
			),
			'order' => array('UPDATE_TIME' => 'DESC'),
			'limit' => $this->arParams['MAX_COUNT_FILES'],
		);
		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$parameters = Driver::getInstance()
			->getRightsManager()
			->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'))
		;

		$urlManager = Driver::getInstance()->getUrlManager();
		$files = array();
		$fullFormatWithoutSec = preg_replace('/:s$/', '', CAllDatabase::dateFormatToPHP(CSite::GetDateFormat("FULL")));
		foreach(\Bitrix\Disk\File::getModelList($parameters) as $file)
		{
			$files[] = array(
				'NAME' => $file->getName(),
				'UPDATE_DATE' => formatDate(
					$fullFormatWithoutSec,
					$file->getUpdateTime()->toUserTime()->getTimestamp(),
					time() + CTimeZone::getOffset()
				),
				'VIEW_URL' => $urlManager->encodeUrn($urlManager->getPathFileDetail($file)),
			);
		}
		unset($file);

		return $files;
	}
}