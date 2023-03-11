<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'clipboard',
	'ui.buttons',
]);

?>

<div class="intranet-button-list-wrapper">

	<?if(!$arResult['HIDE_DESC']):?>
	<div id="CRM_LIST_DESC_CONT" class="intranet-button-list-info">
		<h2 class="intranet-button-list-info-title"><?=Loc::getMessage('CRM_BUTTON_LIST_INFO_TITLE')?></h2>
		<div class="intranet-button-list-info-visual">
			<span class="intranet-button-list-info-visual-item">
				<img src="<?=$this->GetFolder()?>/images/demo_main_<?=(in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by')) ? 'ru' : 'en')?>.png">
			</span>
		</div>
		<div class="intranet-button-list-info-inner">
			<span class="intranet-button-list-info-description"><?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC')?></span>
			<div class="intranet-button-list-info-list-container">
				<ul class="intranet-button-list-info-list">
					<li class="intranet-button-list-info-list-item">
						<?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC_ITEM_1')?>
					</li>
					<li class="intranet-button-list-info-list-item">
						<?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC_ITEM_2')?>
					</li>
					<li class="intranet-button-list-info-list-item">
						<?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC_ITEM_3')?>
					</li>
					<li class="intranet-button-list-info-list-item">
						<?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC_ITEM_4')?>
					</li>
					<li class="intranet-button-list-info-list-item">
						<?=Loc::getMessage('CRM_BUTTON_LIST_INFO_DESC_ITEM_5')?>
					</li>
				</ul>
			</div>
		</div><!--intranet-button-list-info-inner-->

		<span id="CRM_LIST_DESC_BTN_HIDE" class="intranet-button-list-info-btn-hide" title="<?=Loc::getMessage('CRM_BUTTON_LIST_HIDE_DESC')?>"></span>
	</div>
	<?endif;?>

	<div id="crm_web_form_list_container">
<?foreach($arResult['ITEMS_BY_IS_SYSTEM'] as $isSystem => $system):?>
	<div class="intranet-button-list-header-container">
		<h3 data-bx-list-head="" class="intranet-button-list-header">
			<?=htmlspecialcharsbx($system['NAME'])?>
		</h3>
	</div>
	<div data-bx-list-items="">
<?foreach($system['ITEMS'] as $item):
	$isSystem = $item['IS_SYSTEM'];
	?>
	<div class="intranet-button-list-widget-row"
		data-bx-crm-webform-item="<?=intval($item['ID'])?>"
		data-bx-crm-webform-item-is-system="<?=$isSystem?>"
	>
		<div class="intranet-button-list-buttons-container">
			<div class="intranet-button-list-buttons">
				<span class="intranet-button-list-hamburger" data-bx-crm-webform-item-settings=""></span>
				<?if($arResult['PERM_CAN_EDIT'] && $isSystem != 'Y'):?>
					<span class="intranet-button-list-close" data-bx-crm-webform-item-delete="" title="<?=Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_REMOVE')?>"></span>
				<?endif;?>
			</div><!--intranet-button-list-buttons-->
		</div><!--intranet-button-list-button-container-->
		<div class="intranet-button-list-widget-container intranet-button-list-widget-left">
			<div class="intranet-button-list-widget intranet-button-list-widget-number <?=$item['viewClassName']?> <?=($item['ACTIVE'] == 'Y' ? '' : 'intranet-button-list-widget-inactive')?>" data-bx-crm-webform-item-view="">
				<div class="intranet-button-list-widget-head">
					<span class="intranet-button-list-widget-title-container">
						<span class="intranet-button-list-widget-title-inner">
							<a data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_BUTTON_EDIT'])?>">
								<span data-bx-title="" class="intranet-button-list-widget-title"><?=htmlspecialcharsbx($item['NAME'])?></span>
							</a>
						</span>
					</span>
				</div><!--intranet-button-list-widget-head-->
				<div class="intranet-button-list-widget-content">
					<div class="intranet-button-list-widget-content-amt">
						<div class="intranet-button-list-widget-content-inner">
							<div class="intranet-button-list-widget-content-inner-block" title="<?=htmlspecialcharsbx($arResult['TYPE_LIST']['openline'])?>">
								<?if($item['ITEMS']['openline']):?>
									<div class="intranet-button-list-widget-content-inner-item intranet-button-list-widget-active">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-openlines"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=htmlspecialcharsbx($item['ITEMS']['openline']['NAME'])?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?else:?>
									<div class="intranet-button-list-widget-content-inner-item">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-openlines"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=Loc::getMessage('CRM_BUTTON_LIST_NOT_SELECTED')?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?endif;?>
							</div><!--intranet-button-list-widget-content-inner-item-->
							<div class="intranet-button-list-widget-content-inner-block" title="<?=htmlspecialcharsbx($arResult['TYPE_LIST']['crmform'])?>">
								<?if($item['ITEMS']['crmform']):?>
									<div class="intranet-button-list-widget-content-inner-item intranet-button-list-widget-active">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-webform"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=htmlspecialcharsbx($item['ITEMS']['crmform']['NAME'])?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?else:?>
									<div class="intranet-button-list-widget-content-inner-item">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-webform"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=Loc::getMessage('CRM_BUTTON_LIST_NOT_SELECTED')?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?endif;?>
							</div><!--intranet-button-list-widget-content-inner-item-->
							<div class="intranet-button-list-widget-content-inner-block" title="<?=htmlspecialcharsbx($arResult['TYPE_LIST']['callback'])?>">
								<?if($item['ITEMS']['callback']):?>
									<div class="intranet-button-list-widget-content-inner-item intranet-button-list-widget-active">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-call"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=htmlspecialcharsbx($item['ITEMS']['callback']['NAME'])?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?else:?>
									<div class="intranet-button-list-widget-content-inner-item">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-call"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=Loc::getMessage('CRM_BUTTON_LIST_NOT_SELECTED')?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?endif;?>
							</div><!--intranet-button-list-widget-content-inner-item-->

							<?if($arResult['SUPPORTING']['whatsapp']):?>
							<div class="intranet-button-list-widget-content-inner-block" title="<?=htmlspecialcharsbx($arResult['TYPE_LIST']['whatsapp'])?>">
								<?if($item['ITEMS']['whatsapp']):?>
									<div class="intranet-button-list-widget-content-inner-item intranet-button-list-widget-active">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-whatsapp"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=htmlspecialcharsbx($item['ITEMS']['whatsapp']['NAME'])?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?else:?>
									<div class="intranet-button-list-widget-content-inner-item">
										<div class="intranet-button-list-widget-content-inner-item-image intranet-button-list-whatsapp"></div>
										<div class="intranet-button-list-widget-content-inner-item-text"><?=Loc::getMessage('CRM_BUTTON_LIST_NOT_SELECTED')?></div>
									</div><!--intranet-button-list-widget-content-inner-item-->
								<?endif;?>
							</div><!--intranet-button-list-widget-content-inner-item-->
							<?endif;?>
						</div><!--intranet-button-list-widget-content-inner-->
					</div>
				</div><!--intranet-button-list-widget-content-->
			</div><!--intranet-button-list-widget intranet-button-list-widget-number-->
		</div><!--intranet-button-list-widget-container intranet-button-list-widget-left-->
		<div class="intranet-button-list-widget-container intranet-button-list-widget-right">

			<div class="intranet-button-list-inner-info-container">
				<div class="intranet-button-list-creation-date-container">
					<div class="intranet-button-list-creation-date-element">
						<span class="intranet-button-list-text"><?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_DATE_CREATE')?>:</span>
						<span class="intranet-button-list-date"><?=htmlspecialcharsbx($item['DATE_CREATE_DISPLAY'])?></span>
					</div>
				</div><!--intranet-button-list-creation-date-container-->
				<div class="intranet-button-list-active-info-container">
					<div data-bx-crm-webform-item-active-date="" class="intranet-button-list-active-info">
						<div class="intranet-button-list-active-info-def">
							<span class="intranet-button-list-text">
								<?=($item['ACTIVE'] == 'Y' ? Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_ACTIVATED') : Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_DEACTIVATED'))?>
								<?=$item['DATE_CREATE_DISPLAY_DATE']?> <?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_ACT_ON')?> <?=$item['DATE_CREATE_DISPLAY_TIME']?>:
							</span>
							<span class="intranet-button-list-date">
								<?
								if($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . Uri::urnEncode(htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON'])) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="intranet-button-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="intranet-button-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['LINK'])?>" class="intranet-button-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
						<div class="intranet-button-list-active-info-now">
							<span class="intranet-button-list-text">
								<span class="intranet-button-list-activate-comments-act"><?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_ON_NOW')?>:</span>
								<span class="intranet-button-list-activate-comments-deact"><?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_OFF_NOW')?>:</span>
							</span>
							<span class="intranet-button-list-text">
								<?
								if($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . Uri::urnEncode(htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON'])) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="intranet-button-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="intranet-button-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['LINK'])?>" class="intranet-button-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
					</div>
				</div><!--intranet-button-list-active-info-container-->
				<div class="intranet-button-list-position-container">
					<div class="intranet-button-list-position-element">
						<span class="intranet-button-list-position-inner-wrap">
							<span class="intranet-button-list-position-text"><?=Loc::getMessage('CRM_BUTTON_LIST_LOCATION')?>:</span>
							<span class="intranet-button-list-position-text"><?=htmlspecialcharsbx(mb_strtolower($item['LOCATION_DISPLAY']))?></span>
						</span>
					</div>
				</div><!--intranet-button-list-url-container-->
				<div class="intranet-button-list-settings-container">
					<div class="intranet-button-list-settings-element">
						<span class="intranet-button-list-settings-text"><?=Loc::getMessage('CRM_BUTTON_LIST_VIEW')?>:</span>
						<span class="intranet-button-list-settings-text"><?=htmlspecialcharsbx($item['PAGES_USE_DISPLAY'])?></span>
					</div>
				</div><!--intranet-button-list-deal-container-->
			</div><!--intranet-button-list-inner-info-container-->

			<div class="intranet-button-list-button-settings-container">
				<span data-bx-crm-webform-item-btn-getscript="" class="webform-small-button webform-small-button-blue intranet-button-list-button-settings">
					<?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_BTN_GET_SCRIPT')?>
				</span>
				<a data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_BUTTON_EDIT'])?>" class="webform-small-button webform-small-button-transparent intranet-button-list-button-settings">
					<?if(!$arResult['PERM_CAN_EDIT']):?>
						<?=Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_VIEW')?>
					<?else:?>
						<?=Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_EDIT')?>
					<?endif;?>
				</a>
				<span data-bx-crm-webform-item-active-btn=""
					data-bx-text-on="<?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_BTN_ON')?>"
					data-bx-text-off="<?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_BTN_OFF')?>"
					class="webform-small-button <?=($item['ACTIVE'] <> 'Y' ? 'webform-small-button-accept' : 'webform-small-button-transparent')?> intranet-button-list-button-settings"
				>
					<?if($item['ACTIVE'] == 'Y'):?>
						<?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_BTN_OFF')?>
					<?else:?>
						<?=Loc::getMessage('CRM_BUTTON_LIST_ITEM_ACTIVE_BTN_ON')?>
					<?endif;?>
				</span>
			</div><!--intranet-button-list-button-settings-container-->

		</div><!--intranet-button-list-widget-container intranet-button-list-widget-right-->
	</div><!--intranet-button-list-widget-row-->

<?endforeach;?>
	</div>
<?endforeach;?>
</div><!--intranet-button-list-wrapper-->

	<?if($arResult['SHOW_PLUGINS']):?>
	<div class="intranet-button-list-header-container">
		<h3 class="intranet-button-list-header"><?=Loc::getMessage('CRM_BUTTON_LIST_PLUGIN_TITLE')?></h3>
	</div><!--intranet-button-list-header-container-->

	<div class="intranet-button-list-widget-row">
		<div class="intranet-button-list-widget-plugin">
			<span class="intranet-button-list-widget-plugin-item intranet-button-list-widget-plugin-logo-1"></span>
			<span class="intranet-button-list-widget-plugin-item intranet-button-list-widget-plugin-logo-2"></span>
			<span class="intranet-button-list-widget-plugin-item intranet-button-list-widget-plugin-logo-3"></span>
			<span class="intranet-button-list-widget-plugin-item intranet-button-list-widget-plugin-logo-4"></span>
			<span class="intranet-button-list-widget-plugin-item intranet-button-list-widget-plugin-more">
					<span class="intranet-button-list-widget-plugin-item-text">
						<?=Loc::getMessage('CRM_BUTTON_LIST_PLUGIN_BTN_MORE')?>...
					</span>
				</span>
		</div><!--intranet-button-list-widget-plugin-->
		<div class="intranet-button-list-widget-plugin-description">
			<span class="intranet-button-list-widget-plugin-description-item">
				<?=Loc::getMessage('CRM_BUTTON_LIST_PLUGIN_DESC')?>
			</span>
		</div>
	</div><!--intranet-button-list-widget-plugin-container-->
	<?endif;?>

</div>

<script>
	BX.ready(function(){
		(new CrmWebFormList(<?=Json::encode(
			array(
				'context' => 'crm_web_form_list_container',
				'canEdit' => $arResult['PERM_CAN_EDIT'],
				'isFramePopup' => $arParams['IFRAME'],
				'viewUserOptionName' => $arResult['userOptionViewType'],
				'viewList' => $arResult['viewList'],
				'actionList' => $arResult['actionList'],
				'detailPageUrlTemplate' => $arParams['PATH_TO_BUTTON_EDIT'],
				'pathToButtonList' => $arParams['PATH_TO_BUTTON_LIST'],
				'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
				'mess' => array(
					'errorAction' => Loc::getMessage('CRM_BUTTON_LIST_ERROR_ACTION'),
					'deleteConfirmation' => Loc::getMessage('CRM_BUTTON_LIST_DELETE_CONFIRM'),
					'dlgBtnClose' => Loc::getMessage('CRM_BUTTON_LIST_CLOSE'),
					'dlgBtnApply' => Loc::getMessage('CRM_BUTTON_LIST_APPLY'),
					'dlgBtnCancel' => Loc::getMessage('CRM_BUTTON_LIST_CANCEL'),
					'dlgBtnCopyToClipboard' => Loc::getMessage('CRM_BUTTON_LIST_COPY_TO_CLIPBOARD'),
					'dlgGetScriptTitle' => Loc::getMessage('CRM_BUTTON_LIST_ITEM_BTN_GET_SCRIPT')
				)
			))?>
		));
	});
</script>

<?
	if ($arResult['PERM_CAN_EDIT'])
	{
		$this->SetViewTarget("pagetitle", 10);
		?>
			<a id="CRM_BUTTON_LIST_ADD" href="<?=htmlspecialcharsbx($arResult['PATH_TO_BUTTON_NEW'])?>" class="ui-btn ui-btn-primary">
				<?=Loc::getMessage('CRM_BUTTON_LIST_ADD_CAPTION')?>
			</a>
		<?
		$this->EndViewTarget();
	}
?>

<div style="display: none;">
	<div id="SCRIPT_CONTAINER" class="crm-button-list-sidebar-insert-code-container">
		<span class="crm-button-list-sidebar-insert-code-hint"><?=Loc::getMessage('CRM_BUTTON_LIST_SITE_SCRIPT_TIP')?></span>
		<div class="crm-button-list-sidebar-insert-code-inner">
			<div data-bx-webform-script-copy-text="" class="crm-button-list-sidebar-insert-code-item" style="width: 600px; height: 200px;"></div>
		</div>
	</div><!--crm-button-edit-sidebar-insert-code-container-->
</div>
