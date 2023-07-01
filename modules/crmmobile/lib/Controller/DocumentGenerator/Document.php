<?php

namespace Bitrix\CrmMobile\Controller\DocumentGenerator;

use Bitrix\Crm\Controller\DocumentGenerator\CheckModule;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\CrmEntityDataProvider;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Format;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('crm');

final class Document extends JsonController
{
	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return mixed
	 */
	public function getAction(\Bitrix\DocumentGenerator\Document $document)
	{
		$serviceContainer = Container::getInstance();
		$router = $serviceContainer->getRouter();
		$permissions = $serviceContainer->getUserPermissions();

		/** @var array|null $result */
		$result = $this->proxyAction('getAction', [ $document ]);

		if (is_array($result))
		{
			$provider = $document->getProvider();
			// todo Remove code duplication with bitrix:crm.document.view component
			if ($provider instanceof CrmEntityDataProvider)
			{
				$result['entityId'] = (int)$provider->getSource();
				$result['entityTypeId'] = (int)$provider->getCrmOwnerType();
				$result['entityDetailUrl'] = $router->getItemDetailUrl($result['entityTypeId'], $result['entityId']);

				$serviceContainer->getLocalization()->loadMessages();
				$myCompanyProvider = $provider->getMyCompanyProvider();
				$stringOrNull = fn($value) => isset($value) ? (string)$value : null;

				if ($myCompanyProvider)
				{
					[$requisiteData, ] = $myCompanyProvider->getClientRequisitesAndBankDetail();

					$myCompanyId = \Bitrix\DocumentGenerator\DataProviderManager::getInstance()->getValueFromList($provider->getMyCompanyId());
					$link = null;
					if (is_numeric($myCompanyId) && (int)$myCompanyId > 0)
					{
						$link = $router->getItemDetailUrl(\CCrmOwnerType::Company, (int)$myCompanyId);
					}

					$result['myCompanyRequisites'] = [
						'title' => Format\Requisite::formatOrganizationName($requisiteData)
							?? $stringOrNull($myCompanyProvider->getValue('TITLE'))
							?: Loc::getMessage('CRM_COMMON_EMPTY_VALUE')
						,
						'link' => $link,
						'subTitle' => Format\Requisite::formatShortRequisiteString($requisiteData)
							?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE')
						,
						'entityTypeId' => \CCrmOwnerType::Company,
						'entityId' => (int)$myCompanyId,
						'entityName' => $myCompanyProvider->getValue('TITLE'),
					];
				}
				[$clientRequisiteData, ] = $provider->getClientRequisitesAndBankDetail();
				$companyProvider = $provider->getValue('COMPANY');
				if ($companyProvider instanceof DataProvider\Company)
				{
					$result['clientRequisites'] = [
						'title' => Format\Requisite::formatOrganizationName($clientRequisiteData)
							?? $stringOrNull($companyProvider->getValue('NAME'))
							?: Loc::getMessage('CRM_COMMON_EMPTY_VALUE')
						,
						'link' => $router->getItemDetailUrl(\CCrmOwnerType::Company, (int)$companyProvider->getSource()),
						'subTitle' => Format\Requisite::formatShortRequisiteString($clientRequisiteData) ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE'),
						'entityTypeId' => \CCrmOwnerType::Company,
						'entityId' => (int)$companyProvider->getSource(),
						'entityName' => $companyProvider->getValue('TITLE'),
					];
				}
				else
				{
					$contactProvider = $provider->getValue('CONTACT');
					if ($contactProvider instanceof DataProvider\Contact)
					{
						$result['clientRequisites'] = [
							'title' => $clientRequisiteData['RQ_COMPANY_NAME']
								?? $stringOrNull($contactProvider->getValue('FORMATTED_NAME'))
								?: Loc::getMessage('CRM_COMMON_EMPTY_VALUE')
							,
							'link' => $router->getItemDetailUrl(\CCrmOwnerType::Contact, (int)$contactProvider->getSource()),
							'subTitle' => Format\Requisite::formatShortRequisiteString($clientRequisiteData),
							'entityTypeId' => \CCrmOwnerType::Contact,
							'entityId' => (int)$contactProvider->getSource(),
							'entityName' => $contactProvider->getValue('FORMATTED_NAME'),
						];
					}
				}

				$result['isSigningEnabled'] =
					($provider instanceof DataProvider\Deal)
					&& $permissions->checkAddPermissions(\CCrmOwnerType::SmartDocument)
					&& \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
					&& \Bitrix\Sign\Config\Storage::instance()->isAvailable()
				;
				if ($result['isSigningEnabled'])
				{
					$signIntegration = ServiceLocator::getInstance()->get('crm.integration.sign');
					$result['isSigningEnabledInCurrentTariff'] = $signIntegration->isEnabledInCurrentTariff();
					$result['signingInfoHelperSliderCode'] = 'limit_crm_sign_integration';
				}

				if ($result['entityId'] > 0 && $document->FILE_ID > 0)
				{
					$channelSelectorComponentParams = [
						'id' => 'document-channel-selector',
						'entityTypeId' => $result['entityTypeId'],
						'entityId' => $result['entityId'],
						'body' => $document->getTitle(),
						'configureContext' => 'crm.document.view',
						'link' => $document->getPublicUrl(true),
						'isLinkObtainable' => true,
						'isConfigurable' => true,
						'skipTemplate' => true,
					];
					$emailFileDto = null;
					if ($this->isUsingDiskAsDefaultStorage())
					{
						$emailDiskFileId = $document->getEmailDiskFile(true);
						if ($emailDiskFileId > 0)
						{
							$diskFile = \Bitrix\Disk\File::getById($emailDiskFileId);
							$emailFileDto = \Bitrix\Mobile\UI\File::load($diskFile->getFileId());
							$channelSelectorComponentParams['storageTypeId'] = \Bitrix\Crm\Integration\StorageType::Disk;
							$channelSelectorComponentParams['files'] = [$emailDiskFileId];
						}
					}
					global $APPLICATION;
					$result['channelSelector'] = $APPLICATION->IncludeComponent(
						'bitrix:crm.channel.selector',
						'',
						$channelSelectorComponentParams,
					);
					if ($result['channelSelector'] && $emailFileDto)
					{
						$result['channelSelector']['emailAttachment'] = $emailFileDto;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getFieldsAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param array $values
	 * @return array|null
	 */
	public function getFieldsAction(\Bitrix\DocumentGenerator\Document $document, array $values = [])
	{
		$result = $this->proxyAction('getFieldsAction', [ $document, $values ]);
		if (isset($result['documentFields']) && is_array($result['documentFields']))
		{
			$sort = 10;
			$step = 10;
			foreach ($result['documentFields'] as $key => &$field)
			{
				if ($field['type'] === \Bitrix\DocumentGenerator\DataProvider::FIELD_TYPE_DATE)
				{
					$field['timestamp'] = strtotime($field['value']);
				}

				$field['key'] = $key;
				$field['sort'] = $sort;
				$sort += $step;
			}
		}

		return $result;
	}

	/**
	 * @return array|\Bitrix\Main\Engine\AutoWire\Parameter[]
	 */
	public function getAutoWiredParameters()
	{
		if(DocumentGeneratorManager::getInstance()->isEnabled())
		{
			return $this->getDocumentGeneratorController()->getAutoWiredParameters($this);
		}

		return [];
	}

	private function getDocumentGeneratorController(): \Bitrix\DocumentGenerator\Controller\Document
	{
		return new \Bitrix\DocumentGenerator\Controller\Document();
	}

	/**
	 * @param string $action
	 * @param array $arguments
	 * @return mixed
	 */
	private function proxyAction(string $action, array $arguments = [])
	{
		$controller = $this->getDocumentGeneratorController();
		$controller->setScope($this->getScope());
		$result = call_user_func_array([$controller, $action], $arguments);
		$this->errorCollection->add($controller->getErrors());

		return ($result === false) ? null : $result;
	}

	/**
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = function()
		{
			if(!DocumentGeneratorManager::getInstance()->isEnabled())
			{
				$this->errorCollection[] = new Error(
					'Module documentgenerator is not installed'
				);

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			return new EventResult(EventResult::SUCCESS);
		};
		$preFilters[] = new CheckModule();
		if (DocumentGeneratorManager::getInstance()->isEnabled())
		{
			$defaultPostFilters = $this->getDocumentGeneratorController()->getDefaultPreFilters();
			$preFilters = array_merge($preFilters, $defaultPostFilters);
		}

		return $preFilters;
	}

	private function isUsingDiskAsDefaultStorage(): bool
	{
		return (
			Driver::getInstance()->getDefaultStorage() instanceof \Bitrix\DocumentGenerator\Storage\Disk
			&& Loader::includeModule('disk')
		);
	}
}
