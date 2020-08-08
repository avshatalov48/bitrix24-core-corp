<?php

namespace Bitrix\Location\Service;

use Bitrix\Location\Common\BaseService;
use Bitrix\Location\Common\RepositoryTrait;
use	Bitrix\Location\Entity;
use Bitrix\Location\Exception\RuntimeException;
use Bitrix\Location\Infrastructure\AddressLimit;
use Bitrix\Location\Infrastructure\Service\Config\Container;
use Bitrix\Location\Repository\AddressRepository;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\Result;

Loc::loadMessages(__FILE__);

/**
 * Class Address
 * @package Bitrix\Location\Service
 */
final class AddressService extends BaseService
{
	use RepositoryTrait;

	/** @var AddressService */
	protected static $instance;

	/** @var AddressRepository  */
	protected $repository;

	/**
	 * @param int $addressId
	 * @return Entity\Address|bool|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findById(int $addressId)
	{
		$result = false;

		try
		{
			$result = $this->repository->findById($addressId);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param string $entityId
	 * @param string $entityType
	 * @return Entity\Address\AddressCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByLinkedEntity(string $entityId, string $entityType): Entity\Address\AddressCollection
	{
		$result = false;

		try
		{
			$result = $this->repository->findByLinkedEntity($entityId, $entityType);
		}
		catch (RuntimeException $exception)
		{
			$this->processException($exception);
		}

		return $result;
	}

	/**
	 * @param Entity\Address $address
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\Result|\Bitrix\Main\ORM\Data\UpdateResult
	 */
	public function save(Entity\Address $address)
	{
		if($this->isLimitReached() && AddressLimit::isAddressForLimitation($address))
		{
			return (new Result())
				->addError(
					new Error(
						Loc::getMessage('LOCATION_ADDRESS_SERVICE_LIMIT_IS_REACHED')
					)
				);
		}

		return $this->repository->save($address);
	}

	/**
	 * @param int $addressId
	 * @return \Bitrix\Main\ORM\Data\DeleteResult
	 * @throws \Exception
	 */
	public function delete(int $addressId)
	{
		return $this->repository->delete($addressId);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isLimitReached(): bool
	{
		return AddressLimit::isLimitReached();
	}

	/**
	 * AddressService constructor.
	 * @param Container $config
	 */
	protected function __construct(Container $config)
	{
		$this->setRepository($config->get('repository'));
		parent::__construct($config);
	}
}