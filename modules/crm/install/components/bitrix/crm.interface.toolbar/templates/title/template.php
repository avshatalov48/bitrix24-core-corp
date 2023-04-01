<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\AddButton;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\SettingsButton;
use Bitrix\UI\Buttons\Split\Button as SplitButton;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var array $arParams */

Extension::load(['crm.toolbar-component', 'ui.design-tokens']);

$toolbarId = $arParams['TOOLBAR_ID'];
$items = [];
$settingsItems = [];
$enableMoreButton = false;

foreach($arParams['BUTTONS'] as $item)
{
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true)
	{
		$enableMoreButton = true;
		continue;
	}

	if($enableMoreButton)
	{
		$settingsItems[] = $item;
	}
	else
	{
		$items[] = $item;
	}
}

if(!empty($settingsItems))
{
	$settingsButtonId = htmlspecialcharsbx($toolbarId);
	$settingsMenuId = htmlspecialcharsbx("{$toolbarId}_settings_menu");
	$settingsButtonItems = Bitrix\Crm\UI\Tools\ToolBar::mapItems(
		$settingsItems,
		$toolbarId,
		$arParams['TOOLBAR_PARAMS'] ?? []
	);
	$settingsButton = new SettingsButton([
		'id' => $settingsButtonId,
		'menu' => [
			'id' => $settingsMenuId,
			'items' => $settingsButtonItems,
			'offsetLeft' => 20,
			'closeByEsc' => true,
			'angle' => true,
		],
	]);
	Toolbar::addButton($settingsButton);
}

$itemCount = count($items);
for($i = 0; $i < $itemCount; $i++)
{
	$item = $items[$i];
	$text = isset($item['TEXT']) ? htmlspecialcharsbx(strip_tags($item['TEXT'])) : '';
	$title = isset($item['TITLE']) ? htmlspecialcharsbx(strip_tags($item['TITLE'])) : '';
	$icon = isset($item['ICON']) ? htmlspecialcharsbx($item['ICON']) : '';
	$link = $item['LINK'] ?? '#';
	$onClick = (isset($item['ONCLICK']) && $item['ONCLICK']) ? new JsCode($item['ONCLICK']) : '';
	$type = $item['TYPE'] ?? '';
	$buttonId = "{$toolbarId}_button_{$i}";

	// disabled button configuration
	$disabledButtonDataset = [];
	$disabledButtonClass = '';
	$isDisabled = isset($item['IS_DISABLED']) && $item['IS_DISABLED'] === true;
	if($isDisabled)
	{
		$link = null;
		$onClick = null;
		$disabledButtonDataset = [
			'hint' => htmlspecialcharsbx($item['HINT']),
			'hint-no-icon' => '',
		];
		$disabledButtonClass = 'ui-btn-disabled-ex'; // to correct display hint
	}

	if($type === 'crm-context-menu')
	{
		$menuItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : [];
		$menuButton = new Button([
			'id' => htmlspecialcharsbx($buttonId),
			'link' => $link,
			'text' => $text,
			'color' => Color::SUCCESS,
			'click' => new JsCode('
				var popup = this.menuWindow.popupWindow;
				if (popup) {popup.setOffset({offsetLeft: BX.pos(popup.bindElement).width - 17});}
			'),
			'menu' => [
				'id' => htmlspecialcharsbx($buttonId).'_menu',
				'items' => Bitrix\Crm\UI\Tools\ToolBar::mapItems($menuItems),
				'closeByEsc' => true,
				'angle' => true,
			]
		]);

		Toolbar::addButton($menuButton, ButtonLocation::AFTER_TITLE);
	}
	elseif($type === 'crm-btn-double')
	{
		$bindElementID = "{$buttonId}_anchor";
		$splitItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : [];
		$splitButton = new SplitButton([
			'id' => htmlspecialcharsbx($buttonId),
			'icon' => '',
			'title' => $title,
			'text' => $text,
			'color' => Color::SUCCESS,
			'menuButton' => [
				'click' => new JsCode('
					var popup = this.getSplitButton().menuWindow.popupWindow;
					if (popup) { popup.setOffset({offsetLeft: BX.pos(popup.bindElement).width - 20});}
				')
			],
			'menu' => [
				'id' => htmlspecialcharsbx($bindElementID),
				'items' => Bitrix\Crm\UI\Tools\ToolBar::mapItems($splitItems),
				'closeByEsc' => true,
				'angle' => true,
			],
			'mainButton' => [
				'link' => $link,
				'click' => $onClick,
			],
			'dataset' => $disabledButtonDataset,
			'className' => $disabledButtonClass,
		]);

		Toolbar::addButton($splitButton, ButtonLocation::AFTER_TITLE);
	}
	elseif(!isset($item['SEPARATOR']))
	{
		$addButton = new AddButton([
			'id' => htmlspecialcharsbx($buttonId),
			'icon' => '',
			'text' => $text,
			'link' => $link,
			'onclick' => $onClick,
			'dataset' => $disabledButtonDataset,
			'className' => $disabledButtonClass
		]);

		Toolbar::addButton($addButton, ButtonLocation::AFTER_TITLE);
	}
}
?>

<script type="text/javascript">
	BX.ready(function() {
		if(BX.getClass('BX.Crm.ToolbarComponent'))
		{
			var toolbar = BX.Crm.ToolbarComponent.Instance;
		}
	})
</script>
