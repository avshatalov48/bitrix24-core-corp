<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/** @var array $arParams */

CJSCore::RegisterExt('popup_menu', ['js' => ['/bitrix/js/main/popup_menu.js']]);
Main\UI\Extension::load([
	'ui.buttons',
	'ui.buttons.icons'
]);

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

//region toolbar buttons
$this->SetViewTarget('inside_pagetitle', 0);
?><div id="<?=htmlspecialcharsbx($toolbarId)?>" class="pagetitle-container pagetitle-align-left-container"><?
$itemCount = count($items);
for($i = 0; $i < $itemCount; $i++)
{
	$item = $items[$i];
	$type = $item['TYPE'] ?? '';
	$text = isset($item['TEXT']) ? htmlspecialcharsbx(strip_tags($item['TEXT'])) : '';
	$title = isset($item['TITLE']) ? htmlspecialcharsbx(strip_tags($item['TITLE'])) : '';
	$link = isset($item['LINK']) ? htmlspecialcharsbx($item['LINK']) : '#';
	$icon = isset($item['ICON']) ? htmlspecialcharsbx($item['ICON']) : '';
	$onClick = isset($item['ONCLICK']) ? htmlspecialcharsbx($item['ONCLICK']) : '';

	if($type === 'crm-context-menu')
	{
		$buttonID = "{$toolbarId}_button_{$i}";

		$menuItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : array();
		?>
		<button id="<?=htmlspecialcharsbx($buttonID)?>" class="ui-btn ui-btn-success ui-btn-dropdown" style="margin-right: 12px;" <?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>>
			<?=$text?>
		</button>
		<?
		if(!empty($menuItems))
		{
			?><script type="text/javascript">
				BX.ready(
					function()
					{
						BX.InterfaceToolBar.create(
							"<?=CUtil::JSEscape($toolbarId)?>",
							BX.CrmParamBag.create(
								{
									"buttonId": "<?=CUtil::JSEscape($buttonID)?>",
									"items": <?=CUtil::PhpToJSObject($menuItems)?>
								}
							)
						);
					}
				);
			</script><?
		}
	}
	elseif($type === 'crm-btn-double')
	{
		$buttonID = "{$toolbarId}_button_{$i}";
		$bindElementID = "{$buttonID}_anchor";
		$menuItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : array();
		?>
		<script type="text/javascript">
			BX.ready(
				function()
				{
					BX.InterfaceToolBar.create(
						"<?=CUtil::JSEscape($toolbarId)?>",
						BX.CrmParamBag.create(
							{
								"buttonId": "<?=CUtil::JSEscape($buttonID)?>",
								"bindElementId": "<?=CUtil::JSEscape($bindElementID)?>",
								"items": <?=CUtil::PhpToJSObject($menuItems)?>,
								"autoClose": true
							}
						)
					);
				}
			);
		</script>
        <div id="<?=$bindElementID?>" class="ui-btn-split ui-btn-success" style="margin-right: 12px;">
            <a href="<?=$link?>" class="ui-btn-main" title="<?=$title?>"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>><?=$text?></a>
            <button id="<?=$buttonID?>" class="ui-btn-menu"></button>
        </div>
		<?
	}
	elseif(!isset($item['SEPARATOR']))
	{
		?>
		<a href="<?=$link?>" class="ui-btn ui-btn-success ui-btn-icon-add crm-btn-toolbar-add" title="<?=$title?>"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>><?=$text?></a>
		<?
	}
}
?></div><?
$this->EndViewTarget();

if(!empty($settingsItems))
{
	$buttonID = "{$toolbarId}_button";
	//region Settings
	$this->SetViewTarget('inside_pagetitle', 10000);

	?><div id="<?=htmlspecialcharsbx($toolbarId)?>" class="pagetitle-container pagetitle-align-right-container">
	<script type="text/javascript">
		BX.ready(
			function ()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarId)?>",
					BX.CrmParamBag.create(
						{
							"buttonId": "<?=CUtil::JSEscape($buttonID)?>",
							"items": <?=CUtil::PhpToJSObject($settingsItems)?>
						}
					)
				);
			}
		);
	</script>
	<button id="<?=htmlspecialcharsbx($buttonID)?>" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"></button>
	</div><?

	$this->EndViewTarget();
}
