<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

class Contact extends ListEntity
{
	protected const DEFAULT_SELECT_FIELD_NAMES = [
		'DATE_CREATE',
		'ID',
		'HONORIFIC',
		'NAME',
		'SECOND_NAME',
		'LAST_NAME',
		'COMPANY_ID',
		'PHONE',
		'EMAIL',
		'ASSIGNED_BY_ID',
	];

	protected const FIELD_ALIASES = [
		'ASSIGNED_BY' => 'ASSIGNED_BY_ID',
		'CREATED_BY' => 'CREATED_BY_ID',
		'MODIFY_BY' => 'MODIFY_BY_ID',
	];

	public function getEntityType(): string
	{
		return \CCrmOwnerType::ContactName;
	}

	protected function getItemName(array $item): string
	{
		return \CCrmContact::PrepareFormattedName(
			[
				'HONORIFIC' => $item['HONORIFIC'] ?? '',
				'NAME' => $item['NAME'] ?? '',
				'SECOND_NAME' => $item['SECOND_NAME'] ?? '',
				'LAST_NAME' => $item['LAST_NAME'] ?? '',
			]
		);
	}

	protected function getEntityClass(): \CCrmContact
	{
		return new \CCrmContact();
	}

	protected function appendRelatedEntitiesValues(array &$items): void
	{
		$companies = $this->getAccessibleCompanies($items);
		$clientType = \CCrmOwnerType::CompanyName;

		foreach ($items as &$item)
		{
			if (!empty($item['COMPANY_ID']))
			{
				$companyId = $item['COMPANY_ID'];
				if (isset($companies[$companyId]))
				{
					$company = $companies[$companyId];
					$company['ID'] = $companyId;

					$companyInfo = $this->getClientInfoByType($company, $clientType, $company['TITLE'], false);
					$item[$clientType] = $companyInfo[mb_strtolower($clientType)];
				}
			}
		}
		unset($item);
	}

	protected function getAccessibleCompanies(array $items): array
	{
		$companiesIds = [];
		foreach ($items as $item)
		{
			if (!empty($item['COMPANY_ID']))
			{
				$companiesIds[] = $item['COMPANY_ID'];
			}
		}

		if (empty($companiesIds))
		{
			return [];
		}

		$parameters = [
			'filter' => [
				'@ID' => $companiesIds,
			],
			'select' => [
				'ID',
				'TITLE',
			],
		];

		$companies = Container::getInstance()
			->getFactory(\CCrmOwnerType::Company)
			->getItemsFilteredByPermissions($parameters)
		;

		$accessibleCompanies = [];
		foreach ($companies as $company)
		{
			$accessibleCompanies[$company->getId()] = [
				'TITLE' => $company->getTitle(),
				'FM' => $company->getFm(),
			];
		}

		return $accessibleCompanies;
	}

	protected function getClient(array $item, array $params = []): ?array
	{
		$data = $this->getSelfContactInfo($item, \CCrmOwnerType::ContactName);

		if (!empty($data))
		{
			$data['hidden'] = true;
		}

		$companyId = ($item['COMPANY_ID'] ?? null);
		if ($companyId)
		{
			$companyData = [
				'title' => Loc::getMessage('M_CRM_KANBAN_CONTACT_FIELD_COMPANY'),
				'hidden' => !isset($params['displayValues']['COMPANY_ID']),
				'company' => $item['COMPANY'],
			];

			$data = array_merge($data, $companyData);
		}

		return (empty($data) ? null : $data);
	}

	protected function prepareFields(array $item = [], array $params = []): array
	{
		unset($params['displayValues']['COMPANY_ID']);

		return parent::prepareFields($item, $params);
	}

	protected function getFilterPresets(): array
	{
		return (new \Bitrix\Crm\Filter\Preset\Contact())->getDefaultPresets();
	}
}
