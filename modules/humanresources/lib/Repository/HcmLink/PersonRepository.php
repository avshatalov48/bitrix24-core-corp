<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\PersonTable;
use Bitrix\HumanResources\Result\Result;
use Bitrix\HumanResources\Result\SuccessResult;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\Content;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class PersonRepository implements Contract\Repository\HcmLink\PersonRepository
{
	public function add(Item\HcmLink\Person $person): Item\HcmLink\Person
	{
		$model = $this->fillModelFromItem($person);

		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new CreationFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	public function update(Item\HcmLink\Person $person): Item\HcmLink\Person
	{
		$model = PersonTable::getById($person->id)->fetchObject();
		if ($model === null)
		{
			throw (new UpdateFailedException())->setErrors('You pass not saved person');
		}

		$person->updatedAt = new DateTime();

		$model = $this->fillModelFromItem($person, $model);
		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}

		return $person;
	}

	public function saveAll(Item\Collection\HcmLink\PersonCollection $personCollection)
	{
		$persons = $personCollection->getItemMap();
		array_walk($persons, [$this, 'save']);

		return new Item\Collection\HcmLink\PersonCollection(...$persons);
	}

	public function getByCompany(int $companyId): Item\Collection\HcmLink\PersonCollection
	{
		return $this->getAllBy([['=COMPANY_ID' => $companyId]]);
	}

	public function getModelByUnique(int $companyId, string $code): ?Model\HcmLink\Person
	{
		$query = PersonTable::query()
							->setSelect(['*'])
							->setFilter(['=COMPANY_ID' => $companyId, '=CODE' => $code])
							->setLimit(1)
		;

		return $query->fetchObject();
	}

	public function getByUnique(int $companyId, string $code): ?Item\HcmLink\Person
	{
		$model = $this->getModelByUnique($companyId, $code);

		return $model ? $this->getItemFromModel($model) : null;
	}

	public function getList(
		int $companyId,
		int $limit = 100,
		int $offset = 0,
		?DateTime $fromDate = null,
	): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->where('COMPANY_ID', $companyId)
			->setLimit($limit)
			->setOffset($offset)
			->addOrder('UPDATED_AT')
		;

		if ($fromDate)
		{
			$query->where('UPDATED_AT', '>=', $fromDate);
		}

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getMappedUserIdsByCompanyId($companyId): array
	{
		$userIds = PersonTable::query()
			->setSelect(['USER_ID'])
			->setDistinct()
			->where('COMPANY_ID', $companyId)
			->where('USER_ID', '!=', 0)
			->fetchAll()
		;

		return array_map('intval', array_column($userIds, 'USER_ID'));
	}

	protected function getAllBy(array $filter): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->addOrder('UPDATED_AT')
		;

		$result = [];
		foreach ($query->fetchCollection()->getAll() as $item)
		{
			$result[] = $this->getItemFromModel($item);
		}

		return new Item\Collection\HcmLink\PersonCollection(...$result);
	}
	protected function getBy(array $filter): ?Item\HcmLink\Person
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->setLimit(1)
		;
		$model = $query->fetchObject();

		return  $model ? $this->getItemFromModel($model): null;
	}
	protected function fillModelFromItem(
		Item\HcmLink\Person $item,
		?Model\HcmLink\Person $model = null,
	): Model\HcmLink\Person
	{
		$model = $model ?? PersonTable::createObject(true);
		$model->setCompanyId($item->companyId)
			->setUserId($item->userId)
			->setCode($item->code)
			->setTitle($item->title)
		;

		if ($item->createdAt)
		{
			$model->setCreatedAt($item->createdAt);
		}

		if ($item->updatedAt)
		{
			$model->setUpdatedAt($item->updatedAt);
		}

		return $model;
	}

	protected function getItemFromModel(
		Model\HcmLink\Person $model,
	): Item\HcmLink\Person
	{
		return new Item\HcmLink\Person(
			companyId: $model->getCompanyId(),
			code: $model->getCode(),
			title: $model->getTitle(),
			createdAt: $model->getCreatedAt(),
			updatedAt: $model->getUpdatedAt(),
			userId: $model->getUserId(),
			id: $model->hasId() ? $model->getId() : null,
		);
	}

	protected function toItemCollection(
		Model\HcmLink\PersonCollection $collection,
	): Item\Collection\HcmLink\PersonCollection
	{
		$result = [];
		foreach ($collection as $model)
		{
			$result[] = $this->getItemFromModel($model);
		}

		return new Item\Collection\HcmLink\PersonCollection(...$result);
	}

	public function getPersonCollectionWithoutMapped(int $companyId): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->whereNull('USER_ID')
			->where('COMPANY_ID', $companyId)
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getByIdsExcludeMapped(array $ids, int $companyId): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->where('USER_ID', 0)
			->where('COMPANY_ID', $companyId)
			->whereIn('ID', $ids)
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getByCompanyExcludeMapped(int $companyId, ?int $limit = null): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->where('USER_ID', 0)
			->where('COMPANY_ID', $companyId)
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getMappedPersons(
		ConditionTree $filter,
		int $limit = 20,
		int $offset = 0,
	): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->where('USER_ID', '!=', 0)
			->setLimit($limit)
			->setOffset($offset)
			->where($filter)
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	public function getMappedPersonsByCompanyId(
		int $companyId,
		ConditionTree $filter,
		int $limit = 20,
		int $offset = 0,
	): Item\Collection\HcmLink\PersonCollection
	{
		$query = PersonTable::query()
			->setSelect(['*'])
			->where('COMPANY_ID', $companyId)
			->where('USER_ID', '!=', 0)
			->setLimit($limit)
			->setOffset($offset)
			->where($filter)
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	public function countAllMappedByCompanyId(int $companyId): int
	{
		return (int)PersonTable::query()
			->where('USER_ID', '!=', 0)
			->where('COMPANY_ID', $companyId)
			->queryCountTotal()
		;
	}

	public function deleteLink(Item\Collection\HcmLink\PersonCollection $personCollection): Item\Collection\HcmLink\PersonCollection
	{
		$newCollection = new PersonCollection();

		foreach ($personCollection as $person)
		{
			$result = PersonTable::update(
				$person->id,
				[
					'USER_ID' => 0,
					'UPDATED_AT' => new DateTime(),
				]
			);

			if ($result->isSuccess())
			{
				$newCollection->add($person);
			}
		}

		return $newCollection;
	}

	public function getListByIds(array $ids): Item\Collection\HcmLink\PersonCollection
	{
		$persons = PersonTable::query()
			->setSelect(['*'])
			->whereIn('ID', $ids)
			->fetchCollection()
			->getAll()
		;

		$result = array_map([$this, 'getItemFromModel'], $persons);

		return new Item\Collection\HcmLink\PersonCollection(...$result);
	}

	public function getById(int $id): ?Item\HcmLink\Person
	{
		$model = PersonTable::query()
			->setSelect(['*'])
			->whereIn('ID', $id)
			->fetchObject()
		;

		return $model !== null ? $this->getItemFromModel($model) : null;
	}

	//todo perf
	public function getCountMappedPersonsByUserIds(int $companyId, array $userIds): int
	{
		$query = (new ConditionTree())
			->where('COMPANY_ID', $companyId)
			->whereIn('USER_ID', $userIds)
		;

		return PersonTable::getCount($query);
	}

	public function searchByIndexAndCompanyId(string $search, int $companyId, int $limit = 20): PersonCollection
	{
		$models = PersonTable::query()
			->registerRuntimeField(
				new ReferenceField('INDEX',
					Model\HcmLink\Index\PersonTable::getEntity(),
					['=this.ID' => 'ref.PERSON_ID'],
					['join_type' => Join::TYPE_INNER],
				),
			)
			->where('COMPANY_ID', $companyId)
			->where('USER_ID', 0)
			->whereMatch(
				'INDEX.SEARCH_CONTENT',
				\Bitrix\Main\ORM\Query\Filter\Helper::matchAgainstWildcard(
					Content::prepareStringToken($search), '*', 1,
				),
			)
			->setLimit($limit)
			->fetchCollection()
			->getAll()
		;

		$result = array_map([$this, 'getItemFromModel'], $models);

		return new Item\Collection\HcmLink\PersonCollection(...$result);
	}

	public function deleteSearchIndexByPersonId(int $personId): Result|SuccessResult
	{
		$result = Model\HcmLink\Index\PersonTable::delete($personId);

		if ($result->isSuccess() === false)
		{
			return (new Result())->addError(new Error('Do not delete search index'));
		}

		return new SuccessResult();
	}

	public function updateSearchIndexByPersonId(int $personId, string $searchContent): Result|SuccessResult
	{
		$result = Model\HcmLink\Index\PersonTable::update($personId, ['SEARCH_CONTENT' => $searchContent]);

		if ($result->isSuccess() === false)
		{
			return (new Result())->addError(new Error('Do not update search index'));
		}

		return new SuccessResult();
	}

	public function addSearchIndexByPersonId(int $personId, string $searchContent): \Bitrix\Main\Result|SuccessResult
	{
		$result = Model\HcmLink\Index\PersonTable::add([
			'PERSON_ID' => $personId,
			'SEARCH_CONTENT' => $searchContent,
		]);

		if ($result->isSuccess() === false)
		{
			return (new Result())->addError(new Error('Do not add search index'));
		}

		return new SuccessResult();
	}

	public function hasPersonSearchIndex(int $personId): bool
	{
		$result = Model\HcmLink\Index\PersonTable::query()
			->where('PERSON_ID', $personId)
			->fetch()
		;

		return $result !== false;
	}

	/**
	 * @param list<int> $companyIds
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function countNotMappedAndGroupByCompanyId(array $companyIds = []): array
	{
		$countQuery = Model\HcmLink\PersonTable::query()
			->setSelect(['CNT', 'COMPANY_ID'])
			->registerRuntimeField(
				'',
				new ExpressionField(
					'CNT',
					'COUNT(*)',
				),
			)
			->where('USER_ID', '=', 0)
			->setGroup('COMPANY_ID')
		;

		if (!empty($companyIds))
		{
			$countQuery->whereIn('COMPANY_ID', $companyIds);
		}

		$notMappedCount = $countQuery->fetchAll();
		$result = [];

		foreach ($notMappedCount as $notMapped)
		{
			$result[$notMapped['COMPANY_ID']] = $notMapped['CNT'];
		}

		return $result;
	}

	public function existByUserId(int $userId): bool
	{
		$person = PersonTable::query()
		   ->setSelect(['ID'])
		   ->where('USER_ID', $userId)
		   ->setLimit(1)
		   ->exec()
		;

		return (bool) $person->fetch();
	}

	public function getByUserIdsAndGroupByCompanyId(int $userId): array
	{
		$modelCollection = PersonTable::query()
			->setSelect(['*'])
			->where('USER_ID', $userId)
			->fetchCollection()
		;

		$result = [];
		foreach ($modelCollection->getAll() as $model)
		{
			$result[$model->getCompanyId()] = $this->getItemFromModel($model);
		}

		return $result;
	}

	public function countUnmappedPersons(int $companyId): int
	{
		$countQuery = Model\HcmLink\PersonTable::query()
			->where('USER_ID', 0)
			->where('COMPANY_ID', $companyId)
			->queryCountTotal()
		;

		return (int)$countQuery;
	}

	public function getUnmappedPersonsByCompanyId(int $companyId, int $limit, ?string $searchName = null): PersonCollection
	{
		$query = Model\HcmLink\PersonTable::query()
			->setSelect(['*'])
			->where('USER_ID', 0)
			->where('COMPANY_ID', $companyId)
		;

		if (!empty($searchName))
		{
			$query->registerRuntimeField(
				new Reference(
					'PERSON_INDEX',
					Model\HcmLink\Index\PersonTable::class,
					Join::on('this.ID', 'ref.PERSON_ID'),
					['join_type' => 'INNER'],
				),
			)->whereMatch(
				'PERSON_INDEX.SEARCH_CONTENT',
				\Bitrix\Main\ORM\Query\Filter\Helper::matchAgainstWildcard(
					Content::prepareStringToken($searchName)
				),
			);
		}

		$models = $query->setOrder(['MATCH_COUNTER' => 'ASC', 'ID' => 'ASC'])
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->toItemCollection($models);
	}

	public function updateCounter(int $personId): Result|SuccessResult
	{
		$result = PersonTable::update($personId, [
			'MATCH_COUNTER' => new SqlExpression('?# + 1', 'MATCH_COUNTER')
		]);

		if ($result->isSuccess() === false)
		{
			return (new Result())->addError(new Error('Do not update counter'));
		}

		return new SuccessResult();
	}
}
