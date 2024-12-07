<?php

namespace Bitrix\Sign\Connector;

use Bitrix\Sign\Connector;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Type\Field\ConnectorType;

class FieldConnectorFactory
{
	public function __construct(
		private MemberConnectorFactory $memberConnectorFactory,
		private FileRepository $fileRepository,
		private DocumentRepository $documentRepository,
	)
	{
	}

	public function create(Item\Field $field, ?Item\Member $member): ?Contract\Connector
	{
		return match (true)
		{
			$field->connectorType === ConnectorType::CRM_ENTITY && $member !== null => $this->getCrmEntityConnector($field, $member),
			$field->connectorType === ConnectorType::REQUISITE && $member !== null => new Connector\Field\Requisite(
				$field,
				$member,
				$this->memberConnectorFactory,
				$this->documentRepository
			),
			default => null,
		};
	}

	private function getCrmEntityConnector(Item\Field $field, Item\Member $member): ?Field\CrmEntity
	{
		if ($member->entityId === null)
		{
			return null;
		}

		return new Connector\Field\CrmEntity(
			$field,
			$member,
			$this->memberConnectorFactory,
			new Connector\Crm\SmartDocument($member->entityId)
		);
	}
}