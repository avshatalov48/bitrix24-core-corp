<?php

namespace Bitrix\Sign\Controllers\V1\Integration\HumanResources;

use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMultipleVacancyEmployeesResult;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Service\Container;
use Bitrix\HumanResources;
use Bitrix\Main;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Document\SchemeType;

class HcmLink extends \Bitrix\Sign\Engine\Controller
{
	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_EDIT),
	)]
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

	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
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
			'allUserIds' => $userIds,
		];
	}

	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
	public function loadMultipleVacancyEmployeeAction(string $documentUid): array
	{
		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));
			return [];
		}

		$container = Container::instance();

		$document = $container->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Invalid documentUid'));
			return [];
		}

		if ($document->hcmLinkCompanyId === null)
		{
			return [];
		}

		$company = HumanResources\Service\Container::getHcmLinkCompanyRepository()
			->getById($document->hcmLinkCompanyId)
		;
		if (!$company)
		{
			return [];
		}

		$userIds = $container->getMemberRepository()
			->listUserIdsWithEmployeeIdIsNotSetByDocumentId($document->id, $document->representativeId);
		;

		$result = HumanResources\Service\Container::getHcmLinkMapperService()
			->getEmployeesWithMultipleVacancy($document->hcmLinkCompanyId, ...$userIds)
		;

		if (!$result instanceof GetMultipleVacancyEmployeesResult)
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		return [
			'company' => [
				'title' => $company->title,
			],
			'employees' => $result->employees,
		];
	}

	/**
	 * @param string $documentUid
	 * @param array{array{userId: int, employeeId: int}} $selectedEmployeeCollection
	 *
	 * @return array
	 */
	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
	public function saveSelectedEmployeesAction(
		string $documentUid,
		array $selectedEmployeeCollection,
	): array
	{
		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));
			return [];
		}

		$document = Container::instance()->getDocumentRepository()->getByUid($documentUid);
		if ($document === null)
		{
			$this->addError(new Main\Error('Document not found'));

			return [];
		}

		if (
			!$document->hcmLinkCompanyId
			|| empty($selectedEmployeeCollection)
		)
		{
			return [];
		}

		$employeeIdByUserIdMap = [];
		foreach ($selectedEmployeeCollection as $item)
		{
			if (
				!is_numeric($item['userId'] ?? null)
				|| !is_numeric($item['employeeId'] ?? null)
			)
			{
				continue;
			}

			$employeeIdByUserIdMap[(int)$item['userId']] = (int)$item['employeeId'];
		}

		$memberRepository = Container::instance()->getMemberRepository();
		$memberService = Container::instance()->getMemberService();

		$userIds = array_keys($employeeIdByUserIdMap);

		$memberCollection = $memberRepository->listMembersByDocumentIdAndUserIds(
			$document->id,
			$document->representativeId,
			...$userIds
		);
		foreach ($memberCollection as $member)
		{
			$userId = $memberService->getUserIdForMember($member, $document);

			if (!isset($employeeIdByUserIdMap[$userId]))
			{
				continue;
			}

			$member->employeeId = $employeeIdByUserIdMap[$userId];
			$memberRepository->update($member);
		}

		return [];
	}

	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
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
