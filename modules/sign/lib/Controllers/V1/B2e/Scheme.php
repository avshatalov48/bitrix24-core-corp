<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Main;
use Bitrix\Sign\Service;

class Scheme extends Controller
{
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
	public function loadAction(string $documentUid): array
	{
		$document = Service\Container::instance()->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('No document found'));
			return [];
		}

		if (empty($document->companyUid))
		{
			$this->addError(new Main\Error('Document has no connected company'));
			return [];
		}

		$result = Service\Container::instance()->getApiB2eProviderSchemesService()
			->loadAvailableSchemes($document->companyUid)
		;

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'schemes' => $result->getData(),
		];
	}
}