<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Model\Dynamic\Type;

class ItemSettings extends EntitySettings implements ISettingsSupportsCategory
{
	/** @var Type */
	protected $type;
	protected $categoryId = 0;

	public function __construct(array $params, Type $type)
	{
		parent::__construct($params);
		if(isset($params['categoryId']))
		{
			$this->categoryId = (int)$params['categoryId'];
		}
		$this->type = $type;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getEntityTypeID()
	{
		return $this->getType()->getEntityTypeId();
	}

	public function getEntityTypeName(): string
	{
		return Container::getInstance()->getFactory($this->type->getEntityTypeId())->getUserFieldEntityId();
	}

	/**
	 * @inheritDoc
	 */
	public function getUserFieldEntityID(): string
	{
		return $this->getEntityTypeName();
	}

	/**
	 * @inheritDoc
	 */
	public function getCategoryId(): ?int
	{
		return $this->categoryId;
	}
}
