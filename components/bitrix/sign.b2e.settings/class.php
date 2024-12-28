<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Main\Grid\Cell;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;

Loc::loadMessages(__FILE__);

class SignB2eSettingsComponent extends \CBitrixComponent
{
	private const GRID_ID = 'USER_FIELD_GRID_ID_LEGAL';
	private const FIELD_ACTION_DELETE = 'delete';
	private const ACTION_BUTTON_PREFIX = 'action_button_';

	protected AccessController $accessController;

	public function executeComponent(): void
	{
		if (!\Bitrix\Sign\Config\Storage::instance()->isB2eAvailable())
		{
			showError('access denied');

			return;
		}

		$this->accessController = new AccessController(\Bitrix\Main\Engine\CurrentUser::get()->getId());
		if (!$this->accessController->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_DELETE))
		{
			showError('access denied');

			return;
		}

		$this->handleAction();
		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	private function prepareResult(): void
	{
		$this->prepareComponentParams();
		$this->prepareGridParams();
		$this->prepareData();
	}

	public function prepareComponentParams(): void
	{
		$this->arResult['IS_SHOW_TITLE'] = true;
		$this->arResult['TITLE'] = Loc::getMessage('SIGN_B2E_SETTINGS_PAGE_TITLE_MSG_VER_1');
	}

	public function prepareGridParams(): void
	{
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->arResult['COLUMNS'] = $this->getGridColumns();
	}

	private function getGridColumns(): array
	{
		return [
			'id' => [
				'id' => 'ID',
				'name' => Loc::getMessage('SIGN_B2E_SETTINGS_COLUMN_NAME_ID'),
				'sort' => 'ID',
				'default' => false,
				'editable' => false,
				'type' => Grid\Types::GRID_INT,
			],
			'title' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('SIGN_B2E_SETTINGS_COLUMN_NAME_TITLE'),
				'default' => true,
				'editable' => false,
			],
			'type' => [
				'id' => 'TYPE',
				'name' => Loc::getMessage('SIGN_B2E_SETTINGS_COLUMN_NAME_TYPE'),
				'default' => true,
				'editable' => false,
			],
		];
	}

	private function prepareData(): void
	{
		$provider = new LegalInfoProvider();
		$fields = $provider->getUserFields();

		$this->arResult['GRID_DATA'] = array_values(
			array_map(
				static function($field) use ($provider) {
					return [
						'ID' => (int)$field['ID'],
						'TITLE' => $provider->getCaption($field),
						'TYPE' => $field['USER_TYPE']['DESCRIPTION'] ?? $field['USER_TYPE_ID'],
					];
				},
				array_filter($fields, [$this,'isNotDefaultLegalField'])
			)
		);

		$this->arResult['TOTAL_COUNT'] = count($this->arResult['GRID_DATA']);
	}

	protected function handleAction(): void
	{
		if (!$this->accessController->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_DELETE))
		{
			return;
		}

		global $USER_FIELD_MANAGER;

		$ids = $this->request->get('ID');
		$action = $this->request->get(self::ACTION_BUTTON_PREFIX . self::GRID_ID);
		if ($action === self::FIELD_ACTION_DELETE)
		{
			$userFields = $USER_FIELD_MANAGER->getUserFields(\Bitrix\Sign\Config\LegalInfo::USER_FIELD_ENTITY_ID);
			if (!is_array($ids))
			{
				$ids = [$ids];
			}

			$convertedUserFields = array_column($userFields, 'FIELD_NAME', 'ID');
			$userTypeEntity = new \CUserTypeEntity();
			foreach ($ids as $id)
			{
				$id = (int)$id;
				if (
					isset($convertedUserFields[$id])
					&& in_array($convertedUserFields[$id], LegalInfoProvider::LEGAL_USER_FIELD_DEFAULT, true)
				)
				{
					continue;
				}

				$userTypeEntity->Delete($id);
			}
		}
	}

	private function isNotDefaultLegalField(array $field): bool
	{
		return !in_array($field['FIELD_NAME'], LegalInfoProvider::LEGAL_USER_FIELD_DEFAULT, true);
	}
}
