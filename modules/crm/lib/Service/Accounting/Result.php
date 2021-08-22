<?php

namespace Bitrix\Crm\Service\Accounting;

use Bitrix\Main\Error;

class Result extends \Bitrix\Main\Result
{
	protected $currencyId = '';
	protected $personTypeId = 0;
	protected $price = 0;
	protected $locationId = '';
	protected $taxValue = 0;

	public const CALCULATE_ERROR_CODE_UNDEFINED = 0;
	public const CALCULATE_ERROR_CODE_SALE_MODULE_NOT_INSTALLED = 1;
	public const CALCULATE_ERROR_CODE_COULD_NOT_GET_USER_ID = 2;
	public const CALCULATE_ERROR_CODE_INVALID_PRODUCT_ROWS = 3;

	public function addErrorByCalculateError(int $errorCode): self
	{
		if ($errorCode === static::CALCULATE_ERROR_CODE_SALE_MODULE_NOT_INSTALLED)
		{
			return $this->addError(new Error('Sale module is not installed'));
		}
		if ($errorCode === static::CALCULATE_ERROR_CODE_COULD_NOT_GET_USER_ID)
		{
			return $this->addError(new Error('Could not get anonymous user id to calculate order'));
		}
		if ($errorCode === static::CALCULATE_ERROR_CODE_INVALID_PRODUCT_ROWS)
		{
			return $this->addError(new Error('Invalid product rows'));
		}

		return $this->addError(new Error('Could not calculate total sums'));
	}

	public static function initializeFromArray(?array $data): self
	{
		$result = new self;
		if ($data === null)
		{
			return $result->addErrorByCalculateError(static::CALCULATE_ERROR_CODE_UNDEFINED);
		}
		if (isset($data['err']))
		{
			return $result->addErrorByCalculateError((int)$data['err']);
		}

		$result->price = (float)($data['PRICE'] ?? 0);
		$result->currencyId = $data['CURRENCY'] ?? '';
		$result->personTypeId = (int)($data['PERSON_TYPE_ID'] ?? 0);
		$result->locationId = (string)($data['TAX_LOCATION'] ?? '');
		$result->taxValue = (float)($data['TAX_VALUE'] ?? 0);

		$result->data = $data;

		return $result;
	}

	public function getPrice(): ?float
	{
		return $this->price;
	}

	public function getCurrencyId(): ?string
	{
		return $this->currencyId;
	}

	public function getPersonTypeId(): ?int
	{
		return $this->personTypeId;
	}

	public function getLocationId(): ?string
	{
		return $this->locationId;
	}

	public function getTaxValue(): ?float
	{
		return $this->taxValue;
	}
}
