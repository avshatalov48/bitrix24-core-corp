<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Item;
use Bitrix\Main;

class B2eDocumentService
{
	public function setMyCompany(Item\Document $document, int $companyId): Main\Result
	{
		$result = new Main\Result();
		if (!Main\Loader::includeModule('crm'))
		{
			return $result->addError(new Main\Error('Module `crm` is not installed'));
		}

		if (!DocumentScenario::isB2EScenario($document->scenario))
		{
			return $result->addError(new Main\Error('Invalid document scenario'));
		}

		if ($document->entityId === null && $document->entityId <= 0)
		{
			return $result->addError(new Main\Error('Invalid document `entityId`'));
		}

		$company = Container::getInstance()
			->getFactory(\CCrmOwnerType::Company)
			->getItem($companyId)
		;

		if ($company === null)
		{
			return $result->addError(new Main\Error('Company doesnt exist'));
		}

		$smartB2eDocument = Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartB2eDocument)
			->getItem($document->entityId)
		;

		$smartB2eDocument->setMycompanyId($companyId);
		return $smartB2eDocument->save();
	}
}