<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\EntityAddressType;

global $APPLICATION;

\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.fonts.opensans"]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');

$settings = isset($arParams['SETTINGS']) && is_array($arParams['SETTINGS']) ? $arParams['SETTINGS'] : array();

// Looking for 'tab_1' (Only single tab is supported).
$mainTab = null;
foreach($arResult['TABS'] as $tab):
	if($tab['id'] !== 'tab_1')
		continue;
	$mainTab = $tab;
	break;
endforeach;

//Take first tab if tab_1 is not found
if(!$mainTab):
	$mainTab = reset($arResult['TABS']);
endif;

if(!($mainTab && isset($mainTab['fields']) && is_array($mainTab['fields'])))
	return;

$hasRequiredFields = false;

$arUserSearchFields = array();
$arSections = array();
$sectionIndex = -1;
foreach($mainTab['fields'] as &$field):
	if(!is_array($field) || $field['isHidden'])
		continue;

	$fieldID = isset($field['id']) ? $field['id'] : '';

	if($field['type'] === 'section'):

		$arSections[] = array(
			'SECTION_FIELD' => $field,
			'SECTION_ID' => $fieldID,
			'SECTION_NAME' => isset($field['name']) ? $field['name'] : $fieldID,
			'FIELDS' => array()
		);
		$sectionIndex++;
		continue;
	endif;

	if($sectionIndex < 0):
		$arSections[] = array(
			'SECTION_FIELD' => null,
			'SECTION_ID' => '',
			'SECTION_NAME' => '',
			'FIELDS' => array()
		);
		$sectionIndex = 0;
	endif;

	$arSections[$sectionIndex]['FIELDS'][] = $field;
endforeach;
unset($field);

if(isset($arParams['TABS_META']))
{
	$arResult['TABS_META'] = $arParams['TABS_META'];
}
elseif($arParams['SHOW_SETTINGS'] && $arResult['OPTIONS']['settings_disabled'])
{
	$arResult['TABS_META'] = array();
	foreach($arResult['TABS'] as $tabID => $tabData)
	{
		$arResult['TABS_META'][$tabID] = array('id'=>$tabID, 'name'=>$tabData['name'], 'title'=>$tabData['title']);
		foreach($tabData['fields'] as $field)
		{
			$fieldInfo = array('id'=>$field['id'], 'name'=>$field['name'], 'type'=>$field['type']);
			if(isset($field['required']))
			{
				$fieldInfo['required'] = $field['required'];
			}
			if(isset($field['persistent']))
			{
				$fieldInfo['persistent'] = $field['persistent'];
			}
			if(isset($field['associatedField']))
			{
				$fieldInfo['associatedField'] = $field['associatedField'];
			}
			if(isset($field['rawId']))
			{
				$fieldInfo['rawId'] = $field['rawId'];
			}
			$arResult['TABS_META'][$tabID]['fields'][$field['id']] = &$fieldInfo;
			unset($fieldInfo);
		}
	}
}

if(isset($arParams['AVAILABLE_FIELDS']))
{
	$arResult['AVAILABLE_FIELDS'] = $arParams['AVAILABLE_FIELDS'];
}

$formIDLower = mb_strtolower($arParams['FORM_ID']);
$containerID = 'container_'.$formIDLower;
$undoContainerID = 'undo_container_'.$formIDLower;

$mode = isset($arParams['MODE'])? mb_strtoupper($arParams['MODE']) : 'EDIT';
$isVisible = $mode !== 'VIEW' || !isset($arResult['OPTIONS']['show_in_view_mode']) || $arResult['OPTIONS']['show_in_view_mode'] === 'Y';
?><div id="<?=$undoContainerID?>"></div>
<div id="<?=$containerID?>" class="bx-interface-form bx-crm-edit-form"<?=!$isVisible ? ' style="display:none;"' : ''?>>
<script type="text/javascript">
	var bxForm_<?=$arParams['FORM_ID']?> = null;
</script><?
if($arParams['SHOW_FORM_TAG']):
?><form name="form_<?=$arParams['FORM_ID']?>" id="form_<?=$arParams["FORM_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
	<?=bitrix_sessid_post();?>
	<input type="hidden" id="<?=$arParams["FORM_ID"]?>_active_tab" name="<?=$arParams["FORM_ID"]?>_active_tab" value="<?=htmlspecialcharsbx($arResult["SELECTED_TAB"])?>"><?
endif;

$canCreateUserField = (
	CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
	&& (!isset($arParams['ENABLE_USER_FIELD_CREATION']) || $arParams['ENABLE_USER_FIELD_CREATION'] !== 'N')
);
$canEditSection = !(isset($arParams['ENABLE_SECTION_EDIT']) && $arParams['ENABLE_SECTION_EDIT'] === 'N');
$canCreateSection = (
	$canEditSection	&& !(isset($arParams['ENABLE_SECTION_CREATION']) && $arParams['ENABLE_SECTION_CREATION'] === 'N')
);

if (isset($arParams['IS_MODAL']) && $arParams['IS_MODAL'] === 'Y')
{
	?><div class="crm-title-block-modal">
	<span id="<?=$arParams['FORM_ID']?>_menu" class="crm-toolbar-btn crm-title-btn">
			<span class="crm-toolbar-btn-icon"></span>
		</span>
	</div><?
}
else
{
	$title = isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '';
	if(is_string($title) && $title !== ''):
	?><div class="crm-title-block">
		<span class="crm-title-text"><?=strip_tags($title)?></span>
		<span id="<?=$arParams['FORM_ID']?>_menu" class="crm-toolbar-btn crm-title-btn">
			<span class="crm-toolbar-btn-icon"></span>
		</span>
	</div><?
	endif;
}

$prefix = isset($arParams['~PREFIX'])? mb_strtolower($arParams['~PREFIX']) : '';
$sectionWrapperID = $formIDLower.'_section_wrapper';
?><div id="<?=$sectionWrapperID?>" class="crm-offer-main-wrap"><?
$sipManagerRequired = false;
$enableFieldDrag = !isset($settings['ENABLE_FIELD_DRAG']) || $settings['ENABLE_FIELD_DRAG'] === 'Y';
$enableSectionDrag = $canEditSection;
if($enableSectionDrag && isset($settings['ENABLE_SECTION_DRAG']))
{
	$enableSectionDrag = $settings['ENABLE_SECTION_DRAG'] === 'Y';
}

