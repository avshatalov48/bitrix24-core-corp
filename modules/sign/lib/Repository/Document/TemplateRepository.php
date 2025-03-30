<?php

namespace Bitrix\Sign\Repository\Document;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Sign\Internal\Document\Template as TemplateModel;
use Bitrix\Sign\Internal\Document\TemplateCollection as TemplateCollectionModel;
use Bitrix\Sign\Internal\Document\TemplateTable;
use Bitrix\Sign\Item;
use Bitrix\Sign\Model\ItemBinder\BaseItemToModelBinder;
use Bitrix\Sign\Result\Operation\Document\Template\CreateTemplateResult;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

class TemplateRepository
{
	public function add(Item\Document\Template $item): Result
	{
		$item->uid = $this->generateUniqueUid();
		$filledMemberEntity = $this
			->extractModelFromItem($item)
		;

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();
		$item->initOriginal();

		return (new CreateTemplateResult($item));
	}

	public function getByUid(string $uid): ?Item\Document\Template
	{
		$model = TemplateTable::query()
			->setSelect(['*'])
			->where('UID', $uid)
			->setLimit(1)
			->fetchObject()
		;

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function update(Item\Document\Template $item): Result
	{
		$item->dateModify = new Type\DateTime();
		$model = TemplateTable::getByPrimary($item->id)->fetchObject();

		$binder = new BaseItemToModelBinder($item, $model);
		$binder->setChangedItemPropertiesToModel();

		$saveResult = $model->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->initOriginal();

		return (new Result());
	}

	public function list(?int $limit = null): Item\Document\TemplateCollection
	{
		$query = TemplateTable::query()
			->setSelect(['*'])
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	/**
	 * @param Status[] $statuses
	 * @param Visibility[] $visibilities
	 */
	public function listWithStatusesAndVisibility(
		array $statuses,
		array $visibilities,
	): Item\Document\TemplateCollection
	{
		$query = TemplateTable::query()
			->setSelect(['*'])
			->whereIn('STATUS', array_map(static fn($status) => $status->toInt(), $statuses))
			->whereIn('VISIBILITY', array_map(static fn($visibility) => $visibility->toInt(), $visibilities))
			->where('DOCUMENT.INITIATED_BY_TYPE', InitiatedByType::EMPLOYEE->toInt());

		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getB2eEmployeeTemplateList(
		ConditionTree $filter,
		int $limit = 10,
		int $offset = 0,
	): Item\Document\TemplateCollection
	{
		$limit = max(0, $limit);
		$offset = max(0, $offset);

		$query = $this->prepareB2eEmployeeTemplateListQuery($filter, $limit, $offset);
		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getB2eEmployeeTemplateListCount(ConditionTree $filter): int
	{
		$query = $this->prepareB2eEmployeeTemplateListQuery($filter);

		return $query->queryCountTotal();
	}

	private function prepareB2eEmployeeTemplateListQuery(ConditionTree $filter, int $limit = 10, int $offset = 0): Query
	{
		return TemplateTable::query()
			->setSelect(['*'])
			->setLimit($limit)
			->setOffset($offset)
			->where($filter)
			->addOrder('ID', 'DESC')
		;
	}

	private function extractModelFromItem(Item\Document\Template $item): TemplateModel
	{
		return $this->getFilledModelFromItem($item, TemplateTable::createObject(false));
	}

	private function getFilledModelFromItem(Item\Document\Template $item, TemplateModel $model): TemplateModel
	{
		return $model
			->setCreatedById($item->createdById)
			->setStatus($item->status->toInt())
			->setDateCreate($item->dateCreate)
			->setUid($item->uid)
			->setDateModify($item->dateModify)
			->setModifiedById($item->modifiedById)
			->setTitle($item->title)
			->setVisibility($item->visibility->toInt())
		;
	}

	private function extractItemFromModel(TemplateModel $model): Item\Document\Template
	{
		return new Item\Document\Template(
			title: $model->getTitle(),
			createdById: $model->getCreatedById(),
			status: Type\Template\Status::tryFromInt($model->getStatus()) ?? Type\Template\Status::NEW,
			dateCreate: Type\DateTime::createFromMainDateTime($model->getDateCreate()),
			id: $model->getId(),
			uid: $model->getUid(),
			dateModify: Type\DateTime::createFromMainDateTimeOrNull($model->getDateModify()),
			modifiedById: $model->getModifiedById(),
			visibility: Type\Template\Visibility::tryFromInt($model->getVisibility()) ?? Type\Template\Visibility::VISIBLE,
		);
	}

	private function generateUniqueUid(): string
	{
		do
		{
			$uid = $this->generateUid();
		}
		while ($this->existByUid($uid));

		return $uid;
	}

	private function generateUid(): string
	{
		return Random::getStringByAlphabet(32, Random::ALPHABET_ALPHALOWER | Random::ALPHABET_NUM);
	}

	private function existByUid(string $uid): bool
	{
		$row = TemplateTable::query()
			->setSelect(['ID'])
			->where('UID', $uid)
			->setLimit(1)
			->fetch()
		;

		return !empty($row);
	}

	private function extractItemCollectionFromModelCollection(TemplateCollectionModel $models): Item\Document\TemplateCollection
	{
		$items = array_map($this->extractItemFromModel(...), $models->getAll());

		return new Item\Document\TemplateCollection(...$items);
	}

	public function updateTitle(int $templateId, string $title): Result
	{
		return TemplateTable::update($templateId, ['TITLE' => $title]);
	}

	public function updateVisibility(int $templateId, Type\Template\Visibility $visibility): Result
	{
		return TemplateTable::update($templateId, ['VISIBILITY' => $visibility->toInt()]);
	}

	public function getById(int $id): ?Item\Document\Template
	{
		$model = TemplateTable::getByPrimary($id)->fetchObject();

		if ($model === null)
		{
			return null;
		}

		return $this->extractItemFromModel($model);
	}

	public function deleteById(int $id): Result
	{
		return TemplateTable::delete($id);
	}
}