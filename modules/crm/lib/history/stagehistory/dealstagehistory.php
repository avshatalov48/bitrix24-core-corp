<?php

namespace Bitrix\Crm\History\StageHistory;

use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\History\Entity\EO_DealStageHistory;

/**
 * @internal
 *
 * @method EO_DealStageHistory[] getListFilteredByPermissions
 */
final class DealStageHistory extends AbstractDealLikeStageHistory
{
	/**
	 * @inheritDoc
	 */
	protected function getDataClass(): string
	{
		return DealStageHistoryTable::class;
	}
}
