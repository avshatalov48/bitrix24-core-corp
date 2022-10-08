<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Web\Json,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\Imopenlines\Limit;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'clipboard',
]);

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

\Bitrix\ImConnector\Connector::initIconCss();
if(\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
}
?>

<div class="crm-webform-list-wrapper">
	<?if(!$arResult['HIDE_DESC']):?>
	<div id="CRM_LIST_DESC_CONT" class="crm-webform-list-info">
		<h2 class="crm-webform-list-info-title"><?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_1')?></h2>
		<div class="crm-webform-list-info-visual">
			<span class="crm-webform-list-info-visual-item" style="width: 402px; height: 227px">
				<img src="<?=$this->GetFolder() . Loc::getMessage('OL_COMPONENT_INDEX_PICTURE')?>">
			</span>
		</div>
		<span class="imopenlines-list-info-description"><?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_2')?></span>
		<div class="imopenlines-list-info-list-container">
			<ul class="imopenlines-list-info-list">
				<li class="imopenlines-list-info-list-item">
					<?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_3')?>
				</li>
				<li class="imopenlines-list-info-list-item">
					<?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_4')?>
				</li>
				<li class="imopenlines-list-info-list-item">
					<?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_5')?>
				</li>
				<li class="imopenlines-list-info-list-item">
					<?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_6')?>
				</li>
				<li class="imopenlines-list-info-list-item">
					<?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_7')?>
				</li>
			</ul>
		</div>
		<span class="imopenlines-list-info-slogan"><?=Loc::getMessage('OL_COMPONENT_LIST_PROMO_8')?></span>
		<span id="CRM_LIST_DESC_BTN_HIDE" class="crm-webform-list-info-btn-hide" title="<?=Loc::getMessage('COL_COMPONENT_LIST_HIDE_DESC')?>"></span>
	</div>
	<?endif;?>
	<div id="crm_web_form_list_container">
<?if(!empty($arResult['LINES'])):?>
	<div class="crm-webform-list-header-container">
		<h3 id="close-title" class="crm-webform-list-header"><?=Loc::getMessage('OL_COMPONENT_LIST_HEADER')?></h3>
	</div>
