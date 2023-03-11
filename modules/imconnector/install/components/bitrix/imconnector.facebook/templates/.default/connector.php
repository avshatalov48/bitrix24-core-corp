<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$helpDeskLinkStart = "<a href=\"javascript:void(0)\" onclick='top.BX.Helper.show(\"redirect=detail&code=10443976\");event.preventDefault();'>";
$helpDeskLinkEnd = '</a>';

$helpDeskLinkNoPage = str_replace(
	['#A#', '#A_END#'],
	[$helpDeskLinkStart, $helpDeskLinkEnd],
	Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_NO_SPECIFIC_PAGE')
);

$langPostfix = $arResult['NEED_META_RESTRICTION_NOTE'] ? '_META_RU' : '';
$lang = [
	'index_title' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_TITLE' . $langPostfix),
	'index_subtitle' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_SUBTITLE' . $langPostfix),
	'index_additional_description' => Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_ADDITIONAL_DESCRIPTION' . $langPostfix),
];

if (!empty($arResult['NEED_META_RESTRICTION_NOTE']) && !$arResult['ACTIVE_STATUS'])
{
	$this->SetViewTarget('fb_meta_restriction_note');
	?>
	<div class="imconnector-restriction-note">
		<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_RESTRICTIONS_META_RU')?>
	</div>
	<?php
	$this->EndViewTarget();
}

