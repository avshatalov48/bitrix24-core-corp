<?php

namespace Bitrix\Crm\Service\WebForm\DefaultValue;

use Bitrix\Main\PhoneNumber\MetadataProvider;
use Bitrix\Main\PhoneNumber\Parser;

class ContactPhoneRule implements Rule
{
	public function getTargetFieldType(): string
	{
		return 'CONTACT_PHONE';
	}

	public function getValueKey(): string
	{
		return 'VALUE';
	}

	public function isApplicable(array $field): bool
	{
		return is_null($field['VALUE']);
	}

	public function getValue(array $field): string
	{
		$userDefaultPhoneCode = \CUserOptions::GetOption('crm', 'webform_phone_default_code', null);

		if (!is_null($userDefaultPhoneCode))
		{
			return $userDefaultPhoneCode;
		}

		return $this->getRegionPhoneCode() ?? '';
	}

	private function getRegionPhoneCode(): ?string
	{
		$countryIsoCode = Parser::detectCountry();
		if (!$countryIsoCode)
		{
			return null;
		}

		$metadataProvider = MetadataProvider::getInstance();
		$countryMetadata = $metadataProvider->getCountryMetadata($countryIsoCode);
		if ($countryMetadata && isset($countryMetadata['countryCode']))
		{
			return "+{$countryMetadata['countryCode']}";
		}

		return null;
	}
}