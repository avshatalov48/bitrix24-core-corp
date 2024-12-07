<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('crm'))
{
	return;
}

class CCrmCommunicationChannelListComponent extends \Bitrix\Crm\Component\Base
{
	public function executeComponent()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_TITLE'));

		$this->arResult['COLUMNS'] = $this->getColumns();
		$this->arResult['ROWS'] = $this->getRows();

		$this->includeComponentTemplate();
	}

	private function getColumns(): array
	{
		return [
			[
				'id' => 'ID',
				'default' => true,
				'name' => 'ID',
			],
			[
				'id' => 'SORT',
				'default' => true,
				'name' => Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_COLUMN_SORT'),
			],
			[
				'id' => 'TITLE',
				'default' => true,
				'name' => Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_COLUMN_TITLE'),
			],
			[
				'id' => 'RULES',
				'default' => true,
				'name' => Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_COLUMN_RULES'),
			],
			[
				'id' => 'QUEUE',
				'default' => true,
				'name' => Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_COLUMN_QUEUE'),
			],
			[
				'id' => 'ENTITIES',
				'default' => true,
				'name' => Loc::getMessage('CRM_COMMUNICATIONCHANNEL_RULE_LIST_COLUMN_ENTITIES'),
			]
		];
	}

	private function getRows(): array
	{
		$ruleController = Crm\Service\Communication\Controller\RuleController::getInstance();
		$rules = $ruleController->getList();

		$rows = [];
		foreach ($rules as &$rule)
		{
			$id = (int)$rule['ID'];
			$rule['ID'] = '<a href="' . $this->getDetailsUrl($id) . '">' . $id . '</a>';

			$rows[] = [
				'id' => $id,
				//'data' => $rule,
				'columns' => $rule,
				// editable
				// actions
			];
		}
		unset($rule);

		return $rows;
	}

	private function getDetailsUrl(int $id): string
	{
		return '/crm/configs/communication_channel_routes/details/' . $id . '/';
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];
		$buttons[\Bitrix\UI\Toolbar\ButtonLocation::AFTER_TITLE][] = new \Bitrix\UI\Buttons\Button([
			'color' =>\Bitrix\UI\Buttons\Color::SUCCESS,
			'text' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
			'onclick' => new \Bitrix\UI\Buttons\JsCode(
				"BX.SidePanel.Instance.open(
				'/crm/configs/communication_channel_routes/details/0/',
				{
					width: 700,
					allowChangeHistory: false
				});"),
		]);

		return array_merge(parent::getToolbarParameters(), [
			'buttons' => $buttons,
			'isWithFavoriteStar' => true,
			'hideBorder' => true,
		]);
	}
}
