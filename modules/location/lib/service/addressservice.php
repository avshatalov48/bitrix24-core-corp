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
 * Class AddressService
 *
 * Service to work with addresses
 *
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
	 * Find Address by addressId.
	 *
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
	 * Find Address by linked entity
	 *
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
	 * Save Address
	 *
	 * @param Entity\Address $address
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\Result|\Bitrix\Main\ORM\Data\UpdateResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function save(Entity\Address $address)
	{
		if(AddressLimit::isAddressForLimitation($address) && $this->isLimitReached())
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
	 * Delete Address
	 *
	 * @param int $addressId
	 * @return \Bitrix\Main\ORM\Data\DeleteResult
	 * @throws \Exception
	 */
	public function delete(int $addressId): \Bitrix\Main\ORM\Data\DeleteResult
	{
		return $this->repository->delete($addressId);
	}

	/**
	 * Check if Address count limit is reached
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @internal
	 */
	public function isLimitReached(): bool
	{
		$salescenterReceivePaymentAppArea = (defined('SALESCENTER_RECEIVE_PAYMENT_APP_AREA')
			&& SALESCENTER_RECEIVE_PAYMENT_APP_AREA === true
		);
		$crmOrderDetailsArea = (defined('CRM_ORDER_DETAILS_AREA')
			&& CRM_ORDER_DETAILS_AREA === true
		);

		$isSourceLimited = false;
		if($source = SourceService::getInstance()->getSource())
		{
			$isSourceLimited = AddressLimit::isSourceLimited($source->getCode());
		}

		return (
			!$salescenterReceivePaymentAppArea
			&& !$crmOrderDetailsArea
			&& $isSourceLimited
			&& AddressLimit::isLimitReached()
		);
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