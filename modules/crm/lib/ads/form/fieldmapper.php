<?php

namespace Bitrix\Crm\Ads\Form;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\Internals\FieldTable;
use Bitrix\Seo\LeadAds;

Loc::loadMessages(__FILE__);

/**
 * Class FieldMapper.
 * @package Bitrix\Crm\Ads\Form
 */
class FieldMapper
{
	/**
	 * To Ads form.
	 *
	 * @param Form $form CRM-form.
	 * @return array|null
	 */
	public static function toAdsForm(Form $form)
	{
		$fields = $form->getFieldsMap();

		$result = [];
		foreach ($fields as $field)
		{
			if(FieldTable::isUiFieldType($field['type']))
			{
				continue;
			}

			$item = self::getMapTypeItem($field['type']);
			$type = $item ? $item['SEO_TYPE'] : LeadAds\Field::TYPE_INPUT;
			$name = !empty($item['CRM_NAME']) ? $item['CRM_NAME'] : $field['entity_field_name'];

			if ($type === LeadAds\Field::TYPE_CHECKBOX && empty($field['items']))
			{
				$type = LeadAds\Field::TYPE_RADIO;
				$field['items'] = [
					['value' => 'N', 'title' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_NO')],
					['value' => 'Y', 'title' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_YES')],
				];
			}

			$adsField = new LeadAds\Field($type, $name, $field['caption'], $field['name']);

			if (isset($field['items']) && is_array($field['items']))
			{
				foreach ($field['items'] as $fieldItem)
				{
					if (!$fieldItem['title'] || !$fieldItem['value'])
					{
						continue;
					}

					$adsField->addOption($fieldItem['value'], $fieldItem['title']);
				}
			}

			$result[] = $adsField;
		}

		return $result;
	}

	protected static function getMapTypeItem($crmType = null, $seoType = null)
	{
		if (empty($crmType) && empty($seoType))
		{
			return null;
		}

		$map = [
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_PHONE,
				'SEO_TYPE' => LeadAds\Field::TYPE_INPUT,
				'CRM_NAME' => 'PHONE',
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_EMAIL,
				'SEO_TYPE' => LeadAds\Field::TYPE_INPUT,
				'CRM_NAME' => 'EMAIL',
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_STRING,
				'SEO_TYPE' => LeadAds\Field::TYPE_INPUT,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_TYPED_STRING,
				'SEO_TYPE' => LeadAds\Field::TYPE_INPUT,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_LIST,
				'SEO_TYPE' => LeadAds\Field::TYPE_SELECT,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_LIST,
				'SEO_TYPE' => LeadAds\Field::TYPE_SELECT,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_CHECKBOX,
				'SEO_TYPE' => LeadAds\Field::TYPE_CHECKBOX,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_RADIO,
				'SEO_TYPE' => LeadAds\Field::TYPE_RADIO,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_TEXT,
				'SEO_TYPE' => LeadAds\Field::TYPE_TEXT_AREA,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_PRODUCT,
				'SEO_TYPE' => LeadAds\Field::TYPE_SELECT,
				'CRM_NAME' => null,
			],
			[
				'CRM_TYPE' => FieldTable::TYPE_ENUM_BOOL,
				'SEO_TYPE' => LeadAds\Field::TYPE_SELECT,
				'CRM_NAME' => null,
			],
		];

		foreach ($map as $item)
		{
			if ($crmType && $item['CRM_TYPE'] === $crmType)
			{
				return $item;
			}

			if ($seoType && $item['SEO_TYPE'] === $seoType)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @param string $type
	 * @return string[][]
	 */
	public static function getDefaultMap(string $type) : array
	{
		/**@var array<string,string[][]> $mappings*/
		static $mappings;

		$mappings = $mappings ?? [];

		if (!array_key_exists($type,$mappings))
		{
			switch ($type)
			{
				case LeadAds\Service::TYPE_VKONTAKTE:
					$map = [
						['CRM_FIELD_TYPE' => 'CONTACT_NAME', 'ADS_FIELD_TYPE' => 'NAME'],
						['CRM_FIELD_TYPE' => 'CONTACT_LAST_NAME', 'ADS_FIELD_TYPE' => 'LAST_NAME'],
						['CRM_FIELD_TYPE' => 'CONTACT_EMAIL', 'ADS_FIELD_TYPE' => 'EMAIL'],
						['CRM_FIELD_TYPE' => 'CONTACT_PHONE', 'ADS_FIELD_TYPE' => 'PHONE'],
						['CRM_FIELD_TYPE' => 'CONTACT_ADDRESS', 'ADS_FIELD_TYPE' => 'ADDRESS'],
						['CRM_FIELD_TYPE' => 'CONTACT_BIRTHDATE', 'ADS_FIELD_TYPE' => 'BIRTHDAY'],
					];
					break;
				case LeadAds\Service::TYPE_FACEBOOK:
					$map = [
						['CRM_FIELD_TYPE' => 'CONTACT_PHONE', 'ADS_FIELD_TYPE' => 'PHONE'],
						['CRM_FIELD_TYPE' => 'CONTACT_NAME', 'ADS_FIELD_TYPE' => 'FIRST_NAME'],
						['CRM_FIELD_TYPE' => 'CONTACT_LAST_NAME', 'ADS_FIELD_TYPE' => 'LAST_NAME'],
						['CRM_FIELD_TYPE' => 'CONTACT_EMAIL', 'ADS_FIELD_TYPE' => 'EMAIL'],
						['CRM_FIELD_TYPE' => 'COMPANY_NAME', 'ADS_FIELD_TYPE' => 'COMPANY_NAME'],
					];
					break;
				default:
					$map = [];
					break;
			}

			$mappings[$type] = $map;
		}

		return $mappings[$type];
	}
}