foreach($arSections as &$arSection):
	$sectionNodePrefix = mb_strtolower($arSection['SECTION_ID']);
	if($prefix !== "")
		$sectionNodePrefix = "{$prefix}_{$sectionNodePrefix}";

	?><table id="<?=$sectionNodePrefix?>_contents" class="crm-offer-info-table<?=$mode === 'VIEW' ? ' crm-offer-main-info-text' : ''?>"><tbody><?
	$associatedField = isset($arSection['SECTION_FIELD']['associatedField']) && is_array($arSection['SECTION_FIELD']['associatedField'])
		? $arSection['SECTION_FIELD']['associatedField'] : null;

	?><tr id="<?=$arSection['SECTION_ID']?>"><?
		$sectionName = isset($arSection['SECTION_NAME'])
			? htmlspecialcharsbx($arSection['SECTION_NAME']) : $arSection['SECTION_ID'];
		if($associatedField !== null && isset($associatedField['value'])):
			$sectionName = htmlspecialcharsbx($associatedField['value']);
		endif;
		?><td colspan="5">
			<div class="crm-offer-title">
				<span class="crm-offer-drg-btn"<?= ($enableSectionDrag ? '' : ' style="display: none;"')?>></span>
				<span class="crm-offer-title-text"><?=$sectionName?></span>
				<span class="crm-offer-title-set-wrap"><?
				if($mode === 'EDIT'):
				?><span id="<?= $sectionNodePrefix ?>_edit" class="crm-offer-title-edit"<?= ($canEditSection ? '' : ' style="display: none;"') ?>></span><?
				endif;
				?><span id="<?=$sectionNodePrefix?>_delete" class="crm-offer-title-del"<?= ($canEditSection ? '' : ' style="display: none;"') ?>></span><?
				?></span>
			</div><?
			if($associatedField !== null):
				$associatedFieldID = isset($associatedField['id']) ? htmlspecialcharsbx($associatedField['id']) : '';
				$associatedFieldValue= isset($associatedField['value']) ? htmlspecialcharsbx($associatedField['value']) : '';
				?><input type="hidden" id="<?=$associatedFieldID?>" name="<?=$associatedFieldID?>" value="<?=$associatedFieldValue?>" /><?
			endif;
		?></td>
	</tr><?
	$fieldCount = 0;
	foreach($arSection['FIELDS'] as &$field):
		$fieldNodePrefix = mb_strtolower($field["id"]);
		if($prefix !== "")
			$fieldNodePrefix = "{$prefix}_{$fieldNodePrefix}";

		$visible = isset($field['visible']) ? (bool)$field['visible'] : true;
		$dragDropType = $field['type'] === 'lhe' ? 'lhe' : '';
		$containerClassName = $field['type'] === 'address' ? 'crm-offer-row crm-offer-info-address-row' : 'crm-offer-row';

		if(is_array($field['options']) && isset($field['options']['nohover']) && $field['options']['nohover'])
			$containerClassName .= ' crm-offer-row-no-hover';

		$rowContainerID = "{$fieldNodePrefix}_wrap";
		?><tr id="<?=$rowContainerID?>"<?=$visible ? '' : 'style="display:none;"'?> class="<?=$containerClassName?>" data-dragdrop-context="field" data-dragdrop-id="<?=$field["id"]?>"<?=$dragDropType !== '' ? ' data-dragdrop-type="'.$dragDropType.'"' : ''?>>
			<td class="crm-offer-info-drg-btn" <?= ($enableFieldDrag ? '' : ' style="display: none;"') ?>><span class="crm-offer-drg-btn"></span></td><?
		$required = isset($field['required']) && $field['required'] === true;
		$persistent = isset($field['persistent']) && $field['persistent'] === true;

		//default attributes
		if(!is_array($field['params']))
			$field['params'] = array();

		if($field['type'] == '' || $field['type'] == 'text')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '30';
		}
		elseif($field['type'] == 'textarea')
		{
			if($field['params']['cols'] == '')
				$field['params']['cols'] = '40';

			if($field['params']['rows'] == '')
				$field['params']['rows'] = '3';
		}
		elseif($field['type'] == 'date')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}
		elseif($field['type'] == 'date_short')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}

		$params = '';
		if(is_array($field['params']) && $field['type'] <> 'file')
			foreach($field['params'] as $p=>$v)
				$params .= ' '.$p.'="'.$v.'"';

		$val = isset($field['value'])
				? $field['value']
				: (isset($arParams['~DATA'][$field['id']]) ? $arParams['~DATA'][$field['id']] : '');

		$valEncoded = '';
		//Custom type don't use $valEncoded, arrays can't be encoded
		if($field['type'] !== 'custom' && !is_array($val))
		{
			$valEncoded = htmlspecialcharsbx(is_string($val) ? htmlspecialcharsback($val) : $val);
		}

		if($field['type'] === 'vertical_container'):
			?><td class="crm-offer-info-right" colspan="4">
			<div class="crm-offer-editor-title">
				<div class="crm-offer-editor-title-contents-wapper">
					<?if($required):?><span class="required">*</span><?endif;?>
					<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
				</div>
			</div>
			<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?=$val?></div>
			<span class="crm-offer-edit-btn-wrap"><?
				if(!$required && !$persistent):
				?><span class="crm-offer-item-del"></span><?
				endif;
				?><span class="crm-offer-item-edit"></span>
			</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif ($field['type'] === 'recurring_params'):
			?>
			<td class="crm-offer-last-td" colspan="4">
				<div class="crm-offer-editor-wrap crm-offer-info-data-wrap">
					<?php
						echo $val;
						if(!$required && !$persistent)
						{
							?>
							<span class="crm-offer-info-right-btn">
								<span class="crm-offer-item-del" style="margin-top:0"></span>
							</span>
							<?php
						}
					?>
				</div>
			</td>
			<?
		elseif($field['type'] === 'lhe'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$params['id'] = mb_strtolower("{$arParams['FORM_ID']}_{$field['id']}");

			// rewrite bReplaceTabToNbsp option
			if (is_array($params) && isset($params['bReplaceTabToNbsp']) && !$params['bReplaceTabToNbsp'])
			{
				?><script type="text/javascript">
				BX.ready(function () {
					BX.addCustomEvent(window, "LHE_OnBeforeParsersInit", function(lhe) {
						if (lhe !== null && typeof(lhe) === "object" && lhe.hasOwnProperty("arConfig")
							&& BX.type.isPlainObject(lhe.arConfig) && lhe.hasOwnProperty("id")
							&& BX.type.isNotEmptyString(lhe["id"])
							&& lhe["id"] === "<?=$params['id']?>")
						{
							lhe.arConfig["bReplaceTabToNbsp"] = false;
						}
					});
				});
				</script><?
			}

			CModule::IncludeModule('fileman');
			$lhe = new CLightHTMLEditor();
			?><td class="crm-offer-info-right" colspan="4">
				<div class="crm-offer-editor-title">
					<div class="crm-offer-editor-title-contents-wapper">
						<?if($required):?><span class="required">*</span><?endif;?>
						<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
					</div>
				</div>
				<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?$lhe->Show($params);?></div>
				<span class="crm-offer-edit-btn-wrap"><?
					if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
					endif;
					?><span class="crm-offer-item-edit"></span>
				</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif($field['type'] === 'multiple_address'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) && is_array($params['DATA']) ? $params['DATA'] : array();
			$addressScheme = isset($params['SCHEME']) && is_array($params['SCHEME']) ? $params['SCHEME'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			$fielNameTemplate = isset($params['FIELD_NAME_TEMPLATE']) ? $params['FIELD_NAME_TEMPLATE'] : '';

			$addressCreationCaption = GetMessage('intarface_form_add');
			$addressAlreadyExists = isset($params['ALREADY_EXISTS'])
				? $params['ALREADY_EXISTS'] : GetMessage('CRM_ADDRESS_ALREADY_EXISTS');

			if (is_array($params['ADDRESS_TYPE_INFOS']) && !empty($params['ADDRESS_TYPE_INFOS']))
			{
				$addressTypeInfos = $params['ADDRESS_TYPE_INFOS'];
			}
			else
			{
				$addressTypeInfos = [
					[
						'id' => EntityAddressType::Primary,
						'name' => EntityAddressType::getDescription(EntityAddressType::Primary)
					],
					[
						'id' => EntityAddressType::Registered,
						'name' => EntityAddressType::getDescription(EntityAddressType::Registered)
					]
				];
			}
			$addressTypeDesc = array();
			foreach ($addressTypeInfos as $typeInfo)
			{
				if (isset($typeInfo['id']) && isset($typeInfo['name']))
					$addressTypeDesc[$typeInfo['id']] = $typeInfo['name'];
			}
			$currentAddressTypeID = EntityAddressType::Primary;
			$createAddressButtonID = mb_strtolower("{$arParams['FORM_ID']}_{$field['id']}_add");
			$addressLabels = Bitrix\Crm\EntityAddress::getLabels();

			$addressDataWrapperID = "{$fieldNodePrefix}_data_wrap";

			?><td class="crm-offer-requisite-table-wrap" colspan="4">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
				<div class="crm-offer-info-data-wrap" id="<?=htmlspecialcharsEx($addressDataWrapperID)?>">
					<div class="crm-offer-requisite-block-wrap">
						<span id="<?=$createAddressButtonID?>" class="crm-offer-requisite-option">
							<span class="crm-offer-requisite-option-caption">
								<?=htmlspecialcharsbx($addressCreationCaption)?>:
							</span>
							<span class="crm-offer-requisite-option-text">
								<?=htmlspecialcharsbx(
									isset($addressTypeDesc[$currentAddressTypeID])
										? $addressTypeDesc[$currentAddressTypeID]
										: EntityAddressType::getDescription($currentAddressTypeID)
								)?>
							</span>
							<span class="crm-offer-requisite-option-arrow"></span>
						</span>
						<div class="crm-offer-requisite-form-wrap">
							<div class="crm-multi-address"><?
							foreach($addressData as $addressTypeID => $addresFields):
								if($fielNameTemplate === '')
									$itemWrapperID = "{$field['id']}_wrapper";
								else
									$itemWrapperID = str_replace(
										array('#TYPE_ID#', '#FIELD_NAME#'),
										array($addressTypeID, 'wrapper'),
										$fielNameTemplate
									);
								$itemWrapperID = mb_strtolower($itemWrapperID);
								$itemTitle = isset($addressTypeDesc[$addressTypeID]) ?
									$addressTypeDesc[$addressTypeID] :
									EntityAddressType::getDescription($addressTypeID);
								?><div class="crm-multi-address-item" id="<?=htmlspecialcharsbx($itemWrapperID)?>">
									<table class="crm-offer-info-table"><tbody>
										<tr>
											<td colspan="5">
												<div class="crm-offer-title">
													<span class="crm-offer-title-text"><?=htmlspecialcharsbx($itemTitle)?></span>
													<span class="crm-offer-title-set-wrap">
														<span class="crm-offer-title-del"></span>
													</span>
												</div>
											</td>
										</tr><?
										foreach($addressScheme as $addressSchemeItem):
											$addresFieldName = $addressSchemeItem['name'];
											$addresFieldType = $addressSchemeItem['type'];
											$addresFieldQualifiedName = $addresFieldName;
											if($fielNameTemplate !== '')
												$addresFieldQualifiedName = str_replace(
													array('#TYPE_ID#', '#FIELD_NAME#'),
													array($addressTypeID, $addresFieldName),
													$fielNameTemplate
												);
											$addresFieldValue = isset($addresFields[$addresFieldName]) ? $addresFields[$addresFieldName] : "";

											if($addresFieldType === 'locality'):
												$addressSchemeItemParams = $addressSchemeItem['params'];
												$addressLocalityType = $addressSchemeItemParams['locality'];
												$addresSearchFieldName = $addressSchemeItem['related'];
												$addresSearchFieldQualifiedName = $addresSearchFieldName;
												if($fielNameTemplate !== '')
													$addresSearchFieldQualifiedName = str_replace(
														array('#TYPE_ID#', '#FIELD_NAME#'),
														array($addressTypeID, $addresSearchFieldName),
														$fielNameTemplate
													);
												?><tr style="display: none;">
													<td colspan="4">
														<input type="hidden" name="<?=$addresFieldQualifiedName?>" value="<?=htmlspecialcharsbx($addresFieldValue)?>"/>
														<script type="text/javascript">
															BX.ready(
																function()
																{
																	BX.CrmLocalitySearchField.create(
																		"<?=$addresSearchFieldQualifiedName?>",
																		{
																			localityType: "<?=$addressLocalityType?>",
																			serviceUrl: "<?=$addressServiceUrl?>",
																			searchInput: "<?=$addresSearchFieldQualifiedName?>",
																			dataInput: "<?=$addresFieldQualifiedName?>"
																		}
																	);
																}
															);
														</script>
													</td>
												</tr><?
											else:
												?><tr>
													<td class="crm-offer-info-left">
														<span class="crm-offer-info-label-alignment"></span>
														<span class="crm-offer-info-label"><?=$addressLabels[$addresFieldName]?>:</span>
													</td>
													<td class="crm-offer-info-right">
														<div class="crm-offer-info-data-wrap"><?
															if($addresFieldType === 'multilinetext'):?>
																<textarea class="crm-offer-textarea" name="<?=htmlspecialcharsEx($addresFieldQualifiedName)?>"><?=htmlspecialcharsbx($addresFieldValue)?></textarea><?
															else:?>
																<input class="crm-offer-item-inp" name="<?=htmlspecialcharsEx($addresFieldQualifiedName)?>" type="text" value="<?=htmlspecialcharsbx($addresFieldValue)?>" />
															<?endif;
														?></div>
													</td>
												<td class="crm-offer-info-right-btn"></td>
												<td class="crm-offer-last-td"></td>
												</tr><?
											endif;
										endforeach;
										?>
									</tbody></table>
								</div><?
							endforeach;
							?></div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					BX.ready(
						function()
						{
							BX.CrmMultipleAddressItemEditor.messages =
							{
								copyConfirmation: "<?=GetMessageJS("CRM_ADDRESS_COPY_CONFIRMATION")?>",
								deletionConfirmation: "<?=GetMessageJS('CRM_ADRESS_DELETE_CONFIRMATION')?>",
								deleteButton: "<?=GetMessageJS("intarface_form_del")?>"
							};

							BX.CrmMultipleAddressEditor.messages =
							{
								alreadyExists: "<?=CUtil::JSEscape($addressAlreadyExists)?>"
							};

							BX.CrmMultipleAddressEditor.create(
								"<?=$fieldNodePrefix?>",
								{
									fieldId: "",
									formId: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
									scheme: <?=CUtil::PhpToJSObject($addressScheme)?>,
									currentTypeId: <?=$currentAddressTypeID?>,
									typeInfos: <?=CUtil::PhpToJSObject($addressTypeInfos)?>,
									fieldLabels: <?=CUtil::PhpToJSObject($addressLabels)?>,
									data: <?=!empty($addressData) ? CUtil::PhpToJSObject($addressData) : '{}'?>,
									container: BX("<?=CUtil::JSEscape($addressDataWrapperID)?>"),
									createButtonContainer: BX("<?=CUtil::JSEscape($createAddressButtonID)?>"),
									serviceUrl: "<?=CUtil::JSEscape($addressServiceUrl)?>",
									fielNameTemplate: "<?=CUtil::JSEscape($fielNameTemplate)?>"
								}
							);
						}
					);
				</script>
			</td>
			<?
		elseif($field['type'] === 'address'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) ? $params['DATA'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			?><td class="crm-offer-info-left" colspan="2">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
				<div class="crm-offer-info-data-wrap">
					<table class="crm-offer-info-table"><tbody><?
					$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
					foreach($addressData as $itemKey => $item):
						$itemValue = isset($item['VALUE']) ? $item['VALUE'] : '';
						$itemName = isset($item['NAME']) ? $item['NAME'] : $itemKey;
						$itemLocality = isset($item['LOCALITY']) ? $item['LOCALITY'] : null;
						?><tr>
							<td class="crm-offer-info-left">
								<span class="crm-offer-info-label-alignment"></span>
								<span class="crm-offer-info-label"><?=$addressLabels[$itemKey]?>:</span>
							</td>
							<td class="crm-offer-info-right">
								<div class="crm-offer-info-data-wrap"><?
									if(is_array($itemLocality)):
										$searchInputID = "{$arParams['FORM_ID']}_{$itemName}";
										$dataInputID = "{$arParams['FORM_ID']}_{$itemLocality['NAME']}";
										?><input class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$itemName?>" type="text" value="<?=htmlspecialcharsbx($itemValue)?>" />
										<input type="hidden" id="<?=$dataInputID?>" name="<?=$itemLocality['NAME']?>" value="<?=htmlspecialcharsbx($itemLocality['VALUE'])?>"/>
										<script type="text/javascript">
											BX.ready(
												function()
												{
													BX.CrmLocalitySearchField.create(
														"<?=$searchInputID?>",
														{
															localityType: "<?=$itemLocality['TYPE']?>",
															serviceUrl: "<?=$addressServiceUrl?>",
															searchInput: "<?=$searchInputID?>",
															dataInput: "<?=$dataInputID?>"
														}
													);
												}
											);
										</script><?
									else:
										if(isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']):
											?><textarea class="crm-offer-textarea" name="<?=htmlspecialcharsEx($itemName)?>"><?=htmlspecialcharsbx($itemValue)?></textarea><?
										else:
											?><input class="crm-offer-item-inp" name="<?=htmlspecialcharsEx($itemName)?>" type="text" value="<?=htmlspecialcharsbx($itemValue)?>" /><?
										endif;
									endif;
								?></div>
							</td>
						</tr><?
					endforeach;
				?></tbody></table>
				</div>
			</td><!-- "crm-offer-info-left" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
			?></td>
			<td class="crm-offer-last-td"></td><?
		elseif($field['type'] === 'bank_details'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$containerId = isset($params['CONTAINER_ID']) ? $params['CONTAINER_ID'] : '';
			?><td class="crm-offer-requisite-table-wrap" colspan="4">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
			<div class="crm-offer-info-data-wrap" id="<?=htmlspecialcharsEx($containerId)?>"></div>
			<script type="text/javascript">
				BX.ready(function(){
					BX.Crm.RequisiteBankDetailsArea.create(
						"<?= CUtil::JSEscape("{$field['id']}_area") ?>",
						{
							formId: "<?= CUtil::JSEscape($arParams['FORM_ID']) ?>",
							mode: "<?= CUtil::JSEscape($mode) ?>",
							container: BX("<?= CUtil::JSEscape($params['CONTAINER_ID']) ?>"),
							presetCountryId: <?= intval($params['PRESET_COUNTRY_ID']) ?>,
							fieldList: <?= CUtil::PhpToJSObject($params['FIELD_LIST']) ?>,
							dataList: <?= CUtil::PhpToJSObject($params['DATA_LIST']) ?>,
							fieldNameTemplate: <?= CUtil::PhpToJSObject($params['FIELD_NAME_TEMPLATE']) ?>,
							lastInForm: <?=$params['IS_LAST_IN_FORM'] === 'Y' ? 'true' : 'false'?>,
							messages: {
								"addBlockBtnText": "<?= GetMessageJS('CRM_BANK_DETAILS_ADD_BTN_TEXT') ?>",
								'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>',
								"fieldNamePlaceHolder": "<?= GetMessageJS('interface_form_bank_details_ttl_placeholder') ?>"
							}
						}
					);
				});
			</script>
			</td>
			<?
		else:
			?><td class="crm-offer-info-left">
				<div class="crm-offer-info-label-wrap"><span class="crm-offer-info-label-alignment"></span><span class="crm-offer-info-label">
					<?if($required):?><span class="required">*</span><?endif;?>
					<?if(!in_array($field['type'], array('checkbox', 'vertical_checkbox'))):?><?=htmlspecialcharsEx($field['name'])?>:<?endif;?>
				</span></div>
			</td><?
			?><td class="crm-offer-info-right"><div class="crm-offer-info-data-wrap"><?
			$advancedInfoHTML = '';
			switch($field['type']):
					case 'label':
						echo '<div id="'.$field["id"].'" class="crm-fld-block-readonly">', nl2br(htmlspecialcharsEx($val)), '</div>';
						break;
					case 'custom':
						{
							$isUserField = mb_strpos($field['id'], 'UF_') === 0;
							$wrap = isset($field['wrap']) && $field['wrap'] === true;
							if($isUserField):
								?>
								<div class="bx-crm-edit-user-field"><?
								elseif($wrap):
								?>
								<div class="bx-crm-edit-field"><?
									endif;

									echo $val;
									if($isUserField || $wrap):
									?></div><?
							endif;
						}
						break;
					case 'checkbox':
					case 'vertical_checkbox':
						$chkBxId = mb_strtolower($field['id']).'_chbx';
						?><input type="hidden" name="<?=$field['id']?>" value="N">
						<input class="crm-offer-checkbox" type="checkbox" id="<?=$chkBxId?>" name="<?=$field['id']?>" value="Y"<?=($val == 'Y'? ' checked':'')?><?=$params?>/>
						<label class="crm-offer-label" for="<?=$chkBxId?>"><?=htmlspecialcharsEx($field['name'])?></label><?
						break;
					case 'textarea':
						?><textarea class="crm-offer-textarea" name="<?=$field["id"]?>"<?=$params?>><?=$valEncoded?></textarea><?
						break;
					case 'list':
						?><select class="crm-item-table-select" name="<?=$field["id"]?>"<?=$params?>><?
							if(is_array($field["items"])):
								if(!is_array($val))
									$val = array($val);
								$val = array_map("strval", $val);
								foreach($field["items"] as $k=>$v):
									?><option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array(strval($k), $val, true) ? ' selected':'')?>><?=htmlspecialcharsEx($v)?></option><?
								endforeach;
							endif;
							?></select><?
						break;
					case 'file':
						?><div class="bx-crm-edit-file-field"><?
							$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
							foreach($arDefParams as $k=>$v)
								if(!array_key_exists($k, $field["params"]))
									$field["params"][$k] = $v;

							echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
							if($val <> '')
								echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);
							?></div><?
						break;
					case 'date':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=$valEncoded?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: true, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_short':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=$valEncoded?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: false, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_link':
						$dataID = "{$arParams['FORM_ID']}_{$field['id']}_DATA";
						$viewID = "{$arParams['FORM_ID']}_{$field['id']}_VIEW";
						?><span id="<?=htmlspecialcharsbx($viewID)?>" class="bx-crm-edit-datetime-link"><?=htmlspecialcharsEx($val !== '' ? $val : GetMessage('interface_form_set_datetime'))?></span>
						<input id="<?=htmlspecialcharsbx($dataID)?>" type="hidden" name="<?=htmlspecialcharsbx($field['id'])?>" value="<?=$valEncoded?>" <?=$params?>>
						<script type="text/javascript">BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::addslashes($dataID)?>'), BX('<?=CUtil::addslashes($viewID)?>'), { showTime: false }); });</script><?
						break;
					case 'intranet_user_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$rsUser = CUser::GetByID($val);
							if($arUser = $rsUser->Fetch()):
								$params['USER'] = $arUser;
							endif;
							?><input type="text" class="crm-offer-item-inp" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
						<input type="hidden" name="<?=htmlspecialcharsbx($params['INPUT_NAME'])?>" value="<?=$valEncoded?>"><?
							$arUserSearchFields[] = $params;
							$APPLICATION->IncludeComponent(
								'bitrix:intranet.user.selector.new',
								'',
								array(
									'MULTIPLE' => 'N',
									'NAME' => $params['NAME'],
									'INPUT_NAME' => $params['SEARCH_INPUT_NAME'],
									'POPUP' => 'Y',
									'SITE_ID' => SITE_ID,
									'NAME_TEMPLATE' => $params['NAME_TEMPLATE']
								),
								null,
								array('HIDE_ICONS' => 'Y')
							);
						endif;
						break;
					case 'crm_entity_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							$entityID = isset($params['INPUT_VALUE']) ? intval($params['INPUT_VALUE']) : 0;
							$newEntityID = isset($params['NEW_INPUT_VALUE']) ? intval($params['NEW_INPUT_VALUE']) : 0;
							$rqLinkedId = isset($params['REQUISITE_LINKED_ID']) ? intval($params['REQUISITE_LINKED_ID']) : 0;
							$rqLinkedInputId = '';
							$bdLinkedId = isset($params['BANK_DETAIL_LINKED_ID']) ? intval($params['BANK_DETAIL_LINKED_ID']) : 0;
							$bdLinkedInputId = '';
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$cardViewMode = $requireRequisiteData = false;
							if ($entityType === 'CONTACT' || $entityType === 'COMPANY')
							{
								$cardViewMode = $requireRequisiteData = true;
							}
							$selectorSearchOptions = is_array($params['ENTITY_SELECTOR_SEARCH_OPTIONS'])
								? $params['ENTITY_SELECTOR_SEARCH_OPTIONS'] : array();
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo(
								$entityType,
								$entityID,
								array(
									'ENTITY_EDITOR_FORMAT' => true,
									'REQUIRE_REQUISITE_DATA' => $requireRequisiteData,
									'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
										$params['NAME_TEMPLATE'] :
										\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
								)
							);
							$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($containerID.'_descr').'" class="crm-offer-info-description"></div>';
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper"></div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=$entityID?>" /><?
							if($newDataInputName !== ''):
							?><input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="<?=$newEntityID?>" /><?
							endif;
							if ($requireRequisiteData)
							{
								$rqLinkedInputName = '';
								if (isset($params['REQUISITE_INPUT_NAME']))
								{
									$rqLinkedInputName = $params['REQUISITE_INPUT_NAME'];
								}
								else
								{
									$postfix = '_REQUISITE_ID';
									if (isset($params['INPUT_NAME']))
										$rqLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$rqLinkedInputName = $field['id'].$postfix;
								}
								$rqLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$rqLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($rqLinkedInputId) ?>" name="<?= htmlspecialcharsbx($rqLinkedInputName) ?>" value="<?= $rqLinkedId ?>" /><?

								$bdLinkedInputName = '';
								if (isset($params['BANK_DETAIL_INPUT_NAME']))
								{
									$bdLinkedInputName = $params['BANK_DETAIL_INPUT_NAME'];
								}
								else
								{
									$postfix = '_BANK_DETAIL_ID';
									if (isset($params['INPUT_NAME']))
										$bdLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$bdLinkedInputName = $field['id'].$postfix;
								}
								$bdLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$bdLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($bdLinkedInputId) ?>" name="<?= htmlspecialcharsbx($bdLinkedInputName) ?>" value="<?= $bdLinkedId ?>" /><?
							}
							?><div class="bx-crm-entity-buttons-wrapper">
							<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span><?
							if($newDataInputName !== ''):
							?> <span class="bx-crm-edit-crm-entity-add"><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?></span><?
							endif;
						?></div>
						</div><?
							$serviceUrl = '';
							$createUrl = '';
							$actionName = '';
							$dialogSettings = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel')
							);
							if($entityType === 'CONTACT')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$createUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0, false);
								$actionName = 'SAVE_CONTACT';

								$dialogSettings['title'] = GetMessage('interface_form_add_contact_dlg_title');
								$dialogSettings['lastNameTitle'] = GetMessage('interface_form_add_contact_fld_last_name');
								$dialogSettings['nameTitle'] = GetMessage('interface_form_add_contact_fld_name');
								$dialogSettings['secondNameTitle'] = GetMessage('interface_form_add_contact_fld_second_name');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_contact_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_contact_fld_phone');
								$dialogSettings['exportTitle'] = GetMessage('interface_form_add_contact_fld_export');
							}
							elseif($entityType === 'COMPANY')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$createUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false);
								$actionName = 'SAVE_COMPANY';

								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['companyTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['industryTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_conpany_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_company_fld_phone');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE'));
								$dialogSettings['industryItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'));
							}
							elseif($entityType === 'DEAL')
							{
								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['dealTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['dealPriceTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('DEAL_TYPE'));
							}
							elseif($entityType === 'QUOTE')
							{
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
							}
							//$sipManagerRequired = true;
							?><script type="text/javascript">
							BX.ready(
									function()
									{
										var entitySelectorId = CRM.Set(
											BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
											'<?=CUtil::JSEscape($selectorID)?>',
											'',
											<?php
												echo CUtil::PhpToJsObject(
													CCrmEntitySelectorHelper::PreparePopupItems(
														$entityType,
														false,
														isset($params['NAME_TEMPLATE']) ?
															$params['NAME_TEMPLATE'] :
															\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
														50,
														array(
															'REQUIRE_REQUISITE_DATA' => $requireRequisiteData,
															'SEARCH_OPTIONS' => $selectorSearchOptions
														)
													)
												);
											?>,
											false,
											false,
											['<?=CUtil::JSEscape(mb_strtolower($entityType))?>'],
											<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
											true,
											{
												requireRequisiteData: <?= $requireRequisiteData ? 'true' : 'false' ?>,
												selectorSearchOptions: <?=CUtil::PhpToJSObject($selectorSearchOptions)?>
											}
										);

										BX.CrmEntityEditor.messages =
										{
											'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
											'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
											'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
											'prefPhoneLong': '<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>',
											'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>',
											'tabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>',
											'contactTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>',
											'companyTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>',
											'tabTitleContactRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>',
											'tabTitleCompanyRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>',
											'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>'
										};

										BX.CrmEntityEditor.create(
											'<?=CUtil::JSEscape($editorID)?>',
											{
												'context': '<?=CUtil::JSEscape($context)?>',
												'typeName': '<?=CUtil::JSEscape($entityType)?>',
												'containerId': '<?=CUtil::JSEscape($containerID)?>',
												'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
												'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
												'entitySelectorId': entitySelectorId,
												'serviceUrl': '<?= CUtil::JSEscape($serviceUrl) ?>',
												'createUrl': '<?= CUtil::JSEscape($createUrl) ?>',
												'actionName': '<?= CUtil::JSEscape($actionName) ?>',
												'dialog': <?=CUtil::PhpToJSObject($dialogSettings)?>,
												'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
												'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
												'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
												'rqLinkedId': '<?=CUtil::JSEscape($rqLinkedId)?>',
												'bdLinkedId': '<?=CUtil::JSEscape($bdLinkedId)?>'
											},
											null,
											BX.CrmEntityInfo.create(<?=CUtil::PhpToJSObject($entityInfo)?>)
										);
									}
							);
						</script><?
						endif;
						break;
					case 'crm_client_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params))
						{
							$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
							$entityID = $inputValue = isset($params['INPUT_VALUE']) ? $params['INPUT_VALUE'] : '';
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							switch(mb_substr($entityID, 0, 2))
							{
								case 'C_':
									$valEntityType = 'contact';
									break;
								case 'CO':
									$valEntityType = 'company';
									break;
								default:
									$valEntityType = '';
							}
							$entityID = intval(mb_substr($entityID, intval(mb_strpos($entityID, '_')) + 1));
							$rqLinkedId = isset($params['REQUISITE_LINKED_ID']) ? intval($params['REQUISITE_LINKED_ID']) : 0;
							$rqLinkedInputId = '';
							$bdLinkedId = isset($params['BANK_DETAIL_LINKED_ID']) ? intval($params['BANK_DETAIL_LINKED_ID']) : 0;
							$bdLinkedInputId = '';
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$createEntitiesBlockID = "{$arParams['FORM_ID']}_CREATE_ENTITIES_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$addContactButtonID = "{$arParams['FORM_ID']}_ADD_CONTACT_BTN_{$field['id']}";
							$addCompanyButtonID = "{$arParams['FORM_ID']}_ADD_COMPANY_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$cardViewMode = $requireRequisiteData = true;
							$selectorSearchOptions = is_array($params['ENTITY_SELECTOR_SEARCH_OPTIONS'])
								? $params['ENTITY_SELECTOR_SEARCH_OPTIONS'] : array();
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo(
								$valEntityType,
								$entityID,
								array(
									'ENTITY_EDITOR_FORMAT' => true,
									'ENTITY_PREFIX_ENABLED' => true,
									'REQUIRE_REQUISITE_DATA' => true,
									'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
										$params['NAME_TEMPLATE'] :
										\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
								)
							);
							$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($containerID.'_descr').'" class="crm-offer-info-description"></div>';
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper"></div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=htmlspecialcharsbx($inputValue)?>" />
							<? if($newDataInputName !== ''): ?>
								<input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="" />
							<? endif;
							if ($requireRequisiteData)
							{
								$rqLinkedInputName = '';
								if (isset($params['REQUISITE_INPUT_NAME']))
								{
									$rqLinkedInputName = $params['REQUISITE_INPUT_NAME'];
								}
								else
								{
									$postfix = '_REQUISITE_ID';
									if (isset($params['INPUT_NAME']))
										$rqLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$rqLinkedInputName = $field['id'].$postfix;
								}
								$rqLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$rqLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($rqLinkedInputId) ?>" name="<?= htmlspecialcharsbx($rqLinkedInputName) ?>" value="<?= $rqLinkedId ?>" /><?

								$bdLinkedInputName = '';
								if (isset($params['BANK_DETAIL_INPUT_NAME']))
								{
									$bdLinkedInputName = $params['BANK_DETAIL_INPUT_NAME'];
								}
								else
								{
									$postfix = '_BANK_DETAIL_ID';
									if (isset($params['INPUT_NAME']))
										$bdLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$bdLinkedInputName = $field['id'].$postfix;
								}
								$bdLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$bdLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($bdLinkedInputId) ?>" name="<?= htmlspecialcharsbx($bdLinkedInputName) ?>" value="<?= $bdLinkedId ?>" /><?
							}
							?>
							<!--<div class="bx-crm-entity-buttons-wrapper">-->
								<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span>
								<? if($newDataInputName !== ''): ?>
									<br>
									<span id="<?=htmlspecialcharsbx($createEntitiesBlockID)?>" class="bx-crm-edit-description"<?=($entityID>0)?' style="display: none;"':''?>>
									<span><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?> </span>
									<span id="<?=htmlspecialcharsbx($addCompanyButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_company'))?></span>
									<span><?= htmlspecialcharsbx(' '.GetMessage('interface_form_add_btn_or')).' '?></span>
									<span id="<?=htmlspecialcharsbx($addContactButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_contact'))?></span>
									</span>
								<? endif; ?>
							<!--</div>-->
							</div><?
							$dialogSettings['CONTACT'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_contact_dlg_title'),
								'lastNameTitle' => GetMessage('interface_form_add_contact_fld_last_name'),
								'nameTitle' => GetMessage('interface_form_add_contact_fld_name'),
								'secondNameTitle' => GetMessage('interface_form_add_contact_fld_second_name'),
								'emailTitle' => GetMessage('interface_form_add_contact_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_contact_fld_phone'),
								'exportTitle' => GetMessage('interface_form_add_contact_fld_export')
							);
							$dialogSettings['COMPANY'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_company_dlg_title'),
								'titleTitle' => GetMessage('interface_form_add_company_fld_title_name'),
								'companyTypeTitle' => GetMessage('interface_form_add_conpany_fld_company_type'),
								'industryTitle' => GetMessage('interface_form_add_company_fld_industry'),
								'emailTitle' => GetMessage('interface_form_add_conpany_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_company_fld_phone'),
								'companyTypeItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE')),
								'industryItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'))
							);
							//$sipManagerRequired = true;
							?><script type="text/javascript">
							BX.ready(
								function()
								{
									var entitySelectorId = CRM.Set(
										BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
										'<?=CUtil::JSEscape($selectorID)?>',
										'',
										<?php
											echo CUtil::PhpToJsObject(
												CCrmEntitySelectorHelper::PreparePopupItems(
													$entityType,
													true,
													isset($params['NAME_TEMPLATE']) ?
														$params['NAME_TEMPLATE'] :
														\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
													50,
													array(
														'REQUIRE_REQUISITE_DATA' => true,
														'SEARCH_OPTIONS' => $selectorSearchOptions
													)
												)
											);
										?>,
										false,
										false,
										<?=CUtil::PhpToJsObject($entityType)?>,
										<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
										true,
										{
											requireRequisiteData: true,
											selectorSearchOptions: <?=CUtil::PhpToJSObject($selectorSearchOptions)?>
										}
									);

									BX.CrmEntityEditor.messages =
									{
										'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
										'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
										'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
										'prefPhoneLong': '<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>',
										'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>',
										'tabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>',
										'contactTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>',
										'companyTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>',
										'tabTitleContactRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>',
										'tabTitleCompanyRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>',
										'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>'
									};

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID.'_C')?>',
										{
											'context': '<?=CUtil::JSEscape($context)?>',
											'typeName': 'CONTACT',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addContactButtonID)?>',
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'createUrl': '<?=CUtil::JSEscape(CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0, false))?>',
											'actionName': 'SAVE_CONTACT',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['CONTACT'])?>,
											'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
											'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
											'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
											'rqLinkedId': '<?=CUtil::JSEscape($valEntityType === 'contact' ? $rqLinkedId : 0)?>',
											'bdLinkedId': '<?=CUtil::JSEscape($valEntityType === 'contact' ? $bdLinkedId : 0)?>',
											'skipInitInput': '<?=CUtil::JSEscape($valEntityType !== 'contact' ? 'true' : 'false')?>'

										},
										null,
										<?= (($valEntityType === 'contact') ? 'BX.CrmEntityInfo.create('.CUtil::PhpToJSObject($entityInfo).')' : 'null') ?>
									);

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID).'_CO'?>',
										{
											'context': '<?=CUtil::JSEscape($context)?>',
											'typeName': 'COMPANY',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addCompanyButtonID)?>',
											'buttonChangeIgnore': true,
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'createUrl': '<?=CUtil::JSEscape(CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false))?>',
											'actionName': 'SAVE_COMPANY',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['COMPANY'])?>,
											'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
											'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
											'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
											'rqLinkedId': '<?=CUtil::JSEscape($valEntityType === 'company' ? $rqLinkedId : 0)?>',
											'bdLinkedId': '<?=CUtil::JSEscape($valEntityType === 'company' ? $bdLinkedId : 0)?>',
											'skipInitInput': '<?=CUtil::JSEscape($valEntityType !== 'company' ? 'true' : 'false')?>'
										},
										null,
										<?= (($valEntityType === 'company') ? 'BX.CrmEntityInfo.create('.CUtil::PhpToJSObject($entityInfo).')' : 'null') ?>
									);
								}
							);
						</script><?
						}
						break;
					case 'crm_multiple_client_selector':
						{
							CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
							$params = isset($field['componentParams']) ? $field['componentParams'] : array();
							if(!empty($params))
							{
								$userPermissions = CCrmPerms::GetCurrentUserPermissions();

								$ownerType = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
								$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;

								$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
								$readOnly = isset($params['READ_ONLY']) ? $params['READ_ONLY'] : false;
								$enableLazyLoad = isset($params['ENABLE_LAZY_LOAD']) ? $params['ENABLE_LAZY_LOAD'] : false;

								$entitiesInputName = isset($params['ENTITIES_INPUT_NAME'])
									? $params['ENTITIES_INPUT_NAME'] : 'ENTITY_IDS';
								$enableEntityCreation = isset($params['ENABLE_ENTITY_CREATION'])
									? $params['ENABLE_ENTITY_CREATION'] : false;
								$enableRequisites = isset($params['ENABLE_REQUISITES'])
									? $params['ENABLE_REQUISITES'] : true;

								$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
								$entityCreateUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::ResolveID($entityType), 0, false);
								$entityIDs = isset($params['ENTITY_IDS']) && is_array($params['ENTITY_IDS'])
									? $params['ENTITY_IDS'] : array();

								$selectorSearchOptions = is_array($params['ENTITY_SELECTOR_SEARCH_OPTIONS'])
									? $params['ENTITY_SELECTOR_SEARCH_OPTIONS'] : array();

								$entityInfos = array();
								$entityCount = count($entityIDs);
								$loaderCfg = null;
								if($enableLazyLoad)
								{
									$loader = isset($params['LOADER']) && is_array($params['LOADER'])
										? $params['LOADER'] : array();
									$loaderCfg = array(
										'action'=> isset($loader['ACTION']) ? $loader['ACTION'] : 'GET_CLIENT_INFOS',
										'url'=> isset($loader['URL']) ? $loader['URL'] : ''

									);

									if($entityCount > 0)
									{
										$entityID = $entityIDs[0];
										$isEntityReadPermitted = CCrmAuthorizationHelper::CheckReadPermission(
											$entityType,
											$entityID,
											$userPermissions
										);

										$entityInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
											$entityType,
											$entityID,
											array(
												'ENTITY_EDITOR_FORMAT' => true,
												'IS_HIDDEN' => !$isEntityReadPermitted,
												'REQUIRE_REQUISITE_DATA' => $isEntityReadPermitted && $enableRequisites,
												'REQUIRE_MULTIFIELDS' => $isEntityReadPermitted,
												'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
													$params['NAME_TEMPLATE'] :
													\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
											)
										);
									}
								}
								else
								{
									foreach($entityIDs as $entityID)
									{
										$isEntityReadPermitted = CCrmAuthorizationHelper::CheckReadPermission(
											$entityType,
											$entityID,
											$userPermissions
										);

										$entityInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
											$entityType,
											$entityID,
											array(
												'ENTITY_EDITOR_FORMAT' => true,
												'IS_HIDDEN' => !$isEntityReadPermitted,
												'REQUIRE_REQUISITE_DATA' => $isEntityReadPermitted && $enableRequisites,
												'REQUIRE_MULTIFIELDS' => $isEntityReadPermitted,
												'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
													$params['NAME_TEMPLATE'] :
													\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
											)
										);
									}
								}

								$entitiesInputID = "{$arParams['FORM_ID']}_{$entitiesInputName}";
								?>
								<input type="hidden" id="<?= htmlspecialcharsbx($entitiesInputID) ?>" name="<?= htmlspecialcharsbx($entitiesInputName) ?>" value="<?= implode(',', $entityIDs) ?>" />
								<?

								$viewerContainerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
								$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($viewerContainerID).'" class="crm-offer-info-description"></div>';
								$sipManagerRequired = true;

								?><script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmEntityType.captions =
											{
												"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
												"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
												"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
												"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
												"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
												"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
											};

											BX.CrmClientPanel.messages =
											{
												contact: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
												company: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
												contactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
												companyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>"
											};

											BX.CrmClientRequisitePanelItem.messages =
											{
												bankDetails: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>",
											};

											BX.CrmEntitySummaryView.messages =
											{
												prefContactType: "<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>",
												prefPhone: "<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>",
												prefPhoneLong: "<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>",
												prefEmail: "<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>",
												tabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>",
												contactTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
												companyTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
												tabTitleContactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
												tabTitleCompanyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>",
												bankDetailsTitle: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>"
											};

											BX.CrmCompositeClientSelector.messages = BX.CrmClientPanelGroup.messages =
											{
												selectButton: "<?=GetMessageJS('intarface_form_select')?>",
												createButton: "<?=GetMessageJS('interface_form_add_new_entity')?>"
											};

											BX.CrmClientPanelCommunication.callToFormat = <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>;
											BX.CrmMultipleClientSelector.messages =
											{
												selectButton: "<?=GetMessageJS('intarface_form_select')?>",
												createButton: "<?=GetMessageJS('interface_form_add_new_entity')?>"
											};
											BX.CrmMultipleClientSelector.create(
												"<?=CUtil::JSEscape($field['id'])?>",
												{
													context: "<?=CUtil::JSEscape($context)?>",
													owner: { id: "<?=$ownerID?>", typeName: "<?=CUtil::JSEscape($ownerType)?>" },
													entityType: "<?=CUtil::JSEscape($entityType)?>",
													entityData: <?=CUtil::PhpToJSObject($entityInfos)?>,
													entityCount: <?=$entityCount?>,
													containerId: "<?=CUtil::JSEscape($viewerContainerID)?>",
													entitiesInputId: "<?=CUtil::JSEscape($entitiesInputID)?>",
													enableRequisites: <?=$enableRequisites ? 'true' : 'false'?>,
													selectorSearchOptions: <?=CUtil::PhpToJSObject($selectorSearchOptions)?>,
													enableEntityCreation: <?=$enableEntityCreation ? 'true' : 'false'?>,
													entityCreateUrl: "<?=CUtil::JSEscape($entityCreateUrl)?>",
													readOnly: <?=$readOnly ? 'true' : 'false'?>,
													enableLazyLoad: <?=$enableLazyLoad ? 'true' : 'false'?>,
													loader: <?=$loaderCfg ? CUtil::PhpToJSObject($loaderCfg) : 'null'?>,
													selectorMessages: <?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>
												}
											).layout();
										}
									);
								</script><?
							}
						}
						break;
					case 'crm_single_client_selector':
						{
							CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
							$params = isset($field['componentParams']) ? $field['componentParams'] : array();
							if(!empty($params))
							{
								$userPermissions = CCrmPerms::GetCurrentUserPermissions();

								$ownerType = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
								$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;

								$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
								$readOnly = isset($params['READ_ONLY']) ? $params['READ_ONLY'] : false;

								$requisiteID = isset($params['REQUISITE_ID'])
									? (int)$params['REQUISITE_ID'] : 0;
								$bankDetailID = isset($params['BANK_DETAIL_ID'])
									? (int)$params['BANK_DETAIL_ID'] : 0;

								$entityInputName = isset($params['ENTITY_INPUT_NAME'])
									? $params['ENTITY_INPUT_NAME'] : 'ENTITY_ID';
								$requisiteInputName = isset($params['REQUISITE_INPUT_NAME'])
									? $params['REQUISITE_INPUT_NAME'] : 'REQUISITE_ID';
								$bankDetailInputName = isset($params['BANK_DETAIL_INPUT_NAME'])
									? $params['BANK_DETAIL_INPUT_NAME'] : 'BANK_DETAIL_ID';

								$enableEntityCreation = isset($params['ENABLE_ENTITY_CREATION'])
									? $params['ENABLE_ENTITY_CREATION'] : false;
								$enableRequisites = isset($params['ENABLE_REQUISITES'])
									? $params['ENABLE_REQUISITES'] : true;

								$selectorSearchOptions = is_array($params['ENTITY_SELECTOR_SEARCH_OPTIONS'])
									? $params['ENTITY_SELECTOR_SEARCH_OPTIONS'] : array();

								$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
								$entityCreateUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::ResolveID($entityType), 0, false);
								$entityID = isset($params['ENTITY_ID'])
									? (int)$params['ENTITY_ID'] : 0;

								$entityInfo = null;
								if($entityID > 0)
								{
									$isEntityReadPermitted = CCrmAuthorizationHelper::CheckReadPermission(
										$entityType,
										$entityID,
										$userPermissions
									);

									$entityInfo  = CCrmEntitySelectorHelper::PrepareEntityInfo(
										$entityType,
										$entityID,
										array(
											'ENTITY_EDITOR_FORMAT' => true,
											'REQUIRE_REQUISITE_DATA' => $isEntityReadPermitted && $enableRequisites,
											'REQUIRE_MULTIFIELDS' => $isEntityReadPermitted,
											'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
												$params['NAME_TEMPLATE'] :
												\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
										)
									);
								}

								if($readOnly)
								{
									$requisiteServiceUrl = $entityCreateUrl = $entityInputID = $bankDetailInputID = $requisiteInputID = '';
								}
								else
								{
									$requisiteServiceUrl = isset($params['REQUISITE_SERVICE_URL'])
										? $params['REQUISITE_SERVICE_URL'] : '';
									$entityCreateUrl = isset($params['ENTITY_CREATE_URL'])
										? $params['ENTITY_CREATE_URL'] : '';

									$entityInputID = "{$arParams['FORM_ID']}_{$entityInputName}";
									$requisiteInputID = "{$arParams['FORM_ID']}_{$requisiteInputName}";
									$bankDetailInputID = "{$arParams['FORM_ID']}_{$bankDetailInputName}";
									?>
									<input type="hidden" id="<?= htmlspecialcharsbx($entityInputID) ?>" name="<?= htmlspecialcharsbx($entityInputName) ?>" value="<?= $entityID ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($requisiteInputID) ?>" name="<?= htmlspecialcharsbx($requisiteInputName) ?>" value="<?= $requisiteID ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($bankDetailInputID) ?>" name="<?= htmlspecialcharsbx($bankDetailInputName) ?>" value="<?= $bankDetailID ?>" />
									<?
								}

								$viewerContainerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
								$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($viewerContainerID).'" class="crm-offer-info-description"></div>';
								$sipManagerRequired = true;

								?><script type="text/javascript">
								BX.ready(
									function()
									{
										BX.CrmEntityType.captions =
										{
											"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
											"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
											"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
											"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
											"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
											"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
										};

										BX.CrmClientPanel.messages =
										{
											contact: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
											company: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
											contactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
											companyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>"
										};

										BX.CrmClientRequisitePanelItem.messages =
										{
											bankDetails: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>",
										};

										BX.CrmEntitySummaryView.messages =
										{
											prefContactType: "<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>",
											prefPhone: "<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>",
											prefPhoneLong: "<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>",
											prefEmail: "<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>",
											tabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>",
											contactTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
											companyTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
											tabTitleContactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
											tabTitleCompanyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>",
											bankDetailsTitle: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>"
										};

										BX.CrmCompositeClientSelector.messages = BX.CrmClientPanelGroup.messages =
										{
											selectButton: "<?=GetMessageJS('intarface_form_select')?>",
											createButton: "<?=GetMessageJS('interface_form_add_new_entity')?>"
										};

										BX.CrmClientPanelCommunication.callToFormat = <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>;
										BX.CrmSingleClientSelector.messages =
										{
											selectButton: "<?=GetMessageJS('intarface_form_select')?>",
											createButton: "<?=GetMessageJS('interface_form_add_new_entity')?>"
										};
										BX.CrmSingleClientSelector.create(
											"<?=CUtil::JSEscape($field['id'])?>",
											{
												context: "<?=CUtil::JSEscape($context)?>",
												owner: { id: "<?=$ownerID?>", typeName: "<?=CUtil::JSEscape($ownerType)?>" },
												entityType: "<?=CUtil::JSEscape($entityType)?>",
												entityData: <?=CUtil::PhpToJSObject($entityInfo)?>,
												additionalData:
												{
													requisiteId: <?=$requisiteID?>,
													bankDetailId: <?=$bankDetailID?>
												},
												containerId: "<?=CUtil::JSEscape($viewerContainerID)?>",
												entityInputId: "<?=CUtil::JSEscape($entityInputID)?>",
												requisiteInputId: "<?=CUtil::JSEscape($requisiteInputID)?>",
												bankDetailInputId: "<?=CUtil::JSEscape($bankDetailInputID)?>",
												enableRequisites: <?=$enableRequisites ? 'true' : 'false'?>,
												selectorSearchOptions: <?=CUtil::PhpToJSObject($selectorSearchOptions)?>,
												enableEntityCreation: <?=$enableEntityCreation ? 'true' : 'false'?>,
												entityCreateUrl: "<?=CUtil::JSEscape($entityCreateUrl)?>",
												readOnly: <?=$readOnly ? 'true' : 'false'?>,
												requisiteServiceUrl: "<?=CUtil::JSEscape($requisiteServiceUrl)?>",
												selectorMessages: <?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>
											}
										).layout();
									}
								);
							</script><?
							}
						}
						break;
					case 'crm_composite_client_selector':
						{
							CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
							$params = isset($field['componentParams']) ? $field['componentParams'] : array();

							if(!empty($params))
							{
								$userPermissions = CCrmPerms::GetCurrentUserPermissions();

								$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
								$ownerType = isset($params['OWNER_TYPE']) ? $params['OWNER_TYPE'] : '';
								$ownerID = isset($params['OWNER_ID']) ? (int)($params['OWNER_ID']) : 0;

								$readOnly = isset($params['READ_ONLY']) && (bool)$params['READ_ONLY'];
								$enableMultiplicity = !isset($params['ENABLE_MULTIPLICITY'])
									|| (bool)$params['ENABLE_MULTIPLICITY'];

								$primaryEntityType = isset($params['PRIMARY_ENTITY_TYPE']) ? $params['PRIMARY_ENTITY_TYPE'] : '';
								$primaryEntityID = isset($params['PRIMARY_ENTITY_ID']) ? (int)$params['PRIMARY_ENTITY_ID'] : 0;

								$isEntityReadPermitted = CCrmAuthorizationHelper::CheckReadPermission(
									$primaryEntityType,
									$primaryEntityID,
									$userPermissions
								);

								$selectorSearchOptions = is_array($params['ENTITY_SELECTOR_SEARCH_OPTIONS'])
									? $params['ENTITY_SELECTOR_SEARCH_OPTIONS'] : array();

								$primaryEntityInfo = null;
								if($primaryEntityID > 0)
								{
									$primaryEntityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo(
										$primaryEntityType,
										$primaryEntityID,
										array(
											'ENTITY_EDITOR_FORMAT' => true,
											'IS_HIDDEN' => !$isEntityReadPermitted,
											'REQUIRE_REQUISITE_DATA' => $isEntityReadPermitted,
											'REQUIRE_MULTIFIELDS' => $isEntityReadPermitted,
											'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
												$params['NAME_TEMPLATE'] :
												\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
										)
									);
								}
								$secondaryEntityType = isset($params['SECONDARY_ENTITY_TYPE']) ? $params['SECONDARY_ENTITY_TYPE'] : '';
								$secondaryEntityIDs = isset($params['SECONDARY_ENTITY_IDS']) && is_array($params['SECONDARY_ENTITY_IDS'])
									? $params['SECONDARY_ENTITY_IDS'] : array();
								$secondaryEntityInfos = array();
								foreach($secondaryEntityIDs as $secondaryEntityID)
								{
									$isEntityReadPermitted = CCrmAuthorizationHelper::CheckReadPermission(
										$secondaryEntityType,
										$secondaryEntityID,
										$userPermissions
									);
									$secondaryEntityInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
										$secondaryEntityType,
										$secondaryEntityID,
										array(
											'ENTITY_EDITOR_FORMAT' => true,
											'IS_HIDDEN' => !$isEntityReadPermitted,
											'REQUIRE_REQUISITE_DATA' => $isEntityReadPermitted,
											'REQUIRE_MULTIFIELDS' => $isEntityReadPermitted,
											'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
												$params['NAME_TEMPLATE'] :
												\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
										)
									);
								}

								$requisiteID = isset($params['REQUISITE_ID']) ? (int)$params['REQUISITE_ID'] : 0;
								$bankDetailID = isset($params['BANK_DETAIL_ID']) ? (int)$params['BANK_DETAIL_ID'] : 0;

								if($readOnly)
								{
									$serviceUrl = $requisiteServiceUrl = $createCompanyUrl = $createContactUrl ='';
								}
								else
								{
									$serviceUrl = isset($params['SERVICE_URL'])
										? $params['SERVICE_URL'] : '';
									$requisiteServiceUrl = isset($params['REQUISITE_SERVICE_URL'])
										? $params['REQUISITE_SERVICE_URL'] : '';

									$createCompanyUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false);
									$createContactUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0, false);

									$primaryEntityTypeInputName = isset($params['PRIMARY_ENTITY_TYPE_INPUT_NAME'])
										? $params['PRIMARY_ENTITY_TYPE_INPUT_NAME'] : 'PRIMARY_ENTITY_TYPE';
									$primaryEntityInputName = isset($params['PRIMARY_ENTITY_INPUT_NAME'])
										? $params['PRIMARY_ENTITY_INPUT_NAME'] : 'PRIMARY_ENTITY_ID';
									$secondaryEntitiesInputName = isset($params['SECONDARY_ENTITIES_INPUT_NAME'])
										? $params['SECONDARY_ENTITIES_INPUT_NAME'] : 'SECONDARY_ENTITY_IDS';

									$requisiteInputName = isset($params['REQUISITE_INPUT_NAME']) ? $params['REQUISITE_INPUT_NAME'] : 'REQUISITE_ID';
									$bankDetailInputName = isset($params['BANK_DETAIL_INPUT_NAME']) ? $params['BANK_DETAIL_INPUT_NAME'] : 'BANK_DETAIL_ID';

									$primaryEntityInputID = "{$arParams['FORM_ID']}_{$primaryEntityInputName}";
									$primaryEntityTypeInpuID = "{$arParams['FORM_ID']}_{$primaryEntityTypeInputName}";
									$secondaryEntitiesInputID = "{$arParams['FORM_ID']}_{$secondaryEntitiesInputName}";

									$requisiteInputID = "{$arParams['FORM_ID']}_{$requisiteInputName}";
									$bankDetailInputID = "{$arParams['FORM_ID']}_{$bankDetailInputName}";

									?>
									<input type="hidden" id="<?= htmlspecialcharsbx($primaryEntityTypeInpuID) ?>" name="<?= htmlspecialcharsbx($primaryEntityTypeInputName) ?>" value="<?= $primaryEntityType ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($primaryEntityInputID) ?>" name="<?= htmlspecialcharsbx($primaryEntityInputName) ?>" value="<?= $primaryEntityID ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($secondaryEntitiesInputID) ?>" name="<?= htmlspecialcharsbx($secondaryEntitiesInputName) ?>" value="<?= implode(',', $secondaryEntityIDs) ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($requisiteInputID) ?>" name="<?= htmlspecialcharsbx($requisiteInputName) ?>" value="<?= $requisiteID ?>" />
									<input type="hidden" id="<?= htmlspecialcharsbx($bankDetailInputID) ?>" name="<?= htmlspecialcharsbx($bankDetailInputName) ?>" value="<?= $bankDetailID ?>" />
									<?
								}

								$viewerContainerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
								$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($viewerContainerID).'" class="crm-offer-info-description"></div>';
								$sipManagerRequired = true;

								$customMessages = isset($params['CUSTOM_MESSAGES']) && is_array($params['CUSTOM_MESSAGES'])
									? $params['CUSTOM_MESSAGES'] : array();
								$secondaryEntityMessages = array(
									'header' => isset($customMessages['SECONDARY_ENTITY_HEADER'])
										? $customMessages['SECONDARY_ENTITY_HEADER'] : '',
									'markingTitle' => isset($customMessages['SECONDARY_ENTITY_MARKING_TITLE'])
										? $customMessages['SECONDARY_ENTITY_MARKING_TITLE'] : ''
								);
								?><script type="text/javascript">
									BX.ready(
										function()
										{
											BX.CrmEntityType.captions =
											{
												"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
												"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
												"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
												"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
												"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
												"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
											};

											BX.CrmClientPanel.messages =
											{
												contact: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
												company: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
												contactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
												companyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>"
											};

											BX.CrmClientRequisitePanelItem.messages =
											{
												bankDetails: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>",
											};

											BX.CrmEntitySummaryView.messages =
											{
												prefContactType: "<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>",
												prefPhone: "<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>",
												prefPhoneLong: "<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>",
												prefEmail: "<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>",
												tabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>",
												contactTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>",
												companyTabTitleAbout: "<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>",
												tabTitleContactRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>",
												tabTitleCompanyRequisites: "<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>",
												bankDetailsTitle: "<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>"
											};

											BX.CrmCompositeClientSelector.messages = BX.CrmClientPanelGroup.messages =
											{
												selectButton: "<?=GetMessageJS('intarface_form_select')?>",
												createButton: "<?=GetMessageJS('interface_form_add_new_entity')?>"
											};

											BX.CrmClientPanelCommunication.callToFormat = <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>;

											BX.CrmCompositeClientSelector.create(
												"<?=CUtil::JSEscape($field['id'])?>",
												{
													context: "<?=CUtil::JSEscape($context)?>",
													owner: { id: "<?=$ownerID?>", typeName: "<?=CUtil::JSEscape($ownerType)?>" },
													primaryEntityType: "<?=CUtil::JSEscape($primaryEntityType)?>",
													primaryEntityData: <?=$primaryEntityInfo ? CUtil::PhpToJSObject($primaryEntityInfo) : 'null'?>,
													secondaryEntityType: "<?=CUtil::JSEscape($secondaryEntityType)?>",
													secondaryEntityData: <?=CUtil::PhpToJSObject($secondaryEntityInfos)?>,
													additionalData:
														{
															requisiteId: <?=$requisiteID?>,
															bankDetailId: <?=$bankDetailID?>
														},
													containerId: "<?=CUtil::JSEscape($viewerContainerID)?>",
													primaryEntityTypeInpuId: "<?=CUtil::JSEscape($primaryEntityTypeInpuID)?>",
													primaryEntityInputId: "<?=CUtil::JSEscape($primaryEntityInputID)?>",
													secondaryEntitiesInputId: "<?=CUtil::JSEscape($secondaryEntitiesInputID)?>",
													requisiteInputId: "<?=CUtil::JSEscape($requisiteInputID)?>",
													bankDetailInputId: "<?=CUtil::JSEscape($bankDetailInputID)?>",
													serviceUrl: "<?=CUtil::JSEscape($serviceUrl)?>",
													requisiteServiceUrl: "<?=CUtil::JSEscape($requisiteServiceUrl)?>",
													selectorSearchOptions: <?=CUtil::PhpToJSObject($selectorSearchOptions)?>,
													createCompanyUrl: "<?=CUtil::JSEscape($createCompanyUrl)?>",
													createContactUrl: "<?=CUtil::JSEscape($createContactUrl)?>",
													selectorMessages: <?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
													secondaryEntityMessages: <?=CUtil::PhpToJsObject($secondaryEntityMessages)?>,
													enableMultiplicity: <?=$enableMultiplicity ? 'true' : 'false'?>,
													readOnly: <?=$readOnly ? 'true' : 'false'?>
												}
											).layout();
										}
									);
								</script><?
							}
						}
						break;
					case 'crm_locality_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						$searchInputID = "{$arParams['FORM_ID']}_{$field['id']}";
						$dataInputID = "{$arParams['FORM_ID']}_{$params['DATA_INPUT_NAME']}";
						?><input type="text" class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$field["id"]?>"  name="<?=$field["id"]?>" value="<?=$valEncoded?>"<?=$params?>/>
						<input type="hidden" id="<?=$dataInputID?>" name="<?=$params['DATA_INPUT_NAME']?>" value="<?=htmlspecialcharsbx($params['DATA_VALUE'])?>"/>
						<script type="text/javascript">
							BX.ready(
								function()
								{
									BX.CrmLocalitySearchField.create(
										"<?=$searchInputID?>",
										{
											localityType: "<?=$params['LOCALITY_TYPE']?>",
											serviceUrl: "<?=$params['SERVICE_URL']?>",
											searchInput: "<?=$searchInputID?>",
											dataInput: "<?=$dataInputID?>"
										}
									);
								}
							);
						</script>
						<?
						break;
					default:
						?><input type="text" class="crm-offer-item-inp" name="<?=$field["id"]?>" value="<?=$valEncoded?>"<?=$params?>><?
				endswitch;
			?></div><?
			if ($advancedInfoHTML !== '')
				echo $advancedInfoHTML;
			?></td><!-- "crm-offer-info-right" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
				?></td>
			<td class="crm-offer-last-td"></td><?
		endif;
		?></tr><?
		$fieldCount++;
	endforeach;
	unset($field);
	?><tr id="<?=$sectionNodePrefix?>_buttons" style="visibility: hidden;">
		<td class="crm-offer-info-drg-btn" <?= ($enableFieldDrag ? '' : ' style="display: none;"') ?>></td>
		<td class="crm-offer-info-left"></td>
		<td class="crm-offer-info-right">
			<div class="crm-offer-item-link-wrap">
				<? if ($canCreateUserField): ?>
				<span id="<?=$sectionNodePrefix?>_add_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_field')?></span>
				<? endif; ?>
				<? if ($canCreateSection): ?>
				<span id="<?=$sectionNodePrefix?>_add_section" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_section')?></span>
				<? endif; ?>
				<span id="<?=$sectionNodePrefix?>_restore_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_restore_field')?></span>
			</div>
		</td>
		<td class="crm-offer-info-right-btn"></td>
		<td class="crm-offer-last-td"></td>
	</tr>
	</tbody></table><!-- "crm-offer-info-table" --><?
endforeach;
unset($arSection);

if ($sipManagerRequired)
{
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>",
				"/bitrix/components/bitrix/crm.lead.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
				"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
				"/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			if(typeof(BX.CrmSipManager.messages) === 'undefined')
			{
				BX.CrmSipManager.messages =
				{
					unknownRecipient: "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
					makeCall: "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
				};
			}
		}
	);
</script><?
}

$arFieldSets = isset($arParams['FIELD_SETS']) ? $arParams['FIELD_SETS'] : array();
if(!empty($arFieldSets)):
	foreach($arFieldSets as &$arFieldSet):
		$html = isset($arFieldSet['HTML']) ? $arFieldSet['HTML'] : '';
		if($html === '')
			continue;
		?><div class="bx-crm-view-fieldset">
		<h2 class="bx-crm-view-fieldset-title"><? if (isset($arFieldSet['REQUIRED']) && $arFieldSet['REQUIRED'] === true): ?><span class="required">*</span><? endif; ?><?=isset($arFieldSet['NAME']) ? htmlspecialcharsbx($arFieldSet['NAME']) : ''?></h2>
			<div class="bx-crm-view-fieldset-content">
				<table class="bx-crm-view-fieldset-content-table">
					<tbody>
					<tr>
						<td class="bx-field-value"><?=$html?></td>
					</tr>
				</tbody>
			</table>
		</div>
		</div><?
	endforeach;
	unset($arFieldSet);
