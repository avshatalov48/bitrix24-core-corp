<?php

use Bitrix\Crm\Format;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\Components\ViewComponent;
use Bitrix\DocumentGenerator\CreationMethod;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!Loader::includeModule('documentgenerator'))
{
	die('Module documentgenerator is not installed');
}

Loc::loadMessages(__FILE__);

class CrmDocumentViewComponent extends ViewComponent
{
	private const TEMPLATE_CODE = 'CRM_DOCUMENT_SHARING';

	public function executeComponent()
	{
		if (!$this->includeModules())
		{
			ShowError(Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_MODULE_ERROR'));
			return;
		}
		if ($this->arParams['MODE'] === 'change' && !check_bitrix_sessid())
		{
			ShowError(Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_CSRF_ERROR'));
			return;
		}
		$result = $this->initDocument();
		if (!$result->isSuccess())
		{
			$this->arResult['ERRORS'] = $result->getErrorMessages();
			$this->includeComponentTemplate();
			return;
		}
		Container::getInstance()->getLocalization()->loadMessages();
		if ($this->document->ID > 0 && $this->arParams['MODE'] === 'edit')
		{
			if (!Driver::getInstance()->getUserPermissions()->canModifyDocument($this->document))
			{
				$this->arResult['ERRORS'] = [Loc::getMessage('DOCGEN_DOCUMENT_VIEW_ACCESS_ERROR')];
				$this->includeComponentTemplate();
				return;
			}
			$this->arResult['FIELDS'] = $this->document->getFields([], true, true);
			$this->includeComponentTemplate('edit');
			return;
		}
		if (!$this->document->ID && Bitrix24Manager::isEnabled() && Bitrix24Manager::isDocumentsLimitReached())
		{
			$this->includeComponentTemplate('limit');
			return;
		}
		$isNewDocument = true;
		if ($this->document->ID > 0)
		{
			$isNewDocument = false;
		}
//		$emptyFields = $this->document->checkFields();
//		if((!empty($emptyFields) && !$this->document->ID && $this->arParams['MODE'] !== 'change') || $this->arParams['EDIT'] && \Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyDocument($this->document))
//		{
//			$this->arResult['FIELDS'] = $this->document->getFields([], false, true);
//			$this->includeComponentTemplate('edit');
//			return;
//		}
		if (
			$this->arParams['MODE'] === 'change'
			&& $this->document->ID > 0
			&& Driver::getInstance()->getUserPermissions()->canModifyDocument($this->document)
		)
		{
			$result = $this->document->update($this->arParams['VALUES']);
		}
		else
		{
			if ($isNewDocument)
			{
				CreationMethod::markDocumentAsCreatedByPublic($this->document);
			}
			$isSendToTransformation = !$this->document->PDF_ID;
			$result = $this->document->getFile($isSendToTransformation);
		}
		$this->arResult = $result->getData();
		if (
			$isSendToTransformation
			&& !empty($this->arResult['transformationCancelReason'])
			&& $this->arResult['transformationCancelReason'] instanceof \Bitrix\Main\Error
			&& $this->arResult['transformationCancelReason']->getCode() === 'TRANSFORM_FORMATS_PROCESSED'
		)
		{
			$this->arResult['isTransformationError'] = true;
			$this->arResult['transformationErrorMessage'] = Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_PROCESSED_NO_PDF_ERROR');
		}
		if (!$result->isSuccess())
		{
			if (($this->arResult['isTransformationError'] ?? false) !== true)
			{
				$this->arResult['ERRORS'] = $result->getErrorMessages();
			}
		}
		$this->arResult['isDisplayTransformationErrors'] =
			\Bitrix\Main\Config\Option::get('crm', 'display_transformation_errors_in_document_slider', 'Y') === 'Y'
		;
		if ($isNewDocument)
		{
			$this->arResult['documentUrl'] = $this->getDocumentUrl();
		}
		$this->arResult['values'] = $this->arParams['VALUES'];
		$this->arResult['editTemplateUrl'] = $this->getEditTemplateUrl();
		if ($this->arResult['editTemplateUrl'])
		{
			$this->arResult['editDocumentUrl'] = $this->getEditDocumentUrl();
		}
		$this->arResult['myCompanyRequisites'] = [
			'title' => Loc::getMessage('CRM_COMMON_EMPTY_VALUE'),
			'link' => null,
			'subTitle' => '',
		];
		$this->arResult['clientRequisites'] = [
			'title' => Loc::getMessage('CRM_COMMON_EMPTY_VALUE'),
			'link' => null,
			'subTitle' => '',
		];
		/** @var DataProvider\CrmEntityDataProvider $provider */
		$provider = $this->document->getProvider();
		if ($provider)
		{
			$this->arResult['PROVIDER'] = get_class($provider);
			$myCompanyProvider = $provider->getMyCompanyProvider();
			if ($myCompanyProvider)
			{
				[$requisiteData, ] = $myCompanyProvider->getClientRequisitesAndBankDetail();

				$myCompanyId = $myCompanyProvider->getSource();
				if (is_numeric($myCompanyId) && (int)$myCompanyId > 0)
				{
					$link = Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Company, (int)$myCompanyId);
				}

				$this->arResult['myCompanyRequisites'] = [
					'title' =>
						Format\Requisite::formatOrganizationName($requisiteData)
						?? $myCompanyProvider->getValue('TITLE')
						?: $this->arResult['myCompanyRequisites']['title']
					,
					'link' => $link ?? $this->arResult['myCompanyRequisites']['link'],
					'subTitle' =>
						Format\Requisite::formatShortRequisiteString($requisiteData)
						?? $this->arResult['myCompanyRequisites']['subTitle']
					,
				];
			}
			[$clientRequisiteData, ] = $provider->getClientRequisitesAndBankDetail();
			$companyProvider = $provider->getValue('COMPANY');
			if ($companyProvider instanceof DataProvider\Company)
			{
				$this->arResult['clientRequisites'] = [
					'title' =>
						Format\Requisite::formatOrganizationName($clientRequisiteData)
						?? $companyProvider->getValue('TITLE')
						?: $this->arResult['clientRequisites']['title']
					,
					'link' =>
						Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Company, (int)$companyProvider->getSource())
						?? $this->arResult['clientRequisites']['link']
					,
					'subTitle' =>
						Format\Requisite::formatShortRequisiteString($clientRequisiteData)
						?? $this->arResult['clientRequisites']['subTitle']
					,
				];
			}
			else
			{
				$contactProvider = $provider->getValue('CONTACT');
				if ($contactProvider instanceof DataProvider\Contact)
				{
					$this->arResult['clientRequisites'] = [
						'title' =>
							Format\Requisite::formatOrganizationName($clientRequisiteData)
							?? $contactProvider->getValue('FORMATTED_NAME')
							?: $this->arResult['clientRequisites']['title']
						,
						'link' =>
							Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Contact, (int)$contactProvider->getSource())
							?? $this->arResult['clientRequisites']['link']
						,
						'subTitle' =>
							Format\Requisite::formatShortRequisiteString($clientRequisiteData)
							?? $this->arResult['clientRequisites']['subTitle']
						,
					];
				}
			}
			$this->arResult['isSigningEnabled'] =
				($provider instanceof \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal)
				&& \Bitrix\Crm\Service\Container::getInstance()
					->getUserPermissions()
					->checkAddPermissions(\CCrmOwnerType::SmartDocument)
				&& \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
				&& \Bitrix\Sign\Config\Storage::instance()->isAvailable()
			;
		}
		else
		{
			$this->arResult['PROVIDER'] = '';
			$this->arResult['editMyCompanyRequisitesUrl'] = '';
		}
		$this->arResult['TEMPLATE_NAME'] = '';
		$this->arResult['TEMPLATE_CODE'] = '';
		if ($this->template)
		{
			$this->arResult['TEMPLATE_NAME'] = $this->template->NAME;
			$this->arResult['TEMPLATE_CODE'] = $this->template->CODE;
		}

		if ($this->getValue() > 0 && $this->document->FILE_ID > 0)
		{
			$link = $this->document->getPublicUrl();
			$this->arResult['channelSelectorParameters'] = [
				'id' => 'document-channel-selector',
				'entityTypeId' => (int)$this->getCrmOwnerType(),
				'entityId' => (int)$this->getValue(),
				'documentTitle' => $this->document->getTitle(),
				'body' => Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_BODY_MSGVER_1'),
				'fullBody' => Loc::getMessage('CRM_DOCUMENT_VIEW_COMPONENT_FULL_BODY_MSGVER_1'),
				'configureContext' => 'crm.document.view',
				'link' => $link,
				'isLinkObtainable' => true,
				'isCombineMessageWithLink' => false,
				'isInsertLinkInMessage' => true,
				'isConfigurable' => true,
				'templateCode' => self::TEMPLATE_CODE,
				'templatePlaceholders' => [
					'DOCUMENT_URL' => $link,
				],
			];
			if (Driver::getInstance()->getDefaultStorage() instanceof \Bitrix\DocumentGenerator\Storage\Disk)
			{
				$this->arResult['channelSelectorParameters']['storageTypeId'] = \Bitrix\Crm\Integration\StorageType::Disk;
				$this->arResult['channelSelectorParameters']['files'] = [$this->getEmailDiskFile(true)];
			}
		}
		$signIntegration = ServiceLocator::getInstance()->get('crm.integration.sign');