<?endif;?>
<div data-bx-crm-webform-item="0"></div>
<?foreach($arResult['LINES'] as $line):?>
	<div class="crm-webform-list-widget-row"
		data-bx-crm-webform-item="<?=intval($line['ID'])?>"
	    data-bx-crm-webform-item-is-system="0"
	>
		<div class="crm-webform-list-buttons-container">
			<div class="crm-webform-list-buttons">
				<!--<span class="crm-webform-list-hamburger" data-bx-crm-webform-item-settings=""></span>-->
				<span class="crm-webform-list-close" data-bx-crm-webform-item-delete="" title="<?=Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_REMOVE')?>"></span>
			</div>
		</div>
		<div class="crm-webform-list-widget-container crm-webform-list-widget-left crm-webform-list-widget-number <?=$line['viewClassName']?> <?=($line['ACTIVE'] == 'Y' ? '' : 'crm-webform-list-widget-inactive')?>" data-bx-crm-webform-item-view="">
			<div class="crm-webform-list-widget">
				<a onclick="BX.SidePanel.Instance.open('<?=str_replace('#ID#', $line['ID'], $arResult['PATH_TO_EDIT'])?>', {width: 996})" style="cursor: pointer;">
					<div class="crm-webform-list-widget-head">
						<span class="crm-webform-list-widget-title-container">
							<span class="crm-webform-list-widget-title-inner">
								<span class="crm-webform-list-widget-title"><?=htmlspecialcharsbx($line['LINE_NAME'])?></span>
							</span>
						</span>
					</div>
				</a>
				<div class="crm-webform-list-widget-content">
					<div class="crm-webform-list-widget-content-amt">
						<span class="crm-webform-list-widget-content-image"></span>
						<div class="crm-webform-list-widget-content-title"><?=Loc::getMessage('OL_COMPONENT_LIST_COUNT_DIALOG_NEW')?></div>
						<div class="crm-webform-list-widget-content-number"><?=$line['STATS_SESSION']?></div>
						<div class="crm-webform-list-widget-content-attempt">
							<span class="crm-webform-list-widget-content-attempt-total">
								<span class="crm-webform-list-widget-content-attempt-total-element"><?=Loc::getMessage('OL_COMPONENT_LIST_COUNT_CLOSE')?></span>
								<div class="crm-webform-list-widget-content-attempt-total-number"><?=$line['STATS_CLOSED']?></div>
							</span>
							<span class="crm-webform-list-widget-content-attempt-success">
								<span class="crm-webform-list-widget-content-attempt-success-element"><?=Loc::getMessage('OL_COMPONENT_LIST_COUNT_IN_WORK')?></span>
								<div class="crm-webform-list-widget-content-attempt-success-number"><?=$line['STATS_IN_WORK']?></div>
							</span>
							<span class="crm-webform-list-widget-content-attempt-success" style="margin-left: 25px;">
								<span class="crm-webform-list-widget-content-attempt-success-element"><?=Loc::getMessage('OL_COMPONENT_LIST_COUNT_MESSAGE_NEW_NEW')?></span>
								<div class="crm-webform-list-widget-content-attempt-success-number"><?=$line['STATS_MESSAGE']?></div>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="crm-webform-list-widget-container crm-webform-list-widget-right">

			<div class="crm-webform-list-inner-info-container">
				<div class="crm-webform-list-inner-block crm-webform-list-creation-date-container">
					<div class="crm-webform-list-creation-date-element">
						<span class="crm-webform-list-text"><?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_DATE_CREATE')?>:</span>
						<span class="crm-webform-list-date"><?=htmlspecialcharsbx($line['DATE_CREATE_DISPLAY'])?></span>
					</div>
				</div>

				<div class="crm-webform-list-active-info-container">
					<div data-bx-crm-webform-item-active-date="" class="crm-webform-list-active-info">
						<div class="crm-webform-list-active-info-def">
							<?if($line['CHANGE_DATE_DISPLAY']):?>
							<span class="crm-webform-list-text">
								<?=Loc::getMessage('OL_COMPONENT_LIST_MODIFY_DATE')?>
								<?=$line['DATE_CREATE_DISPLAY_DATE']?> <?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_ACT_ON')?> <?=$line['CHANGE_DATE_DISPLAY']?>:
							</span>
							<span class="crm-webform-list-date">
								<?
								if($line['CHANGE_BY_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($line['CHANGE_BY_DISPLAY']['ICON']) .'\');';
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
									<a href="<?=htmlspecialcharsbx($line['CHANGE_BY_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($line['CHANGE_BY_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
							<?endif;?>
						</div>
						<div class="crm-webform-list-active-info-now">
							<span class="crm-webform-list-text">
								<span class="crm-webform-list-activate-comments-act"><?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_ON_NOW')?>:</span>
								<span class="crm-webform-list-activate-comments-deact"><?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_OFF_NOW')?>:</span>
							</span>
							<span class="crm-webform-list-date">
								<?
								if($line['CHANGE_BY_NOW_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($line['CHANGE_BY_NOW_DISPLAY']['ICON']) .'\');';
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
									<a href="<?=htmlspecialcharsbx($line['CHANGE_BY_NOW_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($line['CHANGE_BY_NOW_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
					</div>
				</div><!--crm-webform-list-creation-date-container-->

				<div class="crm-webform-list-inner-block crm-webform-list-deal-container">
					<div class="crm-webform-list-deal-element">
						<span class="crm-webform-deal-text"><?=Loc::getMessage('OL_COMPONENT_LIST_COUNT_LEAD', array("#COUNT#" => $line['STATS_LEAD']))?></span>
					</div>
				</div>
				<?if (!empty($line['ACTIVE_CONNECTORS'])):?>
				<div class="crm-webform-list-inner-block crm-webform-list-social-container">
					<div class="crm-webform-list-social-inner">
						<span class="crm-webform-list-social-text"><?=Loc::getMessage('OL_COMPONENT_LIST_CONNECTORS')?></span>
						<span class="crm-webform-list-social-icon-container">
							<?foreach ($line['ACTIVE_CONNECTORS'] as $id=>$name):?>
								<<?
								if($line['CAN_EDIT_CONNECTOR']):?>a<?else:?>span<?endif;
								if($line['CAN_EDIT_CONNECTOR']):?>
									onclick="BX.SidePanel.Instance.open('<?=CUtil::JSEscape(str_replace(['#ID#', '#LINE#'], [$id, $line['ID']], $arResult['PATH_TO_CONNECTOR']))?>', {width: 700})"
								<?endif;
								?> class="<?if($line['CAN_EDIT_CONNECTOR']):?>crm-webform-list-social-icon-cursor<?endif;?> crm-webform-list-social-icon ui-icon ui-icon-service-<?=$arResult['ICON_MAP'][$id]?>" title="<?=$name?>"><i></i></<?
								if($line['CAN_EDIT_CONNECTOR']):?>a<?else:?>span<?endif;
								?>>
							<?endforeach;?>
						</span>
					</div>
				</div>
				<?endif?>
				<div class="crm-webform-list-inner-block crm-webform-list-stats-container">
					<div class="crm-webform-list-stats-inner">
						<span class="crm-webform-list-stats-text"><?=Loc::getMessage('OL_COMPONENT_LIST_STATS')?></span>
						<span class="crm-webform-list-stats-link">
							<a href="<?=str_replace('#ID#', $line['ID'], $arResult['PATH_TO_STATISTICS'])?>" class="crm-webform-list-stats-link-item"><?=Loc::getMessage('OL_COMPONENT_LIST_GOTO')?></a>
						</span>
					</div>
				</div>
				<div class="crm-webform-list-inner-block crm-webform-list-member-container">
					<div class="crm-webform-list-member-inner">
						<span class="crm-webform-list-member-text"><?=Loc::getMessage('OL_COMPONENT_LIST_QUEUE_NEW')?> <?=count($line['QUEUE']); ?></span>
					</div>
				</div>
				<?/*
				<div class="crm-webform-list-member-container">
					<div class="crm-webform-list-member-inner">
						<span class="crm-webform-list-member-text"><?=Loc::getMessage('OL_COMPONENT_LIST_QUEUE_NEW')?></span>
						<span class="crm-webform-list-member-roster-container">
							<span class="crm-webform-list-member-roster">
								<? $queueCount = count($line['QUEUE']); ?>
								<?for($i=0; $i < $queueCount; $i++):?>
								<span class="crm-webform-list-member-roster-user"></span>
								<?endfor;?>
							</span>
						</span>
					</div>
				</div>

				<div class="crm-webform-list-url-container">
					<div class="crm-webform-list-url-element">
						<span class="crm-webform-list-url-inner-wrap">
							<span class="crm-webform-list-url-text"><?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_PUBLIC_LINK')?>:</span>
							<a href="<?=htmlspecialcharsbx($line['PATH_TO_WEB_FORM_FILL'])?>" target="_blank" class="copy-to-clipboard-node crm-webform-list-url-link">
								<?=htmlspecialcharsbx($line['PATH_TO_WEB_FORM_FILL'])?>
							</a>
						</span>
						<span class="copy-to-clipboard-button crm-webform-list-url-link-icon" title="<?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_PUBLIC_LINK_COPY')?>"></span>
					</div>
				</div>
				*/?>
			</div>

			<div class="crm-webform-list-button-settings-container">
				<a onclick="BX.SidePanel.Instance.open('<?=str_replace('#ID#', $line['ID'], $arResult['PATH_TO_EDIT'])?>', {width: 996})"
				   class="webform-small-button webform-small-button-transparent crm-webform-list-button-settings">
					<?if(!$line["CAN_EDIT"]):?>
						<?=Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_VIEW')?>
					<?else:?>
						<?=Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_EDIT')?>
					<?endif;?>
				</a>

				<span data-bx-crm-webform-item-active-btn=""
					  data-bx-text-on="<?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_BTN_ON')?>"
					  data-bx-text-off="<?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_BTN_OFF')?>"
					  class="webform-small-button <?=($line['ACTIVE'] <> 'Y' ? 'webform-small-button-accept' : 'webform-small-button-transparent')?> crm-webform-list-button-settings"
				>
					<?if($line['ACTIVE'] == 'Y'):?>
						<?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_BTN_OFF')?>
					<?else:?>
						<?=Loc::getMessage('OL_COMPONENT_LIST_ITEM_ACTIVE_BTN_ON')?>
					<?endif;?>
				</span>
			</div>
		</div>
	</div>
<?endforeach;?>
</div>
</div>

<script>
	BX.ready(function(){
		(new CrmWebFormList(<?=Json::encode(
			[
				'context' => 'crm_web_form_list_container',
				'canEdit' => true,
				'viewUserOptionName' => $arResult['userOptionViewType'],
				'viewList' => $arResult['viewList'],
				'actionList' => $arResult['actionList'],
				'detailPageUrlTemplate' => $arResult['PATH_TO_EDIT'],
				'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
				'canUseVoteClient' => Limit::canUseVoteClient(),
				'mess' => [
					'errorAction' => Loc::getMessage('OL_COMPONENT_LIST_ERROR_ACTION'),
					'deleteConfirmation' => Loc::getMessage('OL_COMPONENT_LIST_DELETE_CONFIRM'),
					'dlgBtnClose' => Loc::getMessage('OL_COMPONENT_LIST_CLOSE'),
					'dlgBtnApply' => Loc::getMessage('OL_COMPONENT_LIST_APPLY_1'),
					'dlgBtnCancel' => Loc::getMessage('OL_COMPONENT_LIST_CANCEL'),
					'limitInfoHelper' => Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_OL_NUMBER
				]
			])?>
		));
	});
</script>
<?
if ($arResult['PERM_CAN_EDIT'])
{
	$this->SetViewTarget("pagetitle", 10);
	?>
	<div class="webform-small-button webform-small-button-blue webform-small-button-add" id="crm-webform-list-create" title="<?=Loc::getMessage('OL_COMPONENT_LIST_ADD_LINE_DESC')?>">
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text">
			<?=Loc::getMessage('OL_COMPONENT_LIST_ADD_LINE')?>
		</span>
	</div>
	<?
	$this->EndViewTarget();
}
?>