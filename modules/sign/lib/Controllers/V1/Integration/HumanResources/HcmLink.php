<?php

namespace Bitrix\Sign\Controllers\V1\Integration\HumanResources;

use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Result\Service\HcmLink\FilterNotMappedUserIdsResult;
use Bitrix\HumanResources\Result\Service\HcmLink\GetMultipleVacancyEmployeesResult;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\DocumentCollection;
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

		$companies = [];
		$companyCollection = HumanResources\Service\Container::getHcmLinkCompanyRepository()
			 ->getByCompanyId($id)
		;

		foreach ($companyCollection as $company)
		{
			$documentSettingFieldsCollection = HumanResources\Service\Container::getHcmLinkFieldService()
			   ->getListByEntityType(HumanResources\Type\HcmLink\FieldEntityType::DOCUMENT, $company->id)
			;

			$extractFieldsAndMap = static function ($type) use ($documentSettingFieldsCollection) {
				$settingFields = $documentSettingFieldsCollection
					->filter(
						static fn(HumanResources\Item\HcmLink\Field $field) => $field->type == $type
					)
					->map(static fn(HumanResources\Item\HcmLink\Field $field) => [
						'id' => $field->id,
						'title' => $field->title,
					])
				;

				return array_values($settingFields);
			};

			$companies[] = [
				'id' => $company->id,
				'title' => $company->title,
				'subtitle' => $company->data['config']['title'] ?? null,
				'availableSettings' => [
					'documentType' => $extractFieldsAndMap(HumanResources\Type\HcmLink\FieldType::DOCUMENT_UID),
					'externalId' => $extractFieldsAndMap(HumanResources\Type\HcmLink\FieldType::DOCUMENT_REGISTRATION_NUMBER),
					'date' => $extractFieldsAndMap(HumanResources\Type\HcmLink\FieldType::DOCUMENT_DATE),
				],
			];
		}

		return $companies;
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
				'id' => $company->id,
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
			...$userIds,
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

	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUids'
	)]
	public function loadBulkNotMappedMembersAction(array $documentUids): array
	{
		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));

			return [];
		}

		if (empty($documentUids))
		{
			$this->addErrorByMessage('No document uids');

			return [];
		}

		$container = Container::instance();
		$documents = $container->getDocumentRepository()->listByUids($documentUids);
		$notFoundDocuments = array_diff($documentUids, $documents->listUidsWithoutNull());
		if (!empty($notFoundDocuments))
		{
			$this->addErrorByMessage('Not found documents with uids: ' . implode(',', $notFoundDocuments));

			return [];
		}

		$documentsByIntegrationIds = $this->mapDocumentsByIntegrationIds($documents);

		$integrations = [];
		$hcmMapperService = HumanResources\Service\Container::getHcmLinkMapperService();
		foreach ($documentsByIntegrationIds as $hcmLinkCompanyId => $companyDocuments)
		{
			$userIds = $container->getMemberService()->getUniqueUserIdsByDocuments($companyDocuments);
			$result = $hcmMapperService->filterNotMappedUserIds($hcmLinkCompanyId, ...$userIds);

			if (!$result instanceof FilterNotMappedUserIdsResult)
			{
				$this->addErrors($result->getErrors());

				return [];
			}

			$integrations[] = [
				'integrationId' => $hcmLinkCompanyId,
				'userIds' => $result->userIds,
				'allUserIds' => $userIds,
			];
		}

		return $integrations;
	}

	#[ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUids'
	)]
	public function loadBulkMultipleVacancyEmployeeAction(array $documentUids): array
	{
		if (!$this->isAvailable())
		{
			$this->addError(new Main\Error('Is not available', 'HCM_LINK_NOT_AVAILABLE'));

			return [];
		}

		if (empty($documentUids))
		{
			$this->addErrorByMessage('No document uids');

			return [];
		}

		$container = Container::instance();
		$documents = $container->getDocumentRepository()->listByUids($documentUids);
		$notFoundDocuments = array_diff($documentUids, $documents->listUidsWithoutNull());
		if (!empty($notFoundDocuments))
		{
			$this->addErrorByMessage('Not found documents with uids: ' . implode(',', $notFoundDocuments));

			return [];
		}

		$documents = $documents->filter(static fn(Document $document) => $document->hcmLinkCompanyId);
		$documentsByIntegrationIds = $this->mapDocumentsByIntegrationIds($documents);
		if (empty($documentsByIntegrationIds))
		{
			return [];
		}

		$userIdsByDocumentIds = $container
			->getMemberRepository()
			->listUserIdsWithEmployeeIdIsNotSetByDocumentIds($documents)
		;

		if (empty($userIdsByDocumentIds))
		{
			return [];
		}

		$integrations = [];
		foreach ($documentsByIntegrationIds as $hcmLinkCompanyId => $companyDocuments)
		{
			$userIds = [];
			foreach ($companyDocuments as $document)
			{
				$documentUsers = $userIdsByDocumentIds[$document->id] ?? [];
				$userIds = array_values(array_unique(array_merge($userIds, $documentUsers)));
			}

			if (empty($userIds))
			{
				continue;
			}

			$result = HumanResources\Service\Container::getHcmLinkMapperService()
				->getEmployeesWithMultipleVacancy($hcmLinkCompanyId, ...$userIds)
			;

			if (!$result instanceof GetMultipleVacancyEmployeesResult)
			{
				$this->addErrors($result->getErrors());

				return [];
			}

			$company = HumanResources\Service\Container::getHcmLinkCompanyRepository()
				->getById($hcmLinkCompanyId)
			;
			if (!$company)
			{
				$this->addErrorByMessage('Cant find hcmlink company');

				return [];
			}

			$integrations[] = [
				'company' => [
					'id' => $company->id,
					'title' => $company->title,
				],
				'employees' => $result->employees,
			];
		}

		return $integrations;
	}

	/**
	 * @param DocumentCollection $documents
	 *
	 * @return array<int, DocumentCollection>
	 */
	private function mapDocumentsByIntegrationIds(DocumentCollection $documents): array
	{
		$documentsByIntegrationIds = [];
		foreach ($documents as $document)
		{
			if (!$document->hcmLinkCompanyId)
			{
				continue;
			}

			if (!isset($documentsByIntegrationIds[$document->hcmLinkCompanyId]))
			{
				$documentsByIntegrationIds[$document->hcmLinkCompanyId] = new DocumentCollection();
			}
			$documentsByIntegrationIds[$document->hcmLinkCompanyId]->add($document);
		}

		return $documentsByIntegrationIds;
	}
}
