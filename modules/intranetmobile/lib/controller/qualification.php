<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Bitrix24\Marketing\Wizard\Constructor\Manager;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class Qualification extends Base
{
	/**
	 * @restMethod intranetmobile.qualification.saveFieldValue
	 * @param $value
	 * @param string $id
	 * @param string $type
	 * @return bool|null
	 */
	public function saveFieldValueAction($value, string $id, string $type): ?bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			$this->errorCollection->setError(new Error('Module "bitrix24" is not installed'));

			return null;
		}

		if ($type === 'phone-number' && is_array($value))
		{
			return $this->savePhoneFieldValue($value, $id);
		}

		return null;
	}

	private function savePhoneFieldValue(array $value, string $id): ?bool
	{
		$phoneNumber = $value['value'] ?? '';
		$countryCode = $value['countryCode'] ?? '';

		if (!$phoneNumber)
		{
			$this->errorCollection->setError(new Error('No phone number provided'));

			return null;
		}

		$phone = new Phone($phoneNumber, $countryCode);
		if (!$phone->isValid())
		{
			$this->errorCollection->setError(new Error('Invalid phone number format'));

			return null;
		}

		Option::set(
			Manager::MODULE_CONFIGURATION,
			"CJM-$id",
			json_encode([
				'number' => $phone->getRawNumber(),
				'country' => $phone->getCountryCode(),
			]),
		);

		return true;
	}
}
