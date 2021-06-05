<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Contact;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class ContactToFactory extends \Bitrix\Crm\Relation\StorageStrategy
{
	/** @var Service\Factory */
	protected $childFactory;

	/**
	 * ContactToFactory constructor.
	 *
	 * @param Service\Factory $childFactory
	 *
	 * @throws ArgumentException
	 */
	public function __construct(Service\Factory $childFactory)
	{
		if (!$childFactory->isClientEnabled())
		{
			throw new ArgumentException('Client is disabled in the provided factory', 'childFactory');
		}

		$this->childFactory = $childFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$item = $this->childFactory->getItem($child->getEntityId());
		if (!$item)
		{
			return [];
		}

		$parents = [];
		/** @var Contact $contact */
		foreach ($item->get(Item::FIELD_NAME_CONTACTS) as $contact)
		{
			$parents[] = new ItemIdentifier(\CCrmOwnerType::Contact, $contact->getId());
		}

		return $parents;
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$items = $this->childFactory->getItems([
			'select' => [Item::FIELD_NAME_ID],
			'filter' => [
				'=' . Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID' => $parent->getEntityId(),
			],
		]);

		$children = [];
		foreach ($items as $item)
		{
			$children[] = ItemIdentifier::createByItem($item);
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		$item = $this->childFactory->getItem($child->getEntityId());

		if ($item)
		{
			/** @var Contact $contact */
			foreach ($item->get(Item::FIELD_NAME_CONTACTS) as $contact)
			{
				if ($contact->getId() === $parent->getEntityId())
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return $this->editBinding('bindContacts', $parent, $child);
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return $this->editBinding('unbindContacts', $parent, $child);
	}

	protected function editBinding(string $method, ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		$item = $this->childFactory->getItem($child->getEntityId());
		if (!$item)
		{
			return (new Result())->addError(new Error('The child item does not exist: ' . $child));
		}

		/** @see Item::bindContacts() */
		/** @see Item::unbindContacts() */
		$item->$method(
			EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, [$parent->getEntityId()])
		);

		$operation = $this->childFactory->getUpdateOperation($item);

		$operation->disableCheckAccess();

		return $operation->launch();
	}
}
