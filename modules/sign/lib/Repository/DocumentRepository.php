<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Document;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Document\SchemeType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\ProviderCode;

class DocumentRepository
{
	private const INITIATOR_NAME_META_KEY = Document::META_KEYS['initiatorName'];
	private const DOCUMENT_DEFAULT_PARTIES_COUNT = 2;

	/** @var array<DocumentScenario::*, int> */
	private const SCENARIO_NAME_TO_ID_MAP = [
		DocumentScenario::SIMPLE_SIGN_ONE_PARTY_MANY_MEMBERS => 1,
		DocumentScenario::SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS => 2,
		DocumentScenario::DSS_ONE_PARTY_MANY_MEMBERS => 100,
		DocumentScenario::DSS_SECOND_PARTY_MANY_MEMBERS => 101,
	];

	private const SCHEME_TYPE_TO_ID_MAP = [
		SchemeType::DEFAULT => 0,
		SchemeType::ORDER => 1,
	];

	/**
	 * @param Item\Document $item
	 *
	 * @return AddResult
	 */
	public function add(Item\Document $item): Result
	{
		$document = new Internal\Document();

		if ($item->uid !== null)
		{
			$document->setUid($item->uid);
		}
		$scenarioId = $item->scenario === null
			? null
			: $this->getScenarioIdByName($item->scenario)
		;
		$schemeId = $this->getSchemeIdByType($item->scheme);

		$now = new DateTime();
		$addResult = $document->setBlankId($item->blankId)
			->setTitle($item->title)
			->setStatus($item->status)
			->setLangId($item->langId)
			->setEntityId($item->entityId)
			->setEntityType($item->entityType)
			->setExternalId($item->externalId)
			->setMeta($this->getModelMetaByItem($item))
			->setCreatedById(CurrentUser::get()->getId())
			->setModifiedById(CurrentUser::get()->getId())
			->setDateCreate($now)
			->setDateModify($now)
			->setScenario($scenarioId)
			->setVersion($item->version)
			->setParties($item->parties ?? self::DOCUMENT_DEFAULT_PARTIES_COUNT)
			->setScheme($schemeId)
			->setProviderCode($item->providerCode)
			->setTemplateId($item->templateId)
			->setCreatedFromDocumentId($item->createdFromDocumentId)
			->setInitiatedByType($item->initiatedByType->toInt())
			->save()
		;

		$item->id = $addResult->getId();
		return $addResult->setData(['document' => $item]);
	}

	/**
	 * @param Item\Document $item
	 *
	 * @return UpdateResult
	 */
	public function update(Item\Document $item): Result
	{
		if (!$item->id)
		{
			return (new UpdateResult())->addError(new Error('Document not found'));
		}
		$document = Internal\DocumentTable::getById($item->id)
			->fetchObject();

		if (!$document)
		{
			return (new UpdateResult())->addError(new Error('Document not found'));
		}

		if (isset($item->blankId))
		{
			$document->setBlankId($item->blankId);
		}

		if (isset($item->uid))
		{
			$document->setUid($item->uid);
			// Backward compatibility
			$document->setHash($item->uid);
		}

		if (isset($item->title))
		{
			$document->setTitle($item->title);
		}

		if (isset($item->status))
		{
			$document->setStatus($item->status);
		}

		if (isset($item->langId))
		{
			$document->setLangId($item->langId);
		}

		if (isset($item->entityId))
		{
			$document->setEntityId($item->entityId);
		}

		if (isset($item->entityType))
		{
			$document->setEntityType($item->entityType);
		}

		if (isset($item->resultFileId))
		{
			$document->setResultFileId($item->resultFileId);
		}

		if (isset($item->scenario))
		{
			$scenarioId = $this->getScenarioIdByName($item->scenario);
			if ($scenarioId === null)
			{
				return (new Result())->addError(new Error("Scenario with name: `{$item->scenario}` doesnt exist"));
			}

			$document->setScenario($scenarioId);
		}

		if (isset($item->companyUid))
		{
			$document->setCompanyUid($item->companyUid);
		}

		if (isset($item->representativeId))
		{
			$document->setRepresentativeId($item->representativeId);
		}

		if (isset($item->dateCreate))
		{
			$document->setDateCreate($item->dateCreate);
		}

		if (isset($item->dateSign))
		{
			$document->setDateSign($item->dateSign);
		}

		if (isset($item->parties))
		{
			$document->setParties($item->parties);
		}

		if (isset($item->regionDocumentType))
		{
			$document->setRegionDocumentType($item->regionDocumentType);
		}

		if (isset($item->externalId))
		{
			$document->setExternalId($item->externalId);
		}

		if (isset($item->scheme))
		{
			$document->setScheme(
				$this->getSchemeIdByType($item->scheme),
			);
		}

		if (isset($item->stoppedById))
		{
			$document->setStoppedById($item->stoppedById);
		}

		if (isset($item->externalDateCreate))
		{
			$document->setExternalDateCreate($item->externalDateCreate);
		}

		if (isset($item->providerCode))
		{
			$document->setProviderCode($item->providerCode);
		}

		if (isset($item->templateId))
		{
			$document->setTemplateId($item->templateId);
		}

		if (isset($item->createdFromDocumentId))
		{
			$document->setCreatedFromDocumentId($item->createdFromDocumentId);
		}

		if (isset($item->initiatedByType))
		{
			$document->setInitiatedByType($item->initiatedByType->toInt());
		}

		$document->setMeta($this->getModelMetaByItem($item));

		return $document->save()
			->setData(['document' => $item])
		;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByUid(string $uid): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('UID', $uid)
			->setLimit(1)
			->fetchObject();

		return $document === null ? null : $this->extractItemFromModel($document);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByHash(string $uid): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('HASH', $uid)
			->setLimit(1)
			->fetchObject();

		return $document === null ? null : $this->extractItemFromModel($document);
	}

