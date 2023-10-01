<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

class AgentContractProvider extends Provider
{
	protected static function getComponentName(): string
	{
		return 'catalog.agent.contract.detail';
	}

	protected static function getDocumentPrimaryField(): string
	{
		return 'CONTRACT_ID';
	}

	protected static function getTableName(): string
	{
		return AgentContractContractorTable::class;
	}
}
