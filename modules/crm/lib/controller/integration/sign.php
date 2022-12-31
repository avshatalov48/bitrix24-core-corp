<?php
namespace Bitrix\Crm\Controller\Integration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank;
use Bitrix\Sign\File;

class Sign extends Controller
{
	private \Bitrix\Crm\Service\Integration\Sign $signService;

	public function __construct(Request $request = null)
	{
		$this->signService = ServiceLocator::getInstance()->get('crm.integration.sign');
		parent::__construct($request);
	}

	/**
	 * @param int $documentId
	 * @return array
	 */
	public function convertDealAction(int $documentId): array
	{
		return $this->signService->convertDealDocumentToSmartDocument($documentId);
	}
}
