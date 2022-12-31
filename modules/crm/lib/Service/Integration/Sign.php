<?php

namespace Bitrix\Crm\Service\Integration;

use Bitrix\Crm\Activity\Provider\SignDocument;
use Bitrix\Crm\Service\Operation\ConversionResult;
use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sign\Config\Storage;

class Sign
{

	/**
	 * Check that all dependencies is exists
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isAvailable(): bool
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
	public function isEnabled(): bool
	{
		return (
			\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
			&& $this->isAvailable()
		);
	}

	/**
	 * Converting deal to smart document end create sign document
	 * @param int $documentId
	 * @return array
	 */
	public function convertDealDocumentToSmartDocument(int $documentId): array
	{
		if (!$this->isAvailable())
		{
			return [];
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
			$dealId = $document->getFields(['SOURCE'])['SOURCE']['VALUE'];
			if ($dealId)
			{
				$result = $this->convertDealToSmartDocument($dealId);

				if (!$result->isSuccess() || !$result->isConversionFinished())
				{
					return ['errors' => $result->getErrors()];
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
					return [];
				}

				return $result->getData();
			}
		}

		return [];
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
