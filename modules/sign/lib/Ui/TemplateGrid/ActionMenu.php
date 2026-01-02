<?php

namespace Bitrix\Sign\Ui\TemplateGrid;

use Bitrix\Main\Grid\Panel;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;

class ActionMenu
{
	/**
	 * @psalm-return list<array{
	 *     TYPE: string,
	 *     ID: string,
	 *     TEXT: string,
	 *     ICON: string,
	 *     ONCHANGE: list<array{
	 *         ACTION: string,
	 *         DATA: list<array{
	 *             JS: string
	 *         }>
	 *     }>
	 * }>
	 */
	public static function getButtons(): array
	{
		$actionButtons = [];
		if (Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$actionButtons[] = [
				'TYPE' => Panel\Types::BUTTON,
				'ID' => 'sign-template-list-move-to-folder-button',
				'TEXT' => Loc::getMessage('SIGN_TEMPLATE_LIST_PANEL_ACTION_MOVE'),
				'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_move.svg',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => "templateGrid.moveTemplatesToFolder();"
							]
						]
					]
				]
			];
		}

		$actionButtons[] = [
			'TYPE' => Panel\Types::BUTTON,
			'ID' => 'sign-template-list-delete-button',
			'TEXT' => Loc::getMessage('SIGN_TEMPLATE_LIST_PANEL_ACTION_DELETE'),
			'ICON' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
			'ONCHANGE' => [
				[
					'ACTION' => Panel\Actions::CALLBACK,
					'DATA' => [
						[
							'JS' => "templateGrid.deleteSelectedItems();"
						]
					]
				]
			]
		];

		return $actionButtons;
	}
}