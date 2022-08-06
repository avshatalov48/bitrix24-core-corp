<?php

namespace Bitrix\Crm\Integrity\Volatile;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Localization\Loc;
use CCrmCompany;
use CCrmContact;
use CCrmFieldMulti;
use CCrmLead;
use CCrmOwnerType;
use CCrmUserType;

class FieldInfo
{
	public const PATH_SEPARATOR = '.';
	protected const RQ_FIELD_PREFIX = 'RQ';

	/** @noinspection PhpMissingReturnTypeInspection */
	public static function getInstance()
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	protected function getSupportedEntityTypes(): array
	{
		return [
			CCrmOwnerType::Lead,
			CCrmOwnerType::Company,
			CCrmOwnerType::Contact,
		];
	}

	protected function getClassByEntityType(int $entityTypeId): string
	{
		static $entityClass = [];

		if (!isset($entityClass[$entityTypeId]))
		{
			$entityClass[$entityTypeId] = '\\CCrm' . ucfirst(mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId)));
		}

		return $entityClass[$entityTypeId];
	}

	protected function prepareCountries(array $countries): array
	{
		$currentCountryId = EntityPreset::getCurrentCountryId();
		if ($currentCountryId > 0 && !in_array($currentCountryId, $countries, true))
		{
			array_splice($countries, 0, null, $currentCountryId);
		}

		return $countries;
	}

	protected function sortCountries(array $countries): array
	{
		$result = [];

		if (empty($countries))
		{
			return $result;
		}

		$currentCountryId = EntityPreset::getCurrentCountryId();
		$countries = array_fill_keys($countries, true);
		$allowedCountries = EntityRequisite::getAllowedRqFieldCountries();
		if (isset($countries[$currentCountryId]) && in_array($currentCountryId, $allowedCountries, true))
		{
			$result[] = $currentCountryId;
		}
		foreach ($allowedCountries as $countryId)
		{
			if (isset($countries[$countryId]) && $countryId !== $currentCountryId)
			{
				$result[] = $countryId;
			}
		}

		return $result;
	}

	protected function getFieldMap(array $entityTypeIds = [], array $countryIds = []): array
	{
		static $map = null;
		static $usedCountries = [];

		$result = [];

		if ($map === null)
		{
			// Statndard fields
			$map = [
				CCrmOwnerType::Lead => [
					'TITLE',
					'ADDRESS',
					'SOURCE_DESCRIPTION',
					'STATUS_DESCRIPTION',
					'COMMENTS',
				],
				CCrmOwnerType::Company => [
					'TITLE',
					'ADDRESS',
					'COMMENTS',
				],
				CCrmOwnerType::Contact => [
					'FULL_NAME',
					'ADDRESS',
					'SOURCE_DESCRIPTION',
					'COMMENTS',
				],
			];

			// User fields
			foreach (array_keys($map) as $entityTypeId)
			{
				/** @noinspection PhpUndefinedMethodInspection */
				$userFieldsInfo = $this->getClassByEntityType($entityTypeId)::GetUserFields();
				foreach ($userFieldsInfo as $fieldName => $fieldInfo)
				{
					if (
						isset($fieldInfo['USER_TYPE_ID'])
						&& (
							$fieldInfo['USER_TYPE_ID'] === 'string'
							|| $fieldInfo['USER_TYPE_ID'] === 'integer'
							|| $fieldInfo['USER_TYPE_ID'] === 'double'
							|| $fieldInfo['USER_TYPE_ID'] === 'address'
						)
					)
					{
						$map[$entityTypeId][] = $fieldName;
					}
				}
			}

			// Multifields
			$fm = array_fill_keys(array_keys(CCrmFieldMulti::GetEntityTypes()), true);
			unset($fm['PHONE'], $fm['EMAIL'], $fm['LINK']);
			$fm = array_keys($fm);
			foreach (array_keys($map) as $entityTypeId)
			{
				foreach($fm as $fmType)
				{
					$map[$entityTypeId][] = "FM.$fmType";
				}
			}

			// Requisites and bank details
			$requisite = EntityRequisite::getSingleInstance();
			$usedCountries = $this->prepareCountries($this->sortCountries($requisite->getUsedCountries()));
			if (!empty($usedCountries))
			{
				$rqFieldCountryMap = [];
				foreach ($requisite->getRqFieldsCountryMap() as $fieldName => $countries)
				{
					$rqFieldCountryMap[$fieldName] = array_fill_keys($countries, true);
				}
				$userFieldsMap = array_fill_keys($requisite->getUserFields(), true);
				$preset = EntityPreset::getSingleInstance();
				$fieldsInPresetsByCountry = $preset->getSettingsFieldsOfPresets(
					CCrmOwnerType::Requisite,
					'active',
					['ARRANGE_BY_COUNTRY' => true]
				);
				$bankDetail = EntityBankDetail::getSingleInstance();
				$bdFieldsByCountry = $bankDetail->getRqFieldByCountry();
				foreach (array_keys($map) as $entityTypeId)
				{
					if ($entityTypeId !== CCrmOwnerType::Lead)
					{
						foreach ($usedCountries as $countryId)
						{
							$rqFieldsInfo = $requisite->getFormFieldsInfo($countryId);
							$countryCode = EntityPreset::getCountryCodeById($countryId);
							$map[$entityTypeId][] = "RQ.$countryCode.NAME";
							foreach ($fieldsInPresetsByCountry[$countryId] as $fieldName)
							{
								if (
									isset($rqFieldsInfo[$fieldName])
									&& isset($rqFieldsInfo[$fieldName]['type'])
									&& (
										$rqFieldsInfo[$fieldName]['type'] === 'string'
										|| $rqFieldsInfo[$fieldName]['type'] === 'integer'
									)
									&& (
										isset($rqFieldCountryMap[$fieldName][$countryId])
										|| isset($userFieldsMap[$fieldName])
									)
								)
								{
									$map[$entityTypeId][] = "RQ.$countryCode.$fieldName";
								}
							}
							$map[$entityTypeId][] = "RQ.$countryCode.BD.NAME";
							if (isset($bdFieldsByCountry[$countryId]))
							{
								$bdFieldsInfo = $bankDetail->getFormFieldsInfo($countryId);
								foreach ($bdFieldsByCountry[$countryId] as $fieldName)
								{
									if (
										isset($bdFieldsInfo[$fieldName])
										&& isset($bdFieldsInfo[$fieldName]['type'])
										&& (
											$bdFieldsInfo[$fieldName]['type'] === 'string'
											|| $bdFieldsInfo[$fieldName]['type'] === 'integer'
										)
									)
									{
										$map[$entityTypeId][] = "RQ.$countryCode.BD.$fieldName";
									}
								}
							}
							$map[$entityTypeId][] = "RQ.$countryCode.BD.COMMENTS";
						}
					}
				}
			}
		}

		if (empty($entityTypeIds))
		{
			$entityTypeIds = array_keys($map);
		}

		if (empty($countryIds))
		{
			$countryIds = $usedCountries;
		}
		$countryCodeMap = [];
		foreach (array_intersect($countryIds, $usedCountries) as $countryId)
		{
			$countryCodeMap[EntityPreset::getCountryCodeById($countryId)] = true;
		}

		$prefixLength =
			mb_strlen(static::RQ_FIELD_PREFIX)
			+ mb_strlen(EntityPreset::getCountryCodeById(1))
			+ mb_strlen(static::PATH_SEPARATOR) * 2
		;

		foreach ($entityTypeIds as $entityTypeId)
		{
			if (isset($map[$entityTypeId]))
			{
				foreach ($map[$entityTypeId] as $fieldPathName)
				{
					if (
						mb_strlen($fieldPathName) > $prefixLength
						&& mb_substr($fieldPathName, 0, 3) === (static::RQ_FIELD_PREFIX . static::PATH_SEPARATOR)
						&& mb_substr($fieldPathName, 5, 1) === static::PATH_SEPARATOR
					)
					{
						if (isset($countryCodeMap[mb_substr($fieldPathName, 3, 2)]))
						{
							$result[$entityTypeId][] = $fieldPathName;
						}
					}
					else
					{
						$result[$entityTypeId][] = $fieldPathName;
					}
				}
			}
		}

		return $result;
	}

	protected function getEntityFieldTitle(int $entityTypeId, string $fieldName)
	{
		/** @var CCrmLead|CCrmCompany|CCrmContact $entityClassName */
		$entityClassName = $this->getClassByEntityType($entityTypeId);
		$result = $entityClassName::GetFieldCaption($fieldName);
		if ($result === '' || $result === $fieldName)
		{
			$crmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityClassName::$sUFEntityID);
			$userFieldNames = $crmUserType->GetFieldNames();
			if (isset($userFieldNames[$fieldName]))
			{
				$result = $userFieldNames[$fieldName];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	protected function getRequisiteFieldTitle(int $entityTypeId, string $fieldName, int $countryId): string
	{
		$result = '';

		$requisite = EntityRequisite::getSingleInstance();
		$fieldTitles = $requisite->getFieldsTitles($countryId);

		if (isset($fieldTitles[$fieldName]))
		{
			$result = $fieldTitles[$fieldName];
		}

		return $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	protected function getBankDetailFieldTitle(int $entityTypeId, string $fieldName, int $countryId): string
	{
		$result = '';

		$bankDetail = EntityBankDetail::getSingleInstance();
		$fieldTitles = $bankDetail->getFieldsTitles($countryId);

		if (isset($fieldTitles[$fieldName]))
		{
			$result = $fieldTitles[$fieldName];
		}

		return $result;
	}

	protected function getFieldInfoByPathName(int $entityTypeId, string $pathName): array
	{
		$result = [];

		$fieldCategory = FieldCategory::getInstance();
		$fieldName = $this->getFieldNameByPath($pathName);
		$categoryInfo = $fieldCategory->getCategoryByPath($pathName);
		switch ($categoryInfo['categoryId'])
		{
			case FieldCategory::ENTITY:
				$result['title'] = $this->getEntityFieldTitle($entityTypeId, $fieldName);
				break;
			case FieldCategory::ADDRESS:
				$result['title'] = Loc::getMessage('CRM_DUP_VOLATILE_FIELD_ADDRESS_TITLE');
				break;
			case FieldCategory::MULTI:
				$result['title'] = CCrmFieldMulti::GetEntityTypeCaption($fieldName);
				break;
			case FieldCategory::REQUISITE:
				$result['title'] = $this->getRequisiteFieldTitle(
					$entityTypeId,
					$fieldName,
					$categoryInfo['params']['countryId']
				);
				break;
			case FieldCategory::BANK_DETAIL:
				$result['title'] = $this->getBankDetailFieldTitle(
					$entityTypeId,
					$fieldName,
					$categoryInfo['params']['countryId']
				);
				break;
			default:
				$result['title'] = '';
		}

		$result['categoryId'] = $categoryInfo['categoryId'];
		$result['categoryPrefixTitle'] = $categoryInfo['categoryPrefixTitle'];
		$result['categoryParams'] = $categoryInfo['params'];

		return $result;
	}

	public function getPathName(string $fieldPath, string $fieldName): string
	{
		return ($fieldPath === '' ? $fieldName : $fieldPath . static::PATH_SEPARATOR . $fieldName);
	}

	public function splitFieldPath(string $fieldPathName): array
	{
		$result = [
			'path' => '',
			'name' => '',
		];

		$dotPos = mb_strrpos($fieldPathName, '.');
		if ($dotPos === false)
		{
			$result['path'] = '';
			$result['name'] = $fieldPathName;
		}
		else
		{
			$result['path'] = substr($fieldPathName, 0, $dotPos);
			$result['name'] = substr($fieldPathName, $dotPos + 1);
		}

		return $result;
	}

	public function getFieldNameByPath(string $fieldPathName): string
	{
		$info = $this->splitFieldPath($fieldPathName);

		return $info['name'];
	}

	public function getFieldInfo(array $entityTypeIds = [], array $countryIds = []): array
	{
		$result = [];

		$fieldMap = $this->getFieldMap($entityTypeIds, $countryIds);

		foreach ($fieldMap as $entityTypeId => $fields)
		{
			foreach ($fields as $fieldPathName)
			{
				$result[$entityTypeId][$fieldPathName] = $this->getFieldInfoByPathName($entityTypeId, $fieldPathName);
			}
		}

		return $result;
	}
}

