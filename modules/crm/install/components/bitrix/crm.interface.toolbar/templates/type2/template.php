<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
\Bitrix\Main\UI\Extension::load("ui.buttons");
$toolbarID =  $arParams['TOOLBAR_ID'];
$prefix =  $toolbarID.'_';
?><div class="bx-crm-view-menu" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

	$moreItems = array();
	$enableMoreButton = false;
	$labelText = '';
	$documentButton = null;
	foreach($arParams['BUTTONS'] as $k => $item):
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
		$code = isset($item['CODE']) ? $item['CODE'] : '';
		$visible = isset($item['VISIBLE']) ? (bool)$item['VISIBLE'] : true;
		$target = isset($item['TARGET']) ? $item['TARGET'] : '';

		$iconBtnClassName = '';
		if (isset($item['ICON']))
		{
			$iconBtnClassName = 'crm-'.$item['ICON'];
		}

		$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
		if ($type == 'toolbar-split-left')
		{
			$item_tmp = reset($item['LINKS']);
			?><span class="crm-toolbar-btn-split crm-toolbar-btn-left <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>>
			<span class="crm-toolbar-btn-split-l"
				  title="<?=(isset($item_tmp['TITLE']) ? htmlspecialcharsbx($item_tmp['TITLE']) : ''); ?>"
				<? if (isset($item_tmp['ONCLICK'])) { ?> onclick="<?=htmlspecialcharsbx($item_tmp['ONCLICK']); ?>; return false;"<? } ?>>
				<span class="crm-toolbar-btn-split-bg"><span class="crm-toolbar-btn-icon"></span><?
					echo (isset($item_tmp['TEXT']) ? htmlspecialcharsbx($item_tmp['TEXT']) : '');
					?></span>
			</span><span class="crm-toolbar-btn-split-r" onclick="btnMenu_<?=$k; ?>.ShowMenu(this);">
			<span class="crm-toolbar-btn-split-bg"></span></span>
		</span>
			<script>
				var btnMenu_<?=$k; ?> = new PopupMenu('bxBtnMenu_<?=$k; ?>', 1010);
				btnMenu_<?=$k; ?>.SetItems([
					<? foreach ($item['LINKS'] as $v) { ?>
					{
						'DEFAULT': <?=(isset($v['DEFAULT']) && $v['DEFAULT'] ? 'true' : 'false'); ?>,
						'DISABLED': <?=(isset($v['DISABLED']) && $v['DISABLED'] ? 'true' : 'false'); ?>,
						'ICONCLASS': "<?=(isset($v['ICONCLASS']) ? htmlspecialcharsbx($v['ICONCLASS']) : ''); ?>",
						'ONCLICK': "<?=(isset($v['ONCLICK']) ? $v['ONCLICK'] : ''); ?>; return false;",
						'TEXT': "<?=(isset($v['TEXT']) ? htmlspecialcharsbx($v['TEXT']) : ''); ?>",
						'TITLE': "<?=(isset($v['TITLE']) ? htmlspecialcharsbx($v['TITLE']) : ''); ?>"
					},
					<? } ?>
				]);
			</script><?
		}
		else if ($type == 'toolbar-left')
		{
			?><a class="crm-toolbar-btn crm-toolbar-btn-left <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
				 href="<?=htmlspecialcharsbx($link)?>"
			<? if($target !== '') { ?> target="<?=$target?>"<? } ?>
				 title="<?=htmlspecialcharsbx($title)?>"
			<? if ($onclick !== '') { ?> onclick="<?=htmlspecialcharsbx($onclick); ?>; return false;"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>>
			<span class="crm-toolbar-btn-icon"></span><span><?=htmlspecialcharsbx($text); ?></span></a><?
		}
		else if ($type === 'toolbar-menu' || $type == 'toolbar-menu-left')
		{
		if ($code !== '')
		{
		$menuId = $prefix.$code;
		$lastClass = '';
		if ($type === 'toolbar-menu')
		{
			$lastClass = 'crm-toolbar-menu';
		}
		else if ($type == 'toolbar-menu-left')
		{
			$lastClass = 'crm-toolbar-menu-left';
		}
		$classAttribute = ' class="ui-btn ui-btn-md ui-btn-light-border ui-btn-dropdown '.
			$lastClass.'"';
		$idAttribute = ' id="'.htmlspecialcharsbx($menuId).'"';
		$titleAttribute = '';
		if (is_string($title) && $title <> '')
		{
			$titleAttribute = ' title="'.htmlspecialcharsbx($title).'"';
		}
		?>
			<button<?=$classAttribute?><?=$idAttribute?><?=$titleAttribute?>><?=$item['TEXT'];?></button>
			<script>
				BX.ready(function()
				{
					BX.bind(BX('<?=CUtil::JSEscape($menuId);?>'), 'click', function()
					{
						BX.PopupMenu.show(
							'<?=CUtil::JSEscape($menuId);?>_menu',
							BX('<?=CUtil::JSEscape($menuId);?>'),
							<?=CUtil::PhpToJSObject($item['ITEMS']);?>,
							{
								offsetLeft: 0,
								offsetTop: 0,
								closeByEsc: true,
								className: '<?=$lastClass?>'
							}
						);
					});
				});
			</script>
		<?
		unset($menuId, $lastClass, $classAttribute, $idAttribute, $titleAttribute);
		}
		}
		else if ($type == 'crm-document-button')
		{
		if ($code !== '')
		{
		$documentButtonId = $prefix.$code;
		$classAttribute = ' class="ui-btn ui-btn-md ui-btn-light-border ui-btn-dropdown '.
			'crm-btn-dropdown-document"';
		$idAttribute = ' id="'.htmlspecialcharsbx($documentButtonId).'"';
		$titleAttribute = '';
		if (is_string($title) && $title <> '')
		{
			$titleAttribute = ' title="'.htmlspecialcharsbx($title).'"';
		}
		?>
			<button<?=$classAttribute?><?=$idAttribute?><?=$titleAttribute?>><?=$item['TEXT'];?></button>
			<script>
				BX.ready(function()
				{
					if(BX.DocumentGenerator && BX.DocumentGenerator.Button)
					{
						var button = new BX.DocumentGenerator.Button('<?=htmlspecialcharsbx($documentButtonId);?>', <?=CUtil::PhpToJSObject($item['PARAMS']);?>);
						button.init();
					}
					else
					{
						console.warn('BX.DocumentGenerator.Button is not found')
					}
				});
			</script>
		<?
		unset($documentButtonId, $classAttribute, $idAttribute, $titleAttribute);
		}
		}
		else if ($type == 'toolbar-conv-scheme')
		{
		$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();

		$containerID = $params['CONTAINER_ID'] ?? null;
		$labelID = $params['LABEL_ID'] ?? null;
		$buttonID = $params['BUTTON_ID'] ?? null;
		$typeID = isset($params['TYPE_ID']) ? (int)$params['TYPE_ID'] : 0;
		$schemeName = isset($params['SCHEME_NAME']) ? $params['SCHEME_NAME'] : null;
		$schemeDescr = isset($params['SCHEME_DESCRIPTION']) ? $params['SCHEME_DESCRIPTION'] : null;
		$name = isset($params['NAME']) ? $params['NAME'] : $code;
		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$isPermitted = isset($params['IS_PERMITTED']) ? (bool)$params['IS_PERMITTED'] : false;
		$lockScript = isset($params['LOCK_SCRIPT']) ? $params['LOCK_SCRIPT'] : '';

		$hintKey = 'enable_'.mb_strtolower($name).'_hint';
		$hint = isset($params['HINT']) ? $params['HINT'] : array();

		$enableHint = !empty($hint);
		if($enableHint)
		{
			$options = CUserOptions::GetOption("crm.interface.toobar", "conv_scheme_selector", array());
			$enableHint = !(isset($options[$hintKey]) && $options[$hintKey] === 'N');
		}

		$iconBtnClassName = $isPermitted ? 'crm-btn-convert' : 'crm-btn-convert crm-btn-convert-blocked';
		$originUrl = $APPLICATION->GetCurPage();

		$containerID = empty($containerID) ? "{$prefix}{$code}" : $containerID;
		$labelID = empty($labelID) ? "{$prefix}{$code}_label" : $labelID;
		$buttonID = empty($buttonID) ? "{$prefix}{$code}_button" : $buttonID;

		if($isPermitted && $entityTypeID === CCrmOwnerType::Lead)
		{
			Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/crm.js');
		}
		?>
			<span class="crm-btn-convert-wrap">
			<a class="bx-context-button <?=$iconBtnClassName; ?>"
			   id="<?=htmlspecialcharsbx($containerID); ?>"
			   href="<?=htmlspecialcharsbx($link)?>"
				<? if($target !== '') { ?> target="<?=$target?>"<? } ?>
				title="<?=htmlspecialcharsbx($title)?>"
			   onclick="return false;"
				<? if (!$visible) { ?> style="display: none;"<? } ?>>
				<span class="bx-context-button-icon"></span>
				<span>
					<?=htmlspecialcharsbx($text);?>
					<span class="crm-btn-convert-text" id="<?=htmlspecialcharsbx($labelID);?>">
						<?=htmlspecialcharsbx($schemeDescr)?>
					</span>
				</span>
			</a>
			<span class="crm-btn-convert-arrow" id="<?=htmlspecialcharsbx($buttonID);?>"></span><?
			?><script type="text/javascript">
				BX.ready(
					function()
					{
						//region Toolbar script
						<?$selectorID = CUtil::JSEscape($name);?>
						<?$originUrl = CUtil::JSEscape($originUrl);?>
						<?if($isPermitted):?>
						<?if($entityTypeID === CCrmOwnerType::Lead):?>
						BX.CrmLeadConversionSchemeSelector.create(
							"<?=$selectorID?>",
							{
								typeId: <?=$typeID?>,
								entityId: <?=$entityID?>,
								scheme: "<?=$schemeName?>",
								containerId: "<?=$containerID?>",
								labelId: "<?=$labelID?>",
								buttonId: "<?=$buttonID?>",
								originUrl: "<?=$originUrl?>",
								enableHint: <?=CUtil::PhpToJSObject($enableHint)?>,
								hintMessages: <?=CUtil::PhpToJSObject($hint)?>
							}
						);
						<?elseif($entityTypeID === CCrmOwnerType::Deal):?>
						<?php //this card is used in old card (not detail), so we do not change here anything ?>
						BX.CrmDealConversionSchemeSelector.create(
							"<?=$selectorID?>",
							{
								entityId: <?=$entityID?>,
								scheme: "<?=$schemeName?>",
								containerId: "<?=$containerID?>",
								labelId: "<?=$labelID?>",
								buttonId: "<?=$buttonID?>",
								originUrl: "<?=$originUrl?>",
								enableHint: <?=CUtil::PhpToJSObject($enableHint)?>,
								hintMessages: <?=CUtil::PhpToJSObject($hint)?>
							}
						);

						BX.addCustomEvent(window,
							"CrmCreateQuoteFromDeal",
							function()
							{
								BX.CrmDealConverter.getCurrent().convert(
									<?=$entityID?>,
									BX.CrmDealConversionScheme.createConfig(BX.CrmDealConversionScheme.quote),
									"<?=$originUrl?>"
								);
							}
						);

						BX.addCustomEvent(window,
							"CrmCreateInvoiceFromDeal",
							function()
							{
								BX.CrmDealConverter.getCurrent().convert(
									<?=$entityID?>,
									BX.CrmDealConversionScheme.createConfig(BX.CrmDealConversionScheme.invoice),
									"<?=$originUrl?>"
								);
							}
						);
						<?elseif($entityTypeID === CCrmOwnerType::Quote):?>
						BX.CrmQuoteConversionSchemeSelector.create(
							"<?=$selectorID?>",
							{
								entityId: <?=$entityID?>,
								scheme: "<?=$schemeName?>",
								containerId: "<?=$containerID?>",
								labelId: "<?=$labelID?>",
								buttonId: "<?=$buttonID?>",
								originUrl: "<?=$originUrl?>",
								enableHint: <?=CUtil::PhpToJSObject($enableHint)?>,
								hintMessages: <?=CUtil::PhpToJSObject($hint)?>
							}
						);

						BX.addCustomEvent(window,
							"CrmCreateDealFromQuote",
							function()
							{
								BX.CrmQuoteConverter.getCurrent().convert(
									<?=$entityID?>,
									BX.CrmQuoteConversionScheme.createConfig(BX.CrmQuoteConversionScheme.deal),
									"<?=$originUrl?>"
								);
							}
						);

						BX.addCustomEvent(window,
							"CrmCreateInvoiceFromQuote",
							function()
							{
								BX.CrmQuoteConverter.getCurrent().convert(
									<?=$entityID?>,
									BX.CrmQuoteConversionScheme.createConfig(BX.CrmQuoteConversionScheme.invoice),
									"<?=$originUrl?>"
								);
							}
						);
						<?endif;?>
						<?elseif($lockScript !== ''):?>
						var showLockInfo = function()
						{
							<?=$lockScript?>
						};
						BX.bind(BX("<?=$containerID?>"), "click", showLockInfo );
						<?if($entityTypeID === CCrmOwnerType::Deal):?>
						BX.addCustomEvent(window, "CrmCreateQuoteFromDeal", showLockInfo);
						BX.addCustomEvent(window, "CrmCreateInvoiceFromDeal", showLockInfo);
						<?elseif($entityTypeID === CCrmOwnerType::Quote):?>
						BX.addCustomEvent(window, "CrmCreateDealFromQuote", showLockInfo);
						BX.addCustomEvent(window, "CrmCreateInvoiceFromQuote", showLockInfo);
						<?endif;?>
						<?endif;?>
						//endregion
					}
				);
			</script><?
			?></span><?
		}
		elseif ($type == 'toolbar-activity-planner')
		{
		$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();
		if (isset($params['MENU']) && is_array($params['MENU'])):
		$plannerActionNodeId = $prefix.'_act_pl_act';
		$plannerActionOpenerId = $prefix.'_act_pl_opn';

		?>
			<span class="crm-btn-convert-wrap crm-toolbar-btn-planner-wrap">
			<a class="bx-context-button crm-toolbar-button" title="<?=htmlspecialcharsbx($title)?>">
				<span class="crm-toolbar-btn-planner-icon"></span>
				<span class="crm-toolbar-btn-planner-item"><?=htmlspecialcharsbx($text)?>:</span>
				<span class="crm-toolbar-btn-planner-link" id="<?=htmlspecialcharsbx($plannerActionNodeId)?>" data-action-id="<?=htmlspecialcharsbx($params['DEFAULT_ACTION_ID'])?>"><?=htmlspecialcharsbx($params['DEFAULT_ACTION_TEXT'])?></span>
			</a><!--crm-toolbar-btn-planner-wrap-->
			<span class="crm-btn-convert-arrow crm-toolbar-arrow" id="<?=htmlspecialcharsbx($plannerActionOpenerId)?>"></span>
		</span>
			<script>
				BX.ready(
					function()
					{
						BX.Crm.Activity.PlannerToolbar.setActions(<?=Bitrix\Main\Web\Json::encode($params['MENU'])?>);
						BX.Crm.Activity.PlannerToolbar.bindNodes({
							action: BX('<?=htmlspecialcharsbx($plannerActionNodeId)?>'),
							opener: BX('<?=htmlspecialcharsbx($plannerActionOpenerId)?>')
						});
					}
				);
			</script>
		<?
		endif;
		}
		else
		{
			?><a class="ui-btn ui-btn-primary <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
				 href="<?=htmlspecialcharsbx($link)?>"
			<? if($target !== '') { ?> target="<?=$target?>"<? } ?>
				 title="<?=htmlspecialcharsbx($title)?>"
			<? if ($onclick !== '') { ?> onclick="<?=htmlspecialcharsbx($onclick); ?>; return false;"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>><?=htmlspecialcharsbx($text); ?></a><?
		}

	endforeach;
	if(!empty($moreItems)):
		?><a class="bx-context-button crm-btn-more" href="#">
		<span class="bx-context-button-icon"></span>
		<span><?=htmlspecialcharsbx(GetMessage('CRM_INTERFACE_TOOLBAR_BTN_MORE'))?></span>
	</a>
		<script type="text/javascript">
			BX.ready(
				function()
				{
					BX.InterfaceToolBar.create(
						"<?=CUtil::JSEscape($toolbarID)?>",
						BX.CrmParamBag.create(
							{
								"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
								"prefix": "<?=CUtil::JSEscape($prefix)?>",
								"moreButtonClassName": "crm-btn-more",
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
		?><div class="crm-toolbar-label2"><span id="<?= $toolbarID.'_label' ?>"><?=htmlspecialcharsbx($labelText)?></span></div><?
	endif;
	?></div>
