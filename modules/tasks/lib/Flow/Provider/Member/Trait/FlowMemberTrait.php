<?php

namespace Bitrix\Tasks\Flow\Provider\Member\Trait;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

trait FlowMemberTrait
{
	/**
	 * @throws ProviderException
	 * @return string[]
	 */
	protected function getMemberAccessCodesByRole(
		Role $responsibleRole,
		int $flowId,
		?int $offset = null,
		?int $limit = null
	): array
	{
		if ($flowId <= 0)
		{
			return [];
		}

		try
		{
			$query = FlowMemberTable::query()
				->setSelect(['ID', 'ACCESS_CODE'])
				->where('FLOW_ID', $flowId)
				->where('ROLE', $responsibleRole->value)
				->setOffset($offset)
				->setOrder(['ID' => 'ASC'])
				->setLimit($limit);

			return $query->exec()->fetchCollection()->getAccessCodeList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}
	}
}