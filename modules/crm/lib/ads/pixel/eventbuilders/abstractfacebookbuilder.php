<?php

namespace Bitrix\Crm\Ads\Pixel\EventBuilders;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Seo\Conversion\Facebook;

/**
 * Class AbstractFacebookBuilder
 * @package Bitrix\Crm\Ads\Pixel\EventBuilders
 */
abstract class AbstractFacebookBuilder implements CrmConversionEventBuilderInterface
{
	public abstract function getEventParams($entity) : ?array;

	public abstract function getUserData() : array;

	/**
	 * @return array
	 */
	public function buildEvents() : array
	{
		return array_map(
			function($entity) : Facebook\Event {

				return new Facebook\Event($this->getEventParams($entity));
			},
			$this->getUserData()
		);
	}

	protected function getDeal($id): ?array
	{
		if (isset($id))
		{
			return array_filter(DealTable::getRow([
					'select' => [
						'COMPANY_ID',
						'CONTACT_ID',
						'STAGE_ID',
						'CATEGORY_ID',
						'OPPORTUNITY',
						'CURRENCY_ID',
						'SOURCE_ID'
					],
					'filter' => ['=ID' => $id],
				]) ?? []);
		}

		return null;
	}

	protected function getLead(int $id): array
	{
		return LeadTable::getRow([
			'select' => [
				'COMPANY_ID',
				'CONTACT_ID',
				'EMAIL',
				'PHONE',
				'STATUS_ID',
				'OPPORTUNITY',
				'CURRENCY_ID',
				'SOURCE_ID'
			],
			'filter' => ['=ID' => $id],
		]);
	}

	protected function getDealUserData(?array $deal): array
	{
		$userData = [];
		if (!empty($deal))
		{
			if ($contactId = $deal['CONTACT_ID'])
			{
				if (!empty($data = $this->getContactUserData($contactId)))
				{
					$userData[] = $data;
				}
			}
			if ($companyId = $deal['COMPANY_ID'])
			{
				if (!empty($data = $this->getCompanyUserData($companyId)))
				{
					$userData[] = $data;
				}
			}
		}

		return $userData;
	}

	protected function getContactUserData($contactId): ?array
	{
		if ($contactId)
		{
			return array_filter(ContactTable::getRow([
					'select' => [
						'`first_name`' => 'NAME',
						'`last_name`' => 'LAST_NAME',
						'`phone`' => 'PHONE',
						'`email`' => 'EMAIL',
						'`date_of_birth`' => 'BIRTHDATE'
					],
					'filter' => [
						'=ID' => $contactId
					]
				]) ?? []);
		}

		return null;
	}

	protected function getCompanyUserData($companyId): ?array
	{
		if ($companyId)
		{
			return array_filter(CompanyTable::getRow([
					'select' => [
						'`phone`' => 'PHONE',
						'`email`' => 'EMAIL'
					],
					'filter' => [
						'=ID' => $companyId
					]
				]) ?? []);
		}

		return null;
	}

	protected function getLeadUserData(?array $lead): array
	{
		$userData = [];
		if (!empty($lead))
		{
			if ($contactId = $lead['CONTACT_ID'])
			{
				if (!empty($data = $this->getContactUserData($contactId)))
				{
					$userData[] = $data;
				}
			}
			if ($companyId = $lead['COMPANY_ID'])
			{
				if (!empty($data = $this->getCompanyUserData($companyId)))
				{
					$userData[] = $data;
				}
			}
			if ($lead['EMAIL'] || $lead['PHONE'])
			{
				$data = [];
				if ($email = $lead['EMAIL'])
				{
					$data['email'] = $email;
				}
				if ($phone = $lead['PHONE'])
				{
					$data['phone'] = $phone;
				}
				$userData[] = $data;
			}
		}

		return $userData;
	}
}