<?php

namespace Bitrix\Sign\Repository\Document;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Internal\Document\EO_Template_Query;
use Bitrix\Sign\Internal\Document\Template as TemplateModel;
use Bitrix\Sign\Internal\Document\TemplateCollection as TemplateCollectionModel;
use Bitrix\Sign\Internal\Document\TemplateTable;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;

class TemplateRepository
{
	public function add(Item\Document\Template $item): Result
	{
		$item->uid = $this->generateUniqueUid();
		$filledMemberEntity = $this
			->extractModelFromItem($item)
			->setUid($item->uid)
		;

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return (new Result());
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
		$now = new DateTime();
		$model = TemplateTable::getByPrimary($item->id)->fetchObject();
		$filledMemberEntity = $this->getFilledModelFromItem($item, $model)
			->setDateModify($now)
		;

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

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

	public function listWithStatuses(Type\Template\Status... $statuses): Item\Document\TemplateCollection
	{
		$query = TemplateTable::query()
			->setSelect(['*'])
			->whereIn('STATUS', array_map(static fn($status) => $status->toInt(), $statuses))
		;

		$models = $query->fetchCollection();

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function getB2eEmployeeTemplateList(ConditionTree $filter, int $limit = 10, int $offset = 0): Item\Document\TemplateCollection
	{
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
}