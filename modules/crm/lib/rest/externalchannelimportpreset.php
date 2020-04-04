<?php

namespace Bitrix\Crm\Rest;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CCrmExternalChannelImportPreset
 * @package Bitrix\Crm\Rest
 * @deprecated
 */
class CCrmExternalChannelImportPreset
{
	const PRESET_XML_TEMPLATE = '#CRM_REQUISITE_PRESET_DEF_#COUNTRY#_#PERSON_TYPE##';
	const PRESET_PERSON_TYPE_COMPANY = 'COMPANY';
	const PRESET_PERSON_TYPE_PERSON = 'PERSON';

	const OptionName = 'preset_ext_channel';

	protected $class = null;

	public function setOwnerEntity($class)
	{
		$this->class = $class;
	}

	protected function getOwnerEntity()
	{
		return $this->class;
	}

	protected function getPresetCountryById($countryId)
	{
		$arCounries = array();
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/countries.php");

		return array_search($countryId, $arCounries);
	}

	public function getPresetId()
	{
		$id = self::getPresetIdByOption();
		if(empty($id))
		{
			$id = $this->getDefaultPresetId();
		}

		return $id;
	}

	private function getPresetIdByOption()
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		static $presetIdByOption = null;

		if($presetIdByOption === null)
		{
			$typeId = $ownerEntity->getOwnerTypeID();

			$preset = self::getList();
			if(count($preset)>0)
			{
				if($typeId === \CCrmOwnerType::Company)
					$personType = self::PRESET_PERSON_TYPE_COMPANY;
				else
					$personType = self::PRESET_PERSON_TYPE_PERSON;

				if(isset($preset[$personType]))
				{
					$presetIdByOption = (int)$preset[$personType];
				}
			}
		}
		return $presetIdByOption;
	}

	private function getDefaultPresetId()
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		static $presetId = null;

		if($presetId === null)
			$presetId = Crm\EntityRequisite::getDefaultPresetId($ownerEntity->getOwnerTypeID());

		return $presetId;
	}

	public static function setOption(array $params)
	{
		Main\Config\Option::set("crm", self::OptionName, serialize($params));
	}

	public static function getOption()
	{
		return Main\Config\Option::get("crm", self::OptionName, false);
	}

	public static function getList()
	{
		if(($options = static::getOption()) && strlen($options) > 0)
		{
			$options = unserialize($options);
		}
		else
		{
			$options = array();
		}

		return $options;
	}
}