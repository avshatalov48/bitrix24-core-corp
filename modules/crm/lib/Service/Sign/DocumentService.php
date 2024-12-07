<?php

namespace Bitrix\Crm\Service\Sign;

use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService as SignDocumentService;

class DocumentService
{
	private ?SignDocumentService $signDocumentService = null;

	public function __construct()
	{
		if (self::isAvailable())
		{
			$this->signDocumentService = Container::instance()->getDocumentService();
		}
	}

	public static function isAvailable(): bool
	{
		return \Bitrix\Main\Loader::includeModule('crm')
			&& \Bitrix\Main\Loader::includeModule('sign')
			&& Storage::instance()->isAvailable();
	}

	public function getSignDocumentBySmartDocument(int $entityId): ?\Bitrix\Sign\Item\Document
	{
		if (
			self::isAvailable()
			&& method_exists(SignDocumentService::class, 'getSignDocumentBySmartDocumentId')
		)
		{
			return $this->signDocumentService?->getSignDocumentBySmartDocumentId($entityId);
		}

		return null;
	}
}
