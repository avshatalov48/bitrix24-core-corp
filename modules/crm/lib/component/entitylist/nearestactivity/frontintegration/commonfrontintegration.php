<?php

namespace Bitrix\Crm\Component\EntityList\NearestActivity\FrontIntegration;

use Bitrix\Crm\ItemIdentifier;

class CommonFrontIntegration extends FrontIntegration
{
	public function onClickViewHandler(string $preparedGridId, int $activityId): string
	{
		$viewHandler = 'BX.CrmUIGridExtension.viewActivity';

		return $viewHandler . sprintf(
			"('%s', %d, { enableEditButton: %s }); return false;",
			$preparedGridId,
			$activityId,
			($this->getAllowEdit() ? 'true' : 'false')
		);
	}

	public function onClickAddHandler(string $preparedGridId, int $activityId, ItemIdentifier $itemIdentifier): string
	{
		$addHandler = 'BX.CrmUIGridExtension.showActivityAddingPopup';

		return $addHandler . sprintf(
			"(this, '%s', %d, %d, %s, %s, %s); return false;",
			$preparedGridId,
			$itemIdentifier->getEntityTypeId(),
			$itemIdentifier->getEntityId(),
			$this->getCurrentUserInfo(),
			$this->getSettings($itemIdentifier),
			true,
		);
	}

	public function isActivityViewSupport(array $activity): bool
	{
		if (isset($activity['PROVIDER_ID']))
		{
			$provider = \CCrmActivity::GetProviderById($activity['PROVIDER_ID']);
			if ($provider)
			{
				return $provider::hasPlanner($activity);
			}
		}

		return true;
	}

}