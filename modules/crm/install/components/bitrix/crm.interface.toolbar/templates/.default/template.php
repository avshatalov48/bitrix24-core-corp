<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');

$toolbarID =  $arParams['TOOLBAR_ID'];

?><div class="crm-list-top-bar" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

$moreItems = array();
$enableMoreButton = false;
$labelText = '';
$requisitePresetSelectorIndex = 0;
foreach($arParams["BUTTONS"] as $item):
	if ($item['LABEL'] === true)
	{
		$labelText = isset($item['TEXT']) ? $item['TEXT'] : '';
		continue;
	}
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true):
		$enableMoreButton = true;
		continue;
	endif;

	if($enableMoreButton):
		$moreItems[] = $item;
		continue;
	endif;

	$link = isset($item['LINK']) ? $item['LINK'] : '#';
	$text = isset($item['TEXT']) ? $item['TEXT'] : '';
	$title = isset($item['TITLE']) ? $item['TITLE'] : '';
	$type = isset($item['TYPE']) ? $item['TYPE'] : 'context';
	$alignment = isset($item['ALIGNMENT']) ? strtolower($item['ALIGNMENT']) : '';

	if ($type === 'crm-requisite-preset-selector')
	{
		if ($requisitePresetSelectorIndex === 0)
			\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');

		$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();

		$containerId = $toolbarID.'_crm-requisite-toolbar-editor_'.$requisitePresetSelectorIndex;
		?>
		<div id="<?= $containerId ?>" class="crm-offer-requisite-block-wrap" style="display: none;"></div>
		<script type="text/javascript">
			BX.namespace("BX.Crm");
			BX.Crm["RequisiteToolbarEditor_<?= $toolbarID ?>_<?= $requisitePresetSelectorIndex ?>"] = new BX.Crm.RequisiteToolbarEditorClass({
				containerId: "<?= CUtil::JSEscape($containerId) ?>",
				gridId: "<?= CUtil::JSEscape($params['GRID_ID']) ?>",
				requisiteEntityTypeId: <?= CUtil::PhpToJSObject($params['REQUISITE_ENTITY_TYPE_ID']) ?>,
				requisiteEntityId: <?= CUtil::PhpToJSObject($params['REQUISITE_ENTITY_ID']) ?>,
				presetList: <?= CUtil::PhpToJSObject($params['PRESET_LIST']) ?>,
				presetLastSelectedId: <?= CUtil::PhpToJSObject($params['PRESET_LAST_SELECTED_ID']) ?>,
				requisiteDataList: <?= CUtil::PhpToJSObject($params['REQUISITE_DATA_LIST']) ?>,
				requisitePopupAjaxUrl: "/bitrix/components/bitrix/crm.requisite.edit/popup.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				requisiteAjaxUrl: "/bitrix/components/bitrix/crm.requisite.edit/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				requisiteFormEditorAjaxUrl: "/bitrix/components/bitrix/crm.requisite.form.editor/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
				visible: true,
				messages: {
					"CRM_JS_STATUS_ACTION_SUCCESS": "<?=CUtil::JSEscape($params['MESSAGES']['CRM_JS_STATUS_ACTION_SUCCESS'])?>",
					"CRM_JS_STATUS_ACTION_ERROR": "<?=CUtil::JSEscape($params['MESSAGES']['CRM_JS_STATUS_ACTION_ERROR'])?>",
					"presetSelectorTitle": "<?=CUtil::JSEscape($params['MESSAGES']['CRM_REQUISITE_PRESET_SELECTOR_TITLE'])?>",
					"presetSelectorText": "<?=CUtil::JSEscape($params['MESSAGES']['CRM_REQUISITE_PRESET_SELECTOR_TEXT'])?>",
					"popupTitle": "<?=CUtil::JSEscape($params['MESSAGES']['POPUP_TITLE'])?>",
					"popupSaveBtnTitle": "<?=CUtil::JSEscape($params['MESSAGES']['POPUP_SAVE_BUTTON_TITLE'])?>",
					"popupCancelBtnTitle": "<?=CUtil::JSEscape($params['MESSAGES']['POPUP_CANCEL_BUTTON_TITLE'])?>",
					"errPresetNotSelected": "<?=CUtil::JSEscape($params['MESSAGES']['ERR_PRESET_NOT_SELECTED'])?>"
				}
			});
		</script>
		<?
		$requisitePresetSelectorIndex++;
	}
	elseif($type === 'crm-context-menu')
	{
		$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();
		$iconClassName = 'crm-menu-bar-btn';
		if(isset($item['HIGHLIGHT']) && $item['HIGHLIGHT'])
		{
			if($iconClassName !== '')
			{
				$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
			}
			else
			{
				$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
			}
		}

		if(isset($item['ICON']))
		{
			$iconClassName .= ' '.$item['ICON'];
		}

		if($alignment !== '')
		{
			?><span class="crm-toolbar-alignment-<?=htmlspecialcharsbx($alignment)?>"><?
		}
		$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
		?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?>><span><?=htmlspecialcharsbx($text)?></span><span class="crm-btn-menu-arrow"></span></a><?
		if($alignment !== '')
		{
			?></span><?
		}

		if(isset($params['SCRIPTS']) && is_array($params['SCRIPTS']))
		{
			?><script type="text/javascript">
				BX.ready(
					function()
					{
						<?foreach($params['SCRIPTS'] as $script)
						{
							echo $script, ';';
						}?>
					}
				);
			</script><?
		}
	}
	else
	{
		$iconClassName = 'crm-menu-bar-btn';
		if(isset($item['HIGHLIGHT']) && $item['HIGHLIGHT'])
		{
			if($iconClassName !== '')
			{
				$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
			}
			else
			{
				$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
			}
		}

		if(isset($item['ICON']))
		{
			$iconClassName .= ' '.$item['ICON'];
		}

		if($alignment !== '')
		{
			?><span class="crm-toolbar-alignment-<?=htmlspecialcharsbx($alignment)?>"><?
		}
		$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
		?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?>><span class="crm-toolbar-btn-icon"></span><span><?=htmlspecialcharsbx($text)?></span></a><?
		if($alignment !== '')
		{
			?></span><?
		}
	}
endforeach;

if(!empty($moreItems)):
	?><span class="crm-toolbar-alignment-right">
		<span class="crm-setting-btn"></span>
	</span>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarID)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
							"items": <?=CUtil::PhpToJSObject($moreItems)?>
						}
					)
				);
			}
		);
	</script>
<?
endif;
if ($labelText != ''):
?><div class="crm-toolbar-label1"><span id="<?= $toolbarID.'_label' ?>"><?=$labelText?></span></div><?
endif;
?></div>

