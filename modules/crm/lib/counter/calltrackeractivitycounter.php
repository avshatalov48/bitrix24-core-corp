<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;

class CallTrackerActivityCounter extends ActivityCounter
{
	/** @var bool */
	protected $sendPullEvent = true;

	/**
	 * Evaluate counter value
	 * @return int
	 */
	public function calculateValue(): int
	{
		if (!\Bitrix\Crm\Settings\CounterSettings::getInstance()->isEnabled())
		{
			return 0; // counters feature is completely disabled
		}

		$query = $this->prepareEntityQuery(\CCrmOwnerType::Deal);
		$query->setSelect([
			'QTY' => new ExpressionField('QTY', 'COUNT(*)')
		]);

		$fields = $query->fetch();
		return is_array($fields) ? (int)$fields['QTY'] : 0;
	}

	protected function resolveCode()
	{
		return parent::resolveCode(). '_calltracker';
	}

	protected function prepareActivityTableJoin(int $entityTypeID): array
	{
		$join = parent::prepareActivityTableJoin($entityTypeID);
		$join['=ref.TYPE_ID'] = new SqlExpression('?i', \CCrmActivityType::Provider);
		$join['=ref.PROVIDER_ID'] = new SqlExpression('?s', \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID);

		return $join;
	}
}
