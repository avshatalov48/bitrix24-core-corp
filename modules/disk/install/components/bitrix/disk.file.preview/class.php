<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CDiskFilePreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$fileId = $this->arParams['fileId'];
		$file = \Bitrix\Disk\File::loadById($fileId);
		if(!$file)
			return;

		$this->arResult['ID'] = $fileId;
		$this->arResult['NAME'] = $file->getName();
		$this->arResult['SIZE'] = \CFile::formatSize($file->getSize());
		$this->arResult['UPDATED'] = $file->getUpdateTime();
		if(isset($this->arParams['externalLink']) && $this->arParams['externalLink'])
			$this->arResult['URL_DOWNLOAD'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
				'hash' => $this->arParams['hash'],
				'action' => $this->arParams['action']
			));
		else
			$this->arResult['URL_DOWNLOAD'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($file, true);

		$this->arResult['ICON_CLASS'] = \Bitrix\Disk\Ui\Icon::getIconClassByObject($file);
		Main\Application::getInstance()->getTaggedCache()->registerTag('disk_file_'.$this->arResult['ID']);
	}

	public function executeComponent()
	{
		$this->prepareData();
		if($this->arResult['ID'] > 0)
		{
			$this->includeComponentTemplate();
		}
	}
}