<?php

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\User;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Web\Json;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskFileEditorOnlyOfficeComponent extends BaseComponent implements Controllerable, SidePanelWrappable
{
	public function configureActions()
	{
		return [];
	}

	protected function processActionDefault()
	{
		/** @var OnlyOffice\Models\DocumentSession $documentSession */
		$documentSession = $this->arParams['DOCUMENT_SESSION'];

		$sessionGetParams = [
			'userId' => $documentSession->getUserId(),
			'sourceDocumentSessionId' => $documentSession->getId(),
			'documentSessionHash' => $documentSession->getExternalHash(),
		];
		$onlyOfficeController = new Disk\Controller\OnlyOffice();

		$allowEdit = false;
		if (!$documentSession->isVersion())
		{
			$allowEdit = $documentSession->canTransformUserToEdit(CurrentUser::get());
		}

		$configBuilder = new OnlyOffice\ConfigBuilder($documentSession);
		$configBuilder
			->allowEdit($allowEdit)
			->setUser(User::loadById(CurrentUser::get()->getId()))
			/** @see Disk\Controller\OnlyOffice::handleOnlyOfficeAction() */
			->setCallbackUrl($onlyOfficeController->getActionUri('handleOnlyOffice', $sessionGetParams, true))
			/** @see Disk\Controller\OnlyOffice::downloadAction() */
			->setDocumentUrl($onlyOfficeController->getActionUri('download', $sessionGetParams, true))
		;

		$this->arResult['SERVER'] = rtrim(Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_server', ''), '/');
		$this->arResult['EDITOR_JSON'] = Json::encode($configBuilder->build());
		$this->arResult['DOCUMENT_SESSION'] = [
			'ID' => $documentSession->getId(),
			'HASH' => $documentSession->getExternalHash(),
		];
		$this->arResult['OBJECT'] = [
			'ID' => $documentSession->getObjectId(),
		];

		$this->includeComponentTemplate();
	}
}