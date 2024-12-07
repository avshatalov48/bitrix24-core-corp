<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Services\ApacheSuperset;
use Bitrix\BiConnector\Settings\Grid\KeysGrid;
use Bitrix\BiConnector\Settings\Grid\KeysSettings;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector;
use Bitrix\UI\Buttons;
use Bitrix\Main\UI\Filter;
use Bitrix\UI\Toolbar;
use Bitrix\UI\Toolbar\Facade;
use Bitrix\Main\Grid;
use Bitrix\BiConnector\Settings;

class KeyListComponent extends CBitrixComponent implements Controllerable
{
	private CurrentUser $currentUser;
	private bool $canWrite;
	private bool $canRead;
	private ?Buttons\Button $createKeyButton;
	private KeysGrid $grid;
	private const GRID_ID = 'biconnector_key_list';
	private const FILTER_FIELDS_ID = ['ACCESS_KEY', 'LAST_ACTIVITY_DATE', 'DATE_CREATE', 'TIMESTAMP_X'];
	private const MODULE_NAME = 'biconnector';
	private const ONBOARDING_OPTION_NAME = 'onboarding_key_list';

	public function __construct($component = null)
	{
		$this->currentUser = CurrentUser::get();
		$this->canWrite = $this->currentUser->canDoOperation('biconnector_key_manage');
		$this->canRead = $this->canWrite || $this->currentUser->canDoOperation('biconnector_key_view');
		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['KEY_LIST_URL'] ??= 'key_list.php';
		$arParams['KEY_ADD_URL'] ??= 'key_edit.php';
		$arParams['KEY_EDIT_URL'] ??= 'key_edit.php?key_id=#ID#';

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if (!$this->canWrite && !$this->canRead)
		{
			ShowError(Loc::getMessage('ACCESS_DENIED'));

			return;
		}

		if (!Loader::includeModule('biconnector'))
		{
			ShowError(Loc::getMessage('CC_BBKL_ERROR_INCLUDE_MODULE'));

			return;
		}

		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->prepareToolbar();
		$this->arResult['GRID'] = Grid\Component\ComponentParams::get($this->getGrid());
		$this->arResult['GRID']['STUB'] = isset($this->arResult['GRID']['ROWS'])
			&& empty($this->arResult['GRID']['ROWS']) ? $this->getGridEmptyStateBlock() : null;
		$this->arResult['ONBOARDING_BUTTON_ID'] = $this->getCreateKeyButton()->getUniqId();
		$this->arResult['IS_AVAILABLE_ONBOARDING'] = !$this->isOnboardingShowed();
		$this->includeComponentTemplate();
	}

	private function getGrid(): KeysGrid
	{
		if (!isset($this->grid))
		{
			$settings = new KeysSettings([
				'ID' => self::GRID_ID,
				'ALLOW_ROWS_SORT' => false,
				'SHOW_ROW_CHECKBOXES' => false,
				'SHOW_SELECTED_COUNTER' => false,
				'SHOW_TOTAL_COUNTER' => false,
				'EDITABLE' => false,
				'CAN_WRITE' => $this->canWrite,
				'CAN_READ' => $this->canRead,
				'KEY_EDIT_URL' => $this->arParams['KEY_EDIT_URL'],
			]);

			$this->grid = new KeysGrid($settings);
			$gridData = $this->getGridData($this->grid);
			$this->grid->setRawRows($gridData);
		}

		return $this->grid;
	}

