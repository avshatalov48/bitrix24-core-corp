<?php

namespace Bitrix\Sign\Controllers\V1\Integration\HumanResources;

use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Service\Container;
use Bitrix\HumanResources;
use Bitrix\Main;
use Bitrix\Sign\Type\Document\SchemeType;

class HcmLink extends \Bitrix\Sign\Engine\Controller
{
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function checkCompanyAction(int $id): array
	{
		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));
			return [];
		}

		$integrations = HumanResources\Service\Container::getHcmLinkCompanyRepository()
			->getByCompanyId($id)
		;

		$result = [];
		/** @var Company $integration */
		foreach ($integrations->getItemMap() as $integration)
		{
			$result[] = [
				'id' => $integration->id,
				'title' => $integration->title,
				'subtitle' => $integration->data['config']['title'] ?? null,
			];
		}

		return $result;
	}

	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function loadNotMappedMembersAction(string $documentUid): array
	{
		$container = Container::instance();

		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));
			return [];
		}

		$document = $container->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Invalid documentUid'));
			return [];
		}

		if (!$document->hcmLinkCompanyId)
		{
			return [];
		}

		$userIds = $container->getMemberService()->getUserIdsByDocument($document);

		$result = HumanResources\Service\Container::getHcmLinkMapperService()
			->filterNotMappedUserIds($document->hcmLinkCompanyId, ...$userIds)
		;

		if (!$result instanceof FilterNotMappedUserIdsResult)
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'integrationId' => $document->hcmLinkCompanyId,
			'userIds' => $result->userIds,
		];
	}

	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function loadFieldsAction(string $documentUid): array
	{
		$container = Container::instance();

		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));
			return [];
		}

		$document = $container->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Invalid documentUid'));
			return [];
		}

		if (!$document->hcmLinkCompanyId)
		{
			return [];
		}

		$withEmployee = $document->scheme !== SchemeType::ORDER;

		return [
			'fields' => $container->getHcmLinkFieldService()
				->getFieldsForSelector($document->hcmLinkCompanyId, $withEmployee)
			,
		];
	}

	private function isAvailable(): bool
	{
		$hcmLinkService = Container::instance()->getHcmLinkService();

		if (!$hcmLinkService->isAvailable())
		{
			return false;
		}

		if (!Main\Loader::includeModule('humanresources'))
		{
			return false;
		}

		return true;
	}
}
