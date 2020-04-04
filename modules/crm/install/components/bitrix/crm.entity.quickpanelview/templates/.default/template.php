<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');

$entityTypeName = $arResult['ENTITY_TYPE_NAME'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$entityID = $arResult['ENTITY_ID'];
$entityFields = $arResult['ENTITY_FIELDS'];
$entityData = $arResult['ENTITY_DATA'];

$entityContext = $arResult['ENTITY_CONTEXT'];

$guid = $arResult['GUID'];
$innerWrapperClassName = 'crm-lead-header-table crm-lead-header-offer';
$quickPanelHeaderIcon = '';
if($entityTypeID === CCrmOwnerType::Contact)
{
	$innerWrapperClassName .= ' crm-lead-header-table-contact';
}
elseif($entityTypeID === CCrmOwnerType::Company)
{
	$innerWrapperClassName .= ' crm-lead-header-table-company';
}
elseif($entityTypeID === CCrmOwnerType::Deal)
{
	$innerWrapperClassName .= ' crm-lead-header-table-deal';
}
elseif($entityTypeID === CCrmOwnerType::Lead)
{
	$innerWrapperClassName .= ' crm-lead-header-table-lid';
}
elseif($entityTypeID === CCrmOwnerType::Invoice)
{
	$innerWrapperClassName .= ' crm-lead-header-table-bill';
}
$config = $arResult['CONFIG'];

$isExpanded = $config['expanded'] === 'Y';
$isFixed = $config['fixed'] === 'Y';

$headerConfig = array();

if(!function_exists('__CrmQuickPanelViewRenderClient'))
{
	function __CrmQuickPanelViewRenderClient($fieldID, $entityInfo, array $data, $isMultiple, $isTopmost, $panelID)
	{
		$options = isset($entityInfo['MULTI_FIELDS_OPTIONS']) ? $entityInfo['MULTI_FIELDS_OPTIONS'] : array();
		$options['TOPMOST'] = $isTopmost;

		if(!$isMultiple)
		{
			$enableMultifields = isset($data['ENABLE_MULTIFIELDS']) ? (bool)$data['ENABLE_MULTIFIELDS'] : true;
			$options['ENABLE_MULTIFIELDS'] = $enableMultifields;

			if($enableMultifields && isset($entityInfo['FM']))
			{
				$data['FM'] = $entityInfo['FM'];
			}

			CCrmViewHelper::RenderClientSummaryPanel($data, $options);
		}
		else
		{
			$options['COUNT'] = isset($data['childCount']) ? $data['childCount'] : 0;
			$options['SELECTED_INDEX'] = isset($data['currentChildIndex']) ? $data['currentChildIndex'] : 0;
			$children = array();
			foreach($data['children'] as $child)
			{
				$childData = $child['data'];

				if(!(isset($childData['PREFIX']) && $childData['PREFIX'] !== ''))
				{
					$childData['PREFIX'] = strtolower("{$panelID}_{$fieldID}");
				}
				$enableMultifields = isset($childData['ENABLE_MULTIFIELDS'])
					? (bool)$childData['ENABLE_MULTIFIELDS'] : true;
				$options['ENABLE_MULTIFIELDS'] = $enableMultifields;

				if($enableMultifields && isset($entityInfo['FM']))
				{
					$childData['FM'] = $entityInfo['FM'];
				}
				$children[] = $childData;
			}

			CCrmViewHelper::RenderMultipleClientSummaryPanel($children, $options);
		}
	}
}

if(!function_exists('__CrmQuickPanelViewRenderSection'))
{
	function __CrmQuickPanelViewRenderSection($sectionID, &$config, &$entityData, &$entityFields, &$entityContext, $panelID)
	{
		if(!isset($config[$sectionID]))
		{
			return;
		}

		$sectionConfig = $config[$sectionID];
		$sectionConfig = $sectionConfig !== '' ? explode(',', $sectionConfig) : array();

		foreach($sectionConfig as $fieldID)
		{
			$fieldID = trim($fieldID);
			if(!isset($entityData[$fieldID]))
			{
				continue;
			}

			$fieldData = $entityData[$fieldID];
			$type = isset($fieldData['type']) ? $fieldData['type'] : '';
			$data = isset($fieldData['data']) ? $fieldData['data'] : array();
			$enableCaption = isset($fieldData['enableCaption']) ? $fieldData['enableCaption'] : true;
			$editable = $enableEditButton = isset($fieldData['editable']) ? $fieldData['editable'] : false;
			$visible = isset($fieldData['visible']) ? $fieldData['visible'] : true;

			$containerID = $panelID.'_'.$sectionID.'_'.strtolower($fieldID);
			echo '<tr id="', htmlspecialcharsbx($containerID), '"',
				$visible ? '' : ' style="display:none;"',
				'>';

			echo '<td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move"><div class="crm-lead-header-inner-move-btn"></div></td>';
			if($enableCaption)
			{
				echo '<td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title">';
				echo isset($fieldData['caption']) ? htmlspecialcharsbx($fieldData['caption']) : $fieldID;
				echo '</td>';
				if($sectionID !== 'bottom')
				{
					echo '<td class="crm-lead-header-inner-cell">';
				}
				else
				{
					echo '<td class="crm-lead-header-inner-cell crm-lead-header-com-cell">';
				}
			}
			else
			{
				if($sectionID !== 'bottom')
				{
					echo '<td class="crm-lead-header-inner-cell crm-lead-header-inf-block" colspan="2">';
				}
				else
				{
					echo '<td class="crm-lead-header-inner-cell crm-lead-header-com-cell crm-lead-header-inf-block" colspan="2">';
				}
			}

			if($type === 'datetime' || $type === 'date')
			{
				echo '<div class="crm-lead-header-date-wrapper">';
				echo '<div class="crm-lead-header-date-view-wrapper">', isset($data['text']) ? htmlspecialcharsbx($data['text']) : '', '</div>';
				echo '<div class="crm-lead-header-date-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			elseif($type === 'boolean')
			{
				if(isset($data['baseType']) && $data['baseType'] === 'char')
				{
					$checked = isset($data['value']) && strtoupper($data['value']) === 'Y';
				}
				else
				{
					$checked = isset($data['value']) && $data['value'] > 0;
				}
				echo '<div class="crm-lead-header-boolean-wrapper">';
				echo '<div class="crm-lead-header-boolean-view-wrapper">', GetMessage($checked ? 'MAIN_YES' : 'MAIN_NO'), '</div>';
				echo '<div class="crm-lead-header-boolean-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			elseif($type === 'enumeration')
			{
				echo '<div class="crm-lead-header-enumeration-wrapper">';
				echo '<div class="crm-lead-header-enumeration-view-wrapper">', $data['text'] !== '' ? htmlspecialcharsbx($data['text']) : GetMessage('CRM_ENTITY_QPV_CONTROL_NOT_SELECTED'), '</div>';
				echo '<div class="crm-lead-header-enumeration-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			elseif($type === 'link')
			{
				echo '<div class="crm-lead-header-link-wrapper">';
				$text = isset($data['text']) ? htmlspecialcharsbx($data['text']) : '';
				$url = isset($data['url']) ? htmlspecialcharsbx($data['url']) : '';
				echo '<a class="crm-link" target="_blank" href="', $url, '">', $text, '</a>';
				echo '</div>';
			}
			elseif($type === 'multiField')
			{
				$typeName = isset($data['type']) ? $data['type'] : array();
				$options = isset($entityContext['MULTI_FIELDS_OPTIONS'])? $entityContext['MULTI_FIELDS_OPTIONS'] : array();
				$options['TOPMOST'] = true;

				echo CCrmViewHelper::PrepareFormMultiField(
					$entityFields,
					$typeName,
					strtolower($panelID).'_'.uniqid(),
					null,
					$options
				);
			}
			elseif($type === 'address')
			{
				$lines = isset($data['lines']) ? $data['lines'] : array();
				$lineQty = count($lines);

				if($lineQty > 0)
				{
					if($sectionID === 'bottom')
					{
						echo '<div class="crm-lead-header-lhe-wrapper">';
						echo '<div class="crm-lead-header-lhe-view-wrapper">';
						echo implode(', ', $lines);
						echo '</div>';
						echo '</div>';
					}
					else
					{
						$className = $lineQty > 1
							? "crm-client-contacts-block-text crm-client-contacts-block-text-list"
							: "crm-client-contacts-block-text";
						echo '<span class="', $className , '">';
						echo '<span class="crm-client-contacts-block-address">', $lines[0], '</span>';
						if($lineQty > 1)
						{
							echo '<span class="crm-client-contacts-block-text-list-icon"></span>';
						}
						echo '</span>';
					}
				}
			}
			elseif($type === 'responsible')
			{
				if($enableEditButton)
				{
					$enableEditButton = false;
				}

				$guid = strtolower($panelID).'_'.strtolower($data['fieldID']).'_'.uniqid();
				CCrmViewHelper::RenderResponsiblePanel(
					array(
						'FIELD_ID' => $data['fieldID'],
						'CAPTION' => isset($fieldData['caption']) ? $fieldData['caption'] : '',
						'USER_ID' => $data['userID'],
						'NAME' => $data['name'],
						'PHOTO' => $data['photoID'],
						'PHOTO_URL' => $data['photoUrl'],
						'WORK_POSITION' => $data['position'],
						'USER_PROFILE_URL_TEMPLATE' => $data['profileUrlTemplate'],
						'PREFIX' => $guid,
						'EDITABLE' => $editable,
						'INSTANT_EDITOR_ID' => $data['editorID'],
						'SERVICE_URL' => $data['serviceUrl'],
						'USER_INFO_PROVIDER_ID' => $data['userInfoProviderId'],
						'ENABLE_LAZY_LOAD'=> true,
						'USER_SELECTOR_NAME' => $guid
					)
				);
			}
			elseif($type === 'composite_client')
			{
				//region Primary
				$primaryConfig = isset($fieldData['primaryConfig']) ? $fieldData['primaryConfig'] : array();
				$primaryEntityTypeName = isset($primaryConfig['entityTypeName']) ? $primaryConfig['entityTypeName'] : '';
				$primaryKey = $primaryEntityTypeName === CCrmOwnerType::CompanyName ? 'COMPANY_INFO' : 'CONTACT_INFO';
				$primaryEntityInfo = isset($entityContext[$primaryKey]) ? $entityContext[$primaryKey] : null;

				if(is_array($primaryEntityInfo))
				{
					__CrmQuickPanelViewRenderClient(
						"PRIMARY_{$fieldID}",
						$primaryEntityInfo,
						isset($primaryConfig['data']) ? $primaryConfig['data'] : array(),
						false,
						true,
						$panelID
					);
				}
				//endregion
				//region Secondary
				$secondaryConfig = isset($fieldData['secondaryConfig']) ? $fieldData['secondaryConfig'] : array();
				$secondaryEntityTypeName = isset($secondaryConfig['entityTypeName']) ? $secondaryConfig['entityTypeName'] : '';
				$secondaryKey = $secondaryEntityTypeName === CCrmOwnerType::CompanyName ? 'COMPANY_INFO' : 'CONTACT_INFO';
				$secondaryEntityInfo = isset($entityContext[$secondaryKey]) ? $entityContext[$secondaryKey] : null;

				if(is_array($secondaryEntityInfo))
				{
					__CrmQuickPanelViewRenderClient(
						"SECONDARY_{$fieldID}",
						$secondaryEntityInfo,
						isset($secondaryConfig['data']) ? $secondaryConfig['data'] : array(),
						isset($secondaryEntityInfo['IS_MULTIPLE']) && $secondaryEntityInfo['IS_MULTIPLE'],
						false,
						$panelID
					);
				}
				//endregion
			}
			elseif($type === 'client' || $type === 'multiple_client')
			{
				$isMultiple = $type === 'multiple_client';

				$entityTypeName = isset($fieldData['entityTypeName']) ? $fieldData['entityTypeName'] : '';
				$key = $entityTypeName === CCrmOwnerType::CompanyName ? 'COMPANY_INFO' : 'CONTACT_INFO';
				$entityInfo = isset($entityContext[$key]) ? $entityContext[$key] : null;
				if(is_array($entityInfo))
				{
					__CrmQuickPanelViewRenderClient(
						$fieldID,
						$entityInfo,
						$data,
						$isMultiple,
						true,
						$panelID
					);
				}
			}
			elseif($type === 'html')
			{
				echo '<div class="crm-lead-header-lhe-wrapper">';
				echo '<div class="crm-lead-header-lhe-view-wrapper">', isset($data['html']) ? $data['html'] : '', '</div>';
				echo '<div class="crm-lead-header-lhe-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			elseif($type === 'custom')
			{
				echo '<div class="crm-lead-header-custom-wrapper">';
				if(isset($data['html']))
				{
					echo $data['html'];
				}
				echo '</div>';
			}
			elseif($type === 'money')
			{
				echo '<div class="crm-lead-header-text-wrapper">';
				echo '<div class="crm-lead-header-text-view-wrapper">', isset($data['formatted_sum']) ? $data['formatted_sum'] : '', '</div>';
				echo '<div class="crm-lead-header-text-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			elseif($type === 'text')
			{
				$html = isset($data['text']) ? htmlspecialcharsbx($data['text']) : '';
				if(isset($data['multiline']) && $data['multiline'])
				{
					$html = preg_replace('/(\n)/', '<br/>', $html);
				}

				echo '<div class="crm-lead-header-text-wrapper">';
				echo '<div class="crm-lead-header-text-view-wrapper">', $html, '</div>';
				echo '<div class="crm-lead-header-text-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			else
			{
				echo '<div class="crm-lead-header-text-wrapper">';
				echo '<div class="crm-lead-header-text-view-wrapper">', isset($data['text']) ? htmlspecialcharsbx($data['text']) : '', '</div>';
				echo '<div class="crm-lead-header-text-edit-wrapper" style="display: none;"></div>';
				echo '</div>';
			}
			echo '</td>';

			echo '<td class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del">';
			//echo '<div class="crm-lead-header-inner-del-btn"></div>';
			if($enableEditButton)
			{
				echo '<div class="crm-lead-header-inner-edit-btn"></div>';
			}
			echo '</td>';

			echo '</tr>';
		}
	}
}
?><div id="<?="{$guid}_placeholder"?>" class="crm-lead-header-table-placeholder">
<div id="<?="{$guid}_wrap"?>" class="crm-lead-header-table-wrap">
	<div class="crm-lead-header-table-inner-wrap">
		<table id="<?="{$guid}_inner_wrap"?>" class="<?=$innerWrapperClassName?>">
		<tbody>
			<tr id="<?="{$guid}_header"?>">
				<td class="crm-lead-header-header" colspan="3">
					<div class="crm-lead-header-header-left"><div class="crm-lead-header-left-inner">
					<span class="crm-lead-header-icon"></span><?
					if($entityTypeID === CCrmOwnerType::Contact):
						$formattedName = isset($entityFields['FORMATTED_NAME']) ? $entityFields['FORMATTED_NAME'] : '';
						if($arResult['HEAD_IMAGE_URL'] !== ''):
							?><span class="crm-lead-header-img">
								<img alt="" src="<?=htmlspecialcharsbx($arResult['HEAD_IMAGE_URL'])?>" />
							</span><?
						endif;
					?><div class="crm-lead-header-title">
						<span class="crm-lead-header-title-text"><?=$formattedName?></span>
					</div><?
					elseif($entityTypeID === CCrmOwnerType::Company):
						$title = $arResult['HEAD_TITLE'];
						$headerConfig['TITLE'] = array('fieldId' => $arResult['HEAD_TITLE_FIELD_ID']);
						if($arResult['HEAD_IMAGE_URL'] !== ''):
						?><div class="crm-lead-header-company-img">
							<img alt="" src="<?=htmlspecialcharsbx($arResult['HEAD_IMAGE_URL'])?>" />
						</div><?
						endif;
						?><div class="crm-lead-header-company-title">
							<div id="<?="{$guid}_title"?>" class="crm-lead-header-title">
								<span class="crm-lead-header-title-text"><?=$title?></span>
								<span class="crm-lead-header-title-edit-wrapper" style="display: none;"></span>
								<div class="crm-lead-header-title-edit"></div>
							</div>
						</div><?
					elseif($entityTypeID === CCrmOwnerType::Deal || $entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Quote || $entityTypeID === CCrmOwnerType::Invoice):
						$title = $arResult['HEAD_TITLE'];
						$headerConfig['TITLE'] = array('fieldId' => $arResult['HEAD_TITLE_FIELD_ID']);
						?><div id="<?="{$guid}_title"?>" class="crm-lead-header-title">
							<span class="crm-lead-header-title-text"><?=$title?></span>
						<span class="crm-lead-header-title-edit-wrapper" style="display: none;"></span>
							<div class="crm-lead-header-title-edit"></div>
						</div><?
					endif;
					?></div></div>
					<div class="crm-lead-header-header-right"><div class="crm-lead-header-right-inner"><?
						if ($arParams['SHOW_STATUS_ACTION'] !== 'N')
						{
							if($entityTypeID === CCrmOwnerType::Deal || $entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Quote || $entityTypeID === CCrmOwnerType::Invoice):
								$headerConfig['SUM'] = array('fieldId' => $arResult['HEAD_SUM_FIELD_ID']);
								?><div id="<?="{$guid}_progress"?>" class="crm-lead-header-status">
									<span id="<?=htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND_CONTAINER_ID'])?>" class="crm-lead-header-status-title"><?=htmlspecialcharsbx($arResult['HEAD_PROGRESS_LEGEND'])?></span>
										<div class="crm-detail-stage"><?=$arResult['HEAD_PROGRESS_BAR']?></div>
										<span id="<?="{$guid}_sum"?>" class="crm-lead-header-status-sum"><?=GetMessage('CRM_ENTITY_QPV_SUM_HEADER')?>: <span class="crm-lead-header-status-sum-num"><?=$arResult['HEAD_FORMATTED_SUM']?></span></span>
								</div><?
							endif;
						}
						?><div class="crm-lead-header-contact-btns">
							<span id="<?="{$guid}_menu_btn"?>" class="crm-lead-header-contact-btn crm-lead-header-contact-btn-menu"></span>
							<span id="<?="{$guid}_pin_btn"?>" class="crm-lead-header-contact-btn <?=$isFixed ? 'crm-lead-header-contact-btn-pin' : 'crm-lead-header-contact-btn-unpin'?>"></span>
							<span id="<?="{$guid}_toggle_btn"?>" class="crm-lead-header-contact-btn <?=$isExpanded ? 'crm-lead-header-contact-btn-open' : 'crm-lead-header-contact-btn-close'?>"></span>
						</div>
					</div></div>
				</td>
			</tr>
			<tr>
				<td class="crm-lead-header-white" colspan="3"></td>
			</tr>
			<tr>
				<td class="crm-lead-header-blue" colspan="3"></td>
			</tr>
			<tr>
				<td class="crm-lead-header-cell">
					<table id="<?="{$guid}_left_container"?>" class="crm-lead-header-inner-table">
						<tbody>
							<colgroup>
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title" />
								<col class="crm-lead-header-inner-cell" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del" />
							</colgroup>
							<?__CrmQuickPanelViewRenderSection('left', $config, $entityData, $entityFields, $entityContext, $guid);?>
						</tbody>
					</table>
				</td>
				<td class="crm-lead-header-cell">
					<table id="<?="{$guid}_center_container"?>" class="crm-lead-header-inner-table">
						<tbody>
							<colgroup>
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title" />
								<col class="crm-lead-header-inner-cell" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del" />
							</colgroup>
							<?__CrmQuickPanelViewRenderSection('center', $config, $entityData, $entityFields, $entityContext, $guid);?>
						</tbody>
					</table>
				</td>
				<td class="crm-lead-header-cell">
					<table id="<?="{$guid}_right_container"?>" class="crm-lead-header-inner-table">
						<tbody>
							<colgroup>
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title" />
								<col class="crm-lead-header-inner-cell" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del" />
							</colgroup>
							<?__CrmQuickPanelViewRenderSection('right', $config, $entityData, $entityFields, $entityContext, $guid);?>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td class="crm-lead-header-cell crm-lead-header-comments" colspan="3">
					<table id="<?="{$guid}_bottom_container"?>" class="crm-lead-header-inner-table">
						<tbody>
							<colgroup>
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-move" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-title" />
								<col class="crm-lead-header-inner-cell crm-lead-header-com-cell" />
								<col class="crm-lead-header-inner-cell crm-lead-header-inner-cell-del" />
							</colgroup>
							<?__CrmQuickPanelViewRenderSection('bottom', $config, $entityData, $entityFields, $entityContext, $guid);?>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	</div>
</div>
</div>
<div id="<?="{$guid}_message_wrap"?>"></div>
<?

$sipData = isset($entityContext['SIP_MANAGER_CONFIG']) ? $entityContext['SIP_MANAGER_CONFIG'] : array();
if(!empty($sipData)):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				var mgr = BX.CrmSipManager.getCurrent();<?
				foreach($sipData as $item):
				?>
				mgr.setServiceUrl(
					"CRM_<?=CUtil::JSEscape($item['ENTITY_TYPE'])?>",
					"<?=isset($item['SERVICE_URL']) ? CUtil::JSEscape($item['SERVICE_URL']) : ''?>"
				);<?
				endforeach;
				?>
				if(typeof(BX.CrmSipManager.messages) === 'undefined')
				{
					BX.CrmSipManager.messages =
					{
						"unknownRecipient": "<?= GetMessageJS('CRM_ENTITY_QPV_SIP_MGR_UNKNOWN_RECIPIENT')?>",
						"makeCall": "<?= GetMessageJS('CRM_ENTITY_QPV__SIP_MGR_MAKE_CALL')?>"
					};
				}
			}
	);
</script><?
endif;
?><script type="text/javascript">
	BX.ready(
		function() {
			BX.CrmQuickPanelModel.messages =
			{
				notSelected: "<?=GetMessageJS('CRM_ENTITY_QPV_NOT_SELECTED')?>"
			};

			BX.CrmQuickPanelItem.messages =
			{
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_EDIT_CONTEXT_MENU_ITEM'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_DELETE_CONTEXT_MENU_ITEM'))?>",
				deletionConfirmation: "<?=GetMessageJS('CRM_ENTITY_QPV_DELETION_CONFIRMATION')?>"
			};

			BX.CrmQuickPanelControl.messages =
			{
				dataNotSaved: "<?=GetMessageJS('CRM_ENTITY_QPV_CONTROL_FIELD_DATA_NOT_SAVED')?>",
				notSelected: "<?=GetMessageJS('CRM_ENTITY_QPV_NOT_SELECTED')?>",
				yes: "<?=GetMessageJS('MAIN_YES')?>",
				no: "<?=GetMessageJS('MAIN_NO')?>"
			};

			BX.CrmQuickPanelResponsible.messages =
			{
				change: "<?=GetMessageJS('CRM_ENTITY_QPV_RESPONSIBLE_CHANGE')?>"
			};

			BX.CrmQuickPanelClientInfo.messages =
			{
				contactNotSelected: "<?=GetMessageJS('CRM_ENTITY_QPV_CONTACT_NOT_SELECTED')?>",
				companyNotSelected: "<?=GetMessageJS('CRM_ENTITY_QPV_COMPANY_NOT_SELECTED')?>"
			};

			BX.CrmQuickPanelView.messages =
			{
				resetMenuItem: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_RESET_MENU_ITEM'))?>",
				saveForAllMenuItem: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_SAVE_FOR_ALL_MENU_ITEM'))?>",
				resetForAllMenuItem: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_RESET_FOR_ALL_MENU_ITEM'))?>",
				dragDropErrorTitle: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_DRAG_DROP_ERROR_TITLE'))?>",
				dragDropErrorFieldNotSupported: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_DRAG_DROP_ERROR_FIELD_NOT_SUPPORTED'))?>",
				dragDropErrorFieldAlreadyExists: "<?=CUtil::JSEscape(GetMessage('CRM_ENTITY_QPV_DRAG_DROP_ERROR_FIELD_ALREADY_EXISTS'))?>"
			};

			BX.CrmQuickPanelView.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					entityTypeName: "<?=CUtil::JSEscape($entityTypeName)?>",
					entityId: <?=CUtil::JSEscape($entityID)?>,
					prefix: "<?=CUtil::JSEscape($guid)?>",
					canSaveSettingsForAll: <?=$arResult['CAN_EDIT_OTHER_SETTINGS'] ? 'true' : 'false'?>,
					formId: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					entityData: <?=CUtil::PhpToJSObject($arResult['ENTITY_DATA'])?>,
					config: <?=CUtil::PhpToJSObject($config)?>,
					headerConfig: <?=CUtil::PhpToJSObject($headerConfig)?>,
					enableInstantEdit: <?=$arResult['ENABLE_INSTANT_EDIT'] ? 'true' : 'false'?>,
					serviceUrl: "<?='/bitrix/components/bitrix/crm.entity.quickpanelview/settings.php?'.bitrix_sessid_get()?>"
				}
			);

			BX.CrmDragDropBin.messages =
			{
				prompting: "<?=GetMessageJS("CRM_ENTITY_QPV_DD_BIN_PROMPTING")?>"
			};
			BX.CrmDragDropBin.getInstance().showPromptingIfRequired(BX("<?="{$guid}_message_wrap"?>"));
		}
	);
</script>