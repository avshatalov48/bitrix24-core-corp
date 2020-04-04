<?php

use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\DocumentGenerator\Document;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class DocumentsViewComponent extends CBitrixComponent
{
	/** @var Document */
	protected $document;
	protected $errorCollection;

	public function __construct(CBitrixComponent $component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function executeComponent()
	{
		$this->init();
		if(!$this->isSuccess())
		{
			/** @var Error $error */
			foreach($this->errorCollection as $error)
			{
				$this->arResult['ERRORS'][] = $error->getMessage();
			}
			$this->includeComponentTemplate();
			return;
		}

		EventManager::getInstance()->send(new Event(Driver::MODULE_ID, 'onPublicView', ['document' => $this->document]));

		$urlManager = \Bitrix\Main\Engine\UrlManager::getInstance();
		$this->arResult = array_merge($this->document->getFile()->getData(), [
			'downloadUrl' => $urlManager->create('documentgenerator.api.publicdocument.getFile', [
				'id' => $this->arParams['ID'],
				'hash' => $this->arParams['HASH'],
			]),
			'pdfUrl' => $urlManager->create('documentgenerator.api.publicdocument.getPdf', [
				'id' => $this->arParams['ID'],
				'hash' => $this->arParams['HASH'],
			]),
			'printUrl' => $urlManager->create('documentgenerator.api.publicdocument.showPdf', [
				'print' => 'y',
				'id' => $this->arParams['ID'],
				'hash' => $this->arParams['HASH'],
			]),
		]);

		$this->includeComponentTemplate();
	}

	protected function init()
	{
		Loc::loadLanguageFile(__FILE__);
		if(!\Bitrix\Main\Loader::includeModule('documentgenerator'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_VIEW_ERROR_MODULE'))]);
			return;
		}
		$id = intval($this->arParams['ID']);
		if(!$id)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_VIEW_ERROR_LINK'))]);
			return;
		}
		$document = Document::loadById($id);
		if(!$document)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_VIEW_ERROR_LINK'))]);
			return;
		}
		$this->document = $document;
		$link = \Bitrix\DocumentGenerator\Model\ExternalLinkTable::getByHash($this->arParams['HASH']);
		if(!$link || $link['DOCUMENT_ID'] != $this->document->ID)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('DOCGEN_VIEW_ERROR_LINK'))]);
			return;
		}
	}

	/**
	 * @return bool
	 */
	protected function isSuccess()
	{
		return $this->errorCollection->isEmpty();
	}
}