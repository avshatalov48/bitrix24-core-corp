<?php

namespace Bitrix\Crm\Component\EntityList;


use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Localization\Loc;

class ClientFieldHelper
{
	protected $entityTypeId;
	protected $fieldsWithoutPrefix = [
		\CCrmOwnerType::Company => [
			'COMPANY_TYPE'
		]
	];

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
		if (!in_array($this->entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			throw new NotSupportedException(
				\CCrmOwnerType::ResolveName($this->entityTypeId) . 'is not a client entity'
			);
		}
	}

	/**
	 * Will return "CONTACT_" or "COMPANY_"
	 *
	 * @return string
	 */
	public function getFieldPrefix(): string
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeId) . '_';
	}

	/**
	 * Remove CONTACT_/COMPANY_ from beginning of $fieldId
	 *
	 * @param string $fieldId
	 * @return string
	 */
	public function getFieldIdWithoutPrefix(string $fieldId): string
	{
		$fieldsWithoutPrefix = $this->fieldsWithoutPrefix[$this->entityTypeId] ?? [];
		if (!in_array($fieldId, $fieldsWithoutPrefix))
		{
			$fieldPrefix = $this->getFieldPrefix();
			$fieldId = mb_substr($fieldId, mb_strlen($fieldPrefix));
		}

		return $fieldId;
	}

	/**
	 * Add CONTACT_/COMPANY_ to beginning of $fieldId
	 *
	 * @param string $fieldId
	 * @return string
	 */
	public function addPrefixToFieldId(string $fieldId): string
	{
		$fieldsWithoutPrefix = $this->fieldsWithoutPrefix[$this->entityTypeId] ?? [];

		if (in_array($fieldId, $fieldsWithoutPrefix))
		{
			return $fieldId;
		}

		return $this->getFieldPrefix() . $fieldId;
	}

	/**
	 * Get field name (caption)
	 * Entity name prefix may be included, like "Contact: Last name"
	 *
	 * @param string $fieldIdWithoutPrefix
	 * @param bool $addPrefix
	 * @return string
	 */
	public function getFieldName(string $fieldIdWithoutPrefix, bool $addPrefix = false): string
	{
		$entity = $this->getEntityClass();
		$name = $entity::GetFieldCaption($fieldIdWithoutPrefix);

		return
			$addPrefix
				? $this->addPrefixToFieldName($name)
				: $name
		;
	}

	/**
	 * Add entity name prefix to field name (caption)
	 * Result looks like "Contact: $fieldName"
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function addPrefixToFieldName(string $fieldName): string
	{
		$namePattern = '';
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				$namePattern = Loc::getMessage('CRM_CLIENT_DATA_PROVIDER_CONTACT');
				break;
			case \CCrmOwnerType::Company:
				$namePattern = Loc::getMessage('CRM_CLIENT_DATA_PROVIDER_COMPANY');
				break;
		}

		if (mb_strpos($namePattern, '#TITLE#') === false)
		{
			$namePattern .= ': #TITLE#';
		}

		return str_replace('#TITLE#', $fieldName, $namePattern);
	}

	public function getEntityTitle(): string
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				return Loc::getMessage('CRM_CLIENT_DATA_PROVIDER_CONTACT_TITLE');
			case \CCrmOwnerType::Company:
				return Loc::getMessage('CRM_CLIENT_DATA_PROVIDER_COMPANY_TITLE');
		}

		return '';
	}
	
	public function getEntityClass(): string
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				return \CCrmContact::class;

			case \CCrmOwnerType::Company:
				return \CCrmCompany::class;
		}
	}
}