	protected function getFilterConfig(): array
	{
		return [
			'FILTER_ID' => $this->getGrid()->getId(),
			'GRID_ID' => $this->getGrid()->getId(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'THEME' => Filter\Theme::LIGHT,
			'FILTER' => $this->getGrid()->getFilter()?->getFieldArrays(self::FILTER_FIELDS_ID),
		];
	}

	protected function prepareToolbar(): void
	{
		Facade\Toolbar::addFilter($this->getFilterConfig());

		if ($this->canWrite)
		{
			Facade\Toolbar::addButton($this->getCreateKeyButton(), Toolbar\ButtonLocation::AFTER_TITLE);
		}

		Facade\Toolbar::addButton(new Settings\Buttons\Implementation());
		Facade\Toolbar::deleteFavoriteStar();
	}

	protected function getCreateKeyButton(): Buttons\Button
	{
		if (isset($this->createKeyButton))
		{
			return $this->createKeyButton;
		}

		$this->createKeyButton = new Buttons\Button([
			'text' => Loc::getMessage('CT_BBKL_KEY_BUTTON_CREATE_TITLE'),
			'color' => Buttons\Color::SUCCESS,
			'dataset' => [
				'toolbar-collapsed-icon' => Buttons\Icon::ADD,
			],
			'click' => new Buttons\JsCode(
				"top.BX.SidePanel.Instance.open('{$this->arParams['KEY_ADD_URL']}', {width: 650, loader: 'biconnector:create-key'})"
			),
			'id' => 'add-report-button-id',
		]);

		return $this->createKeyButton;
	}

	protected function getGridData(KeysGrid $grid): array
	{
		$keyQuery = BIConnector\KeyTable::query()
			->setSelect([
				'ID',
				'DATE_CREATE',
				'CREATED_BY',
				'CREATED_USER.NAME',
				'CREATED_USER.LAST_NAME',
				'CREATED_USER.SECOND_NAME',
				'CREATED_USER.EMAIL',
				'CREATED_USER.LOGIN',
				'CREATED_USER.PERSONAL_PHOTO',
				'CONNECTION',
				'ACCESS_KEY',
				'ACTIVE',
				'APP_ID',
				'APPLICATION.APP_NAME',
				'LAST_ACTIVITY_DATE',
				'TIMESTAMP_X',
			])
			->setOrder($grid->getOrmOrder())
			->setFilter($grid->getOrmFilter())
			->addFilter(null, [
				'LOGIC' => 'OR',
				'==SERVICE_ID' => null,
				'!=SERVICE_ID' => ApacheSuperset::getServiceId(),
			])
		;

		if (!$this->canWrite)
		{
			$keyQuery->addFilter('=PERMISSION.USER_ID', $this->currentUser->getId());
		}

		return $keyQuery->exec()->fetchAll();
	}

	protected function getGridEmptyStateBlock(): string
	{
		$title = Loc::getMessage('CT_BBKL_KEY_EMPTYSTATE_TITLE');
		$subtitle = Loc::getMessage('CT_BBKL_KEY_EMPTYSTATE_SUBTITLE');

		return "
			<div class=\"biconnector-empty\">
				<div class=\"biconnector-empty__icon --keys\"></div>
				<div class=\"biconnector-empty__title\">$title</div>
				<div class=\"biconnector-empty__title-sub\">$subtitle </div>
			</div>
		";
	}

	public function deleteRowAction(int $id): bool
	{
		if ($this->canWrite && Loader::includeModule('biconnector'))
		{
			BIConnector\KeyUserTable::deleteByFilter(['=KEY_ID' => $id]);

			return BIConnector\KeyTable::delete($id)->isSuccess();
		}

		return false;
	}

	public function activateKeyAction(int $id): bool
	{
		if ($this->canWrite && Loader::includeModule('biconnector'))
		{
			return BIConnector\KeyTable::update($id, [
				'ACTIVE' => 'Y',
			])->isSuccess();
		}

		return false;
	}

	public function deactivateKeyAction(int $id): bool
	{
		if ($this->canWrite && Loader::includeModule('biconnector'))
		{
			return BIConnector\KeyTable::update($id, [
				'ACTIVE' => 'N',
			])->isSuccess();
		}

		return false;
	}

	public function markShowOnboardingAction(): void
	{
		CUserOptions::setOption(self::MODULE_NAME, self::ONBOARDING_OPTION_NAME, time());
	}

	public function isOnboardingShowed(): bool
	{
		return (bool)CUserOptions::getOption(self::MODULE_NAME, self::ONBOARDING_OPTION_NAME);
	}

	public function configureActions(): array
	{
		return [];
	}
}
