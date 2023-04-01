<?php

namespace Bitrix\Crm\Service\Integration;

use Bitrix\Crm\Activity\Provider\SignDocument;
use Bitrix\Crm\Service\Operation\ConversionResult;
use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Integration\Crm\DocumentService;

class Sign
{
	private ?DocumentService $signDocumentService = null;

	public function __construct()
	{
		if (self::isAvailable())
		{
			$this->signDocumentService = ServiceLocator::getInstance()
				->get('sign.service.integration.crm.document');
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
	 * @return array
	 */
	public function convertDealDocumentToSmartDocument(int $documentId, bool $usePrevious = false): Result
	{
		if (!self::isAvailable())
		{
			return new Result();
		}

		$document = Document::loadById($documentId);

		if (!$document)
		{
			return [];
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
					ServiceLocator::getInstance()
						->get('sign.service.integration.crm.document')
						->createSignDocumentFromDealDocument($fileId, $document, $data['SMART_DOCUMENT']);

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

		return [];
	}
	private function checkSignInitiation(Document $document, int $dealId, int $fileId): Result
	{
		$linkedBlank = $this
			->signDocumentService
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

		$result = $this->signDocumentService
			->createSignDocumentFromBlank(
				$fileId,
				$blank,
				$document,
				$smartDocId
			);

		if (!$result->isSuccess() || !isset($result->getData()['signDocument']))
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

		return $operation->launch();
	}

}
