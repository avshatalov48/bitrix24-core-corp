<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\WebForm\WhatsApp;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use \Bitrix\Main\UI\Extension;

/** @var CMain $APPLICATION */
/** @var array $arResult */
/** @var array $arParams */

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.hint',
	'ui.alerts',
	'ui.buttons',
	'ui.buttons.icons',
]);

if(!$arResult['BUTTON']['BACKGROUND_COLOR'])
{
	$arResult['BUTTON']['BACKGROUND_COLOR'] = '#00AEEF';
}

if(!$arResult['BUTTON']['ICON_COLOR'])
{
	$arResult['BUTTON']['ICON_COLOR'] = '#FFFFFF';
}

$serverAddress = \Bitrix\Crm\SiteButton\ResourceManager::getServerAddress();
$serverAddress .= $this->GetFolder() . '/images/';
$arResult['HELLO']['ICONS'] = array(
	array('PATH' => $serverAddress . 'upload-girl-mini-1.png'),
	array('PATH' => $serverAddress . 'upload-girl-mini-2.png'),
	array('PATH' => $serverAddress . 'upload-girl-mini-3.png'),
	array('PATH' => $serverAddress . 'upload-girl-mini-4.png'),
	array('PATH' => $serverAddress . 'upload-man-mini-1.png'),
	array('PATH' => $serverAddress . 'upload-man-mini-2.png'),
	array('PATH' => $serverAddress . 'upload-man-mini-3.png'),
	array('PATH' => $serverAddress . 'upload-man-mini-4.png'),
);

$helloDefCond = array(
	'ICON' => $arResult['HELLO']['ICONS'][0]['PATH'],
	'NAME' => Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_DEF_NAME'),
	'TEXT' => Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_DEF_TEXT'),
	'DELAY' => 1,
	'PAGES' => array(),
);
if (count($arResult['HELLO']['CONDITIONS']) == 0)
{
	$arResult['HELLO']['CONDITIONS'][] = $helloDefCond;
}

foreach ($arResult['ADDITIONAL_CSS'] as $item)
{
	$this->addExternalCss($item);
}

CJSCore::Init(array('clipboard', 'uploader', 'avatar_editor', 'color_picker', 'sidepanel'));
if (\Bitrix\Main\Loader::includeModule('imconnector'))
{
	\Bitrix\ImConnector\Connector::initIconCss();
}
$APPLICATION->SetPageProperty(
	"BodyClass",
	$APPLICATION->GetPageProperty("BodyClass") . " no-all-paddings no-background"
);


if(\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
}

$getFormattedScript = function ($script)
{
	$script = htmlspecialcharsbx($script);
	$script = str_replace("\t", str_repeat('&nbsp;', 8), $script);
	return nl2br($script);
}
?>

<script>
	BX.ready(function(){
		new CrmButtonEditor(<?=Json::encode(array(
			'id' => $arResult['BUTTON']['ID'],
			'isFrame' => $arParams['IFRAME'],
			'isSaved' => $arParams['IS_SAVED'],
			'reloadList' => true,
			'setupWhatsAppLink' => WhatsApp::getSetupLink(),
			'defaultWorkTime' => $arResult['DEFAULT_WORK_TIME'],
			'dictionaryTypes' => $arResult['BUTTON_WIDGET_TYPES'],
			'dictionaryPathEdit' => $arResult['BUTTON_ITEMS_DICTIONARY_PATH_EDIT'],
			'linesData' => $arResult['LINES_DATA'],
			'canRemoveCopyright' => $arResult['CAN_REMOVE_COPYRIGHT'],
			'showWebformRestrictionPopup' => $arResult['WEBFORM_RESTRICTION_POPUP'],
			'canUseMultiLines' => $arResult['CAN_USE_MULTI_LINES'],
			'showMultilinesRestrictionPopup' => $arResult['MULTI_LINES_RESTRICTION_POPUP'],
			'pathToButtonList' => $arParams['PATH_TO_BUTTON_LIST'],
			'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
			'langs' => $arResult['LANGUAGES']['LIST'],
			'mess' => array(
				'errorAction' => Loc::getMessage('CRM_BUTTON_EDIT_ERROR_ACTION'),
				'dlgBtnClose' => Loc::getMessage('CRM_BUTTON_EDIT_CLOSE'),
				'deleteConfirmation' => Loc::getMessage('CRM_BUTTON_EDIT_DELETE_CONFIRM'),
			)
		));?>);
	});
</script>

