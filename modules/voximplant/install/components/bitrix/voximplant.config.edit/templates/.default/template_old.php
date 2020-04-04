<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponent $this
 */

CJSCore::RegisterExt('voximplant_config_edit', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/script.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(array('access', 'voximplant.common', 'voximplant_config_edit', 'phone_number'));
\Bitrix\Voximplant\Ui\Helper::initLicensePopups();
$i = 0;
$defaultM = $arResult["DEFAULT_MELODIES"];
$melodiesToLoad = array();
?>
<form action="<?=\Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage()."?ACTION=save"?>" method="POST" id="config_edit_form">
<?=bitrix_sessid_post()?>
<input type="hidden" name="action" value="save" />
<input type="hidden" name="ID" value="<?=$arResult["ITEM"]["ID"]?>">
<?
if ($arResult['IFRAME'])
{
	?><input type="hidden" name="IFRAME" value="Y" /><?
}
?>

<?if ($_GET['NEW'] == 'Y'):?>
	<div class="tel-set-main-new-phone">
	<?if ($arResult["ITEM"]["PHONE_VERIFIED"] == 'Y'):?>
		<?=GetMessage('VI_CONFIG_NEW_RENT')?>
	<?else:?>
		<?=GetMessage('VI_CONFIG_NEW_RESERVE')?>
	<?endif;?>
	</div>
<?endif;?>

<?if ($arResult["ITEM"]["PHONE_VERIFIED"] == 'N'):?>
	<?if ($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_RENT):?>
		<div class="tel-set-main-notice tel-phones-list-notice">
			<?=GetMessage('VI_CONFIG_RESERVE_NOTICE', Array('#LINK_START#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'configs.php" target="_blank">', '#LINK_END#' => '</a>'))?>
		</div>
	<?endif;?>
<?endif;?>

<div class="tel-set-main-wrap" id="tel-set-main-wrap">
	<div class="tel-set-top-title"><?=htmlspecialcharsbx($arResult["ITEM"]["PHONE_NAME_FORMATTED"])?></div>
	<div class="tel-set-inner-wrap">
		<div class="tel-set-cont-block">
			<?if(strlen($arResult["ERROR"])>0):?>
				<div class="tel-set-cont-error"><?=$arResult['ERROR']?></div>
			<?endif;?>
			<?if($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_LINK):?>
				<div id="backphone-placeholder" style="padding-bottom: 20px"></div>
				<div class="tel-set-main-notice tel-phones-list-notice"><?= GetMessage('VI_CONFIG_LINK_CALLS_WARNING')?></div>
			<?endif?>
			<?if(!empty($arResult["SIP_CONFIG"])):?>
				<?if($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD):?>
					<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_SIP_CLOUD_TITLE")?></div>
					<div class="tel-set-sip-blocks">
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_OUT_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_C_CONFIG')?>
							</div>
							<input type="hidden" name="SIP[NEED_UPDATE]" value="N" id="vi_sip_reg_need_update" />
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_C_NUMBER')?></td>
										<td class="tel-set-sip-td-r"><input type="text" name="SIP[PHONE_NAME]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PHONE_NAME'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
									</tr>
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_SERVER')?></td>
										<td class="tel-set-sip-td-r">
											<input type="text" name="SIP[SERVER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['SERVER'])?>" class="tel-set-inp tel-set-inp-sip" />
										</td>
									</tr>
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_LOGIN')?></td>
										<td class="tel-set-sip-td-r">
											<input type="text" name="SIP[LOGIN]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['LOGIN'])?>" class="tel-set-inp tel-set-inp-sip" />
										</td>
									</tr>
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_PASS')?></td>
										<td class="tel-set-sip-td-r">
											<input type="text" name="SIP[PASSWORD]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PASSWORD'])?>" class="tel-set-inp tel-set-inp-sip"/>
										</td>
									</tr>
								</tbody>
								<tbody id="vi-tel-sip-show-additional-fields">
									<tr align="right">
										<td class="tel-set-sip-td-l">
											<span class="tel-set-sip-additional-fields js-tel-set-sip-additional-fields"><?=GetMessage('VI_CONFIG_SIP_T_ADDITIONAL_FIELDS')?></span>
										</td>
										<td>&nbsp;</td>
									</tr>
								</tbody>
								<tbody id="vi-tel-sip-additional-fields" class="tel-set-sip-additional-fields-hidden tel-connect-pbx-animate">
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_AUTH_USER')?></td>
										<td class="tel-set-sip-td-r">
											<input type="text" name="SIP[AUTH_USER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['AUTH_USER'])?>" class="tel-set-inp tel-set-inp-sip" />
										</td>
									</tr>
									<tr>
										<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_OUTBOUND_PROXY')?></td>
										<td class="tel-set-sip-td-r">
											<input type="text" name="SIP[OUTBOUND_PROXY]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['OUTBOUND_PROXY'])?>" class="tel-set-inp tel-set-inp-sip"/>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_IN_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_C_IN')?>
							</div>
							<div class="tel-set-sip-reg-status">
								<?=GetMessage('VI_CONFIG_SIP_C_STATUS');?>: <span id="vi_sip_reg_status" class="tel-set-sip-reg-status-result tel-set-sip-reg-status-result-<?=$arResult['SIP_CONFIG']['REG_STATUS']?>"><?=GetMessage('VI_CONFIG_SIP_C_STATUS_'.strtoupper($arResult['SIP_CONFIG']['REG_STATUS']))?></span>.
							</div>
							<div class="tel-set-sip-reg-status-desc" id="vi_sip_reg_status_desc">
								<?=GetMessage('VI_CONFIG_SIP_C_STATUS_'.strtoupper($arResult['SIP_CONFIG']['REG_STATUS']).'_DESC')?>
							</div>
							<div class="tel-set-sip-block-notice">
								<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
							</div>
						</div>
					</div>
				<?else:?>
					<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_SIP_OFFICE_TITLE")?></div>
					<div class="tel-set-sip-blocks">
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_OUT_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_OUT')?>
							</div>
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_C_NUMBER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PHONE_NAME]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PHONE_NAME'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_SERVER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[SERVER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['SERVER'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_LOGIN')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[LOGIN]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['LOGIN'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_PASS')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PASSWORD]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PASSWORD'])?>" class="tel-set-inp tel-set-inp-sip"/></td>
								</tr>
							</table>
						</div>
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_IN_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_IN')?>
							</div>
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_SERVER')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_SERVER'])?>"/>
									</td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_LOGIN')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_LOGIN'])?>"/>
									</td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_PASS')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_PASSWORD'])?>"/>
									</td>
								</tr>
							</table>
							<div class="tel-set-sip-block-notice">
								<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
							</div>
						</div>
					</div>
				<?endif;?>
			<?endif;?>
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_ROUTE_INCOMING")?></div>
			<? if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_SIP): ?>
				<div class="tel-set-item">
					<div class="tel-set-item-num">
						<input type="hidden" value="N" name="USE_SIP_TO">
						<input type="checkbox" class="tel-set-checkbox" <? if ($arResult["ITEM"]["USE_SIP_TO"] == "Y") { ?>checked<? } ?> value="Y" name="USE_SIP_TO" id="id<?=(++$i)?>">
						<span class="tel-set-item-num-text"><?=$i?>.</span>
					</div>
					<div class="tel-set-item-cont-block">
						<label class="tel-set-cont-item-title" for="id<?=$i?>"><?=GetMessage("VI_CONFIG_EDIT_SIP_HEADER_PROCESSING")?></label>
						<span class="tel-set-cont-item-title-description" style="margin-top: -12px;"><?= GetMessage("VI_CONFIG_EDIT_SIP_HEADER_PROCESSING_TIP")?></span>
					</div>
				</div>
			<? endif ?>
			<? if($arResult["SHOW_DIRECT_CODE"]): ?>
				<div class="tel-set-item">
					<div class="tel-set-item-num">
						<input type="hidden" value="N" name="DIRECT_CODE">
						<input type="checkbox" class="tel-set-checkbox" <? if ($arResult["ITEM"]["DIRECT_CODE"] == "Y") { ?>checked<? } ?> value="Y" name="DIRECT_CODE" id="id<?=(++$i)?>">
						<span class="tel-set-item-num-text"><?=$i?>.</span>
					</div>
					<div class="tel-set-item-cont-block">
						<label class="tel-set-cont-item-title" for="id<?=$i?>"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING")?></label>
						<span class="tel-context-help" data-text="<?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_HELP")?>">?</span>
						<div class="tel-set-item-cont">
							<div class="tel-set-item-text"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_TIP")?></div>
							<div class="tel-set-item-select-block">
								<span class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_OMITTED_CALL")?></span>
								<select name="DIRECT_CODE_RULE" class="tel-set-inp tel-set-item-select">
									<option value="<?=CVoxImplantIncoming::RULE_QUEUE?>" <?=($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_QUEUE ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1_2")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_PSTN?>" <?=($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_PSTN ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_2")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>" <?=($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_VOICEMAIL ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2_1")?></option>
								</select>
							</div>
						</div>
					</div>
				</div>
			<? endif ?>

			<?if($arResult['SHOW_IVR']):?>
				<div class="tel-set-item">
					<div class="tel-set-item-num tel-set-item-num-with-select">
						<input type="hidden" value="N" name="IVR">
						<input id="vi-set-ivr" type="checkbox" class="tel-set-checkbox" <? if ($arResult["ITEM"]["IVR"] == "Y") { ?>checked<? } ?> value="Y" name="IVR" id="id<?=(++$i)?>">
						<span class="tel-set-item-num-text"><?=$i?>.</span>
					</div>
					<div class="tel-set-item-cont-block">
						<label class="tel-set-cont-item-title" for="id<?=$i?>"><?=GetMessage("TELEPHONY_USE_IVR")?></label>
						<select name="IVR_ID" class="tel-set-inp tel-set-item-select" data-role="select-ivr" >
							<option value="new"><?=GetMessage('VI_CONFIG_CREATE_IVR')?></option>
							<option disabled>&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;</option>
							<? foreach ($arResult["IVR_MENUS"] as $ivr): ?>
								<option value="<?=htmlspecialcharsbx($ivr["ID"])?>"<?=($ivr["ID"] == $arResult["ITEM"]["IVR_ID"] ? " selected" : "")?>><?=htmlspecialcharsbx($ivr["NAME"])?></option>
							<? endforeach; ?>
						</select>
						<? if(\Bitrix\Voximplant\Ivr\Ivr::isEnabled()): ?>
							<span id="vi-group-show-ivr" class="tel-set-group-show-config" data-role="show-ivr-config"><?=GetMessage('VI_CONFIG_IVR_SETTINGS')?></span>
						<? else: ?>
							<div class="tel-lock-holder-select" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>">
								<div onclick="BX.Voximplant.showLicensePopup('main')" class="tel-lock <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div>
							</div>
							<script>
								BX.bind(BX('vi-set-ivr'), 'change', function(e)
								{
									BX.PreventDefault(e);
									BX('vi-set-ivr').checked = false;
									BX.Voximplant.showLicensePopup('main');
								});
							</script>
						<? endif ?>
					</div>
				</div>
			<?endif?>


			<?if(IsModuleInstalled('crm')):?>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="CRM" type="hidden" value="N" />
					<input type="checkbox" id="id<?=(++$i)?>" name="CRM" <? if ($arResult["ITEM"]["CRM"] == "Y") { ?>checked<? } ?> value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=$i?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="id<?=$i?>" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_EDIT_CRM_CHECKING")?>
					</label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-select-block">
							<input id="vi_crm_forward" type="checkbox" name="CRM_FORWARD" <?if($arResult["ITEM"]["CRM_FORWARD"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_FORWARD")?></div>
						</div>
						<div id="vi_crm_rule" class="tel-set-item-select-block tel-set-item-crm-rule" style="<?=($arResult["ITEM"]["CRM_FORWARD"] == "Y"? 'height: 40px': '')?>">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_CHECKING_OMITTED_CALL")?></div>
							<select class="tel-set-inp tel-set-item-select" name="CRM_RULE">
								<option value="<?=CVoxImplantIncoming::RULE_QUEUE?>"<?=(CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1")?></option>
								<option value="<?=CVoxImplantIncoming::RULE_PSTN?>"<?=(CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_3")?></option>
								<?if($arResult['SHOW_RULE_VOICEMAIL']):?>
									<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2_1")?></option>
								<?endif?>
							</select>
						</div>
						<div class="tel-set-item-select-block" style="background-color: #fff; padding-top: 10px;">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE")?></div>
							<select class="tel-set-inp tel-set-item-select" name="CRM_CREATE" id="vi_crm_create">
								<?foreach (array("1" => CVoxImplantConfig::CRM_CREATE_NONE, "2" => CVoxImplantConfig::CRM_CREATE_LEAD) as $ii => $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["CRM_CREATE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_".$ii)?></option>
								<?endforeach;?>
							</select>
							<span class="tel-set-group-show-config" data-role="show-crm-exception-list"><?=GetMessage("VI_CONFIG_CONFIGURE_CRM_EXCEPTIONS_LIST")?></span>
						</div>
						<script type="text/javascript">
							BX.bind(BX('vi_crm_create'), 'change', function(e){
								if (this.options[this.selectedIndex].value != '<?=CVoxImplantConfig::CRM_CREATE_NONE?>')
								{
									BX('vi_crm_source').style.height = '106px';
								}
								else
								{
									BX('vi_crm_source').style.height = '0';
								}
							});
						</script>
						<div id="vi_crm_source"  class="tel-set-item-select-block tel-set-item-crm-rule" style="background-color: #fff; <?=($arResult["ITEM"]["CRM_CREATE"] != CVoxImplantConfig::CRM_CREATE_NONE? 'height: 106px;': '')?>">
							<div class="tel-set-item-select-block">
								<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE")?></div>
								<select class="tel-set-inp tel-set-item-select" name="CRM_CREATE_CALL_TYPE">
									<option value="<?=CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING?>"<?=(CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_INCOMING")?></option>
									<option value="<?=CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING?>"<?=(CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_OUTGOING")?></option>
									<option value="<?=CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL?>"<?=(CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_ALL")?></option>
								</select>
							</div>
							<div class="tel-set-item-select-block">
								<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_SOURCE")?></div>
								<select class="tel-set-inp tel-set-item-select" name="CRM_SOURCE" id="vi_crm_source_select">
									<?foreach ($arResult['CRM_SOURCES'] as $ii => $k):?>
										<option value="<?=$ii?>"<?=($ii == $arResult["ITEM"]["CRM_SOURCE"] ? " selected" : "")?>><?=htmlspecialcharsbx($k)?></option>
									<?endforeach;?>
								</select>
								<?if (!CVoxImplantAccount::IsPro() || CVoxImplantAccount::IsDemo()):?>
									<div class="tel-lock-holder-select" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>"><div onclick="BX.Voximplant.showLicensePopup('main')" class="tel-lock <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div></div>
								<?endif;?>
							</div>
						</div>
						<?if (!CVoxImplantAccount::IsPro()):?>
						<script type="text/javascript">
							viCrmSource = BX('vi_crm_source_select').options.selectedIndex;
							BX.bind(BX('vi_crm_source_select'), 'change', function(e){
								BX.Voximplant.showLicensePopup('main');
								this.selectedIndex = viCrmSource;
							});
						</script>
						<?endif;?>
						<div class="tel-set-item-select-block">
							<input type="checkbox" name="CRM_TRANSFER_CHANGE" <?if($arResult["ITEM"]["CRM_TRANSFER_CHANGE"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_TRANSFER_CHANGE")?></div>
						</div>
					</div>
				</div>
			</div>
			<?endif;?>

			<div class="tel-set-item">
				<div class="tel-set-item-num tel-set-item-num-with-select">
					<span class="tel-set-item-num-text"><?=(++$i)?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<div class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_ROUTE_TO_SELECT")?>
						<select id="vi-group-id-select" class="tel-set-inp tel-set-item-select" name="QUEUE_ID" data-role="select-group">
							<option value="new"><?=GetMessage('VI_CONFIG_CREATE_GROUP')?></option>
							<option disabled>&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;</option>
							<? foreach ($arResult["QUEUES"] as $queue): ?>
								<option value="<?=htmlspecialcharsbx($queue["ID"])?>"<?=($queue["ID"] == $arResult["ITEM"]["QUEUE_ID"] ? " selected" : "")?>><?=htmlspecialcharsbx($queue["NAME"])?></option>
							<? endforeach; ?>
						</select>
						<span id="vi-group-show-config" class="tel-set-group-show-config" data-role="show-group-config"><?=GetMessage('VI_CONFIG_GROUP_SETTINGS')?></span>
					</div>
					<div class="tel-set-item-select-block">
						<input id="vi_timeman" type="checkbox" name="TIMEMAN" <?if($arResult["ITEM"]["TIMEMAN"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
						<label for="vi_timeman" class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TIMEMAN_SUPPORT")?></label>
					</div>
					<?if (!IsModuleInstalled("timeman")):?>
						<script type="text/javascript">
							BX.bind(BX('vi_timeman'), 'change', function(e){
								BX('vi_timeman').checked = false;
								alert('<?=GetMessage(IsModuleInstalled("bitrix24")? "VI_CONFIG_EDIT_TIMEMAN_SUPPORT_B24": "VI_CONFIG_EDIT_TIMEMAN_SUPPORT_CP")?>');
							});
						</script>
					<?endif;?>
					<div id="vi-group-settings-placeholder-wrapper" class="tel-set-height-animated" style="max-height: 0;">
						<div id="vi-group-settings-placeholder"></div>
					</div>
				</div>
			</div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="RECORDING" type="hidden" value="N" />
					<input type="checkbox" id="vi-recording" name="RECORDING" <?=($arResult["ITEM"]["RECORDING"] == "Y" ? 'checked' : '')?> value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=(++$i)?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi-recording" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_EDIT_RECORD")?>
					</label>
					<?if ($arResult['RECORD_LIMIT']['ENABLE']):?>
						<div class="tel-lock-holder-title" title="<?=GetMessage("VI_CONFIG_LOCK_RECORD_ALT", Array("#LIMIT#" => $arResult['RECORD_LIMIT']['LIMIT'], '#REMAINING#' => $arResult['RECORD_LIMIT']['REMAINING']))?>"><div onclick="BX.Voximplant.showLicensePopup('main')"  class="tel-lock tel-lock-half <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div></div>
					<?elseif (!$arResult['RECORD_LIMIT']['ENABLE'] && $arResult['RECORD_LIMIT']['DEMO']):?>
						<div class="tel-lock-holder-title" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>"><div onclick="BX.Voximplant.showLicensePopup('main')"  class="tel-lock tel-lock-demo"></div></div>
					<?endif;?>
					<span class="tel-set-cont-item-title-description" style="margin-top: -12px; margin-bottom: 12px"><?=GetMessage("VI_CONFIG_EDIT_RECORD_TIP")?></span>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-alert">
							<?=GetMessage("VI_CONFIG_EDIT_RECORD_TIP2")?>
						</div>
					</div>
					<div id="vi-recording-details" class="tel-set-height-animated" style="<?=($arResult["ITEM"]["RECORDING"] == "Y" ? 'max-height: 160px' : 'max-height: 0')?>">
						<input id="vi_recording_notice" type="checkbox" name="RECORDING_NOTICE" <?if($arResult["ITEM"]["RECORDING_NOTICE"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
						<label class="tel-set-cont-item-title" for="vi_recording_notice" style="display: inline-block; font-weight: normal;"><div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_RECORD_NOTICE")?></div></label><br>
						<input id="vi_transcribe"
							   type="checkbox"
							   name="TRANSCRIBE"
							   value="Y"
							   class="tel-set-checkbox"
							   <?if($arResult["ITEM"]["TRANSCRIBE"] == "Y") { ?>checked<? }?>
							   <?if(!\Bitrix\Voximplant\Transcript::isEnabled()) { ?>disabled<? }?>
						/>
						<label class="tel-set-cont-item-title" for="vi_transcribe" style="display: inline-block; font-weight: normal;"><div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TRANSCRIBE")?></div></label>

						<?if (!\Bitrix\Voximplant\Transcript::isEnabled() || \Bitrix\Voximplant\Transcript::isDemo()):?>
							<div class="tel-lock-holder-select" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>"><div onclick="BX.Voximplant.showLicensePopup('main')" class="tel-lock <?=(\Bitrix\Voximplant\Transcript::isDemo()? 'tel-lock-demo': '')?>"></div></div>
						<?endif;?>

						<div class="tel-set-cont-item-title-description" style="margin: -12px 0 12px 23px"><?=GetMessage("VI_CONFIG_TRANSCRIPTION_HINT", array("#URL#" => CVoxImplantMain::getPricesUrl()))?></div>
						<div id="vi_transcribe_lang" class="tel-set-item-select-block tel-set-item-crm-rule" style="<?=($arResult["ITEM"]["TRANSCRIBE"] == "Y"? 'height: 40px': '')?>">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TRANSCRIBE_LANGUAGE")?></div>
							<select class="tel-set-inp tel-set-item-select" name="TRANSCRIBE_LANG">
								<? foreach ($arResult['TRANSCRIBE_LANGUAGES'] as $languageId => $languageName): ?>
									<option value="<?= htmlspecialcharsbx($languageId)?>" <?=($arResult["ITEM"]["TRANSCRIBE_LANG"] == $languageId ? "selected" : "")?>><?=htmlspecialcharsbx($languageName)?></option>
								<? endforeach ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?if($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_RENT):?>
				<div class="tel-set-item">
					<div class="tel-set-item-num">
						<input name="REDIRECT_WITH_CLIENT_NUMBER" type="hidden" value="N" />
						<input type="checkbox" name="REDIRECT_WITH_CLIENT_NUMBER" <?if($arResult["ITEM"]["REDIRECT_WITH_CLIENT_NUMBER"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
						<span class="tel-set-item-num-text"><?=(++$i)?>.</span>
					</div>
					<div class="tel-set-item-cont-block">
						<label for="vi_vote" class="tel-set-cont-item-title">
							<?=GetMessage("VI_CONFIG_REDIRECT_WITH_CLIENT_NUMBER")?>
						</label>
						<span class="tel-set-cont-item-title-description" style="margin-top: -12px;"><?=GetMessage("VI_CONFIG_REDIRECT_WITH_CLIENT_NUMBER_TIP")?></span>
					</div>
				</div>
			<?endif;?>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="VOTE" type="hidden" value="N" />
					<input type="checkbox" id="vi_vote" name="VOTE" <?if($arResult["ITEM"]["VOTE"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=(++$i)?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_vote" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_VOTE")?>
					</label>
					<?if (!CVoxImplantAccount::IsPro() || CVoxImplantAccount::IsDemo()):?>
						<div class="tel-lock-holder-title" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>"><div onclick="BX.Voximplant.showLicensePopup('main')"  class="tel-lock <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div></div>
					<?endif;?>
					<span class="tel-set-cont-item-title-description" style="margin-top: -12px;"><?=GetMessage("VI_CONFIG_VOTE_TIP")?></span>
				</div>
				<?if (!CVoxImplantAccount::IsPro()):?>
					<script type="text/javascript">
						BX.bind(BX('vi_vote'), 'change', function(e){
							BX('vi_vote').checked = false;
							BX.Voximplant.showLicensePopup('main');
						});
					</script>
				<?endif;?>
			</div>
		</div>
		<!-- backup number -->
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_BACKUP_NUMBER")?></div>

			<div class="tel-set-item">
				<div class="tel-set-item-num">
					&nbsp;<input type="checkbox" name="USE_SPECIFIC_BACKUP_NUMBER" id="vi_use_specific_backup_number" class="tel-set-checkbox" value="Y" <?= ($arResult["ITEM"]["BACKUP_NUMBER"] == "" ? 0 : "checked")?> />
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_use_specific_backup_number" class="tel-set-cont-item-title" ><?=GetMessage("VI_CONFIG_SET_USE_SPECIFIC_BACKUP_NUMBER_USE")?>
						<span class="tel-set-cont-item-title-description"><?=GetMessage("VI_CONFIG_SET_BACKUP_NUMBER")?></span>
					</label>
					<div id="vi_backup_number_settings" class="tel-set-height-animated" style="max-height: <?= ($arResult["ITEM"]["BACKUP_NUMBER"] == "" ? 0 : "100px")?>">
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_BACKUP_NUMBER")?></div>
							&nbsp;&mdash;&nbsp;
							<input class="tel-set-inp tel-set-inp-line-prefix" name="BACKUP_NUMBER" type="text" value="<?=htmlspecialcharsbx($arResult["ITEM"]["BACKUP_NUMBER"])?>" size="15" maxlength="20">
						</div>
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_BACKUP_LINE")?></div>
							&nbsp;&mdash;&nbsp;
							<select name="BACKUP_LINE" class="tel-set-inp tel-set-item-select">
								<?foreach ($arResult['BACKUP_LINES'] as $k => $v):?>
									<option value="<?= $k ?>" <?= ($arResult["ITEM"]["BACKUP_LINE"] == $k ? "selected" : "")?>><?= $v ?></option>
								<?endforeach;?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- callback settings-->
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_CALLBACK_SETTINGS")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					&nbsp;<input type="checkbox" name="CALLBACK_REDIAL" id="vi_callback_redial" class="tel-set-checkbox" value="Y" <?if ($arResult["ITEM"]["CALLBACK_REDIAL"] === "Y"):?>checked="checked"<?endif?>/>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_callback_redial" class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_CALLBACK_REDIAL")?></label>
					<div id="vi_callback_redial_options" class="tel-set-height-animated" style="max-height: <?= ($arResult["ITEM"]["CALLBACK_REDIAL"] === "Y" ? '100px' : 0)?>">
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_CALLBACK_REDIAL_ATTEMPTS")?> &nbsp;&mdash;&nbsp;</div>
							<select class="tel-set-inp" name="CALLBACK_REDIAL_ATTEMPTS" style="width: auto;">
								<?foreach (array(1, 2, 3, 4, 5) as $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["CALLBACK_REDIAL_ATTEMPTS"] ? " selected" : "")?>><?=$k?></option>
								<?endforeach;?>
							</select>
						</div>
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_CALLBACK_REDIAL_PERIOD")?> &nbsp;&mdash;&nbsp;</div>
							<select class="tel-set-inp" name="CALLBACK_REDIAL_PERIOD" style="width: auto;">
								<?foreach (array(60, 120, 180) as $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["CALLBACK_REDIAL_PERIOD"] ? " selected" : "")?>><?=$k?> <?=GetMessage('VI_CONFIG_CALLBACK_REDIAL_PERIOD_SECONDS')?></option>
								<?endforeach;?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- work time-->
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					&nbsp;<input type="checkbox" name="WORKTIME_ENABLE" id="WORKTIME_ENABLE" class="tel-set-checkbox" value="Y" <?if ($arResult["ITEM"]["WORKTIME_ENABLE"] === "Y"):?>checked="checked"<?endif?>/>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="WORKTIME_ENABLE" class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_ENABLE")?></label>
					<div class="tel-set-item-cont tel-set-item-crm-rule" id="vi_worktime" <?if ($arResult["ITEM"]["WORKTIME_ENABLE"] == "Y"):?>style="height: auto"<?endif?>>
						<table>
							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_TIMEZONE")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_TIMEZONE" class="tel-set-inp tel-set-item-select">
										<?if (is_array($arResult["TIME_ZONE_LIST"]) && !empty($arResult["TIME_ZONE_LIST"])):?>
											<?foreach($arResult["TIME_ZONE_LIST"] as $tz=>$tz_name):?>
												<option value="<?=htmlspecialcharsbx($tz)?>"<?=($arResult["ITEM"]["WORKTIME_TIMEZONE"] == $tz? ' selected="selected"' : '')?>><?=htmlspecialcharsbx($tz_name)?></option>
											<?endforeach?>
										<?endif?>
									</select>
								</td>
							</tr>
							<?if (!empty($arResult["WORKTIME_LIST_FROM"]) && !empty($arResult["WORKTIME_LIST_TO"])):?>
							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_TIME")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_FROM" class="tel-set-inp tel-set-item-select" style="min-width: 70px">
										<?foreach($arResult["WORKTIME_LIST_FROM"] as $key => $val):?>
											<option value="<?= $key?>" <?if ($arResult["ITEM"]["WORKTIME_FROM"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
										<?endforeach;?>
									</select>
									&nbsp; &mdash; &nbsp;
									<select name="WORKTIME_TO" class="tel-set-inp tel-set-item-select" style="min-width: 70px">
										<?foreach($arResult["WORKTIME_LIST_TO"] as $key => $val):?>
											<option value="<?= $key?>" <?if ($arResult["ITEM"]["WORKTIME_TO"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
										<?endforeach;?>
									</select>
								</td>
							</tr>
							<?endif?>

							<tr>
								<td>
									<div class="tel-set-item-select-text" style="vertical-align: top"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select size="7" multiple name="WORKTIME_DAYOFF[]" class="tel-set-inp tel-set-item-select-multiple ">
										<?foreach($arResult["WEEK_DAYS"] as $day):?>
											<option value="<?=$day?>" <?=(is_array($arResult["ITEM"]["WORKTIME_DAYOFF"]) && in_array($day, $arResult["ITEM"]["WORKTIME_DAYOFF"]) ? ' selected="selected"' : '')?>><?= GetMessage('VI_CONFIG_WEEK_'.$day)?></option>
										<?endforeach;?>
									</select>
								</td>
							</tr>

							<tr>
								<td style="vertical-align: top; padding-top: 12px;">
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS")?></div>
								</td>
								<td style="vertical-align: top; padding-top: 12px;">&nbsp; &mdash; &nbsp;</td>
								<td>
									<input type="text" name="WORKTIME_HOLIDAYS" class="tel-set-inp" value="<?=htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_HOLIDAYS"])?>"/>
									<div class="tel-set-item-text" style="margin-top: 5px">(<?=GetMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS_EXAMPLE")?>)</div>
								</td>
							</tr>

							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_RULE")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_DAYOFF_RULE" id="WORKTIME_DAYOFF_RULE" class="tel-set-inp tel-set-item-select">
										<?if($arResult['SHOW_RULE_VOICEMAIL']):?>
											<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_2")?></option>
										<?endif?>
										<option value="<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>"<?=(CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_5")?></option>
										<option value="<?=CVoxImplantIncoming::RULE_HUNGUP?>"<?=(CVoxImplantIncoming::RULE_HUNGUP == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_4")?></option>
									</select>
								</td>
							</tr>

							<tr id="vi_dayoff_number" <?if (CVoxImplantIncoming::RULE_PSTN_SPECIFIC != $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"]):?>style="display: none"<?endif?>>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_NUMBER")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<input type="text" name="WORKTIME_DAYOFF_NUMBER" class="tel-set-inp" value="<?=htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_DAYOFF_NUMBER"])?>"/>
								</td>
							</tr>

							<?
							$dayOffMelody = array(
								"MELODY" => (array_key_exists("~WORKTIME_DAYOFF_MELODY", $arResult["ITEM"]) ? $arResult["ITEM"]["~WORKTIME_DAYOFF_MELODY"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOICEMAIL"])),
								"MELODY_ID" => $arResult["ITEM"]["WORKTIME_DAYOFF_MELODY"],
								"DEFAULT_MELODY" => $defaultM["MELODY_VOICEMAIL"],
								"INPUT_NAME" => "WORKTIME_DAYOFF_MELODY"
							);
							$id = "voximplant_dayoff";
							?>
							<?if($arResult['SHOW_MELODIES']):?>
							<tr>
								<td colspan="3">
									<div id="vi-dayoff-melody" class="tel-set-item-cont tel-dayoff-melody <?=$arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] == CVoxImplantIncoming::RULE_VOICEMAIL ? 'tel-dayoff-melody-visible' : ''?>">
										<div class="tel-set-cont-item-title" style="padding-top: 25px;"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_MELODY")?></div>
										<div class="tel-set-item-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_MELODY_TEXT")?></div>
										<div class="tel-set-melody-block">
											<span class="tel-set-player-wrap">
												<?$APPLICATION->IncludeComponent(
													"bitrix:player",
													"",
													Array(
														"PLAYER_ID" => $id."player",
														"PLAYER_TYPE" => "flv",
														"USE_PLAYLIST" => "N",
														"PATH" => $dayOffMelody["MELODY"],
														"PROVIDER" => "sound",
														"STREAMER" => "",
														"WIDTH" => "217",
														"HEIGHT" => "24",
														"PREVIEW" => "",
														"FILE_TITLE" => "",
														"FILE_DURATION" => "",
														"FILE_AUTHOR" => "",
														"FILE_DATE" => "",
														"FILE_DESCRIPTION" => "",
														"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
														"SKIN" => "",
														"CONTROLBAR" => "bottom",
														"WMODE" => "opaque",
														"LOGO" => "",
														"LOGO_LINK" => "",
														"LOGO_POSITION" => "none",
														"PLUGINS" => array(),
														"ADDITIONAL_FLASHVARS" => "",
														"AUTOSTART" => "N",
														"REPEAT" => "none",
														"VOLUME" => "90",
														"MUTE" => "N",
														"ADVANCED_MODE_SETTINGS" => "Y",
														"BUFFER_LENGTH" => "2",
														"ALLOW_SWF" => "N"
													),
													null,
													Array(
														'HIDE_ICONS' => 'Y'
													)
												);?>
											</span>
											<span class="tel-set-file-wrap">
												<?$APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
													array(
														'INPUT_CAPTION' => GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE"),
														'INPUT_NAME' => $dayOffMelody["INPUT_NAME"],
														'INPUT_VALUE' => array($dayOffMelody["MELODY_ID"]),
														'MAX_FILE_SIZE' => 2097152,
														'MODULE_ID' => 'voximplant',
														'FORCE_MD5' => true,
														'CONTROL_ID' => $id,
														'MULTIPLE' => 'N',
														'ALLOW_UPLOAD' => 'F',
														'ALLOW_UPLOAD_EXT' => 'mp3'
													),
													$this->component,
													array("HIDE_ICONS" => true)
												);?>
												<span class="tel-set-melody-item" id="<?=$id?>span">
													<span class="tel-set-item-melody-link tel-set-item-melody-link-active" >
														<?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE")?>
													</span>
													<span class="tel-set-melody-description" id="<?=$id?>notice" >
														<?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP")?>
													</span>
												</span>
												<span class="tel-set-melody-item" id="<?=$id?>default" <?if ($dayOffMelody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
													<span class="tel-set-item-melody-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
												</span>
											</span>
										</div>
									</div>
								</td>
							</tr>
							<?endif?>
						</table>
					</div>
				</div>
			</div>
		</div>
		<? $melodiesToLoad[$id] = $dayOffMelody; ?>
		<script>
			BX.ready(function(){
				BX.bind(BX('WORKTIME_ENABLE'), 'change', function(e){
					if (BX('WORKTIME_ENABLE').checked)
					{
						BX('vi_worktime').style.height = '464px';
						setTimeout(function(){BX('vi_worktime').style.height = 'auto';}, 500);
					}
					else
					{
						BX('vi_worktime').style.height = '464px';
						setTimeout(function(){BX('vi_worktime').style.height = '0';}, 100);
					}
				});

				BX.bind(BX('WORKTIME_DAYOFF_RULE'), 'change', function(e){
					if (this.options[this.selectedIndex].value == '<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>')
					{
						BX('vi_dayoff_number').style.display = '';
					}
					else
					{
						BX('vi_dayoff_number').style.display = 'none';
					}

					if (this.options[this.selectedIndex].value == '<?=CVoxImplantIncoming::RULE_VOICEMAIL?>')
					{
						BX.addClass(BX('vi-dayoff-melody'), 'tel-dayoff-melody-visible');
					}
					else
					{
						BX.removeClass(BX('vi-dayoff-melody'), 'tel-dayoff-melody-visible');
					}
				});
			});
		</script>
		<!-- //work time-->

		<?if(!empty($arResult["SIP_CONFIG"]) && !empty($arResult['FORWARD_LINES'])):?>
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_FORWARD_TITLE")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					&nbsp;<input type="checkbox" name="FORWARD_LINE_ENABLED" id="FORWARD_LINE_ENABLED" class="tel-set-checkbox" value="Y" <?if ($arResult["ITEM"]["FORWARD_LINE"] !== CVoxImplantConfig::FORWARD_LINE_DEFAULT):?>checked="checked"<?endif?>/>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="FORWARD_LINE_ENABLED" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_EDIT_FORWARD_NUMBER")?>
						<span class="tel-set-cont-item-title-description"><?=GetMessage("VI_CONFIG_EDIT_FORWARD_NUMBER_TIP")?></span>
					</label>
					<div class="tel-set-item-cont tel-set-item-crm-rule" id="vi_forward_number-box" <?if ($arResult["ITEM"]["FORWARD_LINE"] !== CVoxImplantConfig::FORWARD_LINE_DEFAULT):?>style="height: auto"<?endif?>>
						<table>
							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_FORWARD_TITLE")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="FORWARD_LINE" class="tel-set-inp tel-set-item-select">
										<?foreach ($arResult['FORWARD_LINES'] as $k => $v):?>
										<option value="<?=$k?>" <? if ($arResult["ITEM"]["FORWARD_LINE"] == $k): ?> selected <? endif; ?>><?=$v?></option>
										<?endforeach;?>
									</select>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		<script>
			BX.ready(function(){
				BX.bind(BX('FORWARD_LINE_ENABLED'), 'change', function(e){
					if (BX('FORWARD_LINE_ENABLED').checked)
					{
						BX('vi_forward_number-box').style.height = '45px';
						setTimeout(function(){BX('vi_forward_number-box').style.height = 'auto';}, 500);
					}
					else
					{
						BX('vi_forward_number-box').style.height = '45px';
						setTimeout(function(){BX('vi_forward_number-box').style.height = '0';}, 100);
					}
				});
			});
		</script>
		<?endif;?>
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_NUMBER_USAGE_FOR_OUTGOING_CALL")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">&nbsp;
					<input id="vi_can_be_selected"
						   class="tel-set-checkbox" value="Y"
						   type="checkbox"
						   name="CAN_BE_SELECTED"
						   <?if ($arResult["ITEM"]["CAN_BE_SELECTED"] === "Y"):?>checked="checked"<?endif?>
						   data-locked="<?=(\Bitrix\Voximplant\Limits::canSelectLine() ? "N" : "Y")?>"
					/>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_can_be_selected" class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_ALLOW_TO_SELECT_NUMBER_FOR_OUTGOING_CALL")?></label>
					<?if (!\Bitrix\Voximplant\Limits::canSelectLine() || CVoxImplantAccount::IsDemo()):?>
						<div class="tel-lock-holder-select" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>">
							<div onclick="BX.Voximplant.showLicensePopup('line-selection')" class="tel-lock <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div>
						</div>
					<?endif;?>
					<div id="vi_number_selection_option" class="tel-set-height-animated" style="max-height: <?= ($arResult["ITEM"]["CAN_BE_SELECTED"] === "Y" ? '250px' : '0')?>">
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_LINE_PREFIX")?></div>
							&nbsp; &mdash; &nbsp;
							<input id="vi-line-prefix"
								   class="tel-set-inp tel-set-inp-line-prefix"
								   name="LINE_PREFIX"
								   type="text"
								   value="<?=htmlspecialcharsbx($arResult["ITEM"]["LINE_PREFIX"])?>"
								   size="10"
								   maxlength="10"
								   data-role="input-line-prefix"
							>
							<span class="tel-context-help" data-text="<?=GetMessage("VI_CONFIG_LINE_PREFIX_HINT")?>">?</span>
						</div>
						<div class="tel-set-item-text"><?=GetMessage("VI_CONFIG_LINE_ALLOWED_USERS")?></div>
						<div class="tel-set-destination-container" data-role="line-access"></div>
					</div>
				</div>
			</div>
		</div>

		<!-- melody -->
		<?if($arResult['SHOW_MELODIES']):?>
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_TUNES")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num"></div>
				<div class="tel-set-item-cont-block">
					<div class="tel-set-item-cont">
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE")?></div>
							<select class="tel-set-inp tel-set-item-select" name="MELODY_LANG">
								<?foreach (CVoxImplantConfig::GetMelodyLanguages() as $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["MELODY_LANG"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE_".$k)?></option>
								<?endforeach;?>
							</select>
						</div>
					</div>
				</div>
			</div>
<?
$melodies = array(
	array(
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_WELCOME", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WELCOME"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_WELCOME"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_WELCOME"],
			"DEFAULT_MELODY" => $defaultM["MELODY_WELCOME"],
			"CHECKBOX" => "MELODY_WELCOME_ENABLE",
			"INPUT_NAME" => "MELODY_WELCOME"
		),
	),
	array(
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_RECORDING_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_RECORDING_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_RECORDING", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_RECORDING"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_RECORDING"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_RECORDING"],
			"DEFAULT_MELODY" => $defaultM["MELODY_RECORDING"],
			"INPUT_NAME" => "MELODY_RECORDING"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_WAIT", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WAIT"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_WAIT"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_WAIT"],
			"DEFAULT_MELODY" => $defaultM["MELODY_WAIT"],
			"INPUT_NAME" => "MELODY_WAIT"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_ENQUEUE_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_ENQUEUE_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_ENQUEUE", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_ENQUEUE"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_ENQUEUE"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_ENQUEUE"],
			"DEFAULT_MELODY" => $defaultM["MELODY_ENQUEUE"],
			"INPUT_NAME" => "MELODY_ENQUEUE"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_HOLD", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_HOLD"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_HOLD"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_HOLD"],
			"DEFAULT_MELODY" => $defaultM["MELODY_HOLD"],
			"INPUT_NAME" => "MELODY_HOLD"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_VOICEMAIL", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOICEMAIL"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOICEMAIL"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_VOICEMAIL"],
			"DEFAULT_MELODY" => $defaultM["MELODY_VOICEMAIL"],
			"INPUT_NAME" => "MELODY_VOICEMAIL"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_VOTE_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_VOTE_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_VOTE", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOTE"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOTE"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_VOTE"],
			"DEFAULT_MELODY" => $defaultM["MELODY_VOTE"],
			"INPUT_NAME" => "MELODY_VOTE"
		),
		array(
			"TITLE" => GetMessage("VI_CONFIG_EDIT_VOTE_END_TUNE"),
			"TIP" => GetMessage("VI_CONFIG_EDIT_VOTE_END_TUNE_TIP"),
			"MELODY" => (array_key_exists("~MELODY_VOTE_END", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOTE_END"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOTE_END"])),
			"MELODY_ID" => $arResult["ITEM"]["MELODY_VOTE_END"],
			"DEFAULT_MELODY" => $defaultM["MELODY_VOTE_END"],
			"INPUT_NAME" => "MELODY_VOTE_END"
		)
	)
);

foreach ($melodies as $ii => $group):?>
	<?if ($ii == 0):?>
	<div class="tel-melody-group tel-melody-group-<?=$ii?>">
	<?else:?>
	<div class="tel-melody-group-open"><span class="ui-btn ui-btn-light-border" onclick="viShowMelodyGroup(this)"><?=GetMessage('VI_CONFIG_MORE_TONES')?></span></div>
	<div class="tel-melody-group tel-melody-group-hide tel-melody-group-<?=$ii?>">
	<?endif?>
<?
	foreach ($group as $i => $melody):
	$id = 'voximplant'.$ii.$i;
	CHTTP::URN2URI($APPLICATION->GetCurPageParam("mfi_mode=down&fileID=".$fileID."&cid=".$cid."&".bitrix_sessid_get(), array("mfi_mode", "fileID", "cid")))
?>
			<div class="tel-set-item tel-set-item-border">
				<div class="tel-set-item-num">
					<?if (array_key_exists("CHECKBOX", $melody)):?>
					<input name="<?=$melody["CHECKBOX"]?>" type="hidden" value="N" />
					<input type="checkbox" id="checkbox<?=$melody["CHECKBOX"]?>" name="<?=$melody["CHECKBOX"]?>" class="tel-set-checkbox" value="Y" <? if ($arResult["ITEM"][$melody["CHECKBOX"]] == "Y"): ?> checked <? endif; ?> style="margin-top: 3px;" />
					<?endif;?>
				</div>
				<div class="tel-set-item-cont-block">
					<label class="tel-set-cont-item-title" for="checkbox<?=$melody["CHECKBOX"]?>"><?=$melody["TITLE"]?></label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-text"><?=$melody["TIP"]?></div>
						<div class="tel-set-melody-block">
							<span class="tel-set-player-wrap">
								<?

								$APPLICATION->IncludeComponent(
									"bitrix:player",
									"",
									Array(
										"PLAYER_ID" => $id."player",
										"PLAYER_TYPE" => "flv",
										"USE_PLAYLIST" => "N",
										"PATH" => $melody["MELODY"],
										"PROVIDER" => "sound",
										"STREAMER" => "",
										"WIDTH" => "217",
										"HEIGHT" => "24",
										"PREVIEW" => "",
										"FILE_TITLE" => "",
										"FILE_DURATION" => "",
										"FILE_AUTHOR" => "",
										"FILE_DATE" => "",
										"FILE_DESCRIPTION" => "",
										"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
										"SKIN" => "",
										"CONTROLBAR" => "bottom",
										"WMODE" => "opaque",
										"LOGO" => "",
										"LOGO_LINK" => "",
										"LOGO_POSITION" => "none",
										"PLUGINS" => array(),
										"ADDITIONAL_FLASHVARS" => "",
										"AUTOSTART" => "N",
										"REPEAT" => "none",
										"VOLUME" => "90",
										"MUTE" => "N",
										"ADVANCED_MODE_SETTINGS" => "Y",
										"BUFFER_LENGTH" => "2",
										"ALLOW_SWF" => "N"
									),
								null,
								Array(
									'HIDE_ICONS' => 'Y'
								)
								);?>
							</span>
							<span class="tel-set-file-wrap">
								<?$APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
									array(
										'INPUT_NAME' => $melody["INPUT_NAME"],
										'INPUT_VALUE' => array($melody["MELODY_ID"]),
										'MAX_FILE_SIZE' => 2097152,
										'MODULE_ID' => 'voximplant',
										'FORCE_MD5' => true,
										'CONTROL_ID' => $id,
										'MULTIPLE' => 'N',
										'ALLOW_UPLOAD' => 'F',
										'ALLOW_UPLOAD_EXT' => 'mp3'
									),
									$this->component,
									array("HIDE_ICONS" => true)
								);?>
								<span class="tel-set-melody-item" id="<?=$id?>span">
									<span class="tel-set-item-melody-link tel-set-item-melody-link-active"><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE")?></span>
									<span class="tel-set-melody-description" id="<?=$id?>notice" <?if ($melody["MELODY_ID"] > 0) { ?> style="display:none;" <? } ?>><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP")?></span>
								</span>
								<span class="tel-set-melody-item" id="<?=$id?>default" <?if ($melody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
									<span class="tel-set-item-melody-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
								</span>
							</span>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
			function viShowMelodyGroup(button)
			{
				BX.removeClass(button.parentNode.nextElementSibling, 'tel-melody-group-hide');
				BX.remove(button.parentNode);
			}
			</script>
		<? $melodiesToLoad[$id] = $melody; ?>
	<?endforeach;?>
</div>
<?endforeach;?>
			<div class="tel-set-item tel-set-item-border">
				<div class="tel-set-item-cont-block">
					<div class="tel-set-item-alert">
						<?=GetMessage("VI_CONFIG_EDIT_TUNES_TIP")?>
					</div>
				</div>
			</div>
		</div>
<!-- //melody -->
		<?endif;?>
		<div class="tel-set-footer-btn">
			<span class="ui-btn ui-btn-success" data-role="config-edit-submit"><?=GetMessage("VI_CONFIG_EDIT_SAVE")?></span>
			<a href="<?=CVoxImplantMain::GetPublicFolder()?>lines.php?MODE=<?=$arResult["ITEM"]["PORTAL_MODE"] ?><?=$arResult['IFRAME'] ? '&IFRAME=Y' : ''?>"
			   class="ui-btn ui-btn-link">
				<?=GetMessage("VI_CONFIG_EDIT_BACK")?>
			</a>
		</div>
	</div>
</div>


</form>
<script>
	BX.message({
		VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP : '<?=GetMessageJS('VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP')?>',
		VI_CONFIG_EDIT_UPLOAD_SUCCESS : '<?=GetMessageJS("VI_CONFIG_EDIT_UPLOAD_SUCCESS")?>',
		TELEPHONY_PUT_PHONE: '<?=GetMessageJS('TELEPHONY_PUT_PHONE')?>',
		TELEPHONY_VERIFY_PHONE: '<?=GetMessageJS('TELEPHONY_VERIFY_PHONE')?>',
		TELEPHONY_OR: '<?=GetMessageJS('TELEPHONY_OR')?>',
		TELEPHONY_PUT_PHONE_AGAING: '<?=GetMessageJS('TELEPHONY_PUT_PHONE_AGAING')?>',
		TELEPHONY_CONFIRM: '<?=GetMessageJS('TELEPHONY_CONFIRM')?>',
		TELEPHONY_EXAMPLE: '<?=GetMessageJS('TELEPHONY_EXAMPLE')?>',
		TELEPHONY_VERIFY_CODE: '<?=GetMessageJS('TELEPHONY_VERIFY_CODE')?>',
		TELEPHONY_VERIFY_CODE_2: '<?=GetMessageJS('TELEPHONY_VERIFY_CODE_2')?>',
		TELEPHONY_VERIFY_CODE_3: '<?=GetMessageJS('TELEPHONY_VERIFY_CODE_3')?>',
		TELEPHONY_VERIFY_CODE_4: '<?=GetMessageJS('TELEPHONY_VERIFY_CODE_4')?>',
		TELEPHONY_PUT_CODE: '<?=GetMessageJS('TELEPHONY_PUT_CODE')?>',
		TELEPHONY_JOIN: '<?=GetMessageJS('TELEPHONY_JOIN')?>',
		TELEPHONY_RECALL: '<?=GetMessageJS('TELEPHONY_RECALL')?>',
		TELEPHONY_EMPTY_PHONE: '<?=GetMessageJS('TELEPHONY_EMPTY_PHONE')?>',
		TELEPHONY_EMPTY_PHONE_DESC: '<?=GetMessageJS('TELEPHONY_EMPTY_PHONE_DESC')?>',
		TELEPHONY_CONFIRM_PHONE: '<?=GetMessageJS('TELEPHONY_CONFIRM_PHONE')?>',
		TELEPHONY_PHONE: '<?=GetMessageJS('TELEPHONY_PHONE')?>',
		TELEPHONY_JOIN_TEXT: '<?=GetMessageJS('TELEPHONY_JOIN_TEXT')?>',
		TELEPHONY_DELETE_CONFIRM: '<?=GetMessageJS('TELEPHONY_DELETE_CONFIRM')?>',
		TELEPHONY_REJOIN: '<?=GetMessageJS('TELEPHONY_REJOIN')?>',
		TELEPHONY_CONFIRM_DATE: '<?=GetMessageJS('TELEPHONY_CONFIRM_DATE')?>',
		TELEPHONY_ERROR_MONEY_LOW: '<?=GetMessageJS('TELEPHONY_ERROR_MONEY_LOW')?>',
		TELEPHONY_ERROR_PHONE: '<?=GetMessageJS('TELEPHONY_ERROR_PHONE')?>',
		TELEPHONY_VERIFY_ALERT: '<?=GetMessageJS('TELEPHONY_VERIFY_ALERT')?>',
		TELEPHONY_ERROR_BLOCK: '<?=GetMessageJS('TELEPHONY_ERROR_BLOCK')?>',
		TELEPHONY_WRONG_CODE: '<?=GetMessageJS('TELEPHONY_WRONG_CODE')?>',
		TELEPHONY_ERROR_REMOVE: '<?=GetMessageJS('TELEPHONY_ERROR_REMOVE')?>',
		VI_CONFIG_GROUP_SETTINGS: '<?=GetMessageJS('VI_CONFIG_GROUP_SETTINGS')?>',
		VI_CONFIG_GROUP_SETTINGS_HIDE: '<?=GetMessageJS('VI_CONFIG_GROUP_SETTINGS_HIDE')?>'
	});

	BX.ready(function(e)
	{
		BX.VoxImplantConfigEdit.setDefaults({
			maximumGroups: <?= (int)$arResult['DEFAULTS']['MAXIMUM_GROUPS']?>
		});

		BX.voximplantConfigEditor = new BX.VoxImplantConfigEdit({
			node: BX('config_edit_form'),
			melodies: <?= CUtil::PhpToJSObject($melodiesToLoad)?>,
			accessCodes: <?= CUtil::PhpToJSObject($arResult['ITEM']['LINE_ACCESS'])?>
		})
	});

	<?if($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD):?>
		new BX.Voximplant.Sip(<?=(int)$arResult['SIP_CONFIG']['REG_ID']?>);
	<?endif?>
	<?if($arResult['ITEM']['PORTAL_MODE'] == CVoxImplantConfig::MODE_LINK):?>
		BX.ready(function(){
			BX.ViCallerId.init({
				'placeholder': BX('backphone-placeholder'),
				'number': "<?=$arResult['CALLER_ID']['PHONE_NUMBER']?>",
				'numberFormatted': "<?=$arResult['CALLER_ID']['PHONE_NUMBER_FORMATTED']?>",
				'verified': "<?=$arResult['CALLER_ID']['VERIFIED']?>",
				'verifiedUntil': "<?=$arResult['CALLER_ID']['VERIFIED_UNTIL']?>"
			});
		});
	<?endif?>
</script>