if(
	empty($arResult['PAGE'])
	&& !empty($arResult['ACTIVE_STATUS'])
):
	if (!empty($arResult['STATUS']))
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CHANGE_ANY_TIME')?>
					</div>
					<div class="ui-btn-container">
						<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
						   class="ui-btn ui-btn-primary show-preloader-button">
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
		<? include 'messages.php'?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INFO')?>
				</div>
				<div class="imconnector-field-box">
					<?
					if(!empty($arResult['FORM']['USER']['INFO']['URL']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_USER')?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['URL'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['NAME'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['INFO']['URL']))?>"></span>
						</div>
						<?
					}
					?>
					<?
					if(!empty($arResult['FORM']['PAGE']['URL']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CONNECTED_PAGE')?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['PAGE']['URL'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['PAGE']['URL'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['PAGE']['URL']))?>"></span>
						</div>
						<?
					}
					?>
					<?
					if(!empty($arResult['FORM']['PAGE']['URL_IM']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_PAGE_IM')?>
							</div>
							<a href="<?=htmlspecialcharsbx($arResult['FORM']['PAGE']['URL_IM'])?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=htmlspecialcharsbx($arResult['FORM']['PAGE']['URL_IM'])?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['PAGE']['URL_IM']))?>"></span>
						</div>
						<?
					}
					?>
				</div>
			</div>
		</div>
		<? include 'messages.php'?>
		<?
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=$arResult['NAME']?>
					</div>
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
		<?
	}
else:
	if(empty($arResult['FORM']['USER']['INFO'])) //start case with clear connections
	{
		if (!empty($arResult['ACTIVE_STATUS'])) //case before auth to fb
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-main-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TITLE') ?>
						</div>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_DESCRIPTION') ?>
						</div>
					</div>
				</div>
			</div>
			<?include 'messages.php'?>

			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_AUTHORIZATION') ?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_PAGE') ?>
						</div>
					</div>
					<?
					if (!empty($arResult['FORM']['USER']['URI']))
					{
						?>
						<div class="imconnector-field-social-connector">
							<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
							<div class="ui-btn ui-btn-light-border"
								 onclick="BX.util.popup('<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['FORM']['USER']['URI']))?>', 700, 850)">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_AUTHORIZE') ?>
							</div>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<?
		}
		else
		{    //case before start connecting to fb
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social imconnector-field-section-info">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box" data-role="more-info">
						<div class="imconnector-field-main-subtitle imconnector-field-section-main-subtitle">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_TITLE')?>
						</div>
						<div class="imconnector-field-box-content">

							<div class="imconnector-field-box-content-text-light">
								<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_SUBTITLE') ?>
							</div>

							<ul class="imconnector-field-box-content-text-items">
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_LIST_ITEM_1') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_LIST_ITEM_2') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_LIST_ITEM_3') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_LIST_ITEM_4') ?></li>
							</ul>

							<div class="imconnector-field-box-content-text-light">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_INDEX_ADDITIONAL_DESCRIPTION')?>
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

			<?include 'messages.php'?>
			<?
		}
	}
	else
	{
		?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section imconnector-field-section-social">
				<div class="imconnector-field-box">
					<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
				</div>
				<div class="imconnector-field-box">
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CONNECTED') ?>
					</div>
					<div class="imconnector-field-social-card">
						<div class="imconnector-field-social-card-info">
							<div class="imconnector-field-social-icon"></div>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
							<span class="imconnector-field-social-name">
							<?else:?>
								<a href="<?=$arResult['FORM']['USER']['INFO']['URL'] ?>"
								   target="_blank"
								   class="imconnector-field-social-name">
							<?endif;?>
							<?=htmlspecialcharsbx($arResult['FORM']['USER']['INFO']['NAME']) ?>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
								</span>
						<?else:?>
							</a>
						<?endif;?>
						</div>
						<div class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
							 onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR']) ?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE') ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<? include 'messages.php'?>
		<?
		if(empty($arResult['FORM']['PAGES']))  //case user haven't got any groups.
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-intro">
						<div class="imconnector-intro-text">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_THERE_IS_NO_PAGE_WHERE_THE_ADMINISTRATOR') ?>
						</div>
						<a href="https://www.facebook.com/pages/create/"
						   class="webform-small-button webform-small-button-accept webform-small-button-accept-nomargin"
						   target="_blank">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CREATE_A_PAGE') ?>
						</a>
					</div>
				</div>
			</div>
			<?
		}
		else
		{
			if(empty($arResult['FORM']['PAGE'])) //case user haven't choose active group yet
			{
				?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social-list">
						<div class="imconnector-field-main-title">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_SELECT_THE_PAGE') ?>
						</div>
						<div class="imconnector-field-social-list">
							<?
							foreach ($arResult['FORM']['PAGES'] as $cell => $page)
							{
								if(empty($page['ACTIVE']))
								{
									?>
									<div class="imconnector-field-social-list-item">
										<form action="<?=$arResult['URL']['SIMPLE_FORM'] ?>" method="post">
											<div class="imconnector-field-social-list-inner">
												<div class="imconnector-field-social-icon imconnector-field-social-list-icon"></div>
												<div class="imconnector-field-social-list-info">
													<?if(empty($page['INFO']['URL'])):?>
													<span class="imconnector-field-social-name">
											<?else:?>
												<a href="<?=htmlspecialcharsbx($page['INFO']['URL']) ?>"
												   target="_blank"
												   class="imconnector-field-social-name">
											<?endif;?>
											<?=htmlspecialcharsbx($page['INFO']['NAME']) ?>
											<?if(empty($page['INFO']['URL'])):?>
												</span>
												<?else:?>
													</a>
												<?endif;?>
													<span class="imconnector-ui-option-facebook">
												<label for="human_agent_<?=$cell?>">
													<input id="human_agent_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="human_agent" value="Y" checked>
													<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT')?></span>
												</label><span class="imconnector-ui-hint-icon-facebook" onclick="top.BX.Helper.show('redirect=detail&code=<?=HELP_DESK_HUMAN_AGENT?>');"></span>
												</span>
													<div class="imconnector-field-social-card-human-agent-facebook">
														<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
															'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-facebook-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
															'#END_HELP_DESC#' => '</strong>',
														])?>
													</div>
													<input type="hidden" name="<?=$arResult['CONNECTOR'] ?>_form" value="true">
													<input type="hidden" name="page_id" value="<?=$page['INFO']['ID'] ?>">
													<?=bitrix_sessid_post(); ?>
													<button type="submit"
															name="<?=$arResult['CONNECTOR'] ?>_authorization_page"
															class="ui-btn ui-btn-sm ui-btn-light-border"
															value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>">
														<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>
													</button>
												</div>
											</div>
										</form>
									</div>
									<?
								}
							}
							?>
							<div class="imconnector-field-box-subtitle">
								<?=$helpDeskLinkNoPage?>
							</div>
						</div>
					</div>
				</div>
				<?
			}
			else
			{
				?>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section imconnector-field-section-social-list-fbinstagram">
						<div class="imconnector-field-main-title imconnector-field-main-title-no-border">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CONNECTED_PAGE') ?>
						</div>

						<div class="imconnector-field-social-card">
							<div class="imconnector-field-social-card-info-facebook">
								<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-icon"><i></i></div>

								<div class="imconnector-field-social-list-info">
									<div class="imconnector-field-social-list-info-inner">
										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										<span class="imconnector-field-social-name">
										<?else:?><a href="<?=$arResult['FORM']['PAGE']['URL'] ?>"
													target="_blank"
													class="imconnector-field-social-name">
										<?endif;?>
										<?=htmlspecialcharsbx($arResult['FORM']['PAGE']['NAME']) ?>
										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										</span>
									<?else:?>
										</a>
									<?endif;?>
									</div>

									<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
										<?if ($arResult['DATA_STATUS']['HUMAN_AGENT'] === true):?>
											<span class="imconnector-indicator-human-agent-facebook-on"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT_ON')?>
										<?else:?>
											<span class="imconnector-indicator-human-agent-facebook-off"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT_OFF')?>
										<?endif;?>
									</div>
									<div class="imconnector-field-social-card-human-agent-facebook imconnector-field-social-card-human-agent-facebook-invert">
										<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
											'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-facebook-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
											'#END_HELP_DESC#' => '</strong>',
										])?>
									</div>
									<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
										<form action="<?=$arResult['URL']['SIMPLE_FORM'] ?>" method="post">
											<input type="hidden" name="<?=$arResult['CONNECTOR'] ?>_form" value="true">
											<input type="hidden" name="page_id" value="<?=$arResult['FORM']['PAGE']['ID'] ?>">
											<?=bitrix_sessid_post(); ?>
											<button class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
													name="<?=$arResult['CONNECTOR'] ?>_del_page"
													type="submit"
													value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_DEL_REFERENCE') ?>">
												<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_DEL_REFERENCE') ?>
											</button>
										</form>
									</div>
								</div>
							</div>
						</div>
						<?
						if(count($arResult['FORM']['PAGES']) > 1)
						{
							?>
							<div class="imconnector-field-dropdown-button" id="toggle-list">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OTHER_PAGES') ?>
							</div>

							<div class="imconnector-field-box imconnector-field-social-list-modifier imconnector-field-box-hidden"
								 id="hidden-list">
								<div class="imconnector-field-main-title">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OTHER_PAGES') ?>
								</div>
								<div class="imconnector-field-social-list">
									<?
									foreach ($arResult['FORM']['PAGES'] as $page)
									{
										if(empty($page['ACTIVE']))
										{
											?>
											<div class="imconnector-field-social-list-item">
												<form action="<?=$arResult['URL']['SIMPLE_FORM_EDIT'] ?>" method="post">
													<div class="imconnector-field-social-list-inner">
														<div class="imconnector-field-social-icon imconnector-field-social-list-icon"></div>
														<div class="imconnector-field-social-list-info">
															<?if(empty($page['INFO']['URL'])):?>
															<span class="imconnector-field-social-name">
														<?else:?>
															<a href="<?=$page['INFO']['URL'] ?>"
															   target="_blank"
															   class="imconnector-field-social-name">
														<?endif;?>
														<?=htmlspecialcharsbx($page['INFO']['NAME']) ?>
														<?if(empty($page['INFO']['URL'])):?>
															</span>
														<?else:?>
															</a>
														<?endif;?>
															<span class="imconnector-ui-option-facebook">
														<label for="human_agent_<?=$cell?>">
															<input id="human_agent_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="human_agent" value="Y" checked>
															<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT')?></span>
														</label>
														</span>
															<div class="imconnector-field-social-card-human-agent-facebook">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
																	'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-facebook-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
																	'#END_HELP_DESC#' => '</strong>',
																])?>
															</div>
															<input type="hidden" name="<?=$arResult['CONNECTOR'] ?>_form"
																   value="true">
															<input type="hidden" name="page_id"
																   value="<?=htmlspecialcharsbx($page['INFO']['ID']) ?>">
															<?=bitrix_sessid_post(); ?>
															<button type="submit"
																	name="<?=$arResult['CONNECTOR'] ?>_authorization_page"
																	class="ui-btn ui-btn-sm ui-btn-light-border"
																	value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CHANGE_PAGE') ?>">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_CHANGE_PAGE') ?>
															</button>
														</div>
													</div>

												</form>
											</div>
											<?
										}
									}
									?>
									<div class="imconnector-field-box-subtitle">
										<?=$helpDeskLinkNoPage?>
									</div>
								</div>
							</div>
							<?
						}
						?>
					</div>
				</div>
				<?
			}
		}
	}
endif;
