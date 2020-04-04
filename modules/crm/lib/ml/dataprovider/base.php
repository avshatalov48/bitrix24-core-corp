<?php

namespace Bitrix\Crm\Ml\DataProvider;

use Bitrix\Main\Type\DateTime;

abstract class Base
{
	protected $currentDate;

	/**
	 * Constructor.
	 *
	 * @param DateTime|null $currentDate Date to use instead of "current".
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function __construct(DateTime $currentDate = null)
	{
		$this->currentDate = $currentDate ?: new DateTime();
	}

	/**
	 * Returns map of the features, implemented by this provider.
	 *
	 * @return array
	 */
	abstract public function getFeatureMap();

	/**
	 * Returns feature vector for CRM entity.
	 *
	 * @param int $entityTypeId Id of the entity type.
	 * @param int $entityId Entity Id.
	 *
	 * @return array|false
	 */
	abstract public function getFeatures($entityTypeId, $entityId);
}