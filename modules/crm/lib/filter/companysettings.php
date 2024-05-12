<?php

namespace Bitrix\Crm\Filter;

class CompanySettings extends EntitySettings implements ISettingsSupportsCategory
{
	const FLAG_ENABLE_ADDRESS = 1;
	/** @var int */
	protected $categoryId;

	/** @var string[]  */
	private array $unsupportedFields = [];

	private bool $isMyCompanyMode = false;

	function __construct(array $params)
	{
		parent::__construct($params);
		$this->isMyCompanyMode = ($params['MYCOMPANY_MODE'] ?? false) === true;

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

	/**
	 * @return string[]
	 */
	public function unsupportedFields(): array
	{
		return $this->unsupportedFields;
	}

	public function isMyCompanyMode(): bool
	{
		return $this->isMyCompanyMode;
	}
}