endif;
?></div><!-- "crm-offer-main-wrap" --><?

if(isset($arParams['~BUTTONS'])):
	if(isset($arParams['~BUTTONS']['standard_buttons']) && $arParams['~BUTTONS']['standard_buttons']):
		$buttonsTitles = array(
			'saveAndView' => array(
				'value' => GetMessage('interface_form_save_and_view'),
				'title' => GetMessage('interface_form_save_and_view_title')
			),
			'saveAndAdd' => array(
				'value' => GetMessage('interface_form_save_and_add'),
				'title' => GetMessage('interface_form_save_and_add_title')
			),
			'apply' => array(
				'value' => GetMessage('interface_form_apply'),
				'title' => GetMessage('interface_form_apply_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		if (is_array($arParams['~BUTTONS']['standard_buttons_titles']))
		{
			$customTitles = array_replace_recursive($buttonsTitles, $arParams['~BUTTONS']['standard_buttons_titles']);
			if (is_array($customTitles))
				$buttonsTitles = $customTitles;
			unset($customTitles);
		}
		?><div class="ui-btn-container ui-btn-container-center">
		<input class="ui-btn ui-btn-success" type="submit" name="saveAndView" id="<?=$arParams["FORM_ID"]?>_saveAndView" value="<?=htmlspecialcharsbx($buttonsTitles['saveAndView']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['saveAndView']['title'])?>" /><?
		if(isset($arParams['IS_NEW']) && $arParams['IS_NEW'] === true):
			?><input class="ui-btn ui-btn-success" type="submit" name="saveAndAdd" id="<?=$arParams["FORM_ID"]?>_saveAndAdd" value="<?=htmlspecialcharsbx($buttonsTitles['saveAndAdd']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['saveAndAdd']['title'])?>" /><?
		else:
			?><input class="ui-btn ui-btn-light-border" type="submit" name="apply" id="<?=$arParams["FORM_ID"]?>_apply" value="<?=htmlspecialcharsbx($buttonsTitles['apply']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['apply']['title'])?>" /><?
		endif;
		if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
			?><input class="ui-btn ui-btn-link" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" /><?
		endif;
		?></div><?
	elseif(isset($arParams['~BUTTONS']['wizard_buttons']) && $arParams['~BUTTONS']['wizard_buttons']):
		$buttonsTitles = array(
			'continue' => array(
				'value' => GetMessage('interface_form_continue'),
				'title' => GetMessage('interface_form_continue_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		?><div class="ui-btn-container ui-btn-container-center">
			<input class="ui-btn ui-btn-success" type="submit" name="continue" id="<?=$arParams["FORM_ID"]?>_continue" value="<?=htmlspecialcharsbx($buttonsTitles['continue']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['continue']['title'])?>" /><?
			if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
				?><input class="ui-btn ui-btn-link" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" /><?
			endif;
		?></div><?
	elseif(isset($arParams['~BUTTONS']['dialog_buttons']) && $arParams['~BUTTONS']['dialog_buttons']):
		$buttonsTitles = array(
			'save' => array(
				'value' => GetMessage('interface_form_save'),
				'title' => GetMessage('interface_form_save_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		?><div class="ui-btn-container ui-btn-container-center">
			<input class="ui-btn ui-btn-success" type="submit" name="save" id="<?=$arParams["FORM_ID"]?>_save" value="<?=htmlspecialcharsbx($buttonsTitles['save']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['save']['title'])?>" />
			<input class="ui-btn ui-btn-link" type="submit" name="cancel" id="<?=$arParams["FORM_ID"]?>_cancel" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" />
		</div><?
	endif;
	if(isset($arParams['~BUTTONS']['custom_html'])):
		echo $arParams['~BUTTONS']['custom_html'];
	endif;
endif;

if($arParams['SHOW_FORM_TAG']):
	?></form><?
endif;

if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div style="display:none">

	<div id="form_settings_<?=$arParams["FORM_ID"]?>">
		<table width="100%">
			<tr class="section">
				<td colspan="2"><?echo GetMessage("interface_form_tabs")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
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
				<td colspan="2"><?echo GetMessage("intarface_form_fields")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
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
		</table>
	</div>

</div><?
endif; //$GLOBALS['USER']->IsAuthorized()

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
endif;

if($arResult["OPTIONS"]["expand_tabs"] == "Y"):
	?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.ToggleTabs(true);});<?
endif;
?>bxForm_<?=$arParams["FORM_ID"]?>.Initialize();
bxForm_<?=$arParams["FORM_ID"]?>.EnableSigleSubmit(true);
</script><?

?></div><!-- bx-interface-form --><?
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmFormSectionSetting.messages =
			{
				deleteButton: "<?=CUtil::JSEscape(GetMessage('intarface_form_del'))?>",
				createTextFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_string_field_menu_item'))?>",
				createDoubleFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_double_field_menu_item'))?>",
				createBooleanFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_boolean_field_menu_item'))?>",
				createDatetimeFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_datetime_field_menu_item'))?>",
				createSectionMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_section_menu_item'))?>",
				sectionTitlePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_section_ttl_placeholder'))?>",
				sectionDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_title'))?>",
				sectionDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldSetting.messages =
			{
				saveButton: "<?=CUtil::JSEscape(GetMessage('interface_form_save'))?>",
				cancelButton: "<?=CUtil::JSEscape(GetMessage('interface_form_cancel'))?>",
				inShortListOptionTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_in_short_list_option_title'))?>",
				deleteButton: "<?=CUtil::JSEscape(GetMessage('interface_form_hide'))?>",
				fieldNamePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_field_name_placeholder'))?>",
				fieldDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_title'))?>",
				fieldDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldRenderer.messages =
			{
				addSectionButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_section'))?>",
				addFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_field'))?>",
				restoreFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_restore_field'))?>"
			};

			BX.CrmFormSettingManager.messages =
			{
				newFieldName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_field_name'))?>",
				newSectionName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_section_name'))?>",
				resetMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_reset_menu_item'))?>",
				saveForAllMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_save_for_all_menu_item'))?>",
				sectionHasRequiredFields: "<?=CUtil::JSEscape(GetMessage('interface_form_section_has_required_fields'))?>",
				saved: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_saved'))?>",
				undo: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_undo_change'))?>"
			};

			var isSettingsApplied = <?=$arResult['OPTIONS']['settings_disabled'] !== 'Y' ? 'true' : 'false'?>;

			BX.CrmEditFormManager.create(
				"<?=$formIDLower?>",
				{
					formId: "<?=$arParams['FORM_ID']?>",
					form: bxForm_<?=$arParams["FORM_ID"]?>,
					mode: <?=mb_strtoupper($arParams["MODE"]) === 'VIEW' ? 'BX.CrmFormMode.view' : 'BX.CrmFormMode.edit'?>,
					prefix: "<?=CUtil::JSEscape($prefix)?>",
					sectionWrapperId: "<?=$sectionWrapperID?>",
					undoContainerId: "<?=$undoContainerID?>",
					tabId: "tab_1",
					metaData: window["bxForm_<?=$arParams['FORM_ID']?>"]["oTabsMeta"],
					hiddenMetaData: isSettingsApplied ? window["bxForm_<?=$arParams['FORM_ID']?>"]["oFields"] : [],
					isSettingsApplied: isSettingsApplied,
					canCreateUserField: <?=($canCreateUserField ? 'true' : 'false')?>,
					canCreateSection: <?=($canCreateSection ? 'true' : 'false')?>,
					canSaveSettingsForAll: <?=CCrmAuthorizationHelper::CanEditOtherSettings() ? 'true' : 'false'?>,
					userFieldEntityId: "<?=isset($arParams['USER_FIELD_ENTITY_ID']) ? $arParams['USER_FIELD_ENTITY_ID'] : ''?>",
					userFieldServiceUrl: "<?=isset($arParams['USER_FIELD_SERVICE_URL']) ? $arParams['USER_FIELD_SERVICE_URL'] : ''?>",
					enableInShortListOption: <?= ((isset($arParams['ENABLE_IN_SHORT_LIST_OPTION']) && $arParams['ENABLE_IN_SHORT_LIST_OPTION'] === 'Y') ? 'true' : 'false') ?>,
					isModal: <?= ((isset($arParams['IS_MODAL']) && $arParams['IS_MODAL'] === 'Y') ? 'true' : 'false') ?>,
					dragPriority: <?= (isset($settings['DRAG_PRIORITY']) ? (int)$settings['DRAG_PRIORITY'] : -1) ?>,
					enableFieldDrag: <?=($enableFieldDrag ? 'true' : 'false')?>,
					enableSectionDrag: <?=($enableSectionDrag ? 'true' : 'false')?>,
					serverTime: "<?=time() + CTimeZone::GetOffset()?>"
				}
			);
		}
	);
</script><?
if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;
