<?php

namespace Bitrix\Crm\Format;

use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Integration;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Entity\Format as LocationAddressFormat;
use Bitrix\Location\Service\FormatService;

class AddressFormatter
{
	private static $singleInstance = null;

	protected function getLocationAddressByFields(array $fields) : ?Address
	{
		$result = null;

		if (static::isLocationModuleIncluded())
		{
			if (isset($fields['LOC_ADDR_ID']) && $fields['LOC_ADDR_ID'] > 0)
			{
				$result = Address::load((int)$fields['LOC_ADDR_ID']);
			}
			if (!($result instanceof Address))
			{
				$result = EntityAddress::getLocationAddressByFields($fields, EntityAddress::getDefaultLanguageId());
			}
		}

		return $result;
	}

	protected function getLocationAddressFormat(int $crmAddressFormatId) : ?LocationAddressFormat
	{
		$result = null;

		if (static::isLocationModuleIncluded())
		{
			$languageId = EntityAddress::getDefaultLanguageId();
			if (EntityAddressFormatter::isDefined($crmAddressFormatId))
			{
				$result = FormatService::getInstance()->findByCode(
					Integration\location\Format::getLocationFormatCode($crmAddressFormatId),
					$languageId
				);
			}
			else
			{
				$result = FormatService::getInstance()->findDefault($languageId);
			}
		}

		return $result;
	}

	protected function formatViaLocation(array $fields, int $formatId, string $strategy, string $contentType) : string
	{
		$result = '';

		if ($this->isLocationModuleIncluded() && !empty($fields))
		{
			$locationAddress = $this->getLocationAddressByFields($fields);
			if ($locationAddress instanceof Address)
			{
				$format = $this->getLocationAddressFormat($formatId);
				if ($format instanceof LocationAddressFormat)
				{
					$result = $locationAddress->toString($format, $strategy, $contentType);
				}
			}
		}

		return $result;
	}

	public static function getSingleInstance() : AddressFormatter
	{
		if (self::$singleInstance === null)
			self::$singleInstance = new AddressFormatter();

		return self::$singleInstance;
	}

	public function isLocationModuleIncluded(): bool
	{
		return (bool)EntityAddress::isLocationModuleIncluded();
	}

	public function formatLocationAddressArrayAsString(
		array $location,
		$formatId = EntityAddressFormatter::Undefined,
		$strategy = StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
		$content = StringConverter::CONTENT_TYPE_TEXT
	): string
	{
		$result = '';

		$format = $this->getLocationAddressFormat($formatId);

		if ($format instanceof LocationAddressFormat)
		{
			$result = Address::fromArray($location)->toString($format, $strategy, $content);
		}

		return $result;
	}

	public function formatTextComma(
		array $fields,
		$formatId = EntityAddressFormatter::Undefined
	) : string
	{
		$result = '';

		if ($this->isLocationModuleIncluded())
		{
			// TODO: ... [CRM_ADDR_FMT_LOC_1] - modify the formatting in the location module
			$result = $this->formatViaLocation(
				$fields,
				$formatId,
				StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
				StringConverter::CONTENT_TYPE_TEXT
			);
		}
		else
		{
			$result = EntityAddressFormatter::format(
				$fields,
				[
					'FORMAT' => $formatId,
					'SEPARATOR' => AddressSeparator::Comma,
					'NL2BR' => true,
					'HTML_ENCODE' => false
				]
			);
		}

		return $result;
	}

	public function formatTextMultiline(
		array $fields,
		$formatId = EntityAddressFormatter::Undefined
	) : string
	{
		if ($this->isLocationModuleIncluded())
		{
			// TODO: ... [CRM_ADDR_FMT_LOC_2] - modify the formatting in the location module
			$result = $this->formatViaLocation(
				$fields,
				$formatId,
				StringConverter::STRATEGY_TYPE_TEMPLATE,
				StringConverter::CONTENT_TYPE_TEXT
			);
		}
		else
		{
			$result = EntityAddressFormatter::format(
				$fields,
				[
					'FORMAT' => $formatId,
					'SEPARATOR' => AddressSeparator::NewLine,
					'NL2BR' => true,
					'HTML_ENCODE' => false
				]
			);
		}

		return $result;
	}

	public function formatTextMultilineSpecialchar(
		array $fields,
		$formatId = EntityAddressFormatter::Undefined
	) : string
	{
		if ($this->isLocationModuleIncluded())
		{
			// TODO: ... [CRM_ADDR_FMT_LOC_3] - modify the formatting in the location module
			$result = $this->formatViaLocation(
				$fields,
				$formatId,
				StringConverter::STRATEGY_TYPE_TEMPLATE_NL,
				StringConverter::CONTENT_TYPE_HTML
			);
		}
		else
		{
			$result = EntityAddressFormatter::format(
				$fields,
				[
					'FORMAT' => $formatId,
					'SEPARATOR' => AddressSeparator::NewLine,
					'NL2BR' => true,
					'HTML_ENCODE' => true
				]
			);
		}

		return $result;
	}

	public function formatHtmlMultiline(
		array $fields,
		int $formatId = EntityAddressFormatter::Undefined
	) : string
	{
		if ($this->isLocationModuleIncluded())
		{
			// TODO: ... [CRM_ADDR_FMT_LOC_4] - modify the formatting in the location module
			$result = $this->formatViaLocation(
				$fields,
				$formatId,
				StringConverter::STRATEGY_TYPE_TEMPLATE_BR,
				StringConverter::CONTENT_TYPE_TEXT
			);
		}
		else
		{
			$result = EntityAddressFormatter::format(
				$fields,
				[
					'FORMAT' => $formatId,
					'SEPARATOR' => AddressSeparator::HtmlLineBreak,
					'NL2BR' => true,
					'HTML_ENCODE' => false
				]
			);
		}

		return $result;
	}

	public function formatHtmlMultilineSpecialchar(
		array $fields,
		$formatId = EntityAddressFormatter::Undefined
	) : string
	{
		if ($this->isLocationModuleIncluded())
		{
			// TODO: ... [CRM_ADDR_FMT_LOC_5] - modify the formatting in the location module
			$result = $this->formatViaLocation(
				$fields,
				$formatId,
				StringConverter::STRATEGY_TYPE_TEMPLATE_BR,
				StringConverter::CONTENT_TYPE_HTML
			);
		}
		else
		{
			$result = EntityAddressFormatter::format(
				$fields,
				[
					'FORMAT' => $formatId,
					'SEPARATOR' => AddressSeparator::HtmlLineBreak,
					'NL2BR' => true,
					'HTML_ENCODE' => true
				]
			);
		}

		return $result;
	}
}