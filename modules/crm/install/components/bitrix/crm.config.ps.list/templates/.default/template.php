<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Localization\Loc;
?>
<div class="crm-config-ps-list-wrapper">
	<?if ($arResult['CAN_ADD']):?>
		<a href="<?=$arParams['PATH_TO_PS_ADD'];?>" style="display: block">
			<div class="crm-config-ps-list-createform-container">
				<div class="crm-config-ps-list-createform-element"><?=Loc::getMessage('CRM_PS_LIST_PS_CREATE')?></div>
				<span class="crm-config-ps-list-createform-description"><?=Loc::getMessage('CRM_PS_LIST_PS_CREATE_DESC')?></span>
			</div>
		</a>
	<?endif;?>
	<div class="crm-config-ps-list-header-container">
		<h3 id="close-title" class="crm-config-ps-list-header"><?=Loc::getMessage('CRM_PS_LIST_TITLE')?></h3>
	</div>
	<?foreach ($arResult['PAY_SYSTEMS'] as $paySystem):?>
		<div id="close-row-<?=$paySystem['ID'];?>" class="crm-config-ps-list-widget-row">
			<div class="crm-config-ps-list-buttons-container">
				<div class="crm-config-ps-list-buttons">
					<span class="crm-config-ps-list-hamburger" onclick="BX.PopupMenu.show('menu-<?=$paySystem['ID'];?>', this, [{text : '<?=Loc::getMessage('CRM_PS_EDIT');?>', href : '<?=$paySystem['PATH_TO_PS_EDIT'];?>'}],{angle : {offset : 30}});"></span>
					<span class="crm-config-ps-list-close" onclick="BX.Crm.PaySystemList.delete(<?=$paySystem['ID'];?>);"></span>
				</div>
			</div>
			<div class="crm-config-ps-list-widget-container crm-config-ps-list-widget-left">
				<div class="crm-config-ps-list-widget crm-config-ps-list-widget-number orange-head">
					<div class="crm-config-ps-list-widget-head">
						<span class="crm-config-ps-list-widget-title-container">
							<span class="crm-config-ps-list-widget-title-inner">
								<a href="<?=$paySystem['PATH_TO_PS_EDIT'];?>" class="crm-config-ps-list-widget-title"><?=$paySystem['NAME'];?></a>
							</span>
						</span>
					</div>
					<div class="crm-config-ps-list-widget-content">
						<div class="crm-config-ps-list-widget-content-amt">
							<div class="crm-config-ps-list-widget-content-image">
								<a href="<?=$paySystem['PATH_TO_PS_EDIT'];?>">
									<?
									if ($paySystem["LOGOTIP"] > 0)
									{
										$logo = CFile::GetFileArray($paySystem["LOGOTIP"]);
									}
									else
									{
										$logo = '/bitrix/images/sale/sale_payments/'.$paySystem["HANDLER"].'.png';
										if (!\Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$logo))
										{
											$logo = '/bitrix/images/sale/sale_payments/default.png';
										}
									}

									echo CFile::ShowImage($logo, 200, 70, "border=0", "", false);
									?>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="crm-config-ps-list-widget-container crm-config-ps-list-widget-right">
				<div class="crm-config-ps-list-inner-info-container">
					<div class="crm-config-ps-list-connector-container">
						<div class="crm-config-ps-list-connector-element">
							<span class="crm-config-ps-list-text"><?=$paySystem['DESCRIPTION']['COMMISSION'];?></span>
						</div>
					</div>
					<div class="crm-config-ps-list-connector-cancel-container">
						<div class="crm-config-ps-list-connector-cancel-element">
							<span class="crm-config-ps-list-connector-cancel-text"><?=$paySystem['DESCRIPTION']['RESTRICTION'];?></span>
						</div>
					</div>
					<div class="crm-config-ps-list-deal-container">
						<div class="crm-config-ps-list-deal-element">
							<span class="crm-webform-deal-text"><?=$paySystem['DESCRIPTION']['RETURN'];?></span>
						</div>
					</div>
					<?if (array_key_exists('REFERRER', $paySystem['DESCRIPTION']) && $paySystem['IS_TUNED'] === 'N'):?>
						<div class="crm-config-ps-list-referrer-container">
							<div class="crm-config-ps-list-deal-element">
								<span class="crm-webform-deal-text"><?=$paySystem['DESCRIPTION']['REFERRER'];?></span>
							</div>
						</div>
					<?endif;?>
				</div>
				<div class="crm-config-ps-list-activate-wrapper">
					<div class="crm-config-ps-list-activate-container">
						<input type="hidden" name="current_status_<?=$paySystem['ID'];?>" id="current_status_<?=$paySystem['ID'];?>" value="<?=$paySystem['ACTIVE'];?>">
						<div class="crm-config-ps-list-activate-button-container" onclick="BX.Crm.PaySystemList.activate(<?=$paySystem['ID'];?>);">
							<span id="active_on_<?=$paySystem['ID'];?>" class="crm-config-ps-list-activate-button">
								<span class="crm-config-ps-list-activate-button-text"><?=Loc::getMessage('CRM_PS_LIST_PS_ACTIVE_BTN_ON')?></span>
							</span>
							<?
								if ($paySystem['ACTIVE'] == 'Y')
									$className = 'crm-config-ps-list-not-activate-button';
								else
									$className = 'crm-config-ps-list-activate-button crm-config-ps-off';
							?>
							<span id="active_off_<?=$paySystem['ID'];?>" class="<?=$className;?>">
								<span class="crm-config-ps-list-activate-button-cursor"></span>
								<span class="crm-config-ps-list-not-activate-button-text"><?=Loc::getMessage('CRM_PS_LIST_PS_ACTIVE_BTN_OFF')?></span>
							</span>
						</div>
						<span id="active_title_on_<?=$paySystem['ID'];?>" class="crm-config-ps-list-activate-button-item-<?=$paySystem['ACTIVE'] == 'Y' ? 'on' : 'off'?>"><?=Loc::getMessage('CRM_PS_LIST_PS_ACTIVE_ON')?></span>
						<span id="active_title_off_<?=$paySystem['ID'];?>" class="crm-config-ps-list-activate-button-item-<?=$paySystem['ACTIVE'] == 'Y' ? 'off' : 'on'?>"><?=Loc::getMessage('CRM_PS_LIST_PS_ACTIVE_OFF');?></span>
					</div>
					<span class="crm-config-ps-list-activate-user-wrapper" onclick="BX.toggleClass(BX('container-close'), 'user-container-disabled');BX.toggleClass(BX('container-open'), 'user-container-active')">
					</span>
				</div>
			</div>
		</div>
	<?endforeach;?>
</div>

<script type="text/javascript">
	BX.ready(function ()
	{
		BX.Crm.PaySystemList.init({
			ajaxUrl : '<?=$this->__component->getPath().'/ajax.php'?>'
		});

		BX.message({CRM_PS_DELETE_CONFIRM: '<?=Loc::getMessage("CRM_PS_DELETE_CONFIRM_TITLE")?>'});
	});
</script>
