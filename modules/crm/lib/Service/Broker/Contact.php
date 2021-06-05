<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Service\Broker;

class Contact extends Broker
{
	public function getFormattedName(int $id): ?string
	{
		/** @var \Bitrix\Crm\Contact|null $contact */
		$contact = $this->getById($id);
		if (!$contact)
		{
			return null;
		}

		return $contact->getFormattedName();
	}

	protected function loadEntry(int $id): ?\Bitrix\Crm\Contact
	{
		return ContactTable::getList([
			'select' => $this->getSelect(),
			'filter' => ['=ID' => $id]
		])->fetchObject();
	}

	/**
	 * @param array $ids
	 *
	 * @return \Bitrix\Crm\Contact[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function loadEntries(array $ids): array
	{
		$contactsCollection = ContactTable::getList([
			'select' => $this->getSelect(),
			'filter' => ['@ID' => $ids],
		])->fetchCollection();

		$contacts = [];
		foreach ($contactsCollection as $contact)
		{
			$contacts[$contact->getId()] = $contact;
		}

		return $contacts;
	}

	protected function getSelect(): array
	{
		return [
			'*',
			'EMAIL',
			'COMPANY',
		];
	}
}