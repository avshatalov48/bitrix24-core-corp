<?php

namespace Bitrix\Crm\Filter;

class CompanySettings extends EntitySettings implements ISettingsSupportsCategory
{
	const FLAG_ENABLE_ADDRESS = 1;
	/** @var int */
	protected $categoryId;

	function __construct(array $params)
	{
		parent::__construct($params);

		$this->categoryId = isset($params['categoryID'])
			? (int)$params['categoryID'] : null;
	}

	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmCompany::GetUserFieldEntityID();
	}

	/**
	 * @inheritDoc
	 */
	public function getCategoryId(): ?int
	{
		return $this->categoryId;
	}
}
