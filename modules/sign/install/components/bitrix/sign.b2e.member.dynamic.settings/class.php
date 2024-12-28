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
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;

Loc::loadMessages(__FILE__);

class SignB2eMemberDynamicSettingsComponent extends \CBitrixComponent
{
	private const GRID_ID = 'USER_FIELD_GRID_ID_MEMBER_DYNAMIC';
	private const FIELD_ACTION_DELETE = 'delete';
	private const ACTION_BUTTON_PREFIX = 'action_button_';

	protected AccessController $accessController;

	public function executeComponent(): void
	{
		if (!\Bitrix\Main\Loader::includeModule('sign'))
		{
			showError('module sign not installed');

			return;
		}

		if (!Storage::instance()->isB2eAvailable() || !Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			showError('access denied');

			return;
		}

		$this->accessController = new AccessController(\Bitrix\Main\Engine\CurrentUser::get()->getId());
		if (!$this->accessController->check(ActionDictionary::ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE))
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
		$this->arResult['TITLE'] = Loc::getMessage('SIGN_B2E_MEMBER_DYNAMIC_SETTINGS_PAGE_TITLE');
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
				'name' => Loc::getMessage('SIGN_B2E_MEMBER_DYNAMIC_SETTINGS_COLUMN_NAME_ID'),
				'sort' => 'ID',
				'default' => false,
				'editable' => false,
				'type' => Grid\Types::GRID_INT,
			],
			'title' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('SIGN_B2E_MEMBER_DYNAMIC_SETTINGS_COLUMN_NAME_TITLE'),
				'default' => true,
				'editable' => false,
			],
			'type' => [
				'id' => 'TYPE',
				'name' => Loc::getMessage('SIGN_B2E_MEMBER_DYNAMIC_SETTINGS_COLUMN_NAME_TYPE'),
				'default' => true,
				'editable' => false,
			],
		];
	}

	private function prepareData(): void
	{
		$provider = Container::instance()->getMemberDynamicFieldProvider();

		$this->arResult['GRID_DATA'] = array_values(
			array_map(
				static function($field) use ($provider) {
					return [
						'ID' => (int)$field['ID'],
						'TITLE' => $provider->getCaption($field),
						'TYPE' => $field['USER_TYPE']['DESCRIPTION'] ?? $field['USER_TYPE_ID'],
					];
				},
				$provider->getUserFields(),
			),
		);

		$this->arResult['TOTAL_COUNT'] = count($this->arResult['GRID_DATA']);
	}

	protected function handleAction(): void
	{
		if (!$this->accessController->check(ActionDictionary::ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE))
		{
			return;
		}

		$ids = $this->request->get('ID');
		if (!is_array($ids))
		{
			$ids = [$ids];
		}
		$action = $this->request->get(self::ACTION_BUTTON_PREFIX . self::GRID_ID);
		if ($action === self::FIELD_ACTION_DELETE)
		{
			$this->deleteByIdsAction($ids);
		}
	}

	protected function deleteByIdsAction(array $ids): void
	{
		global $USER_FIELD_MANAGER;
		$userFields = $USER_FIELD_MANAGER->getUserFields(MemberDynamicFieldInfoProvider::USER_FIELD_ENTITY_ID);
		$convertedUserFields = array_column($userFields, 'FIELD_NAME', 'ID');
		$userTypeEntity = new \CUserTypeEntity();
		foreach ($ids as $id)
		{
			$id = (int)$id;
			if (isset($convertedUserFields[$id]))
			{
				$userTypeEntity->Delete($id);
			}
		}
	}
}
