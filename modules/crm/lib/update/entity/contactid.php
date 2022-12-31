<?php

namespace Bitrix\Crm\Update\Entity;

use Bitrix\Crm\Binding\BindingHelper;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * The stepper recalculates CONTACT_ID field in entities where it was set to 0 even with bound contacts.
 */
final class ContactId extends MultipleEntityTypesStepper
{
	protected const OPTION_PREFIX = 'update_contact_id_stepper_';

	/** @var Array<int, null | Array<int, mixed[]>> - [entityTypeId => [itemId => bindings]] */
	private array $typeToBindings = [];

	protected function isSupported(Factory $factory): bool
	{
		return (
			$factory->isFieldExists(Item::FIELD_NAME_CONTACT_ID)
			&& $factory->isFieldExists(Item::FIELD_NAME_CONTACT_BINDINGS)
		);
	}

	protected function getRowsToProcess(Factory $factory, ?int $lastId): Collection
	{
		$query =
			$factory->getDataClass()::query()
				->setSelect([
					$factory->getEntityFieldNameByMap(Item::FIELD_NAME_ID),
					$factory->getEntityFieldNameByMap(Item::FIELD_NAME_CONTACT_ID),
				])
				->where($factory->getEntityFieldNameByMap(Item::FIELD_NAME_CONTACT_ID), 0)
				->addOrder($factory->getEntityFieldNameByMap(Item::FIELD_NAME_ID))
				->setLimit(self::getSingleEntityStepLimit())
		;

		if ($lastId !== null)
		{
			$query->where($factory->getEntityFieldNameByMap(Item::FIELD_NAME_ID), '>', $lastId);
		}

		/** @var Collection $rows */
		$rows = $query->fetchCollection();

		if (count($rows) > 0)
		{
			$this->typeToBindings[$factory->getEntityTypeId()] = BindingHelper::getBulkEntityBindings(
				$factory->getEntityTypeId(),
				$rows->getIdList(),
				\CCrmOwnerType::Contact,
			);
		}

		return $rows;
	}

	protected function processRow(Factory $factory, EntityObject $row): void
	{
		$bindings = $this->typeToBindings[$factory->getEntityTypeId()][$row->getId()] ?? null;
		if (!is_array($bindings))
		{
			return;
		}

		$primaryContactId = EntityBinding::getPrimaryEntityID(\CCrmOwnerType::Contact, $bindings);
		$row->set(
			$factory->getEntityFieldNameByMap(Item::FIELD_NAME_CONTACT_ID),
			$primaryContactId,
		);

		$row->save();
	}
}