	public function getCountByBlankId(int $blankId): int
	{
		$res = Internal\DocumentTable::getList([
			'select' => [
				new \Bitrix\Main\Entity\ExpressionField(
					'CNT', 'COUNT(*)',
				),
			],
			'filter' => [
				'BLANK_ID' => $blankId,
			],
		]);

		if ($row = $res->fetch())
		{
			return $row['CNT'];
		}

		return 0;
	}

	public function listLastByUserCreateId(int $id, int $limit = 10): Item\DocumentCollection
	{
		/** @var Internal\DocumentCollection $models */
		$models = Internal\DocumentTable::query()
			->addSelect('*')
			->where('CREATED_BY_ID', $id)
			->setLimit($limit)
			->addOrder('DATE_CREATE', 'DESC')
			->fetchCollection()
		;
		return $models === null
			? new Item\DocumentCollection()
			: $this->extractItemCollectionByModelCollection($models)
		;
	}

	private function extractItemCollectionByModelCollection(Internal\DocumentCollection $models): Item\DocumentCollection
	{
		return new Item\DocumentCollection(
			...array_map([$this, 'extractItemFromModel'],
			$models->getAll(),
		));
	}

	private function extractItemFromModel(Internal\Document $model): Item\Document
	{
		$meta = $model->getMeta();
		$scenarioId = $model->getScenario();
		$scenario = $scenarioId === null ? $scenarioId : $this->getScenarioNameById($scenarioId);

		return new Item\Document(
			scenario: $scenario,
			parties: $model->getParties() ?? self::DOCUMENT_DEFAULT_PARTIES_COUNT,
			id: $model->getId(),
			title: $model->getTitle(),
			uid: $model->getUid(),
			blankId: $model->getBlankId(),
			langId: $model->getLangId(),
			status: $model->getStatus(),
			initiator: $meta['initiatorName'] ?? null,
			entityType: $model->getEntityType(),
			entityTypeId: EntityType::getEntityTypeIdByType($model->getEntityType()),
			entityId: $model->getEntityId(),
			resultFileId: $model->getResultFileId(),
			version: $model->getVersion(),
			createdById: $model->getCreatedById(),
			companyUid: $model->getCompanyUid(),
			representativeId: $model->getRepresentativeId(),
			scheme: $this->getSchemeTypeById($model->getScheme()),
			dateCreate: $model->getDateCreate(),
			dateSign: $model->getDateSign(),
			regionDocumentType: $model->getRegionDocumentType(),
			externalId: $model->getExternalId(),
			stoppedById: $model->getStoppedById(),
			externalDateCreate: $model->getExternalDateCreate(),
			providerCode: $model->getProviderCode(),
			templateId: $model->getTemplateId(),
			createdFromDocumentId: $model->getCreatedFromDocumentId(),
			initiatedByType: InitiatedByType::tryFromInt($model->getInitiatedByType()) ?? InitiatedByType::COMPANY,
		);
	}

	private function getScenarioIdByName(string $scenarioName): ?int
	{
		return self::SCENARIO_NAME_TO_ID_MAP[$scenarioName] ?? null;
	}

	private function getScenarioNameById(int $scenarioId): ?string
	{
		$scenarioIdToNameMap = array_flip(self::SCENARIO_NAME_TO_ID_MAP);
		return $scenarioIdToNameMap[$scenarioId] ?? null;
	}

	private function getSchemeIdByType(?string $scheme): int
	{
		return self::SCHEME_TYPE_TO_ID_MAP[$scheme] ?? 0;
	}

	private function getSchemeTypeById(int $schemeId): string
	{
		$schemeIdToTypeMap = array_flip(self::SCHEME_TYPE_TO_ID_MAP);
		return $schemeIdToTypeMap[$schemeId] ?? SchemeType::DEFAULT;
	}

	public function getById(?int $id): ?Item\Document
	{
		$model = Internal\DocumentTable::getById($id)->fetchObject();
		return $model === null ? null : $this->extractItemFromModel($model);
	}

	public function getLastByUser(?int $userId): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('CREATED_BY_ID', $userId)
			->setOrder('ID')
			->setLimit(1)
			->fetchObject();