		$this->arResult['isSigningEnabledInCurrentTariff'] = $signIntegration->isEnabledInCurrentTariff();
		$this->arResult['signingInfoHelperSliderCode'] = 'limit_crm_sign_integration';

		$this->arResult['baas']['fastTransform'] = [
			'isAvailable' => DocumentGeneratorManager::getInstance()->isBaasFastTransformFeatureAvailable(),
			'isActive' => DocumentGeneratorManager::getInstance()->isBaasFastTransformFeatureActive(),
		];

		$this->includeComponentTemplate();
	}

	/**
	 * @return string
	 */
	protected function getModule()
	{
		return 'crm';
	}

	/**
	 * @return string|false
	 */
	protected function getEditDocumentUrl()
	{
		if (Driver::getInstance()->getUserPermissions()->canModifyDocument($this->document))
		{
			$uri = new Uri(Application::getInstance()->getContext()->getRequest()->getRequestUri());

			return $uri->deleteParams(['mode', 'templateId', 'values', 'value', 'provider'])
				->addParams(['documentId' => $this->document->ID, 'mode' => 'edit'])
				->getLocator()
			;
		}

		return false;
	}

	/**
	 * @return CrmEntityDataProvider|false
	 */
	protected function getCrmDataProvider()
	{
		if ($this->document)
		{
			$provider = $this->document->getProvider();
			if ($provider instanceof DataProvider\CrmEntityDataProvider)
			{
				return $provider;
			}
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType(): int
	{
		$result = 0;

		$provider = $this->getCrmDataProvider();
		if ($provider)
		{
			$result = $provider->getCrmOwnerType();
		}

		return $result;
	}

	/**
	 * @param bool $docx
	 * @return int
	 */
	protected function getEmailDiskFile($docx = false): int
	{
		$result = 0;

		if ($this->document)
		{
			$result = $this->document->getEmailDiskFile($docx);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getDocumentUrl(): string
	{
		$uri = new Uri(Application::getInstance()->getContext()->getRequest()->getRequestUri());

		return $uri->deleteParams(['mode', 'templateId', 'values', 'value', 'provider'])
			->addParams(['documentId' => $this->document->ID])
			->getLocator()
		;
	}

	/**
	 * @return bool|string
	 */
	protected function getEditTemplateUrl()
	{
		if ($this->template && !$this->template->isDeleted())
		{
			if (!Driver::getInstance()->getUserPermissions()->canModifyTemplate($this->template->ID))
			{
				return true;
			}
			$addUrl = DocumentGeneratorManager::getInstance()->getAddTemplateUrl();
			if ($addUrl)
			{
				$editUrl = new Uri($addUrl);
				$editUrl->addParams([
					'ID' => $this->template->ID,
					'UPLOAD' => 'Y',
				]);

				return $editUrl->getLocator();
			}
		}

		return false;
	}
}
