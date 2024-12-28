<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\Main\Access\AccessibleItem;

final class ExternalDashboardConfigRule extends BaseRule
{
	/**
	 * Check access permission.
	 * @param AccessibleItem|null $item
	 * @param null $params
	 *
	 * @return bool
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!Feature::isExternalEntitiesEnabled())
		{
			return false;
		}

		return parent::execute($item, $params);
	}
}
