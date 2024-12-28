<?php

namespace Bitrix\Crm\UI\SettingsButtonExtender;

use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Component\EntityList\Settings\PermissionItem;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Config;
use Bitrix\Crm\Integration\AI\Operation\Autostart\FillFieldsSettings;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use CUtil;

final class SettingsButtonExtenderParams
{
	private ?string $gridId = null;
	private ?int $categoryId = null;
	private ?bool $isAllItemsCategory = null;
	private ?string $targetItemId = null;

	private ?string $getRootMenuJsCallback = null;
	private ?string $getKanbanRestrictionJsCallback = null;
	private ?string $getKanbanSortSettingsControllerJsCallback = null;
	private array $expandsBehindThan = [
		PermissionItem::DELIMITER_ID,
	];

	public function __construct(private Factory $factory)
	{
	}

	public static function createDefaultForGrid(int $entityTypeId, string $gridId): self
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$self = new self($factory);

		$self->setGridId($gridId);

		Extension::load('crm.toolbar-component');

		$self->setGetRootMenuJsCallback(<<<JS
			const settingsButton = BX.Crm.ToolbarComponent.Instance.getSettingsButton();
			settingsButton ? settingsButton.getMenuWindow() : undefined;
		JS);

		return $self;
	}

	public function setGridId(?string $gridId): self
	{
		$this->gridId = $gridId;

		return $this;
	}

	public function setCategoryId(?int $categoryId): self
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	public function setIsAllItemsCategory(bool $isAllItemsCategory): self
	{
		$this->isAllItemsCategory = $isAllItemsCategory;

		return $this;
	}

	public function setTargetItemId(?string $targetItemId): self
	{
		$this->targetItemId = $targetItemId;

		return $this;
	}

	public function expandsBehindThan(string $itemId): self
	{
		$this->expandsBehindThan[] = $itemId;

		return $this;
	}

	/**
	 * @param string|null $getRootMenuJsCallback WARNING - this string will be eval'ed in JS!
	 *
	 * @return $this
	 */
	public function setGetRootMenuJsCallback(?string $getRootMenuJsCallback): self
	{
		$this->getRootMenuJsCallback = $getRootMenuJsCallback;

		return $this;
	}

	/**
	 * @param string|null $getKanbanRestrictionJsCallback WARNING - this string will be eval'ed in JS!
	 *
	 * @return $this
	 */
	public function setGetKanbanRestrictionJsCallback(?string $getKanbanRestrictionJsCallback): self
	{
		$this->getKanbanRestrictionJsCallback = $getKanbanRestrictionJsCallback;

		return $this;
	}

	/**
	 * @param string|null $getKanbanSortSettingsControllerJsCallback WARNING - this string will be eval'ed in JS!
	 *
	 * @return $this
	 */
	public function setGetKanbanSortSettingsControllerJsCallback(?string $getKanbanSortSettingsControllerJsCallback): self
	{
		$this->getKanbanSortSettingsControllerJsCallback = $getKanbanSortSettingsControllerJsCallback;

		return $this;
	}

	public function buildJsInitCode(): string
	{
		if (empty($this->getRootMenuJsCallback))
		{
			throw new ArgumentNullException('getRootMenuJsCallback');
		}

		Extension::load('crm.settings-button-extender');

		$paramsJsObject = CUtil::PhpToJSObject($this->buildParams(), false, false, true);

		$js = <<<JS
(function () {
	const params = {$paramsJsObject};
	
	params.rootMenu = eval(`{$this->getRootMenuJsCallback}`);
JS;

		if (!empty($this->gridId))
		{
			$escapedGridId = CUtil::JSEscape($this->gridId);

			$js .= PHP_EOL . "params.grid = BX.Reflection.getClass('BX.Main.gridManager') ? BX.Main.gridManager.getInstanceById('{$escapedGridId}') : undefined;";
		}
		if (!empty($this->getKanbanRestrictionJsCallback))
		{
			$js .= PHP_EOL . "params.restriction = eval(`{$this->getKanbanRestrictionJsCallback}`);";
		}
		if (!empty($this->getKanbanSortSettingsControllerJsCallback))
		{
			$js .= PHP_EOL . "params.controller = eval(`{$this->getKanbanSortSettingsControllerJsCallback}`);";
		}

		return $js . PHP_EOL . <<<JS
	if (params.rootMenu)
	{
		/** @see BX.Crm.SettingsButtonExtender */
		new BX.Crm.SettingsButtonExtender(params);
	}
})();
JS;
	}

	public function buildParams(): array
	{
		$entityTypeId = $this->factory->getEntityTypeId();

		$params = [
			'smartActivityNotificationSupported' => $this->factory->isSmartActivityNotificationSupported(),
			'entityTypeId' => $entityTypeId,
			'categoryId' => $this->categoryId,
			'pingSettings' => (new TodoPingSettingsProvider($entityTypeId, (int)$this->categoryId))->fetchAll(),
		];

		$skipPeriod = (new TodoCreateNotification($entityTypeId))->getCurrentSkipPeriod();
		if ($skipPeriod)
		{
			$params['todoCreateNotificationSkipPeriod'] = $skipPeriod;
		}

		if ($this->targetItemId)
		{
			$params['targetItemId'] = $this->targetItemId;
		}

		if (
			AIManager::isAiCallProcessingEnabled()
			&& in_array($entityTypeId, AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
			&& !$this->isAllItemsCategory()
			&& FillFieldsSettings::checkSavePermissions($entityTypeId, $this->categoryId)
		)
		{
			if (
				AIManager::isAiCallAutomaticProcessingAllowed()
				&& AIManager::isBaasServiceAvailable()
			)
			{
				$settings = FillFieldsSettings::get($entityTypeId, $this->categoryId);

				$params['aiAutostartSettings'] = Json::encode($settings);
			}

			$params['aiCopilotLanguageId'] = Config::getLanguageId(
				Container::getInstance()->getContext()->getUserId(),
				$entityTypeId,
				$this->categoryId
			);
		}

		$params['expandsBehindThan'] = $this->expandsBehindThan;

		return $params;
	}

	private function isAllItemsCategory(): bool
	{
		return is_bool($this->isAllItemsCategory) && $this->isAllItemsCategory;
	}
}
