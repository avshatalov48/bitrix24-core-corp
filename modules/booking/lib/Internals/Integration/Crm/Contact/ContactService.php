<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm\Contact;

use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Crm\Merger\ContactMerger;
use Bitrix\Crm\Merger\EntityMerger;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Main\Loader;
use CCrmOwnerType;
use CCrmContact;
use CCrmFieldMulti;
use Bitrix\Crm\Format\PersonNameFormatter;

class ContactService
{
	public function findOrCreate(ContactDto $contactDto): int|null
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		if (
			empty($contactDto->getName())
			|| empty($contactDto->getPhone())
		)
		{
			return null;
		}

		$fields = $this->makeCrmFields($contactDto);
		if ($fields === null)
		{
			return null;
		}

		$existingContactId = $this->findExisting($fields);
		if ($existingContactId)
		{
			return $existingContactId;
		}

		return $this->createNew($fields);
	}

	private function findExisting(array $fields): int|null
	{
		$actualEntitySelector = $this->createActualEntitySelector($fields);
		$contactId = $actualEntitySelector->search()->getContactId();
		if (!$contactId)
		{
			return null;
		}

		$crmContact = new CCrmContact(false);

		$entityMultiFields = [];
		$multiFields = CCrmFieldMulti::GetEntityFields(
			CCrmOwnerType::ContactName,
			$contactId,
			null
		);
		foreach ($multiFields as $multiField)
		{
			$entityMultiFields[$multiField['TYPE_ID']][$multiField['ID']] = [
				'VALUE' => $multiField['VALUE'],
				'VALUE_TYPE' => $multiField['VALUE_TYPE'],
			];
		}

		$crmContactFieldsList = $crmContact->getListEx(
			[],
			[
				'ID' => $contactId,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			[
				'*',
				'UF_*',
			]
		);
		if (!$crmContactFields = $crmContactFieldsList->fetch())
		{
			return null;
		}

		$crmContactFields['FM'] = $entityMultiFields;

		foreach ($crmContactFields as $key => $value)
		{
			if (
				$value === []
				|| $value === null
				|| $value === ''
				|| $value === false
			)
			{
				unset($crmContactFields[$key]);
			}
		}

		$merger = new ContactMerger(0, false);
		$merger->mergeFields(
			$fields,
			$crmContactFields,
			false,
			[
				'ENABLE_UPLOAD' => true,
				'ENABLE_UPLOAD_CHECK' => false,
			]
		);

		$crmContact->update($contactId, $crmContactFields);

		return $contactId;
	}

	private function createNew(array $fields): int|null
	{
		$crmContact = new CCrmContact(false);

		$fields['OPENED'] = ContactSettings::getCurrent()->getOpenedFlag();
		$fields['SOURCE_ID'] = 'BOOKING';

		$id = (int)$crmContact->add(
			$fields,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
			]
		);

		return $id > 0 ? $id : null;
	}

	private function createActualEntitySelector($fields): ActualEntitySelector
	{
		$targetFields = [
			'FM' => [],
		];

		if (isset($fields['FM']))
		{
			EntityMerger::mergeMultiFields(
				$fields['FM'],
				$targetFields['FM'],
			);
		}

		$fieldNameMap = [
			[
				'fieldName' => 'NAME',
			],
			[
				'fieldName' => 'LAST_NAME',
			],
			[
				'fieldName' => 'SECOND_NAME',
			],
		];
		foreach ($fieldNameMap as $item)
		{
			$fieldName = $item['fieldName'];

			if (isset($targetFields[$fieldName]) && $targetFields[$fieldName])
			{
				continue;
			}

			if (!isset($fields[$fieldName]) || !$fields[$fieldName])
			{
				continue;
			}

			$targetFields[$fieldName] = $fields[$fieldName];
		}

		return (new ActualEntitySelector)
			->setCriteria(
				ActualEntitySelector::createDuplicateCriteria(
					$targetFields,
					[
						ActualEntitySelector::SEARCH_PARAM_PHONE,
						ActualEntitySelector::SEARCH_PARAM_EMAIL,
						ActualEntitySelector::SEARCH_PARAM_ORGANIZATION,
						ActualEntitySelector::SEARCH_PARAM_PERSON
					]
				)
			)
			->enableFullSearch()
			->disableExclusionChecking();
	}

	private function makeCrmFields(ContactDto $contactDto): array|null
	{
		$nameParts = [];

		PersonNameFormatter::tryParseName(
			$contactDto->getName(),
			PersonNameFormatter::getFormatID(),
			$nameParts
		);

		$parsedName = $this->getParsedName($contactDto->getName());
		if ($parsedName === null)
		{
			return null;
		}

		$result = [
			'NAME' => $nameParts['NAME'],
			'LAST_NAME' => $nameParts['LAST_NAME'],
			'SECOND_NAME' => $nameParts['SECOND_NAME'],
			'FM' => [],
		];

		$phone = $contactDto->getPhone();
		if ($phone !== null)
		{
			$result['FM']['PHONE'] = [
				'n0' => [
					'VALUE' => $phone,
					'VALUE_TYPE' => 'OTHER'
				],
			];
		}

		$email = $contactDto->getEmail();
		if ($email !== null)
		{
			$result['FM']['EMAIL'] = [
				'n0' => [
					'VALUE' => $email,
					'VALUE_TYPE' => 'OTHER'
				],
			];
		}

		return $result;
	}

	private function getParsedName(string $name): array|null
	{
		$nameParts = [];

		$result = PersonNameFormatter::tryParseName(
			$name,
			PersonNameFormatter::getFormatID(),
			$nameParts
		);

		return $result ? $nameParts : null;
	}
}
