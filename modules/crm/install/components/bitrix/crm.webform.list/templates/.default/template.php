<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

CJSCore::Init(array('clipboard', 'sidepanel'));
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.icons");
if(\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
}

$descImagePath = $this->GetFolder() . "/images/demo_main_";
$descImagePath .= (in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by')) ? 'ru' : (LANGUAGE_ID === 'de' ? 'de' : 'en'));
$descImagePath .= ".png";
?>

<div class="crm-webform-list-wrapper">

	<?if(!$arResult['HIDE_DESC'] || !$arResult['HIDE_DESC_FZ152']):?>
		<div id="CRM_LIST_DESC_CONT" class="crm-webform-list-info">
			<?if(!$arResult['HIDE_DESC_FZ152']):?>
				<h2 class="crm-webform-list-info-title">
					<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_TITLE')?>
				</h2>

				<div class="crm-webform-list-info-inner">

					<div class="crm-webform-list-info-list-container">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_TEXT')?>
						<br>
						<br>
					</div>

					<div class="crm-webform-list-info-list-container">
						<ul class="crm-webform-list-info-list">
							<li class="">
								<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_1', array('%req_path%' => '/crm/configs/mycompany/'))?>
								<br>
								<br>
							</li>
							<li class="">
								<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_2', array('%email%' => htmlspecialcharsbx($arResult['USER_CONSENT_EMAIL'])))?>
								<br>
								<br>
							</li>
							<li class="">
								<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_3')?>
								<br>
								<br>
							</li>
						</ul>
					</div>

					<div class="crm-webform-list-info-list-container">
						<span id="CRM_LIST_WEBFORM_NOTIFY_BTN_HIDE" class="webform-small-button webform-small-button-blue">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_BTN_OK')?>
						</span>
						<a onclick="BX.Helper.show('redirect=detail&HD_ID=5791365'); return false;" href="https://helpdesk.bitrix24.ru/open/5791365/" target="_blank">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_BTN_DETAIL')?>
						</a>
					</div>
				</div>
			<?else:?>
				<?if(!$arResult['HIDE_DESC']):?>
					<h2 class="crm-webform-list-info-title"><?=Loc::getMessage('CRM_WEBFORM_LIST_INFO_TITLE')?></h2>
					<div class="crm-webform-list-info-visual">
						<span class="crm-webform-list-info-visual-item" style="height: 225px;">
							<img src="<?=$descImagePath?>">
						</span>
					</div>
					<div class="crm-webform-list-info-inner">
						<div class="crm-webform-list-info-list-container">
							<ul class="crm-webform-list-info-list">
								<li class="crm-webform-list-info-list-item">
									<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC1')?>
								</li>
								<li class="crm-webform-list-info-list-item">
									<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC2')?>
								</li>
							</ul>
						</div>
					</div>
					<span id="CRM_LIST_DESC_BTN_HIDE" class="crm-webform-list-info-btn-hide" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_HIDE_DESC')?>"></span>
				<?endif;?>
			<?endif;?>
		</div>
	<?endif;?>

	<div id="crm_web_form_list_container">

		<?if(empty($arResult['ITEMS_BY_IS_SYSTEM']) && $arResult['PERM_CAN_EDIT']):?>
			<a href="<?=htmlspecialcharsbx($arResult['PATH_TO_BUTTON_NEW'])?>">
				<div class="crm-webform-list-createform-container">
					<div class="crm-webform-list-createform-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_CAPTION')?></div>
					<span class="crm-webform-list-createform-description"><?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_DESC1')?></span>
				</div>
			</a>
		<?endif;?>

<?foreach($arResult['ITEMS_BY_IS_SYSTEM'] as $isSystem => $system):?>
	<div class="crm-webform-list-header-container">
		<h3 data-bx-list-head="" class="crm-webform-list-header">
			<?=htmlspecialcharsbx($system['NAME'] . ($arResult['FILTER_ACTIVE_CURRENT'] != 'ALL' ? ': ' . $arResult['FILTER_ACTIVE_CURRENT_NAME'] : ''))?>
		</h3>
	</div>
	<div data-bx-list-items="">
<?foreach($system['ITEMS'] as $item):?>
	<div class="crm-webform-list-widget-row"
		data-bx-crm-webform-item="<?=intval($item['ID'])?>"
		data-bx-system="<?=htmlspecialcharsbx($isSystem)?>"
		data-bx-readonly="<?=htmlspecialcharsbx($item['IS_READONLY'])?>"
	>
		<div class="crm-webform-list-buttons-container">
			<div class="crm-webform-list-buttons">
				<span class="crm-webform-list-hamburger" data-bx-crm-webform-item-settings=""></span>
				<?if($arResult['PERM_CAN_EDIT'] && $item['IS_SYSTEM'] != 'Y'):?>
					<span class="crm-webform-list-close" data-bx-crm-webform-item-delete="" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_REMOVE')?>"></span>
				<?endif;?>
			</div><!--crm-webform-list-buttons-->
		</div><!--crm-webform-list-button-container-->
		<div class="crm-webform-list-widget-container crm-webform-list-widget-left">
			<div class="crm-webform-list-widget crm-webform-list-widget-number <?=$item['viewClassName']?> <?=($item['ACTIVE'] == 'Y' ? '' : 'crm-webform-list-widget-inactive')?>" data-bx-crm-webform-item-view="">
				<div class="crm-webform-list-widget-head">
					<span class="crm-widget-settings" data-bx-crm-webform-item-view-settings="" style="display: none;"></span>
					<span class="crm-webform-list-widget-title-container">
						<span class="crm-webform-list-widget-title-inner">
							<a data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_EDIT'])?>">
								<span data-bx-title="" class="crm-webform-list-widget-title"><?=htmlspecialcharsbx($item['NAME'])?></span>
							</a>
						</span>
					</span>
				</div><!--crm-webform-list-widget-head-->
				<div class="crm-webform-list-widget-content">
					<div class="crm-webform-list-widget-content-amt">
						<div class="crm-webform-list-widget-content-lead-ads-cont">
							<?foreach ($item['ADS_FORM'] as $adsFormService):
								$top = empty($top) ? 20 : $top + 20;
								$typeUpped = mb_strtoupper($adsFormService['TYPE']);
								if ($adsFormService['HAS_LINKS']):
								?>
									<a
										href="<?=htmlspecialcharsbx($adsFormService['PATH_TO_ADS'])?>"
										data-bx-crm-webform-ads-btn="<?=htmlspecialcharsbx($adsFormService['TYPE'])?>"
									>
										<div
											data-bx-ads-name="<?=htmlspecialcharsbx($adsFormService['NAME'])?>"
											data-hint="<?=Loc::getMessage('CRM_WEBFORM_LIST_ADS_FORM_DESC_HINT_' . $typeUpped)?>"
											class="crm-webform-list-widget-content-lead-ads"
											style="top: <?=$top?>px;"
										>
											<div class="ui-icon ui-icon-service-<?=htmlspecialcharsbx($adsFormService['ICON'])?> ui-icon-xs"><i></i></div>
											<?=Loc::getMessage('CRM_WEBFORM_LIST_ADS_FORM_STATUS_ON_' . $typeUpped)?>
										</div>
									</a>
								<?
								endif;
							endforeach;?>
						</div>
						<span class="crm-webform-list-widget-content-image"></span>
						<div style="position: relative;left: 118px; display: none;">
							<?foreach($item['itemViewList'] as $viewType => $view):?>
							<div class="crm-webform-list-widget-content-item <?=($view['SELECTED'] ? 'crm-webform-list-widget-content-item-show' : '')?>" data-bx-crm-webform-view-info="<?=$viewType?>">
								<div class="crm-crm-webform-list-widget-content-title"><?=$view['TEXT']?></div>
								<div class="crm-webform-list-widget-content-number">
									<?=$item['SUMMARY_' . $viewType . '_DISPLAY']?>
								</div>
							</div>
							<?endforeach;?>
						</div>
						<div class="crm-webform-list-widget-content-attempt" style="display: none; top: 67px; position: relative;">
							<span class="crm-webform-list-widget-content-attempt-total">
								<span class="crm-webform-list-widget-content-attempt-total-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_FILL_START')?></span>
								<div class="crm-webform-list-widget-content-attempt-total-number">
									<?=intval($item['COUNT_START_FILL'])?>
								</div>
							</span>
							<span class="crm-webform-list-widget-content-attempt-success">
								<span class="crm-webform-list-widget-content-attempt-success-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_FILL_END')?></span>
								<div class="crm-webform-list-widget-content-attempt-success-number">
									<?=intval($item['COUNT_END_FILL'])?>
								</div>
							</span>
						</div>
					</div>
				</div><!--crm-webform-list-widget-content-->
			</div><!--crm-webform-list-widget crm-webform-list-widget-number-->
		</div><!--crm-webform-list-widget-container crm-webform-list-widget-left-->
		<div class="crm-webform-list-widget-container crm-webform-list-widget-right">

			<div class="crm-webform-list-inner-info-container">
				<div class="crm-webform-list-creation-date-container">
					<div class="crm-webform-list-creation-date-element">
						<span class="crm-webform-list-text"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_DATE_CREATE')?>:</span>
						<span class="crm-webform-list-date">
							<?=htmlspecialcharsbx($item['DATE_CREATE_DISPLAY'])?>,
							ID: <?=intval($item['ID'])?>
						</span>
					</div>
				</div><!--crm-webform-list-creation-date-container-->
				<div class="crm-webform-list-active-info-container">
					<div data-bx-crm-webform-item-active-date="" class="crm-webform-list-active-info">
						<div class="crm-webform-list-active-info-def">
							<span class="crm-webform-list-text">
								<?=($item['ACTIVE'] == 'Y' ? Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ACTIVATED') : Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_DEACTIVATED'))?>
								<?=$item['DATE_CREATE_DISPLAY_DATE']?> <?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ACT_ON')?> <?=$item['DATE_CREATE_DISPLAY_TIME']?>:
							</span>
							<span class="crm-webform-list-date">
								<?
								if($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON']) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="crm-webform-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="crm-webform-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
						<div class="crm-webform-list-active-info-now">
							<span class="crm-webform-list-text">
								<span class="crm-webform-list-activate-comments-act"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ON_NOW')?>:</span>
								<span class="crm-webform-list-activate-comments-deact"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_OFF_NOW')?>:</span>
							</span>
							<span class="crm-webform-list-date">
								<?
								if($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON']) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="crm-webform-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="crm-webform-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
					</div>
				</div><!--crm-webform-list-creation-date-container-->
				<div class="crm-webform-list-url-container">
					<div class="crm-webform-list-url-element">
						<span class="crm-webform-list-url-inner-wrap">
							<span class="crm-webform-list-url-text"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK')?>:</span>
							<a href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_FILL'])?>" target="_blank" class="copy-to-clipboard-node crm-webform-list-url-link">
								<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_FILL'])?>
							</a>
						</span>
						<span class="copy-to-clipboard-button crm-webform-list-url-link-icon" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK_COPY')?>"></span>
					</div>
				</div><!--crm-webform-list-url-container-->
				<div class="crm-webform-list-deal-container">
					<div class="crm-webform-list-deal-element">
						<span class="crm-webform-deal-text"><?=$item['ENTITY_COUNTERS_DISPLAY']?></span>
					</div>
				</div><!--crm-webform-list-deal-container-->
				<div class="crm-webform-list-fill-started-container">
					<div class="crm-webform-list-fill-started-element">
						<span class="crm-webform-fill-started-text">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_START_EDIT_BUT_STOPPED')?> - <?=intval($item['COUNT_QUIT_FILL'])?>
						</span>
					</div>
				</div><!--crm-webform-list-fill-started-container-->
			</div><!--crm-webform-list-inner-info-container-->

			<div class="crm-webform-list-button-settings-container">
				<button class="ui-btn ui-btn-md ui-btn-primary" data-bx-crm-webform-item-btn-getscript=""><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_BTN_GET_SCRIPT')?></button>
				<a class="ui-btn ui-btn-md ui-btn-light-border" data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_EDIT'])?>">
					<?if($item['IS_READONLY'] == 'Y'):?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW')?>
					<?else:?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_EDIT')?>
					<?endif;?>
				</a>
				<button class="ui-btn ui-btn-md <?=($item['ACTIVE'] <> 'Y' ? 'ui-btn-success' : 'ui-btn-light-border')?>"
						data-bx-crm-webform-item-active-btn=""
						data-bx-text-on="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_ON')?>"
						data-bx-text-off="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_OFF')?>">
					<?if($item['ACTIVE'] == 'Y'):?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_OFF')?>
					<?else:?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_ON')?>
					<?endif;?>
				</button>
			</div><!--intranet-button-list-button-settings-container-->

		</div><!--crm-webform-list-widget-container crm-webform-list-widget-right-->
	</div><!--crm-webform-list-widget-row-->

<?endforeach;?>
	</div>
<?endforeach;?>
</div><!--crm-webform-list-wrapper-->

	<?if($arResult['SHOW_PLUGINS']):?>
	<div class="crm-webform-list-header-container">
		<h3 class="crm-webform-list-header"><?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_TITLE')?></h3>
	</div><!--crm-webform-list-header-container-->

	<div class="crm-webform-list-widget-row">
		<div class="crm-webform-list-widget-plugin">
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-1"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-2"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-3"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-4"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-more">
					<span class="crm-webform-list-widget-plugin-item-text">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_BTN_MORE')?>...
					</span>
				</span>
		</div><!--crm-webform-list-widget-plugin-->
		<div class="crm-webform-list-widget-plugin-description">
			<span class="crm-webform-list-widget-plugin-description-item">
				<?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_DESC')?>
			</span>
		</div>
	</div>
	<?endif;?>

</div>

<script id="crm-webform-list-template-ads-popup" type="text/html">
	<div>
		<div data-bx-ads-content="" id="crm-webform-list-ads-popup"></div>
		<div data-bx-ads-loader="" style="display: none;" class="crm-webform-list-ads-popup">
			<div class="crm-circle-loader-item">
				<div class="crm-circle-loader">
					<svg class="crm-circle-loader-circular" viewBox="25 25 50 50">
						<circle class="crm-circle-loader-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
					</svg>
				</div>
			</div>
		</div>
	</div>
</script>

<script>
	BX.ready(function(){
		(new CrmWebFormList(<?=Json::encode(
			array(
				'context' => 'crm_web_form_list_container',
				'containerId' => $arParams['CONTAINER_NODE_ID'],
				'canEdit' => $arResult['PERM_CAN_EDIT'],
				'adsConfig' =>  array(
					'canEdit' => $arResult['ADS_FORM']['CAN_EDIT']
				),
				'isFramePopup' => $arParams['IFRAME'],
				'viewUserOptionName' => $arResult['userOptionViewType'],
				'viewList' => $arResult['viewList'],
				'actionList' => $arResult['actionList'],
				'manualActions' => $arResult['manualActions'],
				'listPageUrl' => $arParams['PATH_TO_WEB_FORM_LIST'],
				'detailPageUrlTemplate' => $arParams['PATH_TO_WEB_FORM_EDIT'],
				'adsPageUrlTemplate' => $arParams['PATH_TO_WEB_FORM_ADS'],
				'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
				'restrictionPopup' => $arResult["RESTRICTION_POPUP"],
				'filterList' => $arResult['FILTER'],
				'mess' => array(
					'successAction' => Loc::getMessage('CRM_WEBFORM_LIST_SUCCESS_ACTION'),
					'errorAction' => Loc::getMessage('CRM_WEBFORM_LIST_ERROR_ACTION'),
					'deleteConfirmation' => Loc::getMessage('CRM_WEBFORM_LIST_DELETE_CONFIRM'),
					'dlgBtnClose' => Loc::getMessage('CRM_WEBFORM_LIST_CLOSE'),
					'dlgBtnApply' => Loc::getMessage('CRM_WEBFORM_LIST_APPLY'),
					'dlgBtnCancel' => Loc::getMessage('CRM_WEBFORM_LIST_CANCEL'),
					'dlgBtnCopy' => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK_COPY'),
					'dlgGetScriptTitle' => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_BTN_GET_SCRIPT'),
					'actionFormCacheCleaned' => Loc::getMessage('CRM_WEBFORM_LIST_SUCCESS_ACTION_CACHE_CLEANED'),
				)
			))?>
		));
	});
</script>

<?
$this->SetViewTarget("pagetitle", 10);
?>
	<button id="webform_filter_active" class="ui-btn ui-btn-md ui-btn-themes ui-btn-light-border ui-btn-dropdown" data-bx-text="<?=Loc::getMessage('CRM_WEBFORM_LIST_FILTER_SHOW')?>">
		<?=Loc::getMessage('CRM_WEBFORM_LIST_FILTER_SHOW')?>: <?=$arResult['FILTER_ACTIVE_CURRENT_NAME']?></button>

	<?if ($arResult['PERM_CAN_EDIT']):?>
	<a class="ui-btn ui-btn-md ui-btn-primary" id="CRM_WEBFORM_LIST_ADD" href="<?=htmlspecialcharsbx($arResult['PATH_TO_WEB_FORM_NEW'])?>">
		<?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_CAPTION')?></a>
	<?endif;?>
<?
$this->EndViewTarget();

ob_start();
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.webform.script',
		'',
		array(
			'FORM' => array(),
			'PATH_TO_WEB_FORM_FILL' => $arParams['PATH_TO_WEB_FORM_FILL']
		),
		null,
		array('HIDE_ICONS'=>true, 'ACTIVE_COMPONENT'=>'Y')
	);
ob_end_clean();
?>