<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Format\RequisiteAddressFormatter;
use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Intranet\Settings\Controls\Section;
use Bitrix\Intranet\Settings\Requisite\CompanyList;
use Bitrix\Intranet\Settings\Requisite\PresetList;
use Bitrix\Intranet\Settings\Requisite\RequisiteList;
use Bitrix\Intranet\Settings\Search\SearchEngine;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class RequisiteSettings extends AbstractSettings
{
	public const TYPE = 'requisite';

	private bool $hasModuleCrm;
	private array $visibleFieldForRu = [
		'RQ_COMPANY_FULL_NAME',
		'RQ_COMPANY_NAME',
		'RQ_ADDR',
		'RQ_INN',
		'RQ_KPP',
		'RQ_OGRN',
		'RQ_ACC_NUM',
		'RQ_BIK',
		'RQ_COR_ACC_NUM',
		'RQ_DIRECTOR',
		'RQ_ACCOUNTANT',
	];

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->hasModuleCrm = Loader::includeModule('crm');
	}

	public function save(): Result
	{
		return new Result();
	}

	public function get(): SettingsInterface
	{
		if (!$this->hasModuleCrm)
		{
			return new static();
		}

		$data['sectionRequisite'] = new Section(
			'settings-requisite-section-company_requisite',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_REQUISITE'),
			'ui-icon-set --suitcase',
			canCollapse: false
		);

		$filter['=IS_MY_COMPANY'] = 'Y';
		$companyList = new CompanyList($filter, ['DATE_CREATE' => 'DESC'], ['ID', 'TITLE']);
		$data['COMPANY'] = $companyList->toArray();

		$fieldTitles = [];
		$entityRequisite = new EntityRequisite();
		foreach(EntityRequisite::getAllowedRqFieldCountries() as $countryId)
		{
			$fieldTitles[$countryId] = array_merge(
				$entityRequisite->getFieldsTitles($countryId),
				EntityBankDetail::getSingleInstance()->getFieldsTitles($countryId)
			);
		}

		$defaultPreset = EntityPreset::getDefault();
		$fieldsPreset = $this->getPresetFields((int)$defaultPreset['ID']);

		$data['BITRIX_TITLE'] = \COption::GetOptionString('main', 'site_name', 'Bitrix24');
		$data['EMPTY_REQUISITE'] = $this->fieldFormat(
			$fieldsPreset['FIELDS_NAMES'] ?? [],
			$fieldTitles[EntityPreset::getCurrentCountryId()] ?? [],
			[]
		);
		$data['REQUISITES'] = $this->prepareRequisite($companyList->getRequisiteList(), $fieldTitles);
		[$phonesNumber, $sites, $emails] = $this->getCompanyCommunicationByIds($companyList->getIds());
		$data['PHONES'] = $phonesNumber;
		$data['EMAILS'] = $emails;
		$data['SITES'] = $sites;
		$data['LANDINGS'] = $this->getLendingForCompany($companyList);
		$data['LANDINGS_DATA'] = $this->formatLandingData($companyList);

		return new static($data);
	}

	public static function isAvailable(): bool
	{
		return Loader::includeModule('crm') && class_exists('Bitrix\Crm\Integration\Landing\RequisitesLanding');
	}

	private function prepareRequisite(RequisiteList $requisiteList, array $fieldsTitles): array
	{
		$result = [];
		$presetList = new PresetList($requisiteList);
		foreach ($requisiteList->getOneRequisitePerCompany() as $rqRow)
		{
			$entityId = $rqRow['ENTITY_ID'];

			$preset = $presetList->getById((int)$rqRow['PRESET_ID']);

			if ((int)$preset['COUNTRY_ID'] !== 1)
			{
				$presetFields = array_map(function ($item) {
					return $item['FIELD_NAME'];
				}, $presetList->getFieldNameById((int)$rqRow['PRESET_ID']));

				$presetFields = array_filter($presetFields, function ($field) {
					return $field !== 'RQ_SIGNATURE' && $field !== 'RQ_STAMP';
				});
				$bankField = EntityBankDetail::getSingleInstance()->getRqFieldByCountry()[$preset['COUNTRY_ID']];

				$availableFields = array_merge($presetFields, $bankField);
			}
			else
			{
				$availableFields = $this->visibleFieldForRu;
			}

			$rqRow[EntityRequisite::ADDRESS] = $this->getFormattedAddressFromPool(
				(int)$preset['COUNTRY_ID'],
				$rqRow[EntityRequisite::ADDRESS] ?? []
			);
			$result[$entityId] = $this->fieldFormat($availableFields, $fieldsTitles[(int)$preset['COUNTRY_ID']], $rqRow);
		}
		return $result;
	}

	public function fieldFormat(array $fields, array $titles, array $values): array
	{
		$result = [];
		foreach ($fields as $field)
		{
			$value = $values[$field] ?? '';
			$result[] = [
				'TITLE' => $titles[$field] ?? '',
				'NAME' => $field,
				'VALUE' => !empty($value) ? $value : '',
			];
		}

		return $result;
	}

	private function getFormattedAddressFromPool(int $countryId, array $addressPool): ?string
	{
		if (!empty($addressPool))
		{
			return AddressFormatter::getSingleInstance()->formatTextComma(
				current($addressPool),
				RequisiteAddressFormatter::getFormatByCountryId($countryId)
			);
		}

		return null;
	}

	private function getPresetFields(int $presetId): array
	{
		$presetData = EntityPreset::getSingleInstance()->getById($presetId);
		if (!is_array($presetData))
		{
			return [];
		}

		$fieldsNames = [];
		if (is_array($presetData['SETTINGS']))
		{
			$fields = EntityPreset::getSingleInstance()->settingsGetFields($presetData['SETTINGS']);
			if (!empty($fields))
			{
				foreach ($fields as $fieldInfo)
				{
					if ($fieldInfo['FIELD_NAME'] === 'RQ_SIGNATURE' || $fieldInfo['FIELD_NAME'] === 'RQ_STAMP')
					{
						continue;
					}
					$fieldsNames[] = $fieldInfo['FIELD_NAME'];
				}
			}
		}
		if ((int)$presetData['COUNTRY_ID'] === 1)
		{
			$presetData['FIELDS_NAMES'] = $this->visibleFieldForRu;

			return $presetData;
		}
		$bankField = EntityBankDetail::getSingleInstance()->getRqFieldByCountry()[$presetData['COUNTRY_ID']];
		$presetData['FIELDS_NAMES'] = array_merge($fieldsNames, $bankField);

		return $presetData;
	}

	private function getCompanyCommunicationByIds(array $companyIds): array
	{
		$phonesNumber = [];
		$sites = [];
		$emails = [];

		foreach ($companyIds as $companyId)
		{
			$phone = \CCrmFieldMulti::GetEntityFirstPhone(
				\CCrmOwnerType::CompanyName,
				$companyId,
				true,
				false
			);

			$phonesNumber[$companyId] = $phone?->format() ?? '';

			$email = \CCrmFieldMulti::GetEntityFirstField(
				\CCrmOwnerType::CompanyName,
				$companyId,
				\CCrmFieldMulti::EMAIL,
				true,
				false
			);

			$emails[$companyId] = $email['VALUE'] ?? '';

			$site = \CCrmFieldMulti::GetEntityFirstField(
				\CCrmOwnerType::CompanyName,
				$companyId,
				\CCrmFieldMulti::WEB,
				true,
				false
			);

			$sites[$companyId] = $site['VALUE'] ?? '';
		}

		return [$phonesNumber, $sites, $emails];
	}

	public function getLendingForCompany(CompanyList $companyList): array
	{
		Loader::includeModule('crm');

		return array_map(function (RequisitesLanding $landing) {
			return [
				'is_connected' => $landing->isLandingConnected(),
				'is_public' => $landing->isLandingPublic(),
				'public_url' => $landing->getLandingPublicUrl(),
				'edit_url' => $landing->getLandingEditUrl(),
			];
		}, $companyList->getLandingList()->toArray());
	}

	public function formatLandingData(CompanyList $companyList): array
	{
		$result = [];

		foreach ($companyList->toArray() as $company)
		{
			$companyId = (int)$company['ID'];
			$requisite = $companyList->getRequisiteList()->getByCompanyId($companyId);
			$requisiteId = isset($requisite['ID']) ? (int)$requisite['ID'] : 0;
			$requisiteBank = $requisiteId ? $companyList->getRequisiteList()->getBankRequisiteList()->getByRequisiteId($requisiteId) : 0;

			$landingId = [
				'company_id' => $companyId,
				'requisite_id' => $requisiteId,
				'bank_requisite_id' => (int)($requisiteBank['ID'] ?? null),
			];
			$result[$companyId] = $landingId;
		}

		return $result;
	}

	public function find(string $query): array
	{
		$searchEngine = SearchEngine::initWithDefaultFormatter([
			'settings-requisite-section-company_requisite' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_REQUISITE'),
		]);

		return $searchEngine->find($query);
	}
}

