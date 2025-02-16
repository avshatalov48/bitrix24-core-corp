<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;

class IsReturnCustomer extends Field
{
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return new FieldAfterSaveResult();
		}

		$result = new FieldAfterSaveResult();

		$previousPrimaryClient = $this->getPreviousPrimaryClient($itemBeforeSave);
		$currentPrimaryClient = $this->getPrimaryClient($item);

		if ($previousPrimaryClient && $previousPrimaryClient->getHash() !== $currentPrimaryClient?->getHash())
		{
			$this->synchronizeFieldValueInAllItemsOfThisType($factory, $previousPrimaryClient);
		}

		if ($currentPrimaryClient)
		{
			$firstSuccessfulItemId = $this->synchronizeFieldValueInAllItemsOfThisType($factory, $currentPrimaryClient);

			// even if we updated value in the DB itself, we want to keep in-memory item in sync

			if ($firstSuccessfulItemId === null)
			{
				// no history of successful items - not return customer
				$result->setNewValue($this->getName(), false);
			}
			else
			{
				// the first successful item is NOT return customer, since it was the FIRST
				// other items with the same client after this - return customer
				$isReturnCustomer = $firstSuccessfulItemId !== $item->getId();

				$result->setNewValue($this->getName(), $isReturnCustomer);

				if ($isReturnCustomer && $item->hasField(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH))
				{
					$result->setNewValue(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH, false);
				}
			}
		}
		else
		{
			// no client - not return customer
			$result->setNewValue($this->getName(), false);
		}

		return $result;
	}

	private function getPrimaryClient(Item $item): ?ItemIdentifier
	{
		if ($item->hasField(Item::FIELD_NAME_COMPANY_ID) && $item->getCompanyId() > 0)
		{
			return new ItemIdentifier(\CCrmOwnerType::Company, $item->getCompanyId());
		}

		if ($item->hasField(Item::FIELD_NAME_CONTACTS) && $item->getPrimaryContact())
		{
			return new ItemIdentifier(\CCrmOwnerType::Contact, $item->getPrimaryContact()->getId());
		}

		return null;
	}

	private function getPreviousPrimaryClient(Item $item): ?ItemIdentifier
	{
		if (
			$item->hasField(Item::FIELD_NAME_COMPANY_ID)
			&& $item->remindActual(Item::FIELD_NAME_COMPANY_ID) > 0
		)
		{
			return new ItemIdentifier(
				\CCrmOwnerType::Company,
				$item->remindActual(Item::FIELD_NAME_COMPANY_ID)
			);
		}

		if (
			$item->hasField(Item::FIELD_NAME_CONTACTS)
			&& !empty($item->remindActual(Item::FIELD_NAME_CONTACT_BINDINGS))
		)
		{
			$primaryOrDefaultContactId = EntityBinding::getPrimaryEntityID(
				\CCrmOwnerType::Contact,
				$item->remindActual(Item::FIELD_NAME_CONTACT_BINDINGS),
			);

			return new ItemIdentifier(\CCrmOwnerType::Contact, $primaryOrDefaultContactId);
		}

		return null;
	}

	private function synchronizeFieldValueInAllItemsOfThisType(
		Factory $factory,
		ItemIdentifier $client,
	): ?int
	{
		$firstSuccessfulItemId = $this->getFirstSuccessfulItemIdForClient($factory, $client);
		if ($firstSuccessfulItemId === null)
		{
			return null;
		}

		// the first successful item is NOT return customer, since it was the FIRST
		// other items with the same client after this - return customer

		$this->setTrueForAllItemsOfThisTypeThatBoundToThisClient($factory, $client);
		$this->setFalseInFirstSuccessfulItem($factory, $firstSuccessfulItemId);

		$factory->getDataClass()::cleanCache();
		Container::getInstance()->getEntityBroker($factory->getEntityTypeId())?->resetAllCache();

		return $firstSuccessfulItemId;
	}

	private function getFirstSuccessfulItemIdForClient(Factory $factory, ItemIdentifier $client): ?int
	{
		if (!$factory->isFieldExists(Item::FIELD_NAME_STAGE_SEMANTIC_ID))
		{
			throw new NotSupportedException(
				self::class . ' is not supported for entities without ' . Item::FIELD_NAME_STAGE_SEMANTIC_ID . ' field'
			);
		}

		$getItemsParams = [
			'select' => [Item::FIELD_NAME_ID],
			'filter' => [
				'=' . Item::FIELD_NAME_STAGE_SEMANTIC_ID => PhaseSemantics::SUCCESS,
			],
			'limit' => 1,
			'order' => [
				Item::FIELD_NAME_ID => 'ASC',
			],
		];

		if ($client->getEntityTypeId() === \CCrmOwnerType::Company)
		{
			$getItemsParams['filter']['=' . Item::FIELD_NAME_COMPANY_ID] = $client->getEntityId();
		}
		elseif ($client->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$getItemsParams['filter']['=' . Item::FIELD_NAME_CONTACT_ID] = $client->getEntityId();
			$getItemsParams['filter']['=' . Item::FIELD_NAME_COMPANY_ID] = 0;
		}
		else
		{
			throw new InvalidOperationException('The item has no client');
		}

		$previousSuccessfulItem = $factory->getItems($getItemsParams)[0] ?? null;

		return $previousSuccessfulItem?->getId();
	}

	private function setTrueForAllItemsOfThisTypeThatBoundToThisClient(Factory $factory, ItemIdentifier $client): void
	{
		/**
		 * Basically in the end we will have one of those queries
		 *
		 * UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'Y', IS_REPEATED_APPROACH = 'N' WHERE COMPANY_ID = {$companyID}
		 * UPDATE b_crm_deal SET IS_RETURN_CUSTOMER = 'Y', IS_REPEATED_APPROACH = 'N' WHERE CONTACT_ID = {$contactID} AND (COMPANY_ID IS NULL OR COMPANY_ID = 0)
		 */

		$sql = "UPDATE ?# SET ?# = 'Y'";
		$replacements = [$factory->getDataClass()::getTableName(), $factory->getEntityFieldNameByMap($this->getName())];
		if ($factory->isFieldExists(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH))
		{
			$sql .= ", ?# = 'N'";
			$replacements[] = $factory->getEntityFieldNameByMap(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH);
		}

		if ($client->getEntityTypeId() === \CCrmOwnerType::Company)
		{
			$sql .= ' WHERE ?# = ?i';
			$replacements[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_COMPANY_ID);
			$replacements[] = $client->getEntityId();
		}
		elseif ($client->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$sql .= ' WHERE ?# = ?i AND (?# IS NULL OR ?# = 0)';
			$replacements[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CONTACT_ID);
			$replacements[] = $client->getEntityId();
			$replacements[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_COMPANY_ID);
			$replacements[] = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_COMPANY_ID);
		}
		else
		{
			throw new InvalidOperationException('Unknown client entity type id');
		}

		$sqlExpression = new SqlExpression(
			$sql,
			...$replacements,
		);

		Application::getConnection()->query((string)$sqlExpression);
	}

	private function setFalseInFirstSuccessfulItem(Factory $factory, int $firstSuccessfulItemId): void
	{
		$sqlExpression = new SqlExpression(
			"UPDATE ?# SET ?# = 'N' WHERE ?# = ?i",
			$factory->getDataClass()::getTableName(),
			$factory->getEntityFieldNameByMap($this->getName()),
			$factory->getEntityFieldNameByMap(Item::FIELD_NAME_ID),
			$firstSuccessfulItemId,
		);

		Application::getConnection()->query((string)$sqlExpression);
	}
}
