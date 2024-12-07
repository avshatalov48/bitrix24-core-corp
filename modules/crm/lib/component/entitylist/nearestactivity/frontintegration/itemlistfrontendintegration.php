<?php

namespace Bitrix\Crm\Component\EntityList\NearestActivity\FrontIntegration;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\ItemIdentifier;
use CCrmActivityType;

class ItemListFrontendIntegration extends FrontIntegration
{
	public function onClickViewHandler(string $preparedGridId, int $activityId): string
	{
		$viewHandler = "BX.Crm.Activity.GridActivitiesManager.viewActivity";
		return $viewHandler . sprintf(
			"('%s', %d, { enableEditButton: %s }); return false;",
			$preparedGridId,
			$activityId,
			($this->getAllowEdit() ? 'true' : 'false')
		);
	}

	public function onClickAddHandler(string $preparedGridId, int $activityId, ItemIdentifier $itemIdentifier): string
	{
		$addHandler = "BX.Crm.Activity.GridActivitiesManager.showActivityAddingPopup";
		return "event.stopPropagation(); " . $addHandler . sprintf(
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
		$typeId = (int)($activity['TYPE_ID'] ?? 0);
		if (empty($typeId))
		{
			return false;
		}

		if ($typeId === CCrmActivityType::Provider)
		{
			$providerId = $activity['PROVIDER_ID'] ?? null;

			$supportedProviders = [
				Provider\Tasks\Task::getId(),
				Provider\Tasks\Comment::getId(),
				Provider\RestApp::getId(),
				Provider\ConfigurableRestApp::getId(),
				Provider\CalendarSharing::getId(),
			];

			return in_array($providerId, $supportedProviders, true);
		}
		elseif ($typeId === CCrmActivityType::Task)
		{
			return true;
		}

		return false;
	}
}