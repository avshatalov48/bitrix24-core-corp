<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\EO_Contact;
use Bitrix\Crm\Service\Broker;

/**
 * @method EO_Contact|null getById(int $id)
 * @method EO_Contact[] getBunchByIds(array $ids)
 */
class Contact extends Broker
{
	protected ?string $eventEntityAdd = 'OnAfterCrmContactAdd';
	protected ?string $eventEntityUpdate = 'OnAfterCrmContactUpdate';
	protected ?string $eventEntityDelete = 'OnAfterCrmContactDelete';

	public function getFormattedName(int $id): ?string
	{
		return $this->getById($id)?->getFormattedName();
	}

	protected function loadEntry(int $id): ?\Bitrix\Crm\Contact
	{
		return ContactTable::getList([
			'select' => $this->getSelect(),
			'filter' => ['=ID' => $id]
		])->fetchObject();
	}
	
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

	private function getSelect(): array
	{
		return [
			'*',
			'EMAIL',
			'COMPANY',
		];
	}
}
