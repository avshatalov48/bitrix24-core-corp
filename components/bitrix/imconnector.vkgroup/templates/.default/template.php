<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Connector;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load('ui.buttons');
	Extension::load('ui.hint');
	Connector::initIconCss();
}

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);

if (!empty($arResult['GROUP_ORDERS'])):
	include 'group-orders.php';
else:?>
	<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
		<?=bitrix_sessid_post();?>
	</form>
	<?if(
		empty($arResult['PAGE'])
		&& $arResult['ACTIVE_STATUS']
	):
		if ($arResult['STATUS'] === true):?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-main-subtitle"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED')?></div>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CHANGE_ANY_TIME')?>
						</div>
						<div class="ui-btn-container">
							<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>" class="ui-btn ui-btn-primary show-preloader-button">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
							</a>
							<button class="ui-btn ui-btn-light-border"
									onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<?include 'messages.php';?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INFO')?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_USER')?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['URL'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['NAME'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['INFO']['URL']))?>"></span>
						</div>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?
								if ($arResult['FORM']['GROUP']['TYPE'] === 'event')
								{
									echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_EVENT');
								}
								elseif ($arResult['GROUP']['TYPE'] === 'page')
								{
									echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_PAGE');
								}
								else
								{
									echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_GROUP');
								}
								?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['GROUP']['URL']))?>"></span>
						</div>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_GROUP_IM')?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL_IM'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL_IM'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['GROUP']['URL_IM']))?>"></span>
						</div>
					</div>
				</div>
			</div>
		<?else:?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-main-subtitle"><?=$arResult['NAME']?></div>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?>
						</div>
						<div class="ui-btn-container">
							<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>" class="ui-btn ui-btn-primary show-preloader-button">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?>
							</a>
							<button class="ui-btn ui-btn-light-border"
									onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<?include 'messages.php';?>
		<?endif;
	else:
		if (empty($arResult['FORM']['USER']['INFO'])): //start case with clear connections?>
			<? if (!empty($arResult['ACTIVE_STATUS'])): //case before auth to vk?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social">
						<div class="imconnector-field-box">
							<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-main-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_TITLE') ?>
							</div>
							<div class="imconnector-field-box-content">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_DESCRIPTION') ?>
							</div>
						</div>
					</div>
				</div>
				<?include 'messages.php';?>

				<div class="imconnector-field-container">
					<div class="imconnector-field-section">
						<div class="imconnector-field-main-title">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_AUTHORIZATION') ?>
						</div>
						<div class="imconnector-field-box">
							<div class="imconnector-field-box-content">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_ENTITY') ?>
							</div>
						</div>
						<?if ($arResult['FORM']['USER']['URI'] !== ''):?>
							<div class="imconnector-field-social-connector">
								<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
								<div class="ui-btn ui-btn-light-border"
									 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['URI'])) ?>', 700, 525)">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_AUTHORIZE') ?>
								</div>
							</div>
						<?endif;?>
					</div>
				</div>
			<?else://case before start connecting to vk?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social imconnector-field-section-info">
						<div class="imconnector-field-box">
							<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
						</div>
						<div class="imconnector-field-box" data-role="more-info">
							<div class="imconnector-field-main-subtitle imconnector-field-section-main-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_TITLE')?>
							</div>
							<div class="imconnector-field-box-content">

								<div class="imconnector-field-box-content-text-light">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_SUBTITLE') ?>
								</div>

								<ul class="imconnector-field-box-content-text-items">
									<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_LIST_ITEM_1') ?></li>
									<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_LIST_ITEM_2') ?></li>
									<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_LIST_ITEM_3') ?></li>
									<li class="imconnector-field-box-content-text-item"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_LIST_ITEM_4') ?></li>
								</ul>

								<div class="imconnector-field-box-content-text-light">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_INDEX_ADDITIONAL_DESCRIPTION')?>
								</div>

								<div class="imconnector-field-box-content-btn">
									<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
										<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
										<?=bitrix_sessid_post()?>
										<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
												type="submit"
												name="<?=$arResult['CONNECTOR']?>_active"
												value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
											<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
										</button>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?include 'messages.php';?>
			<?endif;?>
		<?else:?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-main-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED') ?>
						</div>
						<div class="imconnector-field-social-card">
							<div class="imconnector-field-social-card-info">
								<div class="imconnector-field-social-icon"></div>
								<a href="<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['URL']) ?>"
								   target="_blank"
								   class="imconnector-field-social-name">
									<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['NAME']) ?>
								</a>
							</div>
							<div class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
								 onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR']) ?>)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE') ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?include 'messages.php';?>
			<?if(empty($arResult['FORM']['GROUPS'])): //case user haven't got any groups.?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social-list">
						<div class="imconnector-intro">
							<div class="imconnector-intro-text">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_THERE_IS_NO_ENTITY_WHERE_THE_ADMINISTRATOR') ?>
							</div>
							<a href="https://vk.com/groups?tab=admin"
							   class="webform-small-button webform-small-button-accept webform-small-button-accept-nomargin"
							   target="_blank">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_TO_CREATE') ?>
							</a>
						</div>
					</div>
				</div>
			<?else:
				if (empty($arResult['FORM']['GROUP'])): //case user haven't choose active group yet?>
					<div class="imconnector-field-container">
						<div class="imconnector-field-section imconnector-field-section-social-list">
							<div class="imconnector-field-main-title">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_SELECT_THE_ENTITY') ?>
							</div>
							<div class="imconnector-field-social-list">
								<?foreach($arResult['FORM']['GROUPS'] as $group):
									if (empty($group['ACTIVE'])):?>
										<div class="imconnector-field-social-list-item">
											<div class="imconnector-field-social-list-inner">
												<div class="imconnector-field-social-icon imconnector-field-social-list-icon"></div>
												<div class="imconnector-field-social-list-info">
													<a href="<?=htmlspecialcharsbx($group['INFO']['URL']) ?>"
													   target="_blank"
													   class="imconnector-field-social-name">
														<?=htmlspecialcharsbx($group['INFO']['NAME']) ?>
													</a>
													<div class="imconnector-field-box-subtitle">
														<?
														if ($group['INFO']['TYPE'] === 'event')
														{
															echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_EVENT');
														}
														elseif ($group['INFO']['TYPE'] === 'page')
														{
															echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_PAGE');
														}
														else
														{
															echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_GROUP');
														}
														?>
													</div>
												</div>
											</div>
											<?if ($group['URI'] !== ''):?>
												<div class="ui-btn ui-btn-sm ui-btn-light-border"
													 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($group['URI'])) ?>', 700, 525)">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>
												</div>
											<?endif;?>
										</div>
									<?endif;
								endforeach;?>
							</div>
						</div>
					</div>
				<?else:?>
					<div class="imconnector-field-container">
						<div class="imconnector-field-section">
							<div class="imconnector-field-main-title imconnector-field-main-title-no-border">
								<?
								if ($arResult['FORM']['GROUP']['TYPE'] === 'event')
								{
									echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECTED_PUBLIC_EVENT');
								}
								elseif ($arResult['FORM']['GROUP']['TYPE'] === 'page')
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
									<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-icon"><i></i></div>
									<a href="<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['URL']) ?>"
									   target="_blank"
									   class="imconnector-field-social-name">
										<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['NAME']) ?>
									</a>
								</div>
								<form action="<?=$arResult['URL']['SIMPLE_FORM'] ?>" method="post">
									<input type="hidden" name="<?=$arResult['CONNECTOR'] ?>_form" value="true">
									<input type="hidden" name="group_id" value="<?=htmlspecialcharsbx($arResult['FORM']['GROUP']['ID']) ?>">
									<?=bitrix_sessid_post(); ?>
									<button class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
											name="<?=$arResult['CONNECTOR'] ?>_del_group"
											type="submit"
											value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_DEL_REFERENCE') ?>">
										<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_DEL_REFERENCE') ?>
									</button>
								</form>
							</div>

							<?if(count($arResult['FORM']['GROUPS']) > 1):?>
								<div class="imconnector-field-dropdown-button" id="toggle-list">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_MY_OTHER_ENTITY') ?>
								</div>

								<div class="imconnector-field-box imconnector-field-social-list-modifier imconnector-field-box-hidden"
									 id="hidden-list">
									<div class="imconnector-field-main-title">
										<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_OTHER_ENTITY') ?>
									</div>
									<div class="imconnector-field-social-list">
										<?foreach ($arResult['FORM']['GROUPS'] as $group):
											if (empty($group['ACTIVE'])):?>
												<div class="imconnector-field-social-list-item">
													<div class="imconnector-field-social-list-inner">
														<div class="imconnector-field-social-icon imconnector-field-social-list-icon"></div>
														<div class="imconnector-field-social-list-info">
															<a href="<?= htmlspecialcharsbx($group['INFO']['URL']) ?>"
															   target="_blank"
															   class="imconnector-field-social-name">
																<?= htmlspecialcharsbx($group['INFO']['NAME']) ?>
															</a>
															<div class="imconnector-field-box-subtitle">
																<?
																if ($group['INFO']['TYPE'] === 'event')
																{
																	echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_EVENT');
																}
																elseif ($group['INFO']['TYPE'] === 'page')
																{
																	echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_PUBLIC_PAGE');
																}
																else
																{
																	echo Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CONNECT_GROUP');
																}
																?>
															</div>
														</div>
													</div>
													<?if ($group['URI'] !== ''):?>
														<div class="ui-btn ui-btn-sm ui-btn-light-border"
															 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($group['URI'])) ?>', 700, 525)">
															<?=Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_CHANGE') ?>
														</div>
													<?endif;?>
												</div>
											<?endif;
										endforeach;?>
									</div>
								</div>
							<?endif;?>
						</div>
					</div>
				<?endif;
			endif;
		endif;
	endif;
endif;
