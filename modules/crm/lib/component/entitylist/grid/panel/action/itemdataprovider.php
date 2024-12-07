<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action;

use Bitrix\Crm\CallList\CallList;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\CallList\CreateAndStartCallListAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\DeleteAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\EditAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\ItemGroupAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\MyCompanyItemGroupAction;
use Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\RecurringItemGroupAction;
use Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\MessageSender\MassWhatsApp\SendItem;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Settings;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Grid\Panel\Action\DataProvider;
use Bitrix\Main\Grid\Panel\Action\ForAllCheckboxAction;
use Bitrix\Main\ModuleManager;

/**
 * @method ItemSettings getSettings()
 */
final class ItemDataProvider extends DataProvider
{
	public function __construct(
		private Factory $factory,
		private UserPermissions $userPermissions,
		private Context $context,
		ItemSettings $settings,
	)
	{
		parent::__construct($settings);

		if ($this->context->getUserId() !== $this->userPermissions->getUserId())
		{
			throw new ArgumentException('Context and UserPermissions should mention the same user');
		}
	}

	public function prepareActions(): array
	{
		$actions = [];

		if ($this->userPermissions->checkDeletePermissions($this->factory->getEntityTypeId(), 0, $this->getCategoryId()))
		{
			$actions[] = new DeleteAction($this->factory->getEntityTypeId());
		}

		if (!$this->getSettings()->isMyCompany())
		{
			if ($this->userPermissions->checkUpdatePermissions($this->factory->getEntityTypeId(), 0, $this->getCategoryId()))
			{
				$actions[] = new EditAction(
					$this->factory,
					$this->context,
					$this->getCategoryId(),
					$this->getSettings()->getEditableFieldsWhitelist(),
					$this->getSettings()->getColumnNameToEditableFieldNameMap(),
				);
			}

			if (
				!$this->getSettings()->isRecurring()
				&& ModuleManager::isModuleInstalled('voximplant')
				&& CallList::isEntityTypeSupported($this->factory->getEntityTypeId())
			)
			{
				$actions[] = new CreateAndStartCallListAction($this->factory->getEntityTypeId());
			}

			if ($this->isShowWhatsAppMessageAction())
			{
				$actions[] = new WhatsAppMessageAction(
					$this->factory->getEntityTypeId(),
					$this->getCategoryId()
				);
			}
		}

		if (!empty($actions))
		{
			if ($this->getSettings()->isMyCompany())
			{
				$actions[] = new MyCompanyItemGroupAction(
					$this->factory,
					$this->userPermissions,
					$this->getSettings(),
				);
			}
			elseif ($this->getSettings()->isRecurring())
			{
				$actions[] = new RecurringItemGroupAction(
					$this->factory,
					$this->userPermissions,
					$this->getSettings(),
				);
			}
			else
			{
				$actions[] = new ItemGroupAction(
					$this->factory,
					$this->userPermissions,
					$this->getSettings(),
				);
			}

			$actions[] = new ForAllCheckboxAction();
		}

		return $actions;
	}

	private function isShowWhatsAppMessageAction(): bool
	{
		if (!Settings\Crm::isWhatsAppScenarioEnabled())
		{
			return false;
		}

		$sender = SmsManager::getSenderById(SendItem::DEFAULT_PROVIDER);

		if (!$sender)
		{
			return false;
		}

		if (!$sender::isSupported())
		{
			return false;
		}

		if ($this->factory->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			return true;
		}

		if ($this->factory->getEntityTypeId() === \CCrmOwnerType::Company && !$this->getSettings()->isMyCompany())
		{
			return true;
		}

		return false;
	}

	private function getCategoryId(): ?int
	{
		return $this->getSettings()->getCategoryId();
	}
}
