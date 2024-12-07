<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();

\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.design-tokens', 'ui.fonts.opensans', 'ui.hint']);
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');

$toolbarID =  $arParams['TOOLBAR_ID'];

?><div class="crm-list-top-bar" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

$moreItems = array();
$enableMoreButton = false;
$enableHint = false;
$labelText = '';
$requisitePresetSelectorIndex = 0;
foreach($arParams["BUTTONS"] as $item):
	if (isset($item['LABEL']) && $item['LABEL'] === true)
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
	$alignment = isset($item['ALIGNMENT'])? mb_strtolower($item['ALIGNMENT']) : '';

	$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();

	if ($type === 'crm-requisite-preset-selector')
	{
		if ($requisitePresetSelectorIndex === 0)
			\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');

		$containerId = $toolbarID.'_crm-requisite-toolbar-editor_'.$requisitePresetSelectorIndex;
		?>
		<div id="<?= $containerId ?>" class="crm-offer-requisite-block-wrap" style="display: none;"></div>
		<script>
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
		$iconClassName = 'ui-btn ui-btn-xs ui-btn-round ui-btn-dropdown';
		$iconClassName .= isset($item['HIGHLIGHT']) && $item['HIGHLIGHT'] ? ' ui-btn-primary' : ' ui-btn-light-border';
		if (isset($item['ICON']))
		{
			$iconClassName .= $item['ICON'] === 'btn-new' ? ' ui-btn-icon-add' : ' ' . $item['ICON'];
		}

		if($alignment !== '')
		{
			?><span class="crm-toolbar-alignment-<?=htmlspecialcharsbx($alignment)?>"><?
		}
		$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
		?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?>><span class="ui-btn-text"><?=htmlspecialcharsbx($text)?></span></a><?
		if($alignment !== '')
		{
			?></span><?
		}

		if(isset($params['SCRIPTS']) && is_array($params['SCRIPTS']))
		{
			?><script>
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
		$iconClassName = 'ui-btn ui-btn-xs ui-btn-round';
		if ($type === 'disabled')
		{
			$iconClassName .= ' ui-btn ui-btn-icon-lock ui-btn-disabled';
		}
		else
		{
			$iconClassName .= isset($item['HIGHLIGHT']) && $item['HIGHLIGHT'] ? ' ui-btn-primary' : ' ui-btn-light-border';
			if(isset($item['ICON']))
			{
				$iconClassName .= $item['ICON'] === 'btn-new' ? ' ui-btn-icon-add' : ' ' . $item['ICON'];
			}
		}

		if($alignment !== '')
		{
			?><span class="crm-toolbar-alignment-<?=htmlspecialcharsbx($alignment)?>"><?
		}
		$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
		$dataAttrs = '';
		if (!empty($item['ATTRIBUTES']) && is_array($item['ATTRIBUTES']))
		{
			foreach ($item['ATTRIBUTES'] as $key => $value)
			{
				$dataAttrs .= ' '.$key.'="'.htmlspecialcharsbx($value).'"';
			}
		}

		if (!empty($item['HINT']))
		{
			$enableHint = true;
			$hint = htmlspecialcharsbx($item['HINT']);
			$dataAttrs .= "data-hint='{$hint}' data-hint-no-icon";
		}

		?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?><?=$dataAttrs;?>><span class="ui-btn-text"><?=htmlspecialcharsbx($text)?></span></a><?
		if($alignment !== '')
		{
			?></span><?
		}
	}
endforeach;
if ($enableHint)
{
	?>
	<script>
		BX.ready(
			function(){
				BX.UI.Hint.init(BX("<?=CUtil::JSEscape($toolbarID)?>"));
			}
		)
	</script>
	<?php
}
if(!empty($moreItems)):
	?><span class="crm-toolbar-alignment-right">
		<span class="crm-setting-btn"></span>
	</span>
	<script>
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