		return $document === null ? null : $this->extractItemFromModel($document);
	}

	private function getModelMetaByItem(Item\Document $item): array
	{
		$documentModelMeta = [];

		if ($item->initiator !== null)
		{
			$documentModelMeta[self::INITIATOR_NAME_META_KEY] = $item->initiator;
		}

		return $documentModelMeta;
	}

	/**
	 * Get last document by blank id
	 * @param int $blankId
	 *
	 * @return \Bitrix\Sign\Item\Document|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getLastByBlankId(int $blankId): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('BLANK_ID', $blankId)
			->setOrder('ID')
			->setLimit(1)
			->fetchObject();

		return $document === null ? null : $this->extractItemFromModel($document);
	}

	public function getFirstCreatedB2eDocument(): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->whereIn('SCENARIO', [
				self::SCENARIO_NAME_TO_ID_MAP[DocumentScenario::DSS_ONE_PARTY_MANY_MEMBERS],
				self::SCENARIO_NAME_TO_ID_MAP[DocumentScenario::DSS_SECOND_PARTY_MANY_MEMBERS],
			])
			->setOrder(['ID' => 'ASC'])
			->setLimit(1)
			->fetchObject()
		;

		return $document === null ? null : $this->extractItemFromModel($document);
	}

    public function delete(Item\Document $documentItem): DeleteResult
    {
        return Internal\DocumentTable::delete($documentItem->id);
    }

	public function listByIds(array $ids)
	{
		$models = Internal\DocumentTable::query()
			->addSelect('*')
			->whereIn('ID', $ids)
			->fetchCollection()
		;
		return $models === null
			? new Item\DocumentCollection()
			: $this->extractItemCollectionByModelCollection($models)
		;
	}

	public function listByEntityIdsAndType(array $entityIds, string $entityType): Item\DocumentCollection
	{
		$models = Internal\DocumentTable::query()
			->addSelect('*')
			->whereIn('ENTITY_ID', $entityIds)
			->where('ENTITY_TYPE', $entityType)
			->fetchCollection()
		;
		return $models === null
			? new Item\DocumentCollection()
			: $this->extractItemCollectionByModelCollection($models);
	}

	public function getByEntityIdAndType(int $entityId, string $entityType): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE', $entityType)
			->setLimit(1)
			->fetchObject()
		;

		return $document === null ? null : $this->extractItemFromModel($document);
	}

	public function updateProviderCodeToDocumentsByCompanyUid(string $companyUid, string $providerCode, int $limit = 200): Main\Result
	{
		if ($limit <= 0)
		{
			return new Main\Result();
		}

		$documentIds = Internal\DocumentTable::query()
			->setSelect(['ID'])
			->where('COMPANY_UID', $companyUid)
			->where(
				Query::filter()->logic('or')
					->whereNull('PROVIDER_CODE')
					->whereNot('PROVIDER_CODE', $providerCode),
			)
			->setLimit($limit)
			->fetchAll()
		;
		$documentIds = array_column($documentIds, "ID");
		if (empty($documentIds))
		{
			return new Main\Result();
		}

		return Internal\DocumentTable::updateMulti($documentIds, ['PROVIDER_CODE' => $providerCode]);
	}

	/**
	 * @param ProviderCode::* $providerCode
	 * @param SchemeType::* $scheme
	 *
	 * @return list<int>
	 */
	public function listIdsByProviderCodeAndScheme(string $providerCode, string $scheme, int $limit): array
	{
		if ($limit < 1)
		{
			return [];
		}

		$result = Internal\DocumentTable::query()
			->setSelect(['ID'])
			->where('PROVIDER_CODE', $providerCode)
			->where('SCHEME', $scheme)
			->setLimit($limit)
			->fetchAll()
		;
		$documentIds = array_column($result, "ID");

		return $documentIds;
	}

	/**
	 * @param array<int> $documentIds
	 * @param string $newScheme
	 *
	 * @return Result
	 */
	public function updateSchemeToDocumentIds(array $documentIds, string $newScheme): Result
	{
		if (empty($documentIds))
		{
			return new Result();
		}

		$documentIds = array_unique($documentIds);

		return Internal\DocumentTable::updateMulti($documentIds, ['SCHEME' => $this->getSchemeIdByType($newScheme)]);
	}

	/**
	 * @param array<int> $templateIds
	 */
	public function listByTemplateIds(array $templateIds): Item\DocumentCollection
	{
		if (empty($templateIds))
		{
			return new Item\DocumentCollection();
		}

		$models = Internal\DocumentTable::query()
			->setSelect(['*'])
			->whereIn('TEMPLATE_ID', $templateIds)
			->fetchCollection()
		;

		return $this->extractItemCollectionByModelCollection($models);
	}

	public function getByTemplateId(int $id): ?Item\Document
	{
		$document = Internal\DocumentTable::query()
			->setSelect(['*'])
			->where('TEMPLATE_ID', $id)
			->setLimit(1)
			->fetchObject()
		;

		return $document === null ? null : $this->extractItemFromModel($document);
	}
}
