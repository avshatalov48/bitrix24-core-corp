<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\QueryParts;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

final class DeadlineBounds
{
	public function getLowBound(QueryParams $params): ?DateTime
	{
		if (!$params->periodFrom())
		{
			return null;
		}
		$lowBound = DateTime::createFromTimestamp($params->periodFrom()->getTimestamp());
		$lowBound->setTime(0, 0, 0);

		return CCrmDateTimeHelper::getServerTime($lowBound, $params->firstUserId())->disableUserTime();
	}

	public function getHighBound(QueryParams $params): ?DateTime
	{
		if (!$params->periodTo())
		{
			return null;
		}
		$highBound = DateTime::createFromTimestamp($params->periodTo()->getTimestamp());
		$highBound->setTime(23, 59, 59);

		return CCrmDateTimeHelper::getServerTime($highBound, $params->firstUserId())->disableUserTime();
	}

	public function applyFilerToField(string $fieldName, ConditionTree $ct, QueryParams $params): void
	{
		$lowBound = $this->getLowBound($params);
		$highBound = $this->getHighBound($params);

		$sqlHelper = Application::getConnection()->getSqlHelper();
		$ct->where($fieldName, '>=', new SqlExpression($sqlHelper->convertToDbDateTime($lowBound)));
		$ct->where($fieldName, '<=', new SqlExpression($sqlHelper->convertToDbDateTime($highBound)));
	}
}