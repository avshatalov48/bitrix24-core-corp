<?php

namespace Bitrix\Sign\Connector\Field;

use Bitrix\Sign\Connector\MemberConnectorFactory;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Connector\FieldCollection;
use Bitrix\Sign\Item\Connector\FetchRequisiteModifier;
use Bitrix\Sign\Repository\DocumentRepository;

class Requisite implements Contract\Connector, Contract\RequisiteConnector
{
	public function __construct(
		private Item\Field $field,
		private Item\Member $member,
		private MemberConnectorFactory $memberConnectorFactory,
		private DocumentRepository $documentRepository,
	)
	{
	}

	public function fetchFields(): FieldCollection
	{
		$memberRequisitePresetId = $this->member->presetId;
		$requisiteFields = $this->fetchRequisite(
			new FetchRequisiteModifier($memberRequisitePresetId)
		);

		$result = new FieldCollection();
		foreach ($requisiteFields as $requisiteField)
		{
			if (Item\Connector\Field::isValueTypeSupported($requisiteField->value))
			{
				$result->add(new Item\Connector\Field(
					$requisiteField->name,
					$requisiteField->value,
					$requisiteField->label,
				));
			}
		}

		return $result;
	}

	public function fetchRequisite(?FetchRequisiteModifier $fetchModifier = null): Item\Connector\RequisiteFieldCollection
	{
		$memberConnector = $this->memberConnectorFactory->createRequisiteConnector($this->member);
		if ($memberConnector === null)
		{
			return new Item\Connector\RequisiteFieldCollection();
		}

		$memberRequisitePresetId = $this->member->presetId;
		$fields = $memberConnector->fetchRequisite(
			new Item\Connector\FetchRequisiteModifier($memberRequisitePresetId)
		);
		foreach ($fields as $field)
		{
			if (
				$field->name === $this->field->entityCode
				&& Item\Connector\RequisiteField::isValueTypeSupported($field->data)
			)
			{
				return new Item\Connector\RequisiteFieldCollection(clone $field);
			}
		}

		return new Item\Connector\RequisiteFieldCollection();
	}

	public function getName(): string
	{
		$memberRequisitePresetId = $this->member->presetId;
		$requisiteFields = $this->fetchRequisite(
			new FetchRequisiteModifier($memberRequisitePresetId)
		);
		$firstRequisiteField = $requisiteFields->getFirst();

		return $firstRequisiteField?->label ?? '';
	}
}