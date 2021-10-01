<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @global array $arResult
 */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/voximplant.config.edit/templates/.default/style.css');

CJSCore::Init(["socnetlogdest", "voximplant.common", "sidepanel", "ui.hint", "ui.buttons"]);

if ($arResult['ERROR'])
{
	ShowError($arResult['ERROR']);
}

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>
<script>
	BX.message({
		VI_CONFIG_EDIT_QUEUE_TIME_2: '<?=GetMessageJS("VI_CONFIG_EDIT_QUEUE_TIME_2")?>',
		VI_CONFIG_EDIT_QUEUE_TIME_QUEUE_ALL_2: '<?=GetMessageJS("VI_CONFIG_EDIT_QUEUE_TIME_QUEUE_ALL_2")?>',
	})
</script>
<div id="group_edit_form" class="voximplant-container">
	<?= bitrix_sessid_post() ?>
	<input type="hidden" name="action" value="save"/>
	<input type="hidden" name="ID" value="<?= htmlspecialcharsbx($arResult['ITEM']['ID']) ?>"/>
	<div class="voximplant-edit-title">
		<input type="text" class="voximplant-edit-title-input"
			   value="<?= htmlspecialcharsbx($arResult["ITEM"]["NAME"]) ?>"
			   name="NAME"
			   placeholder="<?= GetMessage("VI_CONFIG_EDIT_QUEUE_NAME_PLACEHOLDER") ?>">
	</div>
	<div class="voximplant-number-settings-row">
		<div class="voximplant-control-row">
			<div class="voximplant-number-settings-text">
				<?= GetMessage("VI_CONFIG_EDIT_QUEUE_TIP_2") ?>
			</div>
			<div class="tel-set-destination-container" id="users_for_queue"></div>
			<div class="voximplant-control-row">
				<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_GROUP_PHONE_NUMBER") ?></div>
				<input type="text"
					   name="PHONE_NUMBER"
					   class="voximplant-control-input"
					   placeholder="<?=Loc::getMessage("VI_CONFIG_EDIT_NOT_SPECIFIED")?>"
					   maxlength="4"
					   value="<?= htmlspecialcharsbx($arResult["ITEM"]["PHONE_NUMBER"])?>"
				>
			</div>
			<div class="voximplant-control-row">
				<div class="voximplant-control-subtitle"><?= GetMessage("VI_CONFIG_EDIT_QUEUE_TYPE_2") ?></div>
				<div class="voximplant-control-select-flexible">
					<select class="voximplant-control-select" name="TYPE" id="QUEUE_TYPE">
						<? foreach (array(CVoxImplantConfig::QUEUE_TYPE_EVENLY, CVoxImplantConfig::QUEUE_TYPE_STRICTLY, CVoxImplantConfig::QUEUE_TYPE_ALL) as $k): ?>
							<option value="<?= $k ?>"<?= ($k == $arResult["ITEM"]["TYPE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_QUEUE_TYPE_".mb_strtoupper($k)) ?></option>
						<? endforeach; ?>
					</select>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(GetMessage("VI_CONFIG_EDIT_QUEUE_TYPE_TIP"))?><br><br><?=htmlspecialcharsbx(GetMessage("VI_CONFIG_EDIT_QUEUE_TYPE_TIP_2"))?><br><i><?=htmlspecialcharsbx(GetMessage("VI_CONFIG_EDIT_QUEUE_TYPE_TIP_ASTERISK_3"))?></i>"></span>
					<? if (!\Bitrix\Voximplant\Limits::isQueueAllAllowed() || CVoxImplantAccount::IsDemo()): ?>
						<div class="tel-lock-holder-select" title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
							<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_call_to_all')"
								 class="tel-lock tel-lock-half <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>">
							</div>
						</div>
					<? endif; ?>
				</div>
			</div>
			<div class="voximplant-control-row">
				<div id="vi_queue_time_hint" class="voximplant-control-subtitle">
					<?= $arResult["ITEM"]["TYPE"] == CVoxImplantConfig::QUEUE_TYPE_ALL ? GetMessage("VI_CONFIG_EDIT_QUEUE_TIME_QUEUE_ALL_2") : GetMessage("VI_CONFIG_EDIT_QUEUE_TIME_2") ?>
				</div>
				<select class="voximplant-control-select" name="WAIT_TIME">
					<? foreach (array("2", "3", "4", "5", "6", "7") as $k): ?>
						<option value="<?= $k ?>"<?= ($k == $arResult["ITEM"]["WAIT_TIME"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_QUEUE_AMOUNT_OF_BEEPS_BEFORE_REDIRECT_".$k) ?></option>
					<? endforeach; ?>
				</select>
			</div>
		</div>
		<div class="voximplant-title-dark"><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_2") ?></div>
		<div class="voximplant-number-settings-row">
			<div class="voximplant-number-settings-text">
				<?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_TIP_2") ?>
			</div>
			<div class="voximplant-control-row">
				<div class="voximplant-control-subtitle">
					<?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_NAME") ?>
				</div>
				<div class="voximplant-control-select-flexible">
					<select id="vi_no_answer_rule" class="voximplant-control-select" name="NO_ANSWER_RULE">
						<option value="<?= CVoxImplantIncoming::RULE_VOICEMAIL ?>"<?= (CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_2") ?></option>
						<option value="<?= CVoxImplantIncoming::RULE_PSTN ?>"<?= (CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_3_2") ?></option>
						<option value="<?= CVoxImplantIncoming::RULE_PSTN_SPECIFIC ?>"<?= (CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_5") ?></option>
						<option value="<?= CVoxImplantIncoming::RULE_QUEUE ?>"<?= (CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_QUEUE") ?></option>
						<option value="<?= CVoxImplantIncoming::RULE_NEXT_QUEUE ?>"
							<?= (CVoxImplantIncoming::RULE_NEXT_QUEUE == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>
							<?= (\Bitrix\Voximplant\Limits::isRedirectToQueueAllowed() ? '' : 'style="color: #636363;"') ?>
						>
							<?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_7") ?>
						</option>
						<option value="<?= CVoxImplantIncoming::RULE_HUNGUP ?>"<?= (CVoxImplantIncoming::RULE_HUNGUP == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_4") ?></option>
					</select>
					<? if (!\Bitrix\Voximplant\Limits::isRedirectToQueueAllowed() || CVoxImplantAccount::IsDemo()): ?>
						<div class="tel-lock-holder-select" title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
							<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_missed_call_forward')"
								 class="tel-lock tel-lock-half <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
						</div>
					<? endif; ?>
				</div>
			</div>
			<div id="vi_forward_number"
				 class="voximplant-control-row <?= (CVoxImplantIncoming::RULE_PSTN_SPECIFIC !== $arResult["ITEM"]["NO_ANSWER_RULE"] ? 'inactive' : '') ?>"
				 style="max-height: 100px;">
				<div class="voximplant-control-subtitle">
					<?= GetMessage("VI_CONFIG_EDIT_FORWARD_NUMBER_2") ?>
				</div>
				<input class="voximplant-control-input" type="text" name="FORWARD_NUMBER" value="<?= htmlspecialcharsbx($arResult["ITEM"]["FORWARD_NUMBER"]) ?>">
			</div>
			<div id="vi_next_queue"
				 class="voximplant-control-row <?= (CVoxImplantIncoming::RULE_NEXT_QUEUE !== $arResult["ITEM"]["NO_ANSWER_RULE"] ? 'inactive' : '') ?>"
				 style="max-height: 100px;">
				<div class="voximplant-control-subtitle">
					<?= GetMessage("VI_CONFIG_EDIT_NEXT_QUEUE_2") ?>
				</div>
				<select class="voximplant-control-select" name="NEXT_QUEUE_ID">
					<? foreach ($arResult['QUEUE_LIST'] as $queue): ?>
						<option value="<?= (int)$queue['ID'] ?>" <?= ($queue['ID'] == $arResult['ITEM']['NEXT_QUEUE_ID'] ? 'selected' : '') ?>><?= htmlspecialcharsbx($queue['NAME']) ?></option>
					<? endforeach ?>
				</select>
			</div>
			<div class="voximplant-number-settings-choice">
				<input id="vi_allow_intercept"
					   class="voximplant-number-settings-label" value="Y"
					   type="checkbox"
					   name="ALLOW_INTERCEPT"
					   <? if ($arResult["ITEM"]["ALLOW_INTERCEPT"] === "Y"): ?>checked="checked"<? endif ?>
					   data-locked="<?= (\Bitrix\Voximplant\Limits::canInterceptCall() ? "0" : "1") ?>"
				/>
				<label for="vi_allow_intercept"><?= GetMessage("VI_CONFIG_EDIT_ALLOW_INTERCEPT") ?></label>
				<? if (!\Bitrix\Voximplant\Limits::canInterceptCall() || CVoxImplantAccount::IsDemo()): ?>
					<div class="tel-lock-holder-select" title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
						<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_intercept')"
							 class="tel-lock <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
					</div>
				<? endif; ?>
			</div>
		</div>
	</div>
	<div class="tel-set-footer-btn">
		<span class="ui-btn ui-btn-success" data-role="vi-group-edit-submit">
			<?= GetMessage("VI_CONFIG_EDIT_SAVE") ?>
		</span>
		<? if ($arResult['INLINE_MODE']): ?>
			<span class="ui-btn ui-btn-link" data-role="vi-group-edit-cancel">
				<?= GetMessage("VI_CONFIG_EDIT_CANCEL") ?>
			</span>
		<? else: ?>
			<a href="<?= CVoxImplantMain::GetPublicFolder().'groups.php' ?>"
			   class="ui-btn ui-btn-link"><?= GetMessage("VI_CONFIG_EDIT_BACK") ?></a>
		<? endif ?>
	</div>
</div>

<script>
	BX.ready(function ()
	{
		BX.message({
			LM_ADD1: '<?=GetMessageJS("LM_ADD1")?>',
			LM_ADD2: '<?=GetMessageJS("LM_ADD2")?>'
		});

		new BX.ViGroupEdit({
			node: BX('group_edit_form'),
			destinationParams: <?= CUtil::PhpToJSObject($arResult["DESTINATION"])?>,
			rulePstnSpecific: '<?= CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>',
			groupListUrl: '<?= CVoxImplantMain::GetPublicFolder()."groups.php"?>',
			inlineMode: <?= $arResult['INLINE_MODE'] ? 'true' : 'false'?>,
			externalRequestId: '<?= CUtil::JSEscape($arResult["EXTERNAL_REQUEST_ID"])?>',
			maximumGroupMembers: <?= $arResult['MAXIMUM_GROUP_MEMBERS']?>
		});
		BX.UI.Hint.init(BX('group_edit_form'));
	});
</script>

<script type="text/javascript">
	<?if (!\Bitrix\Voximplant\Limits::isQueueAllAllowed()):?>
	var queueType = BX('QUEUE_TYPE');
	for (var i = 0; i < queueType.options.length; i++)
	{
		if (queueType.options[i].value == '<?=CVoxImplantConfig::QUEUE_TYPE_ALL?>')
		{
			queueType.options[i].style = "color: #636363;";
		}
	}
	<?endif;?>
	BX.bind(BX('QUEUE_TYPE'), 'change', function (e)
	{
		<?if (!\Bitrix\Voximplant\Limits::isQueueAllAllowed()):?>
		if (this.options[this.selectedIndex].value == '<?=CVoxImplantConfig::QUEUE_TYPE_ALL?>')
		{
			BX.UI.InfoHelper.show('limit_contact_center_telephony_call_to_all');
			this.selectedIndex = 0;
			return false;
		}
		<?endif;?>

		if (this.options[this.selectedIndex].value == '<?=CVoxImplantConfig::QUEUE_TYPE_ALL?>')
		{
			BX('vi_queue_time_hint').innerText = BX.message('VI_CONFIG_EDIT_QUEUE_TIME_QUEUE_ALL_2');
		}
		else
		{
			BX('vi_queue_time_hint').innerText = BX.message('VI_CONFIG_EDIT_QUEUE_TIME_2');
		}
	});
</script>

<? if (!\Bitrix\Voximplant\Limits::isRedirectToQueueAllowed()): ?>
	<script>
		BX.bind(BX('vi_no_answer_rule'), 'bxchange', function (e)
		{
			var noAnswerSelect = e.target;
			if (noAnswerSelect.value == '<?=CVoxImplantIncoming::RULE_NEXT_QUEUE?>')
			{
				BX.UI.InfoHelper.show('limit_contact_center_telephony_missed_call_forward');
				noAnswerSelect.selectedIndex = 0;
				return false;
			}
		})
	</script>
<? endif; ?>

