<?php

namespace Bitrix\Crm\Component\EntityList;

class GridId
{
	public const DEFAULT_GRID_MY_COMPANY_SUFFIX = 'MYCOMPANY';

	public const DEFAULT_GRID_ID_PREFIX = 'CRM_';
	protected const DEFAULT_GRID_ID_SUFFIX = '_LIST_V12';
	protected const DYNAMIC_TYPE_GRID_ID_PREFIX = 'crm-type-item-list';

	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function getValue(string $suffix = '', array $context = []): string
	{
		$divider = \CCrmOwnerType::isUseDynamicTypeBasedApproach($this->entityTypeId) ? '-' : '_';
		if ($suffix !== '')
		{
			$suffix = $divider . $suffix;
		}

		return $this->getBaseValue($context) . $suffix;
	}

	public function getValueForCategory(int $categoryId, array $context = []): string
	{
		$gridSuffix = $this->getDefaultSuffix($categoryId);

		return $this->getValue($gridSuffix, $context);
	}

	public function getBaseValue(array $context = []): string
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->entityTypeId))
		{
			return static::DYNAMIC_TYPE_GRID_ID_PREFIX . '-' . $this->entityTypeId;
		}

		$gridId = \CCrmOwnerType::ResolveName($this->entityTypeId);
		if (
			$this->entityTypeId === \CCrmOwnerType::Deal
			&& (isset($context['IS_RECURRING']) && $context['IS_RECURRING'])
		)
		{
			$gridId = 'DEAL_RECUR';
		}

		if (
			$this->entityTypeId === \CCrmOwnerType::Company
			&& (isset($context['IS_MY_COMPANY']) && $context['IS_MY_COMPANY'])
		)
		{
			$gridId = static::DEFAULT_GRID_MY_COMPANY_SUFFIX;
		}

		// possible values:
		//
		// CRM_LEAD_LIST_V12
		// CRM_DEAL_LIST_V12
		// CRM_DEAL_RECUR_LIST_V12
		// CRM_CONTACT_LIST_V12
		// CRM_COMPANY_LIST_V12
		// CRM_MYCOMPANY_LIST_V12
		// etc...

		return static::DEFAULT_GRID_ID_PREFIX . $gridId . static::DEFAULT_GRID_ID_SUFFIX;
	}

	public function getDefaultSuffix(int $categoryId): string
	{
		if ($this->entityTypeId === \CCrmOwnerType::Deal)
		{
			return $categoryId >= 0 ? "C_{$categoryId}" : '';
		}

		if ($this->entityTypeId === \CCrmOwnerType::Contact || $this->entityTypeId === \CCrmOwnerType::Company)
		{
			return $categoryId > 0 ? "C_{$categoryId}" : '';
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->entityTypeId))
		{
			return $categoryId > 0 ? (string)$categoryId : '';
		}

		return '';
	}
}
