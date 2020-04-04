<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Company;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Contact;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\CrmEntityDataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Lead;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Quote;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Order;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Suspended;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\Event;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;

class DocumentGeneratorManager
{
	const PROVIDER_LIST_EVENT_NAME = 'onGetDataProviderList';
	const DOCUMENT_CREATE_EVENT_NAME = 'onCreateDocument';
	const DOCUMENT_UPDATE_EVENT_NAME = 'onUpdateDocument';
	const DOCUMENT_PUBLIC_VIEW_EVENT_NAME = 'onPublicView';
	const DOCUMENT_DELETE_EVENT_NAME = '\Bitrix\DocumentGenerator\Model\Document::OnBeforeDelete';

	protected static $instance;
	protected $isEnabled;

	protected function __construct()
	{
		if(Loader::includeModule('documentgenerator'))
		{
			$this->isEnabled = true;
		}
		else
		{
			$this->isEnabled = false;
		}
	}

	/**
	 * @return DocumentGeneratorManager
	 */
	public static function getInstance()
	{
		if(static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		if($this->isEnabled)
		{
			if(method_exists(Driver::getInstance(), 'isEnabled'))
			{
				return Driver::getInstance()->isEnabled();
			}
			else
			{
				return (class_exists('\DOMDocument', true) && class_exists('\ZipArchive', true));
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isDocumentButtonAvailable()
	{
		return (
			$this->isEnabled() && (
				Driver::getInstance()->getUserPermissions()->canViewDocuments() ||
				Driver::getInstance()->getUserPermissions()->canModifyTemplates()
			)
		);
	}

	/**
	 * @param string $className
	 * @param string $value
	 * @return array
	 */
	public function getDocumentButtonParameters($className, $value)
	{
		if(!$this->isDocumentButtonAvailable())
		{
			return [];
		}
		// subscribe to changes in the list
		TemplateTable::getPullTag();
		\CJSCore::init(["documentpreview"]);
		Loc::loadMessages(__FILE__);
		$params = [
			'provider' => $className,
			'moduleId' => 'crm',
			'value' => $value,
			'templateListUrl' => $this->getAddTemplateUrl($className),
			'className' => 'crm-btn-dropdown-document',
			'menuClassName' => 'document-toolbar-menu',
			'templatesText' => Loc::getMessage('CRM_DOCUMENTGENERATOR_ADD_NEW_TEMPLATE'),
			'documentsText' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DOCUMENTS_LIST'),
		];
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:crm.document.view');
		if(!empty($componentPath))
		{
			$params['loaderPath'] = getLocalPath('components'.$componentPath.'/templates/.default/images/document_view.svg');
			$documentUrl = new Uri(getLocalPath('components'.$componentPath.'/slider.php'));
			$documentUrl->addParams(['providerClassName' => $className,]);
			$params['documentUrl'] = $documentUrl->getUri();
		}

		return $params;
	}

	/**
	 * @param string $provider
	 * @return bool|string
	 */
	public function getAddTemplateUrl($provider = null)
	{
		if($this->isEnabled() && Directory::isDirectoryExists(\Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot().'/crm/documents/'))
		{
			$path = '/crm/documents/templates/';
			$uri = new Uri($path);
			if($provider)
			{
				$uri->addParams(['entityTypeId' => $provider]);
			}
			return $uri->getLocator();
		}

		return false;
	}

	/**
	 * @return array
	 */
	public static function getDataProviders()
	{
		$result = [];
		if(static::getInstance()->isEnabled())
		{
			$providers = [
				Company::class,
				Contact::class,
				Deal::class,
				Invoice::class,
				Lead::class,
				Quote::class,
				Order::class,
			];
			foreach($providers as $provider)
			{
				/** @var Nameable $provider */
				$className = strtolower($provider);
				$result[$className] = [
					'NAME' => $provider::getLangName(),
					'CLASS' => $className,
					'MODULE' => 'crm',
				];
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onCreateDocument(Event $event)
	{
		$document = $event->getParameter('document');
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof CrmEntityDataProvider)
			{
				$provider->onDocumentCreate($document);
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onDeleteDocument(Event $event)
	{
		$document = Document::loadById($event->getParameter('primary'));
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof CrmEntityDataProvider)
			{
				$provider->onDocumentDelete($document);
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onUpdateDocument(Event $event)
	{
		$document = $event->getParameter('document');
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof CrmEntityDataProvider)
			{
				$provider->onDocumentUpdate($document);
			}
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onPublicView(Event $event)
	{
		$document = $event->getParameter('document');
		/** @var Document $document */
		if($document)
		{
			$provider = $document->getProvider();
			if($provider && $provider instanceof CrmEntityDataProvider)
			{
				$provider->onPublicView($document);
			}
		}

		return true;
	}

	/**
	 * Returns array where key - id from \CCrmOwnerType and value - data provider class name
	 *
	 * @return array
	 */
	public function getCrmOwnerTypeProvidersMap()
	{
		static $map = null;
		if($map === null)
		{
			$map = [
				\CCrmOwnerType::Lead => Lead::class,
				\CCrmOwnerType::Deal => Deal::class,
				\CCrmOwnerType::Contact => Contact::class,
				\CCrmOwnerType::Company => Company::class,
				\CCrmOwnerType::Invoice => Invoice::class,
				\CCrmOwnerType::Quote => Quote::class,
				\CCrmOwnerType::Order => Order::class,

				\CCrmOwnerType::SuspendedLead => Suspended::class,
				\CCrmOwnerType::SuspendedDeal => Suspended::class,
				\CCrmOwnerType::SuspendedContact => Suspended::class,
				\CCrmOwnerType::SuspendedCompany => Suspended::class,
				\CCrmOwnerType::SuspendedQuote => Suspended::class,
				\CCrmOwnerType::SuspendedInvoice => Suspended::class,
				\CCrmOwnerType::SuspendedOrder => Suspended::class,
			];
		}
		return $map;
	}

	/**
	 * @param $oldEntityTypeID
	 * @param $oldEntityID
	 * @param $newEntityTypeID
	 * @param $newEntityID
	 */
	public function transferDocumentsOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if(!$this->isEnabled)
		{
			return;
		}

		$providersMap = $this->getCrmOwnerTypeProvidersMap();
		$oldProvider = $providersMap[$oldEntityTypeID];
		$newProvider = $providersMap[$newEntityTypeID];
		if($oldProvider && $newProvider)
		{
			DocumentTable::transferOwnership($oldProvider, $oldEntityID, $newProvider, $newEntityID);
		}
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @return \Bitrix\Main\Result|null
	 */
	public function deleteDocumentsByOwner($entityTypeId, $entityId)
	{
		if(!$this->isEnabled)
		{
			return;
		}

		$entityId = intval($entityId);
		$provider = $this->getCrmOwnerTypeProvidersMap()[$entityTypeId];
		if($provider && $entityId > 0)
		{
			return DocumentTable::deleteList([
				'=PROVIDER' => strtolower($provider),
				'VALUE' => $entityId
			]);
		}
	}
}