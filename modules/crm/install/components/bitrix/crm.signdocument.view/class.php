<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');
Loc::loadMessages(__FILE__);

class CrmSignDocumentViewComponent extends Bitrix\Crm\Component\Base
{
	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('documentId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if ($this->getErrors())
		{
			return;
		}

		if (!$this->isIframe())
		{
			// todo redirect ?
			return;
		}

		$this->getApplication()->SetTitle(Loc::getMessage('CRM_SIGNDOCUMENT_VIEW_TITLE'));

		if (!\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error('Document signing is not enabled');
			return;
		}

		$documentId = $this->arParams['documentId'] ?? null;

		if (!$documentId)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_SIGNDOCUMENT_VIEW_DOCUMENT_NOT_FOUND'));
			return;
		}

		$document = Bitrix\Sign\Document::getById($documentId);

		if (!$document->getInitiatorMember())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_SIGNDOCUMENT_VIEW_DOCUMENT_INITIATOR_NOT_FOUND'));
			return;
		}
		$documentHash = $document->getHash();
		$memberHash = $this->arParams['memberHash'] ?? $document->getInitiatorMember()->getHash();

		$this->arResult['pdfSource'] = sprintf(
			'/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc&documentHash=%s&memberHash=%s',
			$documentHash,
			$memberHash
		);


		//todo generate real url
		//todo user permissions
	}

	public function executeComponent(): void
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}
		$this->initToolbar();
		$this->includeComponentTemplate();
	}

	protected function initToolbar(): void
	{
		$printButton = (new \Bitrix\UI\Buttons\Button())
			->setIcon(\Bitrix\UI\Buttons\Icon::PRINT)
			->setColor(\Bitrix\UI\Buttons\Color::LIGHT_BORDER)
		;
		$this->arResult['printButtonId'] = $printButton->getUniqId();

		$downloadButton = (new \Bitrix\UI\Buttons\Button())
			->setText(Loc::getMessage('CRM_SIGNDOCUMENT_VIEW_DOCUMENT_DOWNLOAD'))
			->setColor(\Bitrix\UI\Buttons\Color::LIGHT_BORDER)
		;
		$this->arResult['downloadButtonId'] = $downloadButton->getUniqId();

		\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($printButton);
		\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($downloadButton);
	}
}
