<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$entity = $arResult['ENTITY'];
$dataItem = CCrmMobileHelper::PrepareActivityData($entity);
//$typeInfos = CCrmFieldMulti::GetEntityTypes();

$typeID = $entity['TYPE_ID'];
$direction = $entity['DIRECTION'];
$title = isset($entity['SUBJECT']) ? $entity['SUBJECT'] : '';
$ownerTypeID = $entity['OWNER_TYPE_ID'];
$ownerTitle = $entity['OWNER_TITLE'];
$ownerShowUrl = $entity['OWNER_SHOW_URL'];

//DESCRIPTION
$fullDescription = isset($entity['DESCRIPTION']) ? $entity['DESCRIPTION'] : '';
$descriptionType = isset($entity['DESCRIPTION_TYPE']) ? intval($entity['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;
if($descriptionType === CCrmContentType::BBCode)
{
	$bbCodeParser = new CTextParser();
	$fullDescription = $bbCodeParser->convertText($fullDescription);
}
elseif($descriptionType !== CCrmContentType::Html)
{
	$fullDescription = htmlspecialcharsbx($fullDescription);
}

$description = '';
$descriptionCut = '';
$hasDescription = CCrmMobileHelper::PrepareCut($fullDescription, $description, $descriptionCut);
$location = isset($entity['LOCATION']) ? $entity['LOCATION'] : '';
$direction = $entity['DIRECTION'];

$storageTypeID = $entity['STORAGE_TYPE_ID'];
$webdavElements =  isset($entity['WEBDAV_ELEMENTS']) ? $entity['WEBDAV_ELEMENTS'] : array();
$files =  isset($entity['FILES']) ? $entity['FILES'] : array();
$diskFiles =  isset($entity['DISK_FILES']) ? $entity['DISK_FILES'] : array();

if(!function_exists('__CrmActivityViewPrepareNameForJson'))
{
	function __CrmActivityViewPrepareNameForJson($string)
	{
		if(!\Bitrix\Main\Application::getInstance()->isUtfMode())
		{
			return \Bitrix\Main\Text\Encoding::convertEncodingArray($string, SITE_CHARSET, 'UTF-8');
		}
		return $string;
	}
}

?><div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_head_title_pic">
		<img src="<?=htmlspecialcharsbx($dataItem['VIEW_IMAGE_URL'])?>" />
		<span><?=htmlspecialcharsbx($title)?></span>
		<?if($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Email):
			$msgID = '';
			if($typeID === CCrmActivityType::Call)
				$msgID = $direction === CCrmActivityDirection::Incoming ? 'M_CRM_ACTIVITY_VIEW_CALL_IN_LEGEND' : 'M_CRM_ACTIVITY_VIEW_CALL_OUT_LEGEND';
			else
				$msgID = $direction === CCrmActivityDirection::Incoming ? 'M_CRM_ACTIVITY_VIEW_EMAIL_IN_LEGEND' : 'M_CRM_ACTIVITY_VIEW_EMAIL_OUT_LEGEND';
			?><span style="font-size: 13px;color: #87949b;"> <?=htmlspecialcharsbx(GetMessage($msgID))?></span><?
		endif;?>
	</div>
	<div class="crm_block_container">
		<div class="crm_contact_info">
			<table><tbody>
				<tr>
					<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_TIME'))?>:</td>
					<td>
						<strong><?=htmlspecialcharsbx($dataItem['START_TIME'])?></strong>
					</td>
				</tr>
				<?if($ownerTypeID === CCrmOwnerType::Deal && $ownerShowUrl !== ''):?>
					<tr><td colspan="2"><hr/></td></tr>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_DEAL_OWNER'))?>:</td>
						<td class="crm_arrow" onclick="BX.CrmMobileContext.redirect({ url:'<?=CUtil::JSEscape($ownerShowUrl)?>' })">
							<strong><?=htmlspecialcharsbx($ownerTitle)?></strong>
						</td>
					</tr>
				<?endif;?>
				<?if($typeID === CCrmActivityType::Email && $entity['CLIENT_SHOW_URL'] !== ''):?>
					<tr><td colspan="2"><hr/></td></tr>
					<tr>
						<td class="crm_vat"><?=
							htmlspecialcharsbx(
								GetMessage(
									$direction === CCrmActivityDirection::Incoming
										? 'M_CRM_ACTIVITY_VIEW_COMMUNICATION_IN'
										: 'M_CRM_ACTIVITY_VIEW_COMMUNICATION_OUT'
								)
							)?>:</td>
						<td>
							<div class="crm_card_image_small" onclick="BX.CrmMobileContext.redirect({ url:'<?=CUtil::JSEscape($entity['CLIENT_SHOW_URL'])?>' })">
								<img src="<?=htmlspecialcharsbx($entity['CLIENT_IMAGE_URL'])?>" />
							</div>
							<a class="crm_card_name_small" href="<?=htmlspecialcharsbx($entity['CLIENT_SHOW_URL'])?>"><?=htmlspecialcharsbx($entity['CLIENT_TITLE'])?></a>
							<span class="crm_card_name_small fwn"><?=htmlspecialcharsbx($entity['CLIENT_COMMUNICATION_VALUE'])?></span>
						</td>
					</tr>
				<?endif;?>
			</tbody></table>
			<br/>
		</div>
	</div>
	<?if(($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting) && $entity['CLIENT_SHOW_URL'] !== ''):?>
		<div class="crm_block_container">
			<div class="crm_card">
				<div class="crm_card_image" onclick="BX.CrmMobileContext.redirect({ url:'<?=CUtil::JSEscape($entity['CLIENT_SHOW_URL'])?>' })">
					<img src="<?=htmlspecialcharsbx($entity['CLIENT_IMAGE_URL'])?>" />
				</div>
				<div class="crm_card_name"><?=htmlspecialcharsbx($entity['CLIENT_TITLE'])?></div>
				<div class="crm_card_description"><?=htmlspecialcharsbx($entity['CLIENT_LEGEND'])?></div>
				<div class="clb"></div>
			</div>
			<div class="crm_tac lisb"><?
				$callto = isset($entity['CLIENT_CALLTO']) ? $entity['CLIENT_CALLTO'] : null;
				$enableCallto = $callto && ($callto['URL'] !== '' || $callto['SCRIPT'] !== '');
				$mailto = isset($entity['CLIENT_MAILTO']) ? $entity['CLIENT_MAILTO'] : null;
				$enableMailto = $mailto && ($mailto['URL'] !== '' || $mailto['SCRIPT'] !== '');
				?><a class="crm accept-button<?=!$enableCallto ? ' disable' : ''?>" href="<?=$callto['URL'] !== '' ? htmlspecialcharsbx($callto['URL']) : '#'?>"<?=$callto['SCRIPT'] !== '' ? ' onclick="'.htmlspecialcharsbx($callto['SCRIPT']).'"' : (!$enableCallto ? ' onclick="return false;"' : '')?>><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_ACTION_CALL_TO'))?></a>
				<a class="crm_buttons email<?=!$enableMailto ? ' disabled' : ''?>" href="<?=$mailto['URL'] !== '' ?  htmlspecialcharsbx($mailto['URL']) : '#'?>"<?=$mailto['SCRIPT'] !== '' ? ' onclick="'.htmlspecialcharsbx($mailto['SCRIPT']).'"' : (!$enableMailto ? ' onclick="return false;"' : '')?>><span></span></a>
			</div>
			<div class="clb"></div>
		</div>
	<?endif;?>
	<?if($entity['CLIENT_COMPANY_SHOW_URL'] !== ''):?>
	<div class="crm_block_container company crm_arrow" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['CLIENT_COMPANY_SHOW_URL'])?>' });">
		<div class="crm_block-aqua-container">
			<div class="crm_block_title"><?=htmlspecialcharsbx($entity['CLIENT_COMPANY_TITLE'] != '' ? $entity['CLIENT_COMPANY_TITLE'] : GetMessage('M_CRM_ACTIVITY_VIEW_NO_TITLE'))?></div>
			<div class="clb"></div>
		</div>
	</div>
	<?endif;?>
	<?if($typeID === CCrmActivityType::Meeting && $location !== ''):?>
	<div class="crm_block_container">
		<div class="crm_contact_info">
			<table><tbody>
				<tr>
					<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_LOCATION'))?>:</td>
					<td><?=htmlspecialcharsbx($location)?></td>
				</tr>
			</tbody></table>
		</div>
	</div>
	<?endif;?>
	<div class="crm_block_container">
		<div class="crm_contact_info">
			<table><tbody>
				<tr>
					<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_STATUS'))?>:</td>
					<td><?=htmlspecialcharsbx(GetMessage($entity['COMPLETED'] === 'Y' ? 'M_CRM_ACTIVITY_VIEW_STATUS_COMPLETED' : 'M_CRM_ACTIVITY_VIEW_STATUS_NOT_COMPLETED'))?></td>
				</tr>
				<tr>
					<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_RESPONSIBLE'))?>:</td>
					<?if($entity['RESPONSIBLE_SHOW_URL'] !== ''):?>
						<td class="crm_arrow" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['RESPONSIBLE_SHOW_URL'])?>' });">
							<span class="crm_user_link"> <?=htmlspecialcharsbx($entity['RESPONSIBLE_FORMATTED_NAME'])?></span>
						</td>
					<?else:?>
						<td>
							<span class="crm_user_link"> <?=htmlspecialcharsbx($entity['RESPONSIBLE_FORMATTED_NAME'])?></span>
						</td>
					<?endif;?>
				</tr>
			</tbody></table>
			<?if($description !== ''):?>
			<hr/>
			<div class="crm_block_content">
				<div class="crm_block_content_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_DESCRIPTION'))?>:</div>
				<p><span><?=$description?></span><?if($descriptionCut !== ''):?><a class="tdn" href="#" onclick="this.style.display = 'none'; BX.findNextSibling(this, { tagName: 'SPAN' }).style.display = ''; return false;"> <?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_VIEW_CUT'))?></a><span style="display:none;"><?=$descriptionCut?></span><?endif;?></p>
			</div>
			<?endif;?>
			<?if($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk && !empty($diskFiles)):?>
				<hr/>
				<div class="crm_files">
					<ul>
						<?foreach($diskFiles as $diskFileInfo):?>
						<li>
							<a href="#" onclick="app.openDocument({ url: '<?=htmlspecialcharsbx(SITE_DIR."mobile/disk/{$diskFileInfo['ID']}/download/?filename=".__CrmActivityViewPrepareNameForJson($diskFileInfo['NAME']))?>' }); return false;">
								<?=htmlspecialcharsbx($diskFileInfo['NAME'])?><span> (<?=htmlspecialcharsbx($diskFileInfo['SIZE'])?>)</span>
							</a>
						</li>
					<?endforeach;?>
					<?unset($elementInfo);?>
					</ul>
				</div>
			<?elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::File && !empty($files)):?>
				<hr/>
				<div class="crm_files">
					<ul>
					<?foreach($files as $fileInfo):?>
						<li>
							<a href="<?=htmlspecialcharsbx($fileInfo['fileURL'])?>">
								<?=htmlspecialcharsbx($fileInfo['fileName'])?>
								<span> (<?=htmlspecialcharsbx($fileInfo['fileSize'])?>)</span>
							</a>
						</li>
					<?endforeach;?>
					<?unset($fileInfo);?>
					</ul>
				</div>
			<?elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav && !empty($webdavElements)):?>
				<hr/>
				<div class="crm_files">
					<ul>
					<?foreach($webdavElements as $elementInfo):?>
						<li>
							<a href="<?=htmlspecialcharsbx($elementInfo['VIEW_URL'])?>">
								<?=htmlspecialcharsbx($elementInfo['NAME'])?>
								<span> (<?=htmlspecialcharsbx($elementInfo['SIZE'])?>)</span>
							</a>
						</li>
					<?endforeach;?>
					<?unset($elementInfo);?>
					</ul>
				</div>
			<?endif;?>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_LOAD_TEXT')?>'
				}
			);


			var uid = '<?=CUtil::JSEscape($UID)?>';
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: 'ACTIVITY',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'ACTIVITY_EDIT_URL_TEMPLATE' => $arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
							'ACTIVITY_SHOW_URL_TEMPLATE' => $arParams['ACTIVITY_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmActivityView.messages =
			{
				menuSetCompleted: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_SET_COMPLETED')?>',
				menuSetNotCompleted: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_SET_NOT_COMPLETED')?>',
				menuEdit: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_EDIT')?>',
				menuDelete: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_DELETE')?>',
				deletionTitle: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_DELETION_TITLE')?>',
				deletionConfirmation: '<?=GetMessageJS('M_CRM_ACTIVITY_VIEW_DELETION_CONFIRMATION')?>'
			};

			var entityId = <?=$arResult['ENTITY_ID']?>;
			var view = BX.CrmActivityView.create(
				entityId,
				{
					prefix: uid,
					entityId: entityId,
					dispatcher: dispatcher,
					editUrl: '<?=CUtil::JSEscape($entity['EDIT_URL'])?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>
				}
			);
		}
	);
</script>
