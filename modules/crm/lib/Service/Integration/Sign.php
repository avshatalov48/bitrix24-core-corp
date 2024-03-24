<?php

namespace Bitrix\Crm\Service\Integration;

use Bitrix\Crm\Activity\Provider\SignDocument;
use Bitrix\Crm\Service\Operation\ConversionResult;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService as SignDocumentService;
use Bitrix\Sign\Service\Integration\Crm\DocumentService;

class Sign
{
	private ?DocumentService $crmSignDocumentService = null;
	private ?SignDocumentService $signDocumentService = null;

	public function __construct()
	{
		if (self::isAvailable())
		{
			$this->crmSignDocumentService = Container::instance()->getCrmSignDocumentService();
			$this->signDocumentService = Container::instance()->getDocumentService();
		}
	}

	/**
	 * Check that all dependencies is exists
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isAvailable(): bool
	{
		return \Bitrix\Main\Loader::includeModule('documentgenerator')
			&& \Bitrix\Main\Loader::includeModule('crm')
			&& \Bitrix\Main\Loader::includeModule('sign')
			&& Storage::instance()->isAvailable();
	}

	/**
	 * Check that sign-related interfaces should be displayed
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		return (
			\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
			&& self::isAvailable()
		);
	}

	public static function isEnabledInCurrentTariff(): bool
	{
		return static::isEnabled()
			&& \Bitrix\Main\Loader::includeModule('sign')
			&& \Bitrix\Sign\Restriction::isSignAvailable()
			&& \Bitrix\Sign\Restriction::isCrmIntegrationAvailable()
		;
	}

	/**
	 * Converting deal to smart document end create sign document
	 * @param int $documentId
	 * @return Result
	 */
	public function convertDealDocumentToSmartDocument(int $documentId, bool $usePrevious = false): Result
	{
		if (!self::isAvailable())
		{
			return new Result();
		}

		$currentUserId = CurrentUser::get()->getId();
		if (!$this->checkUserPermissionToDealDocumentByDocument($documentId, $currentUserId))
		{
			return (new Result())->addError(
				new Error("User doesnt has access to deal")
			);
		}
		if (!$this->checkUserPermissionToCreateSmartDocument($currentUserId))
		{
			return (new Result())->addError(
				new Error("User doesnt has access to smart document")
			);
		}

		$document = Document::loadById($documentId);

		if (!$document)
		{
			return (new Result())->addError(new Error('Document not found'));
		}
		$fileId = $document->PDF_ID;
		$fileId = $fileId ?: $document->FILE_ID;

		$provider = $document->getProvider();

		if ($fileId && $provider instanceof \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal)
		{
			$dealId = $document->getFields(['SOURCE'])['SOURCE']['VALUE'] ?? null;
			if ($dealId)
			{
				if ($usePrevious)
				{
					return $this->checkSignInitiation($document, $dealId, $fileId);
				}

				$result = $this->convertDealToSmartDocument($dealId);

				if (!$result->isSuccess() || !$result->isConversionFinished())
				{
					return $result;
				}

				$data = $result->getData();
				try
				{
					/** @var \Bitrix\Main\Result $createDocResult */
					$createDocResult = ServiceLocator::getInstance()
						->get('sign.service.integration.crm.document')
						->createSignDocumentFromDealDocument(
							$fileId,
							$document,
							$data['SMART_DOCUMENT'],
							true
						);

					if ($createDocResult && !$createDocResult->isSuccess())
					{
						return $result->addErrors($createDocResult->getErrors());
					}

					$item = \Bitrix\Crm\Service\Container::getInstance()
						->getFactory(\CCrmOwnerType::SmartDocument)
						->getItem($data['SMART_DOCUMENT'])
					;

					SignDocument::onDocumentUpdate(
						$item->getId(),
					);
				}
				catch (ObjectNotFoundException $e)
				{
					return (new Result())->addError(new Error('OBJECT_NOT_FOUND'));
				}

				return $result;
			}
		}

		return (new Result())->addError(new Error('Failed to create document'));
	}

	private function checkSignInitiation(Document $document, int $dealId, int $fileId): Result
	{
		$linkedBlank = $this
			->crmSignDocumentService
			->getLinkedBlankForDocumentGeneratorTemplate($document->TEMPLATE_ID);

		if ($linkedBlank)
		{
			return $this->initiateSign($linkedBlank, $dealId, $document, $fileId);
		}
		
		return new Result();
	}

	private function initiateSign(array $linkedBlank, int $dealId, Document $document, $fileId): Result
	{
		$blank = \Bitrix\Sign\Blank::getById((int)$linkedBlank['BLANK_ID']);
		$result = new Result();
		if (!$blank)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SIGN_NO_BLANK')));
		}

		$smartDocResult = $this->convertDealToSmartDocument($dealId);
		$smartDocId = $smartDocResult->getData()['SMART_DOCUMENT'] ?? null;

		if (
			!$smartDocResult->isSuccess()
			|| !$smartDocResult->isConversionFinished()
			|| !$smartDocId
		)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SIGN_CAN_NOT_CONVERT')));
		}

		$result = $this->crmSignDocumentService
			->createSignDocumentFromBlank(
				$fileId,
				$blank->getId(),
				$document,
				$smartDocId
			);

		$data = $result->getData();
		if (isset($data['newSign']))
		{
			Container::instance()->getDocumentAgentService()->addConfigureAndStartAgent($data['signDocument']->uid);
			return $result;
		}

		if (!$result->isSuccess() || !isset($data['signDocument']))
		{
			return $result;
		}

		/** @var \Bitrix\Sign\Document $doc */
		$doc = $result->getData()['signDocument'];
		$doc->setMeta([
			'initiatorName' => $linkedBlank['INITIATOR'],
		]);

		return $doc->send();
	}

	/**
	 * Convert Deal to SmartDocument
	 * @param $dealId
	 * @return \Bitrix\Crm\Service\Operation\ConversionResult|\Bitrix\Main\Result
	 */
	public function convertDealToSmartDocument($dealId): ConversionResult
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		$deal = $factory->getItem($dealId);

		$config = \Bitrix\Crm\Conversion\ConversionManager::getConfig(\CCrmOwnerType::Deal);
		foreach ($config->getItems() as $item)
		{
			$item->setActive($item->getEntityTypeID() === \CCrmOwnerType::SmartDocument);
			$item->enableSynchronization(false);
		}

		$operation = $factory->getConversionOperation($deal, $config);
		$operation->disableAllChecks();

		$result = $operation->launch();
		$data = $result->getData();
		if ($result->isSuccess() && $result->isConversionFinished() && isset($data[\CCrmOwnerType::SmartDocumentName]))
		{
			$documentFactory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartDocument);
			if ($documentFactory)
			{
				$document = $documentFactory->getItem((int)$data[\CCrmOwnerType::SmartDocumentName]);
				$userId = CurrentUser::get()?->getId();
				if ($document && $userId)
				{
					$document->setAssignedById($userId);

					$changeAssignedResult =
						$documentFactory
							->getUpdateOperation($document)
							->disableAllChecks()
							->launch()
					;

					if (!$changeAssignedResult->isSuccess())
					{
						$result->addErrors($changeAssignedResult->getErrors());
					}
				}
			}

		}

		return $result;
	}

	final public function checkUserPermissionToCreateSmartDocument(int $userId): bool
	{
		$defaultSmartDocumentCategory = \Bitrix\Crm\Service\Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartDocument)
			?->getDefaultCategory()
			?->getId()
		;

		return (new UserPermissions($userId))->checkAddPermissions(
			\CCrmOwnerType::SmartDocument,
			$defaultSmartDocumentCategory
		);
	}

	final public function checkUserPermissionToDealDocumentByDocument(int $documentId, int $userId): bool
	{
		if (!Loader::includeModule('documentgenerator'))
		{
			return false;
		}

		$documentGeneratorDriver = \Bitrix\DocumentGenerator\Driver::getInstance();
		if (!$documentGeneratorDriver->getUserPermissions()->canViewDocuments())
		{
			return false;
		}
		$userPermission = new UserPermissions($userId);

		$dealId = $this->getDealIdByDocument($documentId);
		if ($dealId === null)
		{
			return false;
		}

		return $userPermission->checkUpdatePermissions(
			\CCrmOwnerType::Deal,
			$dealId,
		);
	}

	private function getDealIdByDocument(int $documentId): ?int
	{
		$document = Document::loadById($documentId);
		if (!$document)
		{
			return null;
		}

		$fileId = $document->PDF_ID;
		if ($fileId === null || $fileId === 0)
		{
			$fileId = $document->FILE_ID;
		}

		$provider = $document->getProvider();

		if (!$fileId || !$provider instanceof \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal)
		{
			return null;
		}

		$dealId = $document->getFields(['SOURCE'])['SOURCE']['VALUE'] ?? null;

		return $dealId === null ? null : (int)$dealId;
	}
}
