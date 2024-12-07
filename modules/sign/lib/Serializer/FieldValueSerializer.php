<?php

namespace Bitrix\Sign\Serializer;

use Bitrix\Sign\Connector\FieldConnectorFactory;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\Field\ConnectorType;
use Bitrix\Sign\Type\FieldType;

class FieldValueSerializer
{
	public function __construct(
		private FieldConnectorFactory $fieldConnectorFactory,
	)
	{
	}

	public function serializeAsText(Item\Field $field, ?Item\Member $member = null): ?string
	{
		if (
			!in_array($field->type,
				[
					FieldType::STRING,
					FieldType::EMAIL,
					FieldType::PHONE,
					FieldType::NAME,
					FieldType::DATE,
				]
			)
		)
		{
			return null;
		}

		$fieldConnector = $this->fieldConnectorFactory->create($field, $member);
		if ($fieldConnector === null)
		{
			return null;
		}

		if (
			$field->connectorType === ConnectorType::REQUISITE
			&& $fieldConnector instanceof Contract\RequisiteConnector
			&& $member !== null
		)
		{
			$requisite = $fieldConnector->fetchRequisite(new Item\Connector\FetchRequisiteModifier($member->presetId));
			return (string)$requisite->getFirst()->value;
		}

		$connectorFields = $fieldConnector->fetchFields();
		$fieldValue = $connectorFields->getFirst();

		return $fieldValue === null ? null : (string)$fieldValue->data;
	}

	public function serializeAsFile(Item\Field $field, ?Item\Member $member = null): ?Item\Fs\File
	{
		if (!in_array($field->type, [FieldType::STAMP, FieldType::SIGNATURE, FieldType::FILE], true))
		{
			return null;
		}

		$fieldConnector = $this->fieldConnectorFactory->create($field, $member);
		if ($fieldConnector === null)
		{
			return null;
		}

		$connectorFields = $fieldConnector->fetchFields();
		$fieldValue = $connectorFields->getFirst();
		if ($fieldValue === null)
		{
			return null;
		}
		if (!$fieldValue->data instanceof Item\Fs\File)
		{
			return null;
		}

		return $fieldValue->data;
	}
}