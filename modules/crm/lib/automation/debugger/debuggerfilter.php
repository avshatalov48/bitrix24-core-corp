<?php

namespace Bitrix\Crm\Automation\Debugger;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class DebuggerFilter
{
	public static function getFilterItems(): array
	{
		return [
			'SHOW' => \Bitrix\Main\Localization\Loc::getMessage('CRM_AUTOMATION_DEBUGGER_FILTER_SHOW')
		];
	}

	public static function prepareFilter(array $filter, int $entityTypeId): array
	{
		if (!array_key_exists('ROBOT_DEBUGGER', $filter))
		{
			return $filter;
		}

		if ($filter['ROBOT_DEBUGGER'] === 'SHOW')
		{
			$filter['ID'] = static ::getIds($entityTypeId);
		}

		unset($filter['ROBOT_DEBUGGER']);

		return $filter;
	}

	private static function getIds($entityTypeId): array
	{
		// todo: CBPRuntime::isFeatureEnabled() ?
		if (!(IsModuleInstalled('bizproc') && \Bitrix\Main\Loader::includeModule('bizproc')))
		{
			return [0];
		}

		$session = \Bitrix\Bizproc\Debugger\Session\Manager::getActiveSession();
		if (!$session || !$session->isStartedInDocumentType(\CCrmBizProcHelper::ResolveDocumentType($entityTypeId)))
		{
			return [0];
		}

		$documents = $session->getDocuments();
		$documentType = $session->getDocumentType();
		$result = [];

		foreach ($documents as $document)
		{
			$documentId = [$documentType[0], $documentType[1], $document->getDocumentId()];
			[$entityTypeName, $entityId] = \CCrmBizProcHelper::resolveEntityId($documentId);

			if ($entityId)
			{
				$result[] = $entityId;
			}
		}

		return (count($result) > 0) ? $result : [0];
	}

}
