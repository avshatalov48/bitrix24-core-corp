<?php

namespace Bitrix\BIConnector\Integration\Bizproc;

use Bitrix\Bizproc\Workflow\Entity\WorkflowUserTable;
use Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;

class Template
{
	public static function getListForUser(int $userId): ?array
	{
		if (!Loader::includeModule('bizproc'))
		{
			return null;
		}

		$stateQuery = WorkflowStateTable::query();
		$stateQuery->setSelect(['TID' => 'WORKFLOW_TEMPLATE_ID', 'TNAME' => 'TEMPLATE.NAME']);

		$subQuery = WorkflowUserTable::query();
		$subQuery->addSelect('WORKFLOW_ID');
		$subQuery->addFilter('USER_ID', $userId);

		$stateQuery->registerRuntimeField('',
			new ReferenceField('M',
				\Bitrix\Main\ORM\Entity::getInstanceByQuery($subQuery),
				['=this.ID' => 'ref.WORKFLOW_ID'],
				['join_type' => 'INNER']
			)
		);

		return $stateQuery->exec()->fetchAll();
	}
}
