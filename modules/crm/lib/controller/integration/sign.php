<?php
namespace Bitrix\Crm\Controller\Integration;

use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Integration\Crm\DocumentService;

class Sign extends Controller
{
	private \Bitrix\Crm\Service\Integration\Sign $signService;
	private ?DocumentService $signDocumentService = null;

	public function __construct(Request $request = null)
	{
		$this->signService = ServiceLocator::getInstance()
			->get('crm.integration.sign');

		if (Loader::includeModule('sign'))
		{
			$this->signDocumentService = ServiceLocator::getInstance()
				->get('sign.service.integration.crm.document');
		}
		parent::__construct($request);
	}

	/**
	 * @param int $documentId
	 * @return array
	 */
	public function convertDealAction(int $documentId, bool $usePrevious = false): array
	{
		$result = $this->signService->convertDealDocumentToSmartDocument($documentId, $usePrevious);
		
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		$docId = $result->getData()['SMART_DOCUMENT'] ?? null;
		$document = \Bitrix\Sign\Document::resolveByEntity('SMART', $docId);
		$response = $result->getData();

		if (Storage::instance()->isNewSignEnabled() && $document)
		{
			$response['uid'] = $document->getUid();
		}

		return $response;
	}

	/**
	 * @param int $documentId
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getLinkedBlankAction(int $documentId): array
	{
		if (!Loader::includeModule('documentgenerator'))
		{
			return [];
		}

		$currentUserId = CurrentUser::get()->getId();
		if (!$this->signService->checkUserPermissionToDealDocumentByDocument($documentId, $currentUserId))
		{
			return [];
		}

		$document = Document::loadById($documentId);
		if (!$document)
		{
			return [];
		}

		if (!$this->signDocumentService)
		{
			return [];
		}

		$linkedBlankData = $this->signDocumentService
			->getLinkedBlankForDocumentGeneratorTemplate($document->TEMPLATE_ID) ?: [];

		if ($linkedBlankData['CREATED_AT'] ?? false)
		{
			$linkedBlankData['CREATED_AT'] = $linkedBlankData['CREATED_AT']
				->format(\Bitrix\Main\Context::getCurrent()->getCulture()->getShortDateFormat());
		}

		return $linkedBlankData;
	}
}
