<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Mail;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;

class ContactList extends ContentBlock
{
	private array $listItems = [];
	private array $listContacts = [];
	private string $title = '';

	public function getRendererName(): string
	{
		return 'ContactList';
	}
	public function addListItem(LineOfTextBlocks $contactBlock): self
	{
		$this->listItems[] = $contactBlock;

		return $this;
	}

	public function getListItems(): array
	{
		return $this->listItems;
	}

	public function setContactList(Array $list): void
	{
		$this->listContacts = $list;
	}

	public function getContactList(): array
	{
		return $this->listContacts;
	}

	public function setTitle($title): void
	{
		$this->title = $title;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	protected function getProperties(): ?array
	{
		return [
			'contactBlocks' => $this->getListItems(),
			'contactList' => $this->getContactList(),
			'title' => $this->getTitle(),
		];
	}
}