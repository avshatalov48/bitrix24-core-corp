<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\EntityImplementation;

use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Timeline\HistoryDataModel\EntityImplementation;

class FactoryBased extends EntityImplementation
{
	/** @var Factory */
	protected $factory;

	/**
	 * FactoryBased constructor.
	 *
	 * @param Factory $factory
	 */
	public function __construct(Factory $factory)
	{
		$this->factory = $factory;

		parent::__construct($this->factory->getEntityTypeId());
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldTitle(string $fieldName): ?string
	{
		return $this->factory->getFieldCaption($fieldName);
	}

	protected function getEntityTypeCaption(): string
	{
		return $this->factory->getEntityDescription();
	}
}
