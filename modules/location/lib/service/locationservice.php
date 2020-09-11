<?php

namespace Bitrix\Location\Service;

use Bitrix\Location\Entity\Generic\Collection;
use Bitrix\Location\Common\Point;
use Bitrix\Location\Exception\RuntimeException;
use Bitrix\Location\Common\BaseService;
use Bitrix\Main\Result;
use Bitrix\Location\Entity;
use \Bitrix\Location\Repository\LocationRepository;
use \Bitrix\Location\Infrastructure\Service\Config;

/**
 * Class Location *
 * @package Bitrix\Location\Service
 */
final class LocationService extends BaseService
{
	use \Bitrix\Location\Common\RepositoryTrait;

	/** @var LocationService */
	protected static $instance;

	/** @var LocationRepository  */
	protected $repository = null;

	/**
	 * @param int $id
	 * @param string $languageId
	 * @param int $searchScope
	 * @return Entity\Location|null|bool
	 */
	public function findById(int $id, string $languageId, int $searchScope = LOCATION_SEARCH_SCOPE_ALL)
	{
		$result = false;

		try
		{
			$result = $this->repository->findById($id, $languageId, $searchScope);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param string $externalId
	 * @param string $sourceCode
	 * @param string $languageId
	 * @param int $searchScope
	 * @return Entity\Location|bool|null
	 */
	public function findByExternalId(string $externalId, string $sourceCode, string $languageId, int $searchScope = LOCATION_SEARCH_SCOPE_ALL)
	{
		$result = false;

		try
		{
			$result = $this->repository->findByExternalId($externalId, $sourceCode, $languageId, $searchScope);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param Point $point
	 * @param string $languageId
	 * @param int $searchScope
	 * @return Collection|bool
	 */
	public function findByPoint(Point $point, string $languageId, int $searchScope = LOCATION_SEARCH_SCOPE_ALL)
	{
		$result = false;

		try
		{
			$result = $this->repository->findByPoint($point, $languageId, $searchScope);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param string $text
	 * @param string $languageId
	 * @param int $searchScope
	 * @return Entity\Location\Collection|bool
	 */
	public function findByText(string $text, string $languageId, int $searchScope = LOCATION_SEARCH_SCOPE_ALL)
	{
		$result = false;

		try
		{
			$result = $this->repository->findByText($text, $languageId, $searchScope);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param Entity\Location $location
	 * @param string $languageId
	 * @param int $searchScope
	 * @return Entity\Location\Parents|bool
	 */
	public function findParents(Entity\Location $location, string $languageId, int $searchScope = LOCATION_SEARCH_SCOPE_ALL)
	{
		$result = false;

		try
		{
			$result = $this->repository->findParents($location, $languageId, $searchScope);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param Entity\Location $location
	 * @return Result
	 */
	public function save(Entity\Location $location)
	{
		return $this->repository->save($location);
	}

	/**
	 * @param Entity\Location $location
	 * @return Result
	 */
	public function delete(Entity\Location $location)
	{
		return $this->repository->delete($location);
	}

	protected function __construct(Config\Container $config)
	{
		$this->setRepository($config->get('repository'));
		parent::__construct($config);
	}

	/**
	 * @param Entity\Location\Parents $parents
	 * @return Result
	 */
	public function saveParents(Entity\Location\Parents $parents)
	{
		return $this->repository->saveParents($parents);
	}
}
