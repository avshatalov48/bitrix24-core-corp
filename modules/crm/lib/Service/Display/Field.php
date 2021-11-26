<?php

namespace Bitrix\Crm\Service\Display;


class Field
{
	protected $type = 'string';
	protected $id = '';
	protected $title = '';
	protected $wasRenderedAsHtml = false;
	protected $isMultiple = false;
	protected $isUserField = false;
	protected $displayRawValue = false;
	protected $userFieldParams = [];
	protected $displayParams = [];

	public function __construct(string $id)
	{
		$this->setId($id);
	}

	public static function createFromUserField(string $id, array $userFieldInfo): Field
	{
		$title = $userFieldInfo['LIST_COLUMN_LABEL']
			?? $userFieldInfo['EDIT_FORM_LABEL']
			?? $userFieldInfo['LIST_FILTER_LABEL']
			?? $id
		;

		return (new self($id))
			->setTitle($title)
			->setType($userFieldInfo['USER_TYPE_ID'])
			->setIsMultiple($userFieldInfo['MULTIPLE'] === 'Y')
			->setDisplayParams((array)$userFieldInfo['SETTINGS'])
			->setIsUserField(true)
			->setUserFieldParams($userFieldInfo)
		;
	}

	public static function createFromBaseField(string $id, array $baseFieldInfo): Field
	{
		$displayParams = [];

		$type = $baseFieldInfo['TYPE'] ?? 'string';
		switch ($type)
		{
			case 'crm':
				$crmFieldsMap = [
					'LEAD_ID' => ['LEAD' => 'Y'],
					'DEAL_ID' => ['DEAL' => 'Y'],
					'CONTACT_ID' => ['CONTACT' => 'Y'],
					'COMPANY_ID' => ['COMPANY' => 'Y'],
					'QUOTE_ID' => ['QUOTE' => 'Y'],
					'INVOICE_ID' => ['INVOICE' => 'Y'],
				];
				$displayParams = $crmFieldsMap[$id] ?? [];
				break;
			case 'crm_lead':
				$displayParams = ['LEAD' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_deal':
				$displayParams = ['DEAL' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_contact':
				$displayParams = ['CONTACT' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_company':
				$displayParams = ['COMPANY' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_quote':
				$displayParams = ['QUOTE' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_invoice':
				$displayParams = ['INVOICE' => 'Y'];
				$type = 'crm';
				break;
			case 'crm_status':
				$displayParams = [
					'ENTITY_TYPE' => $baseFieldInfo['CRM_STATUS_TYPE'],
				];
				break;

			case 'char':
				$type = 'boolean';
				break;
		}
		if (isset($baseFieldInfo['VALUE_TYPE']))
		{
			$displayParams['VALUE_TYPE'] = $baseFieldInfo['VALUE_TYPE'];
		}
		$field =
			(new self($id))
				->setType($type)
				->setDisplayParams($displayParams)
		;

		if (isset($baseFieldInfo['TITLE']))
		{
			$field->setTitle($baseFieldInfo['TITLE']);
		}

		return $field;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): Field
	{
		$this->type = $type;

		return $this;
	}
	public function getDisplayParam(string $paramId, $defaultValue = null)
	{
		return $this->displayParams[$paramId] ?? $defaultValue;
	}

	public function getDisplayParams(): array
	{
		return $this->displayParams;
	}

	public function setDisplayParams(array $displayParams): Field
	{
		$this->displayParams = $displayParams;

		return $this;
	}

	public function addDisplayParam(string $paramId, $paramValue): Field
	{
		$this->displayParams[$paramId] = $paramValue;

		return $this;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): Field
	{
		$this->title = $title;

		return $this;
	}

	public function isMultiple(): bool
	{
		return $this->isMultiple;
	}

	public function setIsMultiple(bool $isMultiple): Field
	{
		$this->isMultiple = $isMultiple;

		return $this;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): Field
	{
		$this->id = $id;

		return $this;
	}

	public function isUserField(): bool
	{
		return $this->isUserField;
	}

	public function setIsUserField(bool $isUserField): Field
	{
		$this->isUserField = $isUserField;

		return $this;
	}

	public function getUserFieldParams(): array
	{
		return $this->userFieldParams;
	}

	public function setUserFieldParams(array $userFieldParams): Field
	{
		$this->userFieldParams = $userFieldParams;

		return $this;
	}

	public function wasRenderedAsHtml(): bool
	{
		return $this->wasRenderedAsHtml;
	}

	public function setWasRenderedAsHtml(bool $wasRenderedAsHtml): Field
	{
		$this->wasRenderedAsHtml = $wasRenderedAsHtml;

		return $this;
	}

	public function isValueEmpty($value): bool
	{
		if ($this->getType() === 'boolean')
		{
			return false;
		}

		if ($value === null)
		{
			return true;
		}

		if ($this->isMultiple() && $value === false)
		{
			return true;
		}

		$isBaseTypeString = $this->getType() === 'string';
		if (
			!$isBaseTypeString
			&& $this->isUserField()
			&& isset($this->userFieldParams['USER_TYPE']['BASE_TYPE'])
			&& $this->userFieldParams['USER_TYPE']['BASE_TYPE'] === 'string'
		)
		{
			$isBaseTypeString = true;
		}

		if (
			$isBaseTypeString
			&& (
				empty($value)
				|| (
					is_string($value)
					&& empty(trim($value))
				)
			)
		)
		{
			return true;
		}

		return false;
	}

	public function needDisplayRawValue(): bool
	{
		return $this->displayRawValue;
	}

	public function setDisplayRawValue(bool $displayRawValue): Field
	{
		$this->displayRawValue = $displayRawValue;

		return $this;
	}
}
