<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Grid\Panel\Action\GroupAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Localization\Loc;

abstract class BaseItemGroupAction extends GroupAction
{
	final public function __construct(
		protected Factory $factory,
		protected UserPermissions $userPermissions,
		protected ItemSettings $gridSettings,
	)
	{
	}

	final public function getControl(): ?array
	{
		$control = parent::getControl();
		if ($control === null)
		{
			return null;
		}

		foreach ($control['ITEMS'] as &$item)
		{
			if ($item['VALUE'] === 'default')
			{
				$item['NAME'] = Loc::getMessage('CRM_GRID_PANEL_GROUP_CHOOSE_ACTION');

				$item['ONCHANGE'][] = [
					'ACTION' => Actions::SHOW,
					'DATA' => [
						['ID' => DefaultValue::FOR_ALL_CHECKBOX_ID],
					],
				];
			}
		}

		return $control;
	}

	final protected function canUpdateItemsInCategory(): bool
	{
		return $this->userPermissions->checkUpdatePermissions(
			$this->factory->getEntityTypeId(),
			0,
			$this->gridSettings->getCategoryId()
		);
	}

	final protected function canDeleteItemsInCategory(): bool
	{
		return $this->userPermissions->checkDeletePermissions(
			$this->factory->getEntityTypeId(),
			0,
			$this->gridSettings->getCategoryId()
		);
	}
}
