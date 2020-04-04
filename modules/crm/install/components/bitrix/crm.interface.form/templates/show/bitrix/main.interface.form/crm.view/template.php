<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');

?>
<div class="bx-interface-form">

<script type="text/javascript">
	var bxForm_<?=$arParams["FORM_ID"]?> = null;
</script>

<?if($arParams["SHOW_FORM_TAG"]):?>
<form name="form_<?=$arParams["FORM_ID"]?>" id="form_<?=$arParams["FORM_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
<?=bitrix_sessid_post();?>
<?endif?>
<div class="bx-crm-interface-form">
<input type="hidden" id="<?=$arParams["FORM_ID"]?>_active_tab" name="<?=$arParams["FORM_ID"]?>_active_tab" value="<?=htmlspecialcharsbx($arResult["SELECTED_TAB"])?>">
<?
$tabsExt = isset($arParams['~TABS_EXT']) ? $arParams['~TABS_EXT'] : array();

$hiddenTabs = array();
foreach($arParams['~TABS'] as &$tab)
{
	$tabID = isset($tab['id']) ? $tab['id'] : '';
	if($tabID === '')
	{
		continue;
	}

	$display = $tab && isset($tab['display']) ? $tab['display'] : true;
	if(!$display && !in_array($tabID, $hiddenTabs, true))
	{
		$hiddenTabs[] = $tabID;
	}
}
unset($tab);

$selectedTabID = $arResult["SELECTED_TAB"];
$nTabs = count($arResult["TABS"]);
$shiftSelectedTab = in_array($selectedTabID, $hiddenTabs, true);

if($shiftSelectedTab):
	// Try to shift selection
	foreach($arResult["TABS"] as $tabID => $tab):
		if(!in_array($tabID, $hiddenTabs, true)):
			$arResult["SELECTED_TAB"] = $tabID;
			break;
		endif;
	endforeach;
endif;
?>
<!-- View form tabs  -->
<div id="<?=$arParams["FORM_ID"]?>_tab_block" class="bx-crm-view-tab-block"><?
	foreach($arResult["TABS"] as $tab):
		$tabID = $tab['id'];

		if(in_array($tabID, $hiddenTabs, true))
			continue;

		$tabName = isset($tab['name']) ? $tab['name'] : '';
		if($tabName === '')
			$tabName = $tabID;

		$tabSuffix = isset($tabsExt[$tabID]) && $tabsExt[$tabID]['SUFFIX'] ? $tabsExt[$tabID]['SUFFIX'] : '';
		if($tabSuffix !== '')
			$tabName .= $tabSuffix;

		$tabTitle = isset($tab['title']) ? $tab['title'] : '';
		$onClick = isset($tab['onClick']) ? $tab['onClick'] : '';

		$bSelected = ($tabID === $arResult["SELECTED_TAB"]);?>
		<a id="<?=htmlspecialcharsbx($arParams["FORM_ID"]."_tab_". $tabID)?>" class="bx-crm-view-tab<?=$bSelected ? ' bx-crm-view-tab-active' : ''?>" href="#" onclick="bxForm_<?=$arParams["FORM_ID"]?>.SelectTab('<?=$tabID?>'); <?=$onClick !== '' ? htmlspecialcharsbx($onClick) : ''?> return false;" title="<?=htmlspecialcharsbx($tabTitle)?>">
			<span class="bx-crm-view-tab-left"></span><span class="bx-crm-view-tab-text"><?=htmlspecialcharsbx($tabName)?></span><span class="bx-crm-view-tab-right"></span>
		</a>
		<?endforeach;?>
	<a href="javascript:void(0)" onclick="bxForm_<?=$arParams["FORM_ID"]?>.menu.ShowMenu(this, bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu);" title="<?=htmlspecialcharsbx(GetMessage("interface_form_settings"))?>" class="bx-context-button bx-form-menu"><span></span></a>
</div>
<?$bWasRequired = false;
$sidebarSections = array();
$detailFields = array();
foreach($arResult["TABS"] as &$tab):
	$tabID = $tab['id'];

	if(in_array($tabID, $hiddenTabs, true))
		continue;

?><div id="inner_tab_<?=$tab["id"]?>" class="bx-edit-tab-inner"<?if($tab["id"] <> $arResult["SELECTED_TAB"]) echo ' style="display:none;"'?>>
<div style="height: 100%;">
	<?// Creating of section structure
	$arSections = array();
	$sectionIndex = -1;
	foreach($tab['fields'] as &$field):
		if(!is_array($field))
			continue;

		if($field['type'] === 'section'):
			$arSections[] = array(
				'SECTION_FIELD' => $field,
				'SECTION_ID' => $field['id'],
				'SECTION_NAME' => $field['name'],
				'SECTION_MENU' => isset($field['menu']) ? $field['menu'] : array(),
				'FIELDS_DATA' => array(),
				'EMPTY_FIELD_COUNT' => 0
			);
			$sectionIndex++;
			continue;
		endif;

		if($sectionIndex < 0):
			$arSections[] = array(
				'SECTION_FIELD' => null,
				'SECTION_ID' => '',
				'SECTION_NAME' => '',
				'SECTION_MENU' => array(),
				'FIELDS_DATA' => array(),
				'EMPTY_FIELD_COUNT' => 0
			);
			$sectionIndex = 0;
		endif;

		$type = isset($field['type']) ? $field['type'] : '';
		$val = isset($field['value']) ? $field['value'] : $arParams['~DATA'][$field['id']];
		$isEmptyField = empty($val) && $type !== 'crm_activity_list' && $type !== 'crm_deal_list' && $type !== 'crm_quote_list' && $type !== 'crm_invoice_list' && $type !== 'crm_company_list' && $type !== 'crm_contact_list' && $type !== 'crm_requisite_list' && $type !== 'crm_event_view' && $field['id'] !== 'COMMENTS' && $type !== 'crm_automation' && $type !== 'crm_lists_element';

		// HACK: CHECK FOR USER FIELD EMPTY WRAPPER
		if(!$isEmptyField && strpos($field['id'], 'UF_') === 0 && preg_match('/^<span[^>]*><\/span>$/i', $val) === 1)
			$isEmptyField = true;

		$arSections[$sectionIndex]['FIELDS_DATA'][] = array(
			'FIELD' => $field,
			'IS_EMPTY' => $isEmptyField
		);

		if($isEmptyField)
			$arSections[$sectionIndex]['EMPTY_FIELD_COUNT'] += 1;
	endforeach;
	unset($field);

	$sectionQty = count($arSections);
	$enableTinyView = $sectionQty === 1;

	$fieldLimit = isset($arParams['FIELD_LIMIT']) ? intval($arParams['FIELD_LIMIT']) : 0;
	foreach($arSections as &$arSection):
		$fieldTotal = count($arSection['FIELDS_DATA']);
		if($fieldTotal === 0 || $fieldTotal === $arSection['EMPTY_FIELD_COUNT'])
			continue;

		?><div class="bx-crm-view-fieldset"><?
		if(!$enableTinyView):
		?><h2 class="bx-crm-view-fieldset-title"><?
			echo htmlspecialcharsbx($arSection['SECTION_NAME']);
			$sectionMenu = isset($arSection['SECTION_MENU']) ? $arSection['SECTION_MENU'] : array();
			foreach($sectionMenu as &$sectionMenuItem)
			{
				$name = isset($sectionMenuItem['name']) ? $sectionMenuItem['name'] : '';
				if($name === '')
				{
					continue;
				}

				$selected = isset($sectionMenuItem['selected']) && ($sectionMenuItem['selected'] === true || $sectionMenuItem['selected'] === 'Y');
				$action = isset($sectionMenuItem['action']) ? $sectionMenuItem['action'] : '';
				echo '<a class="bx-crm-view-fieldset-title-button',
					$selected ? ' bx-crm-view-fieldset-title-button-latched"' : '"',
					' href="#" onclick="',
					htmlspecialcharsbx($action), ' return false;">',
					htmlspecialcharsbx($name),
					'</a>';
			}
			unset($sectionMenuItem);
		?></h2><?
		endif;
		?><div class="<?=$enableTinyView ? 'bx-crm-view-fieldset-content-tiny' : 'bx-crm-view-fieldset-content'?>">
			<table class="bx-crm-view-fieldset-content-table"><?

				$hasOnDemandFields = false;
				$fieldCount = 0;

				foreach($arSection['FIELDS_DATA'] as &$fieldData):
					if($fieldData['IS_EMPTY'])
					{
						continue;
					}

					$field = isset($fieldData['FIELD']) ? $fieldData['FIELD'] : null;
					if(!is_array($field) || $field["isHidden"])
					{
						continue;
					}

					$className = isset($field['rowClassName']) ? $field['rowClassName'] : '';
					$type = isset($field['type']) ? $field['type'] : '';
					$val = isset($field["value"]) ? $field["value"] : $arParams["~DATA"][$field["id"]];
					$valEncoded = htmlspecialcharsbx(htmlspecialcharsback($val));

					$isOnDemandField = $fieldLimit > 0 ? ($fieldCount >= $fieldLimit && $field['id'] !== 'COMMENTS') : false;

					if($isOnDemandField):
						if(!$hasOnDemandFields)
							$hasOnDemandFields = true;

						if($className !== '')
							$className .= ' ';

						$className .=  'bx-crm-view-on-demand';
					endif;
					?><tr <?=$className !== '' ? 'class="'.htmlspecialcharsbx($className).'"' : ''?> <?=$isOnDemandField ? 'style="display:none;"' : '' ?>>
					<?
					//default attributes
					if(!is_array($field["params"]))
					{
						$field["params"] = array();
					}

					if($field["type"] == '' || $field["type"] == 'text')
					{
						if($field["params"]["size"] == '')
						{
							$field["params"]["size"] = "30";
						}
					}
					elseif($field["type"] == 'textarea')
					{
						if($field["params"]["cols"] == '')
						{
							$field["params"]["cols"] = "40";
						}
						if($field["params"]["rows"] == '')
						{
							$field["params"]["rows"] = "3";
						}
					}
					elseif($field["type"] == 'date')
					{
						if($field["params"]["size"] == '')
						{
							$field["params"]["size"] = "10";
						}
					}

					$params = '';
					if(is_array($field["params"]) && $field["type"] <> 'file')
					{
						foreach($field["params"] as $p=>$v)
						{
							$params .= ' '.$p.'="'.$v.'"';
						}
					}

					if($field["colspan"] <> true):
						if($field["required"]):
							$bWasRequired = true;
						endif;?>

						<td class="bx-field-name<?if($field["type"] <> 'label') echo' bx-padding'?>"<?if($field["title"] <> '') echo ' title="'.htmlspecialcharsEx($field["title"]).'"'?>><?=($field["required"]? '<span class="required">*</span>':'')?><?=htmlspecialcharsEx($field["name"])?>:</td>
						<?endif;?>


					<td class="bx-field-value"<?=(isset($field["colspan"]) ? ' colspan="2"':'')?>>
						<?switch($field["type"]):
						case 'label':
							echo '<div class="crm-fld-block-readonly">', $val, '</div>';
							break;
						case 'custom':
							echo $val;
							break;
						case 'checkbox':?>
							<input type="hidden" name="<?=$field["id"]?>" value="N">
							<input type="checkbox" name="<?=$field["id"]?>" value="Y"<?=($val == "Y"? ' checked':'')?><?=$params?>>
							<?break;
						case 'textarea':?>
							<textarea name="<?=$field["id"]?>"<?=$params?>><?=$valEncoded?></textarea>
							<?break;
						case 'list':?>
				<select name="<?=$field["id"]?>"<?=$params?>>
				<?if(is_array($field["items"])):
							if(!is_array($val))
								$val = array($val);
							foreach($field["items"] as $k=>$v):?>
								<option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array($k, $val)? ' selected':'')?>><?=htmlspecialcharsbx($v)?></option>
								<?endforeach;?>
				</select>
				<?endif;
						break;
						case 'file':
							$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
							foreach($arDefParams as $k=>$v)
								if(!array_key_exists($k, $field["params"]))
									$field["params"][$k] = $v;

							echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
							if($val <> '')
								echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);

							break;
						case 'date':
							$APPLICATION->IncludeComponent(
								"bitrix:main.calendar",
								"",
								array(
									"SHOW_INPUT"=>"Y",
									"INPUT_NAME"=>$field["id"],
									"INPUT_VALUE"=>$val,
									"INPUT_ADDITIONAL_ATTR"=>$params,
									"SHOW_TIME" => 'Y'
								),
								$component,
								array("HIDE_ICONS"=>true)
							);
							break;
						case 'crm_activity_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$activityListContainerID = $prefix.'_activity_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($activityListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_activities'?>",
												{
													containerID: "<?=CUtil::JSEscape($activityListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.activity.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_activity",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.activity.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_event_view':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							unset($componentData['enableLazyLoad']);

							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								$contextID = isset($componentData['contextId']) ? $componentData['contextId'] : '';
								unset($componentData['contextId']);

								if($contextID !== '')
								{
									$loaderID = strtolower($contextID);
									$eventViewContainerID = "{$loaderID}_wrapper";
								}
								else
								{
									$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
									$loaderID = "{$prefix}_events";
									$eventViewContainerID = "{$prefix}_event_view_wrapper";
								}

								?><div id="<?=htmlspecialcharsbx($eventViewContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($loaderID)?>",
												{
													containerID: "<?=CUtil::JSEscape($eventViewContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_event",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.event.view',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_deal_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$dealListContainerID = $prefix.'_deal_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($dealListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_deals'?>",
												{
													containerID: "<?=CUtil::JSEscape($dealListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.deal.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_deal",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.deal.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_quote_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$quoteListContainerID = $prefix.'_quote_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($quoteListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_quotes'?>",
												{
													containerID: "<?=CUtil::JSEscape($quoteListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.quote.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_quote",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.quote.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_invoice_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$invoiceListContainerID = $prefix.'_invoice_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($invoiceListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_invoices'?>",
												{
													containerID: "<?=CUtil::JSEscape($invoiceListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.invoice.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_invoice",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.invoice.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_company_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$companyListContainerID = $prefix.'_company_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($companyListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_company'?>",
												{
													containerID: "<?=CUtil::JSEscape($companyListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.company.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_company",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.company.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_contact_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$contactListContainerID = $prefix.'_contact_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($contactListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_contact'?>",
												{
													containerID: "<?=CUtil::JSEscape($contactListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.contact.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_contact",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.contact.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_lists_element':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();
							$enableLazyLoad = isset($componentData['enableLazyLoad']) ?
								$componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							unset($componentData['enableLazyLoad']);

							$iblockId = isset($componentParams['IBLOCK_ID']) ? $componentParams['IBLOCK_ID'] : 0;
							$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
							$containerId = $prefix.'_lists_wrapper_'.$iblockId;
							$serviceUrl = '/bitrix/components/bitrix/lists.element.attached.crm/lazyload.ajax.php?&site='
								.SITE_ID.'&'.bitrix_sessid_get().'';
							if($enableLazyLoad === true)
							{
							?>
								<div id="<?=htmlspecialcharsbx($containerId)?>"></div>
								<script type="text/javascript">
									BX.ready(function() {
										BX.CrmFormTabLazyLoader.create(
											"<?=CUtil::JSEscape($prefix)?>",
											{
												containerID: "<?=CUtil::JSEscape($containerId)?>",
												serviceUrl: "<?=$serviceUrl?>",
												formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
												tabID: "tab_lists_"+<?=$iblockId?>,
												params: <?=CUtil::PhpToJSObject($componentData)?>
											}
										);
									});
								</script>
								<?
							}
							else
							{
								$APPLICATION->IncludeComponent('bitrix:lists.element.attached.crm',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y')
								);
							}
								break;
						case 'crm_requisite_list':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$requisiteListContainerID = $prefix.'_requisite_list_wrapper';
								?><div id="<?=htmlspecialcharsbx($requisiteListContainerID)?>"></div>
								<script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_requisite'?>",
												{
													containerID: "<?=CUtil::JSEscape($requisiteListContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.requisite.list/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_requisite",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.requisite.list',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'crm_automation':
							$componentData = isset($field['componentData']) ? $field['componentData'] : array();
							$componentParams = isset($componentData['params']) ? $componentData['params'] : array();

							$enableLazyLoad = isset($componentData['enableLazyLoad']) ? $componentData['enableLazyLoad'] : false;
							if(is_string($enableLazyLoad))
							{
								$enableLazyLoad = strtoupper($enableLazyLoad) === 'Y';
							}
							if($enableLazyLoad === true)
							{
								unset($componentData['enableLazyLoad']);

								$prefix = strtolower("{$arParams['FORM_ID']}_{$field['id']}");
								$automationContainerID = $prefix.'_crm_automation_wrapper';
								?><div id="<?= htmlspecialcharsbx($automationContainerID) ?>"></div>
								<script type="text/javascript">
									BX.ready(
										function ()
										{
											BX.CrmFormTabLazyLoader.create(
												"<?=CUtil::JSEscape($prefix).'_automation'?>",
												{
													containerID: "<?=CUtil::JSEscape($automationContainerID)?>",
													serviceUrl: "/bitrix/components/bitrix/crm.automation/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
													formID: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
													tabID: "tab_automation",
													params: <?=CUtil::PhpToJSObject($componentData)?>
												}
											);
										}
									);
								</script><?
							}
							else
							{
								//Disable control panel in internal mode
								$componentParams['ENABLE_CONTROL_PANEL'] = false;
								$APPLICATION->IncludeComponent('bitrix:crm.automation',
									isset($componentData['template']) ? $componentData['template'] : '',
									$componentParams,
									false,
									array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
								);
							}
							break;
						case 'recurring_params':
							?>
								<div><?=$val?></div>
							<?
							break;
						default:?>
							<input type="text" name="<?=$field["id"]?>" value="<?=$valEncoded?>"<?=$params?>>
							<?break;
					endswitch;?>
					</td>
				</tr><?
					$fieldCount++;
				endforeach;
				unset($fieldData);

				if($hasOnDemandFields):
					?><tr class="bx-crm-view-show-more">
					<td>&nbsp;</td>
					<td>
						<span onclick="bxForm_<?=$arParams["FORM_ID"]?>.ShowOnDemand(this); this.style.display='none';" ><?=htmlspecialcharsbx(GetMessage('intarface_form_show_additional_info'))?></span>
					</td>
				</tr><?
				endif;
				?></table> <!-- bx-crm-view-fieldset-content-table -->
		</div> <!-- bx-crm-view-fieldset-content -->
	</div> <!-- bx-crm-view-fieldset --><?
	endforeach;
	unset($arSection);

	?></div> <!-- wrapper -->
</div> <!-- bx-edit-tab-inner -->
<?endforeach;
unset($tab);
?></div><?
if($arParams["SHOW_FORM_TAG"]):?>
</form>
<?endif?>
</div> <!-- bx-interface-form -->

<?if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div style="display:none">

	<div id="form_settings_<?=$arParams["FORM_ID"]?>">
		<table width="100%">
			<tr class="section">
				<td><?echo GetMessage("interface_form_tabs")?></td>
			</tr>
			<tr>
				<td align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<select style="min-width:150px;" name="tabs" size="10" ondblclick="this.form.tab_edit_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.OnSettingsChangeTab()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="tab_up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabEdit()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_del_btn" value="<?echo GetMessage("intarface_form_del")?>" title="<?echo GetMessage("intarface_form_del_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabDelete()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="section">
				<td><?echo GetMessage("intarface_form_fields")?></td>
			</tr>
			<tr>
				<td align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_available")?></div>
								<select style="min-width:150px;" name="all_fields" multiple size="12" ondblclick="this.form.add_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="add_btn" value="&gt;" title="<?echo GetMessage("intarface_form_add_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="del_btn" value="&lt;" title="<?echo GetMessage("intarface_form_del_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsDelete()"></div>
							</td>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_on_tab")?></div>
								<select style="min-width:150px;" name="fields" multiple size="12" ondblclick="this.form.del_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_sect")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_field")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldEdit()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?if($arResult["IS_ADMIN"]):?>
				<tr class="section">
					<td><?echo GetMessage("interface_form_common")?></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="set_default_settings" id="set_default_settings_<?=$arParams["FORM_ID"]?>" onclick="BX('delete_users_settings_<?=$arParams["FORM_ID"]?>').disabled = !this.checked;"><label for="set_default_settings_<?=$arParams["FORM_ID"]?>"><?echo GetMessage("interface_form_common_set")?></label></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="delete_users_settings" id="delete_users_settings_<?=$arParams["FORM_ID"]?>" disabled><label for="delete_users_settings_<?=$arParams["FORM_ID"]?>"><?echo GetMessage("interface_form_common_del")?></label></td>
				</tr>
			<?endif;?>
		</table>
	</div>

</div>
<?endif //$GLOBALS['USER']->IsAuthorized()?>

<?
$variables = array(
	"mess"=>array(
		"collapseTabs"=>GetMessage("interface_form_close_all"),
		"expandTabs"=>GetMessage("interface_form_show_all"),
		"settingsTitle"=>GetMessage("intarface_form_settings"),
		"settingsSave"=>GetMessage("interface_form_save"),
		"tabSettingsTitle"=>GetMessage("intarface_form_tab"),
		"tabSettingsSave"=>"OK",
		"tabSettingsName"=>GetMessage("intarface_form_tab_name"),
		"tabSettingsCaption"=>GetMessage("intarface_form_tab_title"),
		"fieldSettingsTitle"=>GetMessage("intarface_form_field"),
		"fieldSettingsName"=>GetMessage("intarface_form_field_name"),
		"sectSettingsTitle"=>GetMessage("intarface_form_sect"),
		"sectSettingsName"=>GetMessage("intarface_form_sect_name"),
	),
	"ajax"=>array(
		"AJAX_ID"=>$arParams["AJAX_ID"],
		"AJAX_OPTION_SHADOW"=>($arParams["AJAX_OPTION_SHADOW"] == "Y"),
	),
	"settingWndSize"=>CUtil::GetPopupSize("InterfaceFormSettingWnd"),
	"tabSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormTabSettingWnd", array('width'=>400, 'height'=>200)),
	"fieldSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormFieldSettingWnd", array('width'=>400, 'height'=>150)),
	"component_path"=>(isset($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"])
		&& !empty($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"])) ?
		strval($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"]) : $component->GetRelativePath(),
	"template_path"=>$this->GetFolder(),
	"sessid"=>bitrix_sessid(),
	"current_url"=>$APPLICATION->GetCurPageParam("", array("bxajaxid", "AJAX_CALL")),
	"GRID_ID"=>$arParams["THEME_GRID_ID"],
);

?><script type="text/javascript">
var formSettingsDialog<?=$arParams["FORM_ID"]?>;
bxForm_<?=$arParams["FORM_ID"]?> = new BxCrmInterfaceForm('<?=$arParams["FORM_ID"]?>', <?=CUtil::PhpToJsObject(array_keys($arResult["TABS"]))?>);
bxForm_<?=$arParams["FORM_ID"]?>.vars = <?=CUtil::PhpToJsObject($variables)?>;<?
	if($arParams["SHOW_SETTINGS"] == true):
		?>bxForm_<?=$arParams["FORM_ID"]?>.oTabsMeta = <?=CUtil::PhpToJsObject($arResult["TABS_META"])?>;
		bxForm_<?=$arParams["FORM_ID"]?>.oFields = <?=CUtil::PhpToJsObject($arResult["AVAILABLE_FIELDS"])?>;<?
	endif
	?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu = [];<?
	if($arParams["SHOW_SETTINGS"] == true):
		?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_settings"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_settings_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.ShowSettings()', 'DEFAULT':true, 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings'});<?
		if(!empty($arResult["OPTIONS"]["tabs"])):
			if($arResult["OPTIONS"]["settings_disabled"] == "Y"):
				?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_on"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_on_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.EnableSettings(true)', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings-on'});<?
			else:
				?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_off"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_off_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.EnableSettings(false)', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings-off'});<?
			endif;
		endif;
	endif;

	?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.Initialize();});<?
	if($arResult["OPTIONS"]["expand_tabs"] == "Y"):
		?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.ToggleTabs(true);});<?
	endif;
	?></script><?

if($bWasRequired):
	?><div class="bx-form-notes"><span class="required">*</span><?echo GetMessage("interface_form_required")?></div><?
endif;
?>