<?
if (!empty($arResult['ERRORS']))
{
	?><div class="crm-button-edit-top-block"><?
	foreach ($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
	?></div><?
}
?>


<form id="crm_button_main_form" method="post" action="<?=$APPLICATION->GetCurPageParam()?>">
	<?=bitrix_sessid_post()?>

<?

$showItemWorkTimeInterface = function ($item) use($arResult)
{
	$workTime = $arResult['WORK_TIME'];
	$type = htmlspecialcharsbx($item['TYPE']);
	?>
	<div class="crm-button-edit-channel-lines-display-options-time">
		<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-checkbox-container">
			<input class="crm-button-edit-channel-lines-display-options-time-checkbox" data-crm-button-item-worktime-btn="<?=$type?>"
				type="checkbox" name="ITEMS[<?=$type?>][WORK_TIME][ENABLED]"
				id="ITEMS_<?=$type?>_WORK_TIME_ENABLED"
				<?=($item['WORK_TIME']['ENABLED'] ? 'checked' : '')?> value="Y"
				data-crm-wt-enabled=""
			>
			<label class="crm-button-edit-channel-lines-display-options-time-label" for="ITEMS_<?=$type?>_WORK_TIME_ENABLED">
				<?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_ENABLED')?>
			</label>
		</div>

		<div class="crm-button-edit-channel-lines-display-options-time-inner" style="position: relative;" data-crm-button-item-worktime="<?=$type?>">
			<div data-crm-wt-shadow="" style="<?=($item['WORK_TIME']['ENABLED'] ? 'display: none;' : '')?>" class="crm-button-edit-channel-lines-display-options-time-shadow"></div>

			<div class="crm-button-edit-channel-lines-display-options-time-title"><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_TIME_ZONE')?></div>
			<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-select-container">
				<select class="crm-button-edit-channel-lines-display-options-time-select-max-width-mid crm-button-edit-hello-select-item crm-button-edit-channel-lines-display-options-time-select"
					name="ITEMS[<?=$type?>][WORK_TIME][TIME_ZONE]"
					id="ITEMS_<?=$type?>_WORK_TIME_TIME_ZONE"
				>
					<?foreach ($workTime['TIME_ZONE']['LIST'] as $value => $name):
						$selected = $value == $item['WORK_TIME']['TIME_ZONE'] ? 'selected' : '';
						?>
						<option value="<?=htmlspecialcharsbx($value)?>" <?=$selected?>>
							<?=htmlspecialcharsbx($name)?>
						</option>
					<?endforeach;?>
				</select>
			</div>

			<div class="crm-button-edit-channel-lines-display-options-time-title"><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_TIME')?></div>
			<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-select-container">
				<select class="crm-button-edit-hello-select-item crm-button-edit-channel-lines-display-options-time-select-max-width-short crm-button-edit-channel-lines-display-options-time-select" name="ITEMS[<?=$type?>][WORK_TIME][TIME_FROM]"
						data-crm-wt-time-from=""
						id="ITEMS_<?=$type?>_WORK_TIME_TIME_FROM"
				>
					<?foreach ($workTime['TIME_LIST'] as $value => $name):
						$selected = $value == $item['WORK_TIME']['TIME_FROM'] ? 'selected' : '';
						?>
						<option value="<?=htmlspecialcharsbx($value)?>" <?=$selected?>>
							<?=htmlspecialcharsbx($name)?>
						</option>
					<?endforeach;?>
				</select>
				&nbsp; - &nbsp;
				<select class="crm-button-edit-hello-select-item crm-button-edit-channel-lines-display-options-time-select-max-width-short crm-button-edit-channel-lines-display-options-time-select" name="ITEMS[<?=$type?>][WORK_TIME][TIME_TO]"
						data-crm-wt-time-to=""
						id="ITEMS_<?=$type?>_WORK_TIME_TIME_TO"
				>
					<?foreach ($workTime['TIME_LIST'] as $value => $name):
						$selected = $value == $item['WORK_TIME']['TIME_TO'] ? 'selected' : '';
						?>
						<option value="<?=htmlspecialcharsbx($value)?>" <?=$selected?>>
							<?=htmlspecialcharsbx($name)?>
						</option>
					<?endforeach;?>
				</select>
			</div>

			<div class="crm-button-edit-channel-lines-display-options-time-title" data-crm-wt-days-caption=""><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_DAY_OFF')?></div>
			<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-weekends">
				<?foreach ($workTime['NAMED_WEEK_DAY_LIST'] as $value => $name):
					$selected = in_array($value, $item['WORK_TIME']['DAY_OFF']) ? 'checked' : '';
					?>
					<input class="crm-button-edit-channel-lines-display-options-time-checkbox"
						data-crm-wt-days=""
						data-crm-wt-day-label="<?=htmlspecialcharsbx($name)?>"
						type="checkbox" name="ITEMS[<?=$type?>][WORK_TIME][DAY_OFF][]"
						id="ITEMS_<?=$type?>_WORK_TIME_DAY_OFF_<?=htmlspecialcharsbx($value)?>"
						value="<?=htmlspecialcharsbx($value)?>" <?=$selected?>
					>
					<label class="crm-button-edit-channel-lines-display-options-time-checkbox-name" for="ITEMS_<?=$type?>_WORK_TIME_DAY_OFF_<?=htmlspecialcharsbx($value)?>">
						<?=htmlspecialcharsbx($name)?>
					</label>
				<?endforeach;?>
			</div>

			<div class="crm-button-edit-channel-lines-display-options-time-title"><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_HOLIDAYS')?></div>
			<div class="crm-button-edit-channel-lines-display-options-time-block crm-button-edit-channel-lines-display-options-time-margin-bottom">
				<input type="text" class="crm-button-edit-channel-lines-display-options-time-select-max-width-mid crm-button-edit-channel-lines-display-options-links-block-item crm-button-edit-channel-lines-display-options-time-input"
					id="ITEMS_<?=$type?>_WORK_TIME_HOLIDAYS"
					name="ITEMS[<?=$type?>][WORK_TIME][HOLIDAYS]"
					value="<?=htmlspecialcharsbx($item['WORK_TIME']['HOLIDAYS'])?>"
				>
				<div class="crm-button-edit-channel-lines-display-options-settings-item crm-button-edit-channel-lines-display-options-time-weekends-example">(<?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_HOLIDAYS_EXAMPLE')?>)	</div>
			</div>

			<?if (isset($workTime['ACTIONS'][$type])):?>

				<div class="crm-button-edit-channel-lines-display-options-time-title"><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_ACTION_RULE')?></div>
				<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-select-container">
					<select class="crm-button-edit-channel-lines-display-options-time-select-max-width-mid crm-button-edit-hello-select-item crm-button-edit-channel-lines-display-options-time-select" data-crm-button-item-worktime-action-rule="<?=$type?>" name="ITEMS[<?=$type?>][WORK_TIME][ACTION_RULE]" id="ITEMS_<?=$type?>_WORKTIME_ACTION_RULE" class="tel-set-inp tel-set-item-select">
						<?foreach ($workTime['ACTIONS'][$type] as $value => $name):
							$selected = $value == $item['WORK_TIME']['ACTION_RULE'] ? 'selected' : '';
							?>
							<option value="<?=htmlspecialcharsbx($value)?>" <?=$selected?>>
								<?=htmlspecialcharsbx($name)?>
							</option>
						<?endforeach;?>
					</select>
				</div>

				<div data-crm-button-item-worktime-action-text="<?=$type?>">

					<div class="crm-button-edit-channel-lines-display-options-time-title"><?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_FIELD_ACTION_TEXT')?></div>
					<div class="crm-button-edit-channel-lines-display-options-time-margin-bottom crm-button-edit-channel-lines-display-options-time-input-container">
						<input class="crm-button-edit-channel-lines-display-options-time-select-max-width-mid crm-button-edit-channel-lines-display-options-links-block-item crm-button-edit-channel-lines-display-options-time-input" type="text" name="ITEMS[<?=$type?>][WORK_TIME][ACTION_TEXT]" value="<?=htmlspecialcharsbx($item['WORK_TIME']['ACTION_TEXT'])?>">
					</div>

				</div>
			<?endif;?>

		</div>
	</div>
	<?
};


$showItemInterface = function ($item) use($arResult, $showItemWorkTimeInterface)
{
	if(!$item)
	{
		return;
	}

	$type = htmlspecialcharsbx($item['TYPE']);
	$typeName = htmlspecialcharsbx($item['TYPE_NAME']);
	?>
	<div class="crm-button-edit-channel-lines-container <?=($item['ACTIVE'] == 'Y' ? "crm-button-edit-channel-lines-container-active" : "")?>" data-bx-crm-button-item="<?=$type?>">
		<div class="crm-button-edit-channel-lines-title-container">
			<div class="crm-button-edit-channel-lines-title-inner">
				<span class="crm-button-edit-channel-lines-title-icon crm-button-edit-channel-lines-title-icon-default-<?=$type?>"></span>
				<span class="crm-button-edit-channel-lines-title-item"><?=$typeName?>:</span>
			</div>
			<div class="crm-button-edit-channel-lines-title-activate-container <?=($item['ACTIVE'] == 'Y' ? 'crm-button-edit-channel-lines-title-on' : 'crm-button-edit-channel-lines-title-off')?>" data-bx-crm-button-item-active="<?=$type?>">
				<div class="crm-button-edit-channel-lines-title-activate-button-container">
					<span class="crm-button-edit-channel-lines-title-activate-button">
						<span class="crm-button-edit-channel-lines-title-activate-button-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_ON')?></span>
					</span>
					<span class="crm-button-edit-channel-lines-title-not-activate-button">
						<span class="crm-button-edit-channel-lines-title-activate-button-cursor"></span>
						<span class="crm-button-edit-channel-lines-title-not-activate-button-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_OFF')?></span>
					</span>
				</div>
			</div>

			<input type="hidden" data-bx-crm-button-item-active-val="<?=$type?>" name="ITEMS[<?=$type?>][ACTIVE]" value="<?=htmlspecialcharsbx($item['ACTIVE'])?>">

		</div><!--crm-button-edit-channel-lines-title-container-->
		<div class="crm-button-edit-channel-lines-inner-wrapper">
			<?if(count($item['LIST']) == 0 || ($item['TYPE'] === 'whatsapp' && !WhatsApp::isSetupCompleted())):?>
				<div class="crm-button-edit-channel-make-line">
					<?if ($item['TYPE'] === 'whatsapp'):?>
						<div class="crm-button-edit-channel-make-line-description">
							<span class="crm-button-edit-channel-make-line-description">
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_ADD_DESC')?>
							</span>
						</div>
						<div class="crm-button-edit-channel-make-line-button">
							<a
								class="crm-button-edit-channel-make-line-button webform-small-button webform-small-button-blue"
								data-bx-crm-button-item-channel-setup-whatsapp=""
							>
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_SETUP')?>
							</a>
						</div>
						<a class="crm-button-edit-channel-lines-display-options-settings-button--whatsapp" href="">
							<?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_SETUP_APPROVE')?>
						</a>

					<?else:?>
						<div class="crm-button-edit-channel-make-line-description">
							<span class="crm-button-edit-channel-make-line-description">
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_ADD_DESC')?>
							</span>
						</div>
						<div class="crm-button-edit-channel-make-line-button">
							<a href="<?=htmlspecialcharsbx($item['PATH_LIST'])?>"
							   class="crm-button-edit-channel-make-line-button webform-small-button webform-small-button-blue"
							   data-bx-crm-button-item-channel-setup="<?=($type === 'openline' ? 'sidepanel' : '')?>"
							>
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_SETUP')?>
							</a>
						</div>
					<?endif;?>
				</div><!--crm-button-edit-channel-make-line-->
			<?else:?>
			<div class="crm-button-edit-channel-lines-inner-container">

				<?if ($type != 'openline' && $type !== 'whatsapp'):?>
				<div class="crm-button-edit-channel-lines-inner-create-container">
					<div class="crm-button-edit-channel-lines-inner-create-select-container">
						<select data-bx-crm-button-widget-select="<?=$type?>" id="ITEMS_<?=$type?>" name="ITEMS[<?=$type?>][EXTERNAL_ID]" class="crm-button-edit-channel-lines-inner-create-select-item">
							<?foreach($item['LIST'] as $external):?>
								<option value="<?=htmlspecialcharsbx($external['ID'])?>" <?=($external['SELECTED'] ? 'selected' : '')?>>
									<?=htmlspecialcharsbx($external['NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
					<div class="crm-button-edit-channel-lines-inner-create-button-container">
						<a data-bx-slider-href="" data-bx-crm-button-widget-btn-edit="<?=$type?>" href="" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_EDIT')?></a>
						<?if($item['PATH_ADD']):?>
							<a data-bx-slider-href=""  href="<?=htmlspecialcharsbx($item['PATH_ADD'])?>" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_ADD')?></a>
						<?endif;?>
					</div>
				</div>
				<?endif;?>

				<?if ($type === 'whatsapp'):?>
				<div class="crm-button-edit-channel-lines-inner-create-container">
					<div class="crm-button-edit-channel-lines-inner-create-select-container">
						<select data-bx-crm-button-widget-select="<?=$type?>" id="ITEMS_<?=$type?>" name="ITEMS[<?=$type?>][EXTERNAL_ID]" class="crm-button-edit-channel-lines-inner-create-select-item">
							<?foreach($item['LIST'] as $external):?>
								<option value="<?=htmlspecialcharsbx($external['ID'])?>" <?=($external['SELECTED'] ? 'selected' : '')?>>
									<?=htmlspecialcharsbx($external['NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
					<div class="crm-button-edit-channel-lines-inner-create-button-container">
						<a data-bx-slider-href="" data-bx-crm-button-widget-btn-edit="<?=$type?>" href="" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_EDIT_FORM')?></a>

						<a data-bx-slider-href="" data-bx-crm-button-item-channel-setup-whatsapp="" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_EDIT_WHATSAPP')?></a>

					</div>
				</div>
				<?endif;?>

				<?if ($type == 'openline'):

					$ids = array();
					foreach ($item['LIST'] as $external)
					{
						$ids[] = $external['ID'];
					}

					$defId = '';
					$defIdConfig = array();
					$config = isset($item['CONFIG']) ? $item['CONFIG'] : array();
					$extList = array();
					if ($item['EXTERNAL_ID'])
					{
						$existedListTmp = explode(',', $item['EXTERNAL_ID']);
						TrimArr($existedListTmp);
						foreach ($existedListTmp as $existedId)
						{
							if (in_array($existedId, $ids))
							{
								if ($defId)
								{
									$existedIdConfig = array();
									if (isset($config[$existedId]))
									{
										$existedIdConfig = $config[$existedId];
									}
									$extList[$existedId] = Json::encode($existedIdConfig);
								}
								else
								{
									$defId = $existedId;
									if (isset($config[$existedId]))
									{
										$defIdConfig = $config[$existedId];
									}
								}
							}
						}
					}

					if (!$defId)
					{
						$defId = $ids[0];
					}

					if (!$item['EXTERNAL_ID'])
					{
						$item['EXTERNAL_ID'] = $defId;
					}

					?>
					<div id="items_openline_container">

						<input data-bx-external-id="" type="hidden" name="ITEMS[openline][EXTERNAL_ID]" value="<?=htmlspecialcharsbx($item['EXTERNAL_ID'])?>">

						<div data-bx-list-def="">
							<?=getCrmButtonEditTemplateLine(
								array(
									'%lineid%' => $defId,
									'%lineconfig%' => htmlspecialcharsbx(Json::encode($defIdConfig))
								), $item['PATH_ADD']
							)?>
						</div>

						<?if(!$arResult['CAN_USE_MULTI_LINES']):?>

							<div id="USE_MULTI_LINES" class="crm-button-edit-channel-lines-social-link crm-button-edit-channel-lines-social-link-icon crm-button-edit-channel-lines-social-link-icon-lock crm-button-edit-channel-lines-social-no-margin">
								<span class="crm-button-edit-channel-lines-social-link-item">
									<?=Loc::getMessage('CRM_BUTTON_EDIT_OPENLINE_USE_MULTI_LINES')?>
								</span>
							</div>

						<?else:?>

							<div class="crm-button-edit-channel-lines-display-options-title-container">
								<span class="crm-button-edit-channel-lines-display-options-title-item">
									<?=Loc::getMessage('CRM_BUTTON_EDIT_OPENLINE_ANOTHER_CHANNELS')?>:
								</span>
							</div>

							<?if (count($extList) == 0):?>
								<div data-bx-add-desc="" class="crm-button-edit-channel-lines-wrap">
									<div class="crm-button-edit-channel-lines-social-name">
										<?=Loc::getMessage('CRM_BUTTON_EDIT_OPENLINE_ANOTHER_CHANNELS_EMPTY')?>
									</div>
								</div>
							<?endif;?>

							<div data-bx-list-ext="">
								<?foreach ($extList as $existedId => $existedConfig):?>
									<?=getCrmButtonEditTemplateLine(
										array(
											'%lineid%' => $existedId,
											'%lineconfig%' => htmlspecialcharsbx($existedConfig)
										)
									)?>
								<?endforeach;?>
							</div>

							<div class="crm-button-edit-channel-lines-social-link">
								<span data-bx-add="" class="crm-button-edit-channel-lines-social-link-item">
									<?=Loc::getMessage('CRM_BUTTON_EDIT_OPENLINE_ADD')?>
								</span>
							</div>
						<?endif?>
					</div>

				<?endif?>

				<?if ($type == 'callback'):?>
					<div class="crm-button-edit-channel-lines-phone-container">
						<span class="crm-button-edit-channel-lines-phone-description">
							<?=Loc::getMessage('CRM_BUTTON_EDIT_DETAIL_CALLBACK_PHONE_NUMBER')?>:
						</span>
						<span id="<?=$type?>_phone_number" class="crm-button-edit-channel-lines-phone-number"></span>
					</div>
				<?endif?>


				<?if ($type == 'whatsapp'):?>
					<div class="crm-button-edit-channel-lines-phone-container">
						<span class="crm-button-edit-channel-lines-phone-description">
							<?=Loc::getMessage('CRM_BUTTON_EDIT_DETAIL_CALLBACK_PHONE_NUMBER')?>:
						</span>
						<span id="<?=$type?>_phone_number" class="crm-button-edit-channel-lines-phone-number"></span>
					</div>
				<?endif?>

				<?if ($type == 'crmform'):?>
					<div class="crm-button-edit-channel-lines-phone-container">
						<span class="crm-button-edit-channel-lines-phone-description">
							<?=Loc::getMessage('CRM_BUTTON_EDIT_DETAIL_CRMFORM_FIELDS')?>:
						</span>
						<span id="<?=$type?>_fields" class="crm-button-edit-channel-lines-phone-number"></span>
					</div>
				<?endif?>


			</div><!--crm-button-edit-channel-lines-inner-container-->

			<div class="crm-button-edit-channel-lines-display-options-container">
				<div class="crm-button-edit-channel-lines-display-options-inner-container">
					<div class="crm-button-edit-channel-lines-display-options-title-container">
						<span class="crm-button-edit-channel-lines-display-options-title-item">
							<?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME')?>:
						</span>
					</div>
					<div class="crm-button-edit-channel-lines-display-options-settings-container">
						<div class="crm-button-edit-channel-lines-display-options-settings-button-container">
							<span data-crm-button-item-settings-wt-btn="<?=$type?>" class="crm-button-edit-channel-lines-display-options-settings-button">
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_PAGE_SETTINGS_SETUP')?>
							</span>
							<span class="crm-button-edit-channel-lines-display-options-settings-triangle">
								<span class="crm-button-edit-channel-lines-display-options-settings-triangle-item"></span>
							</span>
						</div>
							<div class="crm-button-edit-channel-lines-display-options-settings-descriptions-container">
							<span data-crm-button-item-settings-wt-txt="<?=$type?>" data-crm-wt-def="<?=Loc::getMessage('CRM_BUTTON_EDIT_WORK_TIME_DISABLED')?>" class="crm-button-edit-channel-lines-display-options-settings-item">

							</span>
						</div>
					</div>
				</div><!--crm-button-edit-channel-lines-display-options-inner-container-->
				<div data-crm-button-item-settings-wt="<?=$type?>" class="crm-button-edit-channel-lines-display-options-links-container">
					<?$showItemWorkTimeInterface($item);?>
				</div>
			</div>


			<div class="crm-button-edit-channel-lines-display-options-container">
				<div class="crm-button-edit-channel-lines-display-options-inner-container">
					<div class="crm-button-edit-channel-lines-display-options-title-container">
						<span class="crm-button-edit-channel-lines-display-options-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_PAGE_SETTINGS')?>:</span>
					</div>
					<div class="crm-button-edit-channel-lines-display-options-settings-container">
						<div class="crm-button-edit-channel-lines-display-options-settings-button-container">
							<span data-crm-button-item-settings-btn="<?=$type?>" class="crm-button-edit-channel-lines-display-options-settings-button">
								<?=Loc::getMessage('CRM_WEBFORM_EDIT_PAGE_SETTINGS_SETUP')?>
							</span>
							<span class="crm-button-edit-channel-lines-display-options-settings-triangle">
								<span class="crm-button-edit-channel-lines-display-options-settings-triangle-item"></span>
							</span>
						</div>
						<div class="crm-button-edit-channel-lines-display-options-settings-descriptions-container">
							<span class="crm-button-edit-channel-lines-display-options-settings-item">
								<?if($item['PAGES_USES']):?>
									<?=Loc::getMessage('CRM_WEBFORM_EDIT_PAGE_SETTINGS_USER')?>
								<?else:?>
									<?=Loc::getMessage('CRM_WEBFORM_EDIT_PAGE_SETTINGS_DEFAULT')?>
								<?endif;?>
							</span>
						</div>
					</div>
				</div><!--crm-button-edit-channel-lines-display-options-inner-container-->
				<div data-crm-button-item-settings="<?=$type?>" class="crm-button-edit-channel-lines-display-options-links-container">
					<div class="crm-button-edit-channel-lines-display-options-links-for-all-container">

						<label for="ITEMS_<?=$type?>_PAGES_MODE_EXCLUDE" class="crm-button-edit-channel-lines-display-options-links-button-container">
							<input id="ITEMS_<?=$type?>_PAGES_MODE_EXCLUDE" class="crm-button-edit-channel-lines-display-options-links-button-item" type="radio" name="ITEMS[<?=$type?>][PAGES][MODE]" value="EXCLUDE" <?=($item['PAGES']['MODE'] != 'INCLUDE' ? 'checked' : '')?>>
							<span class="crm-button-edit-channel-lines-display-options-links-button-description"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_ON_ALL_PAGES')?>:</span>
						</label>

						<?ShowIntranetButtonItemPageInterface($type, $item['PAGES']['LIST']['EXCLUDE'], 'EXCLUDE');?>
					</div><!--crm-button-edit-channel-lines-display-options-links-for-all-container-->
					<div class="crm-button-edit-channel-lines-display-options-links-specified-container">
						<label for="ITEMS_<?=$type?>_PAGES_MODE_INCLUDE" class="crm-button-edit-channel-lines-display-options-links-button-container">
							<input class="crm-button-edit-channel-lines-display-options-links-button-item" type="radio" id="ITEMS_<?=$type?>_PAGES_MODE_INCLUDE" name="ITEMS[<?=$type?>][PAGES][MODE]" value="INCLUDE" <?=($item['PAGES']['MODE'] == 'INCLUDE' ? 'checked' : '')?>>
							<span class="crm-button-edit-channel-lines-display-options-links-button-description"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_ONLY_PAGES')?>:</span>
						</label>

						<?ShowIntranetButtonItemPageInterface($type, $item['PAGES']['LIST']['INCLUDE'], 'INCLUDE');?>
					</div><!--crm-button-edit-channel-lines-display-options-links-specified-container-->
					<div class="crm-button-edit-channel-lines-display-options-links-description-container">
						<div class="crm-button-edit-channel-lines-display-options-links-description-info">
							<span class="crm-button-edit-channel-lines-display-options-links-description-info-item">&#063;</span>
						</div>
						<span class="crm-button-edit-channel-lines-display-options-links-description-text">
							<?=nl2br(Loc::getMessage('CRM_WEBFORM_EDIT_HINT_ANY'))?>
						</span>
					</div><!--crm-button-edit-channel-lines-display-options-links-description-container-->
				</div><!--crm-button-edit-channel-lines-display-options-links-container-->
			</div><!--crm-button-edit-channel-lines-display-options-container-->
			<?endif;?>
		</div><!--crm-button-edit-channel-lines-inner-wrapper-->
	</div><!--crm-button-edit-channel-lines-container-->
	<?
};

function ShowIntranetButtonItemPage($type, $mode, $page, $target = 'ITEMS')
{
	$type = htmlspecialcharsbx($type);
	$mode = htmlspecialcharsbx($mode);
	$page = htmlspecialcharsbx($page);
	$target = htmlspecialcharsbx($target);
	?>
	<div data-crm-button-pages-page="null" class="crm-button-edit-item-pages-page crm-button-edit-channel-lines-display-options-links-block">
		<input placeholder="http://example.com/dir/page" type="text" name="<?=$target?>[<?=$type?>][PAGES][LIST][<?=$mode?>][]" value="<?=$page?>" class="crm-button-edit-channel-lines-display-options-links-block-item">

		<div class="crm-button-edit-item-pages-btn-add crm-button-edit-channel-lines-display-options-links-block-button">
			<span data-crm-button-pages-btn-add="" class="crm-button-edit-channel-lines-display-options-links-block-button-item crm-button-edit-add-icon"></span>
		</div>
		<div class="crm-button-edit-item-pages-btn-del crm-button-edit-channel-lines-display-options-links-block-button">
			<span data-crm-button-pages-btn-del="" class="crm-button-edit-channel-lines-display-options-links-block-button-item crm-button-edit-close-icon"></span>
		</div>
	</div>
	<?
}

function ShowIntranetButtonItemPageInterface($type, $list, $mode, $target = 'ITEMS')
{
	if(!is_array($list) || count($list) == 0)
	{
		$list = array('');
	}
	?>
	<div data-crm-button-pages="null">
		<script type="text/template">
			<?ShowIntranetButtonItemPage($type, $mode, '');?>
		</script>
		<div data-crm-button-pages-list="null">
		<?
		foreach ($list as $page)
		{
			ShowIntranetButtonItemPage($type, $mode, $page);
		}
		?>
		</div>
	</div>
	<?
}
?>

<div class="crm-button-edit-container">


	<?$this->SetViewTarget('sidebar');?>
	<form id="crm_button_sub_form">
	<div class="crm-button-edit-right-container">
		<div class="crm-button-edit-sidebar-title">
			<span class="crm-button-edit-sidebar-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_VIEW')?>:</span>
		</div><!--crm-button-edit-sidebar-title-->
		<div id="BUTTON_COLOR_CONTAINER">
			<div class="crm-button-edit-sidebar-button-preview-container">
				<div id="BUTTON_VIEW_CONTAINER" class="crm-button-edit-sidebar-button-preview-inner">
					<?
					$APPLICATION->IncludeComponent("bitrix:crm.button.button", ".default", array(
						'PREVIEW' => true,
						'LOCATION' => 1,
						'COLOR_ICON' => $arResult['BUTTON']['ICON_COLOR'],
						'COLOR_BACKGROUND' => $arResult['BUTTON']['BACKGROUND_COLOR'],
					));
					?>
				</div>
			</div><!--crm-button-edit-sidebar-button-preview-container-->
			<div class="crm-button-edit-sidebar-button-colorpicker-container">
				<div class="crm-button-edit-sidebar-button-colorpicker-inner">
					<div class="crm-button-edit-sidebar-button-colorpicker-block">
						<span class="crm-webform-edit-left-field-colorpick-control-block">
							<span class="crm-web-form-color-picker crm-webform-edit-left-field-colorpick-background" title="<?=Loc::getMessage('CRM_BUTTON_EDIT_COLOR_BG')?>">
								<?=Loc::getMessage('CRM_BUTTON_EDIT_COLOR_BG')?>
							</span>
							<span class="crm-web-form-color-picker crm-webform-edit-left-field-colorpick-text" title="<?=Loc::getMessage('CRM_BUTTON_EDIT_COLOR_ICON')?>">
								<?=Loc::getMessage('CRM_BUTTON_EDIT_COLOR_ICON')?>
							</span>
						</span>

						<span class="crm-webform-edit-left-field-colorpick-control-block">
							<span class="crm-webform-edit-left-field-colorpick-text-circle-container">
								<input size="7" id="BACKGROUND_COLOR" data-web-form-color-picker="" type="hidden" name="BACKGROUND_COLOR" value="<?=htmlspecialcharsbx($arResult['BUTTON']['BACKGROUND_COLOR'])?>">
								<span class="crm-webform-edit-left-field-colorpick-background-circle"></span>
							</span>
							<span class="crm-webform-edit-left-field-colorpick-text-circle-container">
								<input size="7" id="ICON_COLOR" data-web-form-color-picker="" type="hidden" name="ICON_COLOR" value="<?=$arResult['BUTTON']['ICON_COLOR']?>">
								<span class="crm-webform-edit-left-field-colorpick-text-circle"></span>
							</span>
						</span>

						<?/*$APPLICATION->IncludeComponent(
							"bitrix:main.colorpicker",
							"",
							Array(
								"COMPONENT_TEMPLATE" => ".default",
								"ID" => "",
								"NAME" => "",
								"ONSELECT" => "",
								"SHOW_BUTTON" => "N"
							)
						);*/?>
					</div>
				</div>
			</div><!--crm-button-edit-sidebar-button-preview-container-->
		</div>
		<div class="crm-button-edit-sidebar-title">
			<span class="crm-button-edit-sidebar-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_CHOOSE_LOCATION')?>:</span>
		</div><!--crm-button-edit-sidebar-title-->
		<div id="LOCATION_CONTAINER" class="crm-button-edit-sidebar-button-position-container">
			<div class="crm-button-edit-sidebar-button-position-header">
				<div class="crm-button-edit-sidebar-button-position-header-dots">
					<span class="crm-button-edit-sidebar-button-position-header-dots-item"></span>
					<span class="crm-button-edit-sidebar-button-position-header-dots-item"></span>
					<span class="crm-button-edit-sidebar-button-position-header-dots-item"></span>
				</div>
				<div class="crm-button-edit-sidebar-button-position-header-line"></div>
			</div>
			<div class="crm-button-edit-sidebar-button-position-inner">
				<?foreach($arResult['BUTTON_LOCATION'] as $location):
					?>
					<label for="LOCATION_<?=htmlspecialcharsbx($location['ID'])?>" data-bx-crm-button-loc="" class="crm-button-edit-sidebar-button-position-block <?if($location['SELECTED']):?>crm-button-edit-sidebar-button-position-block-active-<?=htmlspecialcharsbx($location['ID'])?><?endif;?>" title="<?=htmlspecialcharsbx($location['NAME'])?>">
						<span class="crm-button-edit-arrow crm-button-edit-sidebar-button-position-arrow-<?=htmlspecialcharsbx($location['ID'])?>"></span>
						<input data-bx-crm-button-loc-val="" id="LOCATION_<?=htmlspecialcharsbx($location['ID'])?>" class="crm-button-edit-sidebar-button-position-block-item" type="radio" name="LOCATION" value="<?=htmlspecialcharsbx($location['ID'])?>" <?=($location['SELECTED'] ? 'checked' : '')?>>
					</label>
				<?endforeach?>
			</div>
		</div><!--crm-button-edit-sidebar-button-position-container-->
		<div class="crm-button-edit-sidebar-title">
			<span class="crm-button-edit-sidebar-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_DELAY')?>:</span>
		</div><!--crm-button-edit-sidebar-title-->
		<div class="crm-button-edit-sidebar-show-container">
			<div class="crm-button-edit-sidebar-show-inner">
				<div class="crm-button-edit-sidebar-show-item">
					<input type="radio" id="DELAY_CHOISE_NONE" name="DELAY_CHOISE" value="N" <?=($arResult['BUTTON']['DELAY'] <= 0 ? 'checked' : '')?>>
					<label for="DELAY_CHOISE_NONE"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_DELAY_AT_ONCE')?></label>
				</div>
				<br>
				<br>
				<span class="crm-button-edit-sidebar-show-item">
					<input type="radio" id="DELAY_CHOISE_TIME" name="DELAY_CHOISE" value="Y" <?=($arResult['BUTTON']['DELAY'] > 0 ? 'checked' : '')?>>
					<label for="DELAY_CHOISE_TIME"><?=Loc::getMessage('CRM_WEBFORM_EDIT_SHOW_DELAY_DELAY')?></label>
				</span>
				<div class="crm-button-edit-sidebar-show-delay-container">
					<select class="crm-button-edit-sidebar-show-delay" name="DELAY">
						<?foreach($arResult['BUTTON_DELAY'] as $delayItem):?>
							<option value="<?=htmlspecialcharsbx($delayItem['ID'])?>" <?=($delayItem['SELECTED'] ? 'selected' : '')?>>
								<?=htmlspecialcharsbx($delayItem['NAME'])?>
							</option>
						<?endforeach?>
					</select>
				</div><!--crm-button-edit-sidebar-show-delay-container-->
			</div>
		</div><!--crm-button-edit-sidebar-show-container-->

		<div class="crm-button-edit-sidebar-title">
			<span class="crm-button-edit-sidebar-title-item"><?=Loc::getMessage('CRM_BUTTON_EDIT_MOBILE_DEVICES')?>:</span>
		</div><!--crm-button-edit-sidebar-title-->
		<div class="crm-button-edit-sidebar-show-container">
			<div class="crm-button-edit-sidebar-show-inner">
				<label id="DISABLE_ON_MOBILE_CONT" for="DISABLE_ON_MOBILE" class="">
					<input id="DISABLE_ON_MOBILE" name="DISABLE_ON_MOBILE" <?=($arResult['BUTTON']['SETTINGS']['DISABLE_ON_MOBILE'] == 'Y' ? 'checked' : '')?> value="Y" type="checkbox" class="">
					<span class="">
						<?=Loc::getMessage('CRM_BUTTON_EDIT_DO_NOT_SHOW')?>
					</span>
				</label>
			</div>
		</div>

		<div class="crm-button-edit-sidebar-title">
			<span class="crm-button-edit-sidebar-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_REMOVE_LOGO_BX')?>:</span>
		</div><!--crm-button-edit-sidebar-title-->
		<div class="crm-button-edit-sidebar-show-container">
			<div class="crm-button-edit-sidebar-show-inner">
				<label id="COPYRIGHT_REMOVED_CONT" for="COPYRIGHT_REMOVED" class="">
					<input id="COPYRIGHT_REMOVED" name="COPYRIGHT_REMOVED" <?=($arResult['BUTTON']['SETTINGS']['COPYRIGHT_REMOVED'] == 'Y' ? 'checked' : '')?> value="Y" type="checkbox" class="">
					<span class="">
						<span class="<?=($arResult['CAN_REMOVE_COPYRIGHT'] ? '' : 'crm-button-copyright-disabled')?>"><?=Loc::getMessage('CRM_WEBFORM_EDIT_REMOVE_LOGO')?></span>
					</span>
				</label>
			</div>
		</div>

		<?if (!empty($arResult['LANGUAGES']['LIST'])):?>
			<div class="crm-button-edit-sidebar-title">
				<span class="crm-button-edit-sidebar-title-item">
					<?=Loc::getMessage('CRM_BUTTON_EDIT_LANG_CHOOSE')?>:
					<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("CRM_BUTTON_EDIT_LANG_CHOOSE_TIP"))?>"></span>
				</span>
			</div>
			<div class="crm-button-edit-sidebar-show-container">
				<div class="crm-button-edit-sidebar-show-inner">
					<span id="CRM_BUTTON_LANGUAGES"
						data-langs="<?=htmlspecialcharsbx(Json::encode($arResult['LANGUAGES']['LIST']))?>"
					>
						<span class="bx-lang-btn-text" style="position: relative;">
							<span data-langs-text="" style="white-space: nowrap;">
								<?=htmlspecialcharsbx($arResult['LANGUAGES']['LIST'][$arResult['LANGUAGES']['CURRENT']]["NAME"])?>
							</span>
							<span style="white-space: nowrap; color: grey; text-decoration: underline; padding-left: 13px; font-size: 13px"><?=GetMessage("CRM_BUTTON_EDIT_BTN_CHANGE")?></span>
						</span>
						<input data-langs-input="" name="LANGUAGE_ID" type="hidden" value="<?=htmlspecialcharsbx($arResult['LANGUAGES']['CURRENT'])?>">
					</span>
				</div>
			</div>
		<?endif;?>

	</div><!--crm-button-edit-right-container-->
	</form>
	<?$this->EndViewTarget();?>

	<div class="crm-button-edit-left-container">
		<div class="crm-button-edit-button-name-container">
			<div class="crm-button-edit-button-name">
				<input type="text" name="NAME" value="<?=htmlspecialcharsbx($arResult['BUTTON']['NAME'])?>" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_NAME_PLACEHOLDER')?>" class="crm-button-edit-button-item">
			</div>
		</div>
		<div class="crm-button-edit-border"></div><!--crm-button-edit-border-->
		<div class="crm-button-edit-channel-container">
			<?if($arParams['ELEMENT_ID']):?>
				<div class="crm-button-edit-channel-field">
					<div class="crm-button-edit-channel-title">
						<span class="crm-button-edit-channel-title-item">
							<span><?=Loc::getMessage('CRM_WEBFORM_EDIT_SITE_SCRIPT')?></span>
							<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("CRM_WEBFORM_EDIT_SITE_SCRIPT_TIP", ['&lt;/body&gt;' => '</body>']))?>"></span>
						</span>
					</div>
					<div class="crm-button-edit-channel-content">
						<div id="SCRIPT_CONTAINER" class="crm-button-edit-insert-code-container">
							<div class="crm-button-edit-insert-code-inner">
								<div data-bx-webform-script-copy-text class="crm-button-edit-insert-code-item"><?=$getFormattedScript($arResult['SCRIPT'])?></div>
							</div>
							<?if (\Bitrix\Main\Config\Option::get('main', 'save_original_file_name') !== 'Y'):?>
							<div class="ui-alert ui-alert-danger ui-alert-icon-warning">
								<span class="ui-alert-message">
									<?=Loc::getMessage('CRM_BUTTON_EDIT_WARN_SETTING_FILENAME')?>
								</span>
							</div>
							<?endif;?>
							<div class="crm-button-edit-insert-code-button">
								<a data-bx-webform-script-copy-btn="" class="crm-button-edit-insert-code-button-item webform-small-button webform-small-button-blue">
									<?=Loc::getMessage('CRM_WEBFORM_EDIT_COPY_TO_CLIPBOARD')?>
								</a>
							</div>
						</div><!--crm-button-edit-sidebar-insert-code-container-->
					</div><!--crm-button-edit-channel-content-->
				</div><!--crm-button-edit-channel-field-->
			<?else:?>
				<div class="crm-button-edit-channel-description-container">
					<div class="crm-button-edit-channel-description-item">
						<?=nl2br(Loc::getMessage('CRM_WEBFORM_EDIT_DESC'))?>
					</div>
				</div><!--"crm-button-edit-channel-description-container-->
			<?endif;?>

			<div class="crm-button-edit-channel-field">
				<div class="crm-button-edit-channel-title">
					<span class="crm-button-edit-channel-title-item">
						<span><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNELS')?></span>
						<?if($arParams['ELEMENT_ID']):?>
							<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("CRM_WEBFORM_EDIT_DESC"))?>"></span>
						<?endif;?>
					</span>
				</div>
				<div class="crm-button-edit-channel-content">
					<div id="WIDGET_CONTAINER">
						<?
						$showItemInterface($arResult['BUTTON_ITEM_OPEN_LINE'], $this);
						$showItemInterface($arResult['BUTTON_ITEM_CRM_FORM'], $this);
						$showItemInterface($arResult['BUTTON_ITEM_CALLBACK'], $this);
						if ($arResult['SUPPORTING']['whatsapp'])
						{
							$showItemInterface($arResult['BUTTON_ITEM_WHATSAPP'], $this);
						}
						?>
					</div>
				</div><!--crm-button-edit-channel-content-->
			</div><!--crm-button-edit-channel-field-->



			<!---------- NEW BLOCK: AUTO HELLO ---------->

<?

function ShowIntranetButtonHelloPageInterface($type, $list, $mode, $target = 'ITEMS')
{
	if(!is_array($list) || count($list) == 0)
	{
		$list = array('');
	}
	?>
	<div data-crm-button-pages="null">
		<div data-crm-button-pages-list="null">
			<?
			foreach ($list as $page)
			{
				ShowIntranetButtonItemPage($type, $mode, $page, $target);
			}
			?>
		</div>
	</div>
	<?
}

function ShowIntranetButtonHelloBlock($params)
{
	$arResult = $params['arResult'];
	$pageList = $params['pageList'];
	$mode = htmlspecialcharsbx($params['mode']);
	$icon = htmlspecialcharsbx($params['icon']);
	$name = htmlspecialcharsbx($params['name']);
	$text = htmlspecialcharsbx($params['text']);
	$delay = intval($params['delay']);

	static $counter = 0;
	$id = isset($params['id']) ? $params['id'] : $counter++;
	$id = htmlspecialcharsbx($id);
	?>
	<div data-b24-crm-hello-block="<?=$id?>" class="crm-button-edit-constructor-block">
		<?if ($mode == 'INCLUDE'):?>
		<div class="crm-button-edit-constructor-close">
			<span data-b24-hello-btn-remove="" class="crm-button-edit-constructor-close-item"></span>
		</div>
		<?endif;?>
		<div id="crm-button-edit-popup-event" class="crm-button-edit-constructor-popup">
			<div data-b24-crm-hello-cont="" class="b24-widget-button-popup" style="border-color: <?=$arResult['COLOR_BACKGROUND']?>;">
				<div class="b24-widget-button-popup-inner">
					<div class="b24-widget-button-popup-image">
						<span data-b24-hello-icon="" class="b24-widget-button-popup-image-item" style="background-image: url(<?=$icon?>);"></span>
						<span data-b24-hello-icon-btn="" class="b24-widget-button-popup-image-edit"><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_CHANGE')?></span>
						<input data-b24-hello-icon-input type="hidden" name="HELLO[CONDITIONS][<?=$id?>][ICON]" value="<?=$icon?>">
					</div>
					<div class="b24-widget-button-popup-content">
						<div data-b24-hello-name="" class="b24-widget-button-popup-name">
							<div class="b24-widget-button-popup-content-block">
								<span data-b24-hello-name-text="" class="b24-widget-button-popup-name-item"><?=$name?></span>
								<span data-b24-hello-name-btn-edit="" class="b24-widget-button-popup-edit" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_EDIT')?>"></span>
							</div>
							<div class="b24-widget-button-popup-edit-block">
								<input name="HELLO[CONDITIONS][<?=$id?>][NAME]" data-b24-hello-name-input="" type="text" class="b24-widget-button-popup-input" value="<?=$name?>">
								<span data-b24-hello-name-btn-apply="" class="b24-widget-button-popup-edit-confirm" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_APPLY')?>"></span>
							</div>
						</div>
						<div data-b24-hello-text="" class="b24-widget-button-popup-description">
							<div class="b24-widget-button-popup-content-block">
								<span data-b24-hello-text-text="" class="b24-widget-button-popup-description-item"><?=$text?></span>
								<span data-b24-hello-text-btn-edit="" class="b24-widget-button-popup-edit" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_EDIT')?>"></span>
							</div>
							<div class="b24-widget-button-popup-edit-block">
								<textarea name="HELLO[CONDITIONS][<?=$id?>][TEXT]" data-b24-hello-text-input="" class="b24-widget-button-popup-textarea" name="" id="" cols="30" rows="10"><?=$text?></textarea>
								<span data-b24-hello-text-btn-apply="" class="b24-widget-button-popup-edit-confirm" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_APPLY')?>"></span>
							</div>
						</div>
					</div>
				</div>
				<div class="b24-widget-button-popup-triangle"></div>
			</div><!--b24-widget-button-popup-->
		</div><!--crm-button-edit-constructor-popup-->
		<div class="crm-button-edit-hello-select-description">
			<span class="crm-button-edit-hello-select-description-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_TIME_DELAY')?>:</span>
			<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage("CRM_WEBFORM_EDIT_HELLO_TIME_DELAY_TIP"))?>"></span>
		</div>
		<div class="crm-button-edit-hello-select crm-button-edit-select-delay">
			<select name="HELLO[CONDITIONS][<?=$id?>][DELAY]" type="text" class="crm-button-edit-hello-select-item">
				<option value=""><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_TIME_DELAY_NO')?></option>
				<?foreach($arResult['BUTTON_DELAY'] as $delayItem):?>
					<option value="<?=htmlspecialcharsbx($delayItem['ID'])?>" <?=($delayItem['ID'] == $delay ? 'selected' : '')?>>
						<?=htmlspecialcharsbx($delayItem['NAME'])?>
					</option>
				<?endforeach?>
			</select>
		</div>
		<div class="crm-button-edit-hello-select-description">
			<span class="crm-button-edit-hello-select-description-item">
				<?if ($mode == 'INCLUDE'):?>
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_PAGES_LIST')?>:
				<?else:?>
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_PAGES_EXCLUDE')?>:
				<?endif;?>
			</span>
			<span data-hint-html data-hint="<?=htmlspecialcharsbx(nl2br(Loc::getMessage('CRM_WEBFORM_EDIT_HINT_ANY')))?>"></span>
		</div>
		<div class="crm-button-edit-hello-input">
			<?
			ShowIntranetButtonHelloPageInterface($id, $pageList, $mode, 'HELLO[CONDITIONS]')
			?>
		</div>
		<?if ($mode != 'INCLUDE'):?>
			<span class="crm-button-edit-hello-select-description-item">
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_PAGES_EXCLUDE_ADDITIONAL')?>
			</span>
			<div data-b24-hello-excluded-pages="" class="crm-button-edit-hello-pages"></div>
		<?endif;?>
	</div><!--crm-button-edit-constructor-block-->
	<?
}
?>

<script id="template-crm-button-page" type="text/html">
	<?ShowIntranetButtonItemPage('%type%', '%mode%', '', '%target%');?>
</script>

<script id="template-crm-button-hello" type="text/html">
	<?
	ShowIntranetButtonHelloBlock(array(
		'arResult' => $arResult,
		'pageList' => array(),
		'mode' => 'INCLUDE',
		'id' => '%id%',
		'icon' => $helloDefCond['ICON'],
		'name' => $helloDefCond['NAME'],
		'text' => $helloDefCond['TEXT'],
		'delay' => $helloDefCond['DELAY'],
	));
	?>
</script>

			<div id="HELLO_CONTAINER" class="crm-button-edit-channel-field">
				<div class="crm-button-edit-channel-title">
					<span class="crm-button-edit-channel-title-item">
						<span><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_TITLE')?></span>
					</span>
				</div>
				<div class="crm-button-edit-channel-content">
					<div class="crm-button-edit-channel-description-container">
						<div class="crm-button-edit-channel-description-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_DESC')?></div>
					</div><!--"crm-button-edit-channel-description-container-->

					<div data-bx-crm-button-item="sys-hello" class="crm-button-edit-channel-lines-container <?=($arResult['HELLO']['ACTIVE'] == 'Y' ? 'crm-button-edit-channel-lines-container-active' : '')?>">
						<div class="crm-button-edit-channel-lines-title-container">
							<div class="crm-button-edit-channel-lines-title-inner">
								<span class="crm-button-edit-channel-lines-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_TUNE')?>:</span>
							</div>
							<div class="crm-button-edit-channel-lines-title-activate-container <?=($arResult['HELLO']['ACTIVE'] == 'Y' ? 'crm-button-edit-channel-lines-title-on' : 'crm-button-edit-channel-lines-title-off')?>" data-bx-crm-button-item-active="sys-hello">
								<div class="crm-button-edit-channel-lines-title-activate-button-container">
									<span class="crm-button-edit-channel-lines-title-activate-button">
										<span class="crm-button-edit-channel-lines-title-activate-button-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_ON')?></span>
									</span>
									<span class="crm-button-edit-channel-lines-title-not-activate-button">
										<span class="crm-button-edit-channel-lines-title-activate-button-cursor"></span>
										<span class="crm-button-edit-channel-lines-title-not-activate-button-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_OFF')?></span>
									</span>
								</div>
							</div>

							<input type="hidden" data-bx-crm-button-item-active-val="sys-hello" name="HELLO[ACTIVE]" value="<?=htmlspecialcharsbx($arResult['HELLO']['ACTIVE'])?>">

						</div><!--crm-button-edit-channel-lines-title-container-->
						<div class="crm-button-edit-channel-lines-inner-wrapper">
							<div class="crm-button-edit-channel-lines-inner-container">

								<div class="crm-button-edit-hello-container">
									<div class="crm-button-edit-hello-title">
										<span class="crm-button-edit-hello-title-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_MODE')?></span>
									</div>
									<div class="crm-button-edit-hello-select">
										<select data-b24-crm-hello-mode="" name="HELLO[MODE]" type="text" class="crm-button-edit-hello-select-item">
											<option value="EXCLUDE" <?=($arResult['HELLO']['MODE'] == 'EXCLUDE' ? 'selected' : '')?>>
												<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_MODE_EXCLUDE')?>
											</option>
											<option value="INCLUDE" <?=($arResult['HELLO']['MODE'] == 'INCLUDE' ? 'selected' : '')?>>
												<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_MODE_INCLUDE')?>
											</option>
										</select>
									</div>
								</div><!--crm-button-edit-hello-container-->

								<div id="HELLO_ALL_CONTAINER" class="crm-button-edit-constructor-container" style="<?=($arResult['HELLO']['MODE'] == 'INCLUDE' ? 'display: none' : '')?>">
									<div class="crm-button-edit-hello-description">
										<span class="crm-button-edit-hello-title-item">
											<?=Loc::getMessage('CRM_WEBFORM_EDIT_SECTION_ALL')?>:
										</span>
									</div>
									<div>
										<?
										$helloCommon = $arResult['HELLO']['CONDITIONS'][0];
										ShowIntranetButtonHelloBlock(array(
											'arResult' => $arResult,
											'id' => 0,
											'mode' => 'EXCLUDE', // $condition['PAGES']['MODE']
											'pageList' => $helloCommon['PAGES']['LIST'],
											'icon' => $helloCommon['ICON'],
											'name' => $helloCommon['NAME'],
											'text' => $helloCommon['TEXT'],
											'delay' => $helloCommon['DELAY'],
										));
										?>
									</div>
								</div>
								<div class="crm-button-edit-constructor-container">
									<div class="crm-button-edit-hello-description">
										<span class="crm-button-edit-hello-title-item">
											<?=Loc::getMessage('CRM_WEBFORM_EDIT_SECTION_CUSTOM')?>:
										</span>
									</div>

									<div id="HELLO_MY_CONTAINER" class="crm-button-edit-block-scrolled">
										<?for($num = 1, $cnt = count($arResult['HELLO']['CONDITIONS']); $num < $cnt; $num++):
											$condition = $arResult['HELLO']['CONDITIONS'][$num];
											ShowIntranetButtonHelloBlock(array(
												'arResult' => $arResult,
												'id' => $num,
												'mode' => 'INCLUDE', // $condition['PAGES']['MODE']
												'pageList' => $condition['PAGES']['LIST'],
												'icon' => $condition['ICON'],
												'name' => $condition['NAME'],
												'text' => $condition['TEXT'],
												'delay' => $condition['DELAY'],
											));
										endfor;?>
									</div>

									<div class="crm-button-edit-hello-add">
										<span data-b24-crm-hello-add="" class="crm-button-edit-hello-link-item">
											<?=Loc::getMessage('CRM_WEBFORM_EDIT_HELLO_ADD')?>
										</span>
									</div>

								</div><!--crm-button-edit-constructor-container-->

							</div><!--crm-button-edit-channel-lines-inner-container-->
						</div><!--crm-button-edit-channel-lines-inner-wrapper-->
					</div><!--crm-button-edit-channel-lines-container-->
				</div><!--crm-button-edit-channel-content-->
			</div><!--crm-button-edit-channel-field-->

			<!----------- END OF NEW BLOCK: AUTO HELLO ----------->

		</div><!--crm-button-edit-channel-container-->

		<?$APPLICATION->IncludeComponent("bitrix:ui.button.panel", "", [
			'BUTTONS' => ['save', 'cancel' => $arResult['PATH_TO_BUTTON_LIST']]
		]);?>
	</div><!--crm-button-edit-left-container-->

</div><!--crm-button-edit-container-->


</form>

<?
function getCrmButtonEditTemplateAvatar()
{
	ob_start();
	?>
	<span data-crm-button-edit-avatar-item="" data-file-id="%file_id%" data-path="%path%" class="crm-button-edit-photo-upload-item-added-completed-item">
		<span data-remove="" class="crm-button-edit-photo-upload-item-remove"></span>
		<span data-view="" style="background-image: url(%path%)" class="crm-button-edit-photo-upload-item"></span>
		<span class="crm-button-edit-photo-upload-item-selected"></span>
	</span>
	<?
	return ob_get_clean();
}

function getCrmButtonEditTemplateLine($replace = array(), $pathAdd = null)
{
	ob_start();
	?>
	<div data-line="%lineid%" data-line-config="%lineconfig%" class="crm-button-edit-channel-lines-wrap">
		<div class="crm-button-edit-channel-lines-inner-create-container">
			<div class="crm-button-edit-channel-lines-inner-create-select-container">
				<select data-line-list="" class="crm-button-edit-channel-lines-inner-create-select-item"></select>
			</div>
			<div class="crm-button-edit-channel-lines-inner-create-button-container">
				<a data-bx-slider-href="" data-line-edit="" href="" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_WEBFORM_EDIT_CHANNEL_EDIT')?></a>
				<?if (!$pathAdd):?>
					<span data-line-remove="" class="crm-button-edit-channel-lines-inner-create-button-item"><?=Loc::getMessage('CRM_BUTTON_EDIT_OPENLINE_REMOVE')?></span>
				<?endif;?>
			</div>
		</div>

		<div class="crm-button-edit-channel-lines-social-name"><?=Loc::getMessage('CRM_BUTTON_EDIT_DETAIL_OPENLINE_CHANNELS')?>:</div>
		<div data-line-channels="" class="crm-button-edit-channel-lines-social-item-container"></div>
	</div>
	<?
	$s = ob_get_clean();
	return str_replace(array_keys($replace), array_values($replace), $s);
}

function getCrmButtonEditTemplateConnector($replace = array())
{
	ob_start();
	?>
	<label class="crm-button-edit-channel-lines-social-label" for="items_openline_config_%lineid%_%connector%">
		<span data-crm-tooltip="" class="crm-button-edit-channel-lines-social-item ui-icon ui-icon-service-%icon%"><i></i></span>
		<input id="items_openline_config_%lineid%_%connector%" name="ITEMS[openline][EXTERNAL_CONFIG][%lineid%][]" value="%connector%" type="checkbox" %checked% class="crm-button-edit-channel-lines-social-checkbox">
	</label>
	<?
	$s = ob_get_clean();
	return str_replace(array_keys($replace), array_values($replace), $s);
}
?>


<script id="template-crm-button-line" type="text/html">
	<?=getCrmButtonEditTemplateLine()?>
</script>

<script id="template-crm-button-connector" type="text/html">
	<?=getCrmButtonEditTemplateConnector()?>
</script>


<div style="display: none;">
	<script id="crm_button_edit_template_avatar" type="text/html">
		<?=getCrmButtonEditTemplateAvatar()?>
	</script>
	<div id="crm_button_edit_avatar_upload" class="crm-button-edit-photo-upload">
		<div class="crm-button-edit-photo-upload-container">
			<div class="crm-button-edit-photo-upload-item-container-title"><span><?=Loc::getMessage('CRM_BUTTON_EDIT_AVATAR_LOADED')?></span></div>
			<div class="crm-button-edit-photo-upload-item-added-btn-container">

				<span data-crm-button-edit-avatar-edit="" class="crm-button-edit-photo-upload-item-added-btn">
					<span class="crm-button-edit-photo-upload-item-added-btn-inner"></span>
					<span class="crm-button-edit-photo-upload-item-added-btn-inner-bg"></span>
				</span>

				<div class="crm-button-edit-photo-upload-item-added-completed-container">
					<div class="crm-button-edit-photo-upload-item-added-completed-slider-container">
						<div data-crm-button-edit-avatars="" class="crm-button-edit-photo-upload-item-added-completed-block">
							<?foreach($arResult['AVATARS'] as $icon):
								echo str_replace(
									array(
										'%file_id%',
										'%path%',
									),
									array(
										htmlspecialcharsbx($icon['ID']),
										htmlspecialcharsbx($icon['PATH'])
									),
									getCrmButtonEditTemplateAvatar()
								);
							endforeach;?>
						</div>
					</div>
					<div data-crm-button-edit-avatar-prev="" class="crm-button-edit-photo-upload-item-added-completed-block-control-left"></div>
					<div data-crm-button-edit-avatar-next="" class="crm-button-edit-photo-upload-item-added-completed-block-control-right"></div>
				</div>

				<div style="clear: both;"></div>
			</div>
		</div>

		<div class="crm-button-edit-photo-uploaded-container">
			<div class="crm-button-edit-photo-uploaded-item-container-title"><span><?=Loc::getMessage('CRM_BUTTON_EDIT_AVATAR_PRESET')?></span></div>
			<?foreach($arResult['HELLO']['ICONS'] as $icon):?>
				<span data-crm-button-edit-avatar-item="" data-file-id="" data-path="<?=htmlspecialcharsbx($icon['PATH'])?>" class="crm-button-edit-photo-upload-item-container">
					<span style="background-image: url(<?=htmlspecialcharsbx($icon['PATH'])?>)" class="crm-button-edit-photo-upload-item"></span>
					<span class="crm-button-edit-photo-upload-item-selected"></span>
				</span>
			<?endforeach;?>
			<div style="clear: both;"></div>
		</div>
	</div>
</div>
