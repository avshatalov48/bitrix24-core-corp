<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;

$helpDeskCode = 'redirect=detail&code=8288267';

Loc::loadMessages(__FILE__);
?>
<?if (!empty($arResult['STATUS'])):?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-vk-order"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<?if($arResult['DATA_STATUS']['get_order_messages'] === 'Y'):?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CHANGE_ANY_TIME')?>
					</div>
				<?else:?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_GET_INFO')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_CONNECTION_INFO_NEW', [
							'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-vkgroup-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>
				<?endif;?>
			</div>
		</div>
	</div>
	<?include 'messages.php';?>
	<div class="imconnector-field-container">
		<?if ($arResult['DATA_STATUS']['get_order_messages'] === 'Y'):?>
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title imconnector-field-main-title-no-border">
					<?
					if($arResult['FORM']['GROUP']['TYPE'] === 'event')
					{
						echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_PUBLIC_EVENT');
					}
					elseif($arResult['FORM']['GROUP']['TYPE'] === 'page')
					{
						echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_PUBLIC_PAGE');
					}
					else
					{
						echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_GROUP');
					}
					?>
				</div>
				<div class="imconnector-field-social-card">
					<div class="imconnector-field-social-card-info">
						<div class="connector-icon ui-icon ui-icon-service-vk imconnector-field-social-icon"><i></i></div>
						<a href="<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL']) ?>"
						   target="_blank"
						   class="imconnector-field-social-name">
							<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['NAME']) ?>
						</a>
					</div>
					<form action="<?=$arResult['URL']['SIMPLE_FORM'] ?>" method="post">
						<input type="hidden" name="<?= $arResult['CONNECTOR'] ?>_form" value="true">
						<input type="hidden" name="get_order_messages" value="N">
						<?=bitrix_sessid_post(); ?>
						<button class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
								name="<?=$arResult['CONNECTOR']?>_save_orders"
								type="submit"
								value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_DEL_REFERENCE') ?>">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_DEL_REFERENCE') ?>
						</button>
					</form>
				</div>
			</div>
		<?else:?>
			<div class="imconnector-field-section imconnector-field-section-social-list">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_CONNECT_MESSAGES')?>
				</div>
				<div class="imconnector-field-social-list">
					<div class="imconnector-field-social-list-item">
						<div class="imconnector-field-social-list-inner">
							<div class="imconnector-field-social-icon imconnector-field-social-list-icon"></div>
							<div class="imconnector-field-social-list-info">
								<a href="<?= htmlspecialcharsbx($arResult['FORM']['GROUP']['URL']) ?>"
								   target="_blank"
								   class="imconnector-field-social-name">
									<?= htmlspecialcharsbx($arResult['FORM']['GROUP']['NAME']) ?>
								</a>
								<div class="imconnector-field-box-subtitle">
									<?
									if($arResult['FORM']['GROUP']['TYPE'] === 'event')
									{
										echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_PUBLIC_EVENT');
									}
									elseif($arResult['FORM']['GROUP']['TYPE'] === 'page')
									{
										echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_PUBLIC_PAGE');
									}
									else
									{
										echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_GROUP');
									}
									?>
								</div>
							</div>
						</div>
						<form action="<?= $arResult['URL']['SIMPLE_FORM_EDIT'] ?>" method="post">
							<input type="hidden" name="<?= $arResult['CONNECTOR'] ?>_form" value="true">
							<input type="hidden" name="get_order_messages" value="Y">
							<?= bitrix_sessid_post(); ?>
							<button class="ui-btn ui-btn-sm ui-btn-light-border"
									name="<?=$arResult['CONNECTOR']?>_save_orders"
									type="submit"
									value="<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		<?endif;?>
	</div>
<?else:?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social imconnector-field-section-info">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-vk-order"><i></i></div>
			</div>
			<div class="imconnector-field-box" data-role="more-info">
				<div class="imconnector-field-main-subtitle imconnector-field-section-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_SUBTITLE')?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_LIST_ITEM_1')?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_LIST_ITEM_2')?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_LIST_ITEM_3')?></li>
						<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_LIST_ITEM_4')?></li>
					</ul>

					<div class="imconnector-field-box-content-text-light">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ORDERS_INDEX_ADDITIONAL_DESCRIPTION_NEW', [
							'#LINK_START#' => '<a href="#" id="imconnector-vkgroup-link-help">',
							'#LINK_END#' => '</a>',
						])?>
					</div>

					<div class="imconnector-field-box-content-btn">
						<a href="#" class="ui-btn ui-btn-lg ui-btn-success ui-btn-round" onclick="BX.SidePanel.Instance.open('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['URL']['ORIGINAL_FORM']))?>', {width: 680, requestMethod: 'post', events: {onClose: function(e){BX.SidePanel.Instance.postMessage(e.getSlider(), 'ImConnector:vk.reload', {})}}}); return false;"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?include 'messages.php';?>
<?endif;

