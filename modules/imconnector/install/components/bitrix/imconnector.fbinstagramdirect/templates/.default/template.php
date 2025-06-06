<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
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
/** $arResult['CONNECTION_STATUS']; */
/** $arResult['REGISTER_STATUS']; */
/** $arResult['ERROR_STATUS']; */
/** $arResult['SAVE_STATUS']; */

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__ . '/meta.php');

const HELP_DESK_INFO_CONNECT_ID = '13406062';
const HELP_DESK_NO_SPECIFIC_PAGE = '10443976';
const HELP_DESK_CONVERT_TO_BUSINESS_HELP = '10443962';
const HELP_DESK_ACTIVATE_COMMENT = '14671362';
const HELP_DESK_HUMAN_AGENT = '14927782';

if($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	Extension::load([
		'ui.buttons',
		'ui.hint',
		'popup'
	]);
	Connector::initIconCss();
}

$helpDeskLinkStart =
	'<a href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
	. HELP_DESK_NO_SPECIFIC_PAGE
	. '");event.preventDefault();\'>';
$helpDeskLinkEnd = '</a>';

$helpDeskLinkNoPage = str_replace(
	['#A#', '#A_END#'],
	[$helpDeskLinkStart, $helpDeskLinkEnd],
	Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_SPECIFIC_PAGE')
);

$helpDeskLinkConvertToBusinessHelp =
	'<a class="imconnector-field-social-list-item-link" href="javascript:void(0)" onclick=\'top.BX.Helper.show("redirect=detail&code='
	. HELP_DESK_CONVERT_TO_BUSINESS_HELP
	. '");event.preventDefault();\'>'
	. Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONVERT_TO_BUSINESS_HELP')
	. '</a>';

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);

$langPostfix = $arResult['NEED_META_RESTRICTION_NOTE'] ? '_META_RU' : '';
$lang = [
	'index_title' => Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_TITLE' . $langPostfix),
	'index_subtitle' => Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_SUBTITLE' . $langPostfix),
	'index_additional_description' => Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_ADDITIONAL_DESCRIPTION' . $langPostfix),
];
if (!empty($arResult['NEED_META_RESTRICTION_NOTE']) && !$arResult['ACTIVE_STATUS'])
{
	$this->SetViewTarget('fb_meta_restriction_note');
	?>
	<div class="imconnector-restriction-note">
		<?= Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_RESTRICTIONS_META_RU_MSGVER_1')?>
	</div>
	<?php
	$this->EndViewTarget();
}
?>
	<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
		<?=bitrix_sessid_post()?>
	</form>
	<script>
		BX.message({
			'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_TITLE': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_TITLE')?>',
			'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_DESCRIPTION': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_DESCRIPTION')?>',
			'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_OK': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_OK')?>',
			'IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_CANCEL': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONFIRM_BUTTON_CANCEL')?>'
		});
	</script>
<?
if(
	empty($arResult['PAGE'])
	&& !empty($arResult['ACTIVE_STATUS'])
)
{
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
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED')?>
					</div>
					<div class="imconnector-field-box-content">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_ANY_TIME')?>
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
		<?include 'messages.php'?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<div class="imconnector-field-main-title">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INFO')?>
				</div>
				<div class="imconnector-field-box">
					<?
					if(!empty($arResult['FORM']['USER']['INFO']['URL']))
					{
						?>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_USER')?>
							</div>
							<a href="<?=$arResult['FORM']['USER']['INFO']['URL']?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=$arResult['FORM']['USER']['INFO']['NAME']?>
							</a>
							<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
								  data-text="<?=CUtil::JSEscape($arResult['FORM']['USER']['INFO']['URL'])?>"></span>
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
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED_PAGE')?>
							</div>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
							<span class="imconnector-field-box-entity-link">
							<?else:?>
							<a href="<?=$arResult['FORM']['PAGE']['INSTAGRAM']['URL']?>"
								target="_blank"
							   class="imconnector-field-box-entity-link">
							<?endif;?>
								<?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME']?> <?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT']?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
							</span>
							<?else:?>
							</a>
							<?endif;?>
						</div>
						<div class="imconnector-field-box-entity-row">
							<div class="imconnector-field-box-subtitle"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></div>

							<a href="<?=$arResult['FORM']['PAGE']['URL']?>"
							   target="_blank"
							   class="imconnector-field-box-entity-link">
								<?=$arResult['FORM']['PAGE']['NAME']?>
							</a>
							<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
								<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
									  data-text="<?=CUtil::JSEscape($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])?>"></span>
							<?endif;?>
						</div>
						<?
					}
					?>
				</div>
			</div>
		</div>
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
						<a href="<?=$arResult['URL']['SIMPLE_FORM_EDIT']?>"
						   class="ui-btn ui-btn-primary show-preloader-button">
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
		<?include 'messages.php'?>
		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
			</div>
		</div>
		<?
	}
}
else
{
	if(empty($arResult['FORM']['USER']['INFO'])) //start case with clear connections
	{
		if (!empty($arResult['ACTIVE_STATUS']) && $arResult['ACTIVE_STATUS'] === true) //case before auth to fb
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section imconnector-field-section-social">
					<div class="imconnector-field-box">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-main-subtitle">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TITLE_NEW')?>
						</div>
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DESCRIPTION_NEW')?>
						</div>
					</div>
				</div>
			</div>
			<?include 'messages.php'?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_AUTHORIZATION')?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_PAGE')?>
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
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_AUTHORIZE')?>
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
							<?= $lang['index_title']?>
						</div>
						<div class="imconnector-field-box-content">

							<div class="imconnector-field-box-content-text-light">
								<?= $lang['index_subtitle'] ?>
							</div>

							<ul class="imconnector-field-box-content-text-items">
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_LIST_ITEM_1') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_LIST_ITEM_2') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_LIST_ITEM_3') ?></li>
								<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_INDEX_LIST_ITEM_4') ?></li>
							</ul>

							<div class="imconnector-field-box-content-text-light">
								<?=$lang['index_additional_description']?>
							</div>

							<div class="imconnector-field-box-content-btn">
								<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" id="<?=$arResult['CONNECTOR']?>_form_active">
									<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
									<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
									<?=bitrix_sessid_post()?>
									<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
											type="submit"
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
			?>

		<div class="imconnector-field-container">
			<div class="imconnector-field-section">
				<?include 'connection-help.php';?>
			</div>
		</div>
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
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED')?>
					</div>
					<div class="imconnector-field-social-card">
						<div class="imconnector-field-social-card-info">
							<div class="imconnector-field-social-icon"></div>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
								<span class="imconnector-field-social-name">
							<?else:?>
								<a href="<?=$arResult['FORM']['USER']['INFO']['URL']?>"
									target="_blank"
									 class="imconnector-field-social-name">
							<?endif;?>
								<?=$arResult['FORM']['USER']['INFO']['NAME']?>
							<?if(empty($arResult['FORM']['USER']['INFO']['URL'])):?>
								</span>
							<?else:?>
								</a>
							<?endif;?>
						</div>
						<div class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
							 onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?include 'messages.php'?>
		<?
		if(empty($arResult['FORM']['PAGES']))  //case user haven't got any groups.
		{
			?>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<div class="imconnector-field-main-title">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECT_FACEBOOK_PAGE')?>
					</div>
					<div class="imconnector-field-box">
						<div class="imconnector-field-box-content">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_THERE_IS_NO_PAGE_WHERE_THE_ADMINISTRATOR')?>
						</div>
					</div>
					<div class="imconnector-field-social-connector">
						<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-connector-icon"><i></i></div>
						<a href="https://www.facebook.com/pages/create/"
						   class="ui-btn ui-btn-light-border"
						   target="_blank">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CREATE_A_PAGE')?>
						</a>
					</div>
				</div>
			</div>
			<div class="imconnector-field-container">
				<div class="imconnector-field-section">
					<?include 'connection-help.php';?>
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
					<div class="imconnector-field-section imconnector-field-section-social-list imconnector-field-section-social-list-fbinstagram">
						<div class="imconnector-field-main-title">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_SELECT_THE_PAGE')?>
						</div>
						<div class="imconnector-field-social-list">
							<?
							foreach ($arResult['FORM']['PAGES'] as $cell => $page)
							{
							if (empty($page['ACTIVE']))
							{
							?>
							<div class="imconnector-field-social-list-item">
								<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post">
									<div class="imconnector-field-social-list-inner">
										<div class="imconnector-field-social-icon imconnector-field-social-list-icon"<?if(!empty($page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> style='background: url("<?=$page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL']?>"); background-size: cover'<?endif;?>></div>
										<div class="imconnector-field-social-list-info">
											<div class="imconnector-field-social-list-info-inner">
												<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
												<span class="imconnector-field-social-name">
													<?else:?>
													<a href="<?=$page['INFO']['INSTAGRAM']['URL']?>"
													   target="_blank"
													   class="imconnector-field-social-name">
													<?endif;?>
														<?=$page['INFO']['INSTAGRAM']['NAME']?> <?if(!empty($page['INFO']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$page['INFO']['INSTAGRAM']['MEDIA_COUNT'];?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
														<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
														</span>
											<?else:?>
												</a>
											<?endif;?>

												<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

												<?if(empty($page['INFO']['URL'])):?>
												<span class="imconnector-field-social-name">
													<?else:?>
														<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
														<a href="<?=$page['INFO']['URL']?>"
														   target="_blank"
														   class="imconnector-field-social-name imconnector-field-social-name-url">
													<?endif;?>
													<?=$page['INFO']['NAME']?>
													<?if(empty($page['INFO']['URL'])):?>
														</span>
											<?else:?>
												</a>
											<?endif;?>
												<?php
												if($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
												{?>
													<span class="imconnector-field-social-account imconnector-field-social-account-instagram-direct imconnector-field-social-account-business">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
												</span>
													<?php
												}
												else
												{?>
													<span class="imconnector-field-social-account imconnector-field-social-account-instagram-direct">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PERSONAL_ACCOUNT')?>
												</span>
													<?php
												}
												?>
											</div>
											<?if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N'):?>

												<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
													<label for="comment_<?=$cell?>">
														<input id="comment_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="comments" <?if(empty($arResult['FORM']['USER']['IS_USER_PERMISSION_COMMENTS'])):?>disabled<?else:?>value="Y" <?if(!empty($page['DISCONNECT'])):?> onclick="popupCommentShow('comment_<?=$cell?>');"<?else:?> checked<?endif;?><?endif;?>>
														<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_DIRECT_AND_BUSINESS')?></span>
													</label><span class="imconnector-ui-hint-icon-instagram-direct" onclick="top.BX.Helper.show('redirect=detail&code=<?=HELP_DESK_ACTIVATE_COMMENT?>');"></span>
												</div>
												<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
													<label for="human_agent_<?=$cell?>">
														<input id="human_agent_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="human_agent" value="Y" checked>
														<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT')?></span>
													</label>
												</div>
												<div class="imconnector-field-social-card-human-agent-instagram-direct">
													<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
														'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-instagram-direct-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
														'#END_HELP_DESC#' => '</strong>',
													])?>
												</div>
												<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
													<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
													<input type="hidden" name="page_id" value="<?=$page['INFO']['ID']?>">
													<?=bitrix_sessid_post();?>
													<button type="submit"
															name="<?=$arResult['CONNECTOR']?>_authorization_page"
															class="ui-btn ui-btn-sm ui-btn-light-border"
															value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
														<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
													</button>
												</div>
											<?else:?>
												<div class="imconnector-field-social-list-info-inner">
													<?=$helpDeskLinkConvertToBusinessHelp?>
												</div>
											<?endif;?>
								</form>
							</div>
						</div>
					</div>
					<?php
					}
					}
					?>
					<div class="imconnector-field-box-subtitle">
						<?=$helpDeskLinkNoPage?>
					</div>
				</div>
				</div>
				</div>
				<div class="imconnector-field-container">
					<div class="imconnector-field-section">
						<?include 'connection-help.php';?>
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
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CONNECTED_PAGE')?>
						</div>

						<div class="imconnector-field-social-card">
							<div class="imconnector-field-social-card-info-instagram-direct">
								<div<?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> class="imconnector-field-social-icon imconnector-field-social-list-icon" style='background: url("<?=$arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL']?>"); background-size: cover'<?else:?> class="connector-icon ui-icon ui-icon-service-<?=$iconCode?> imconnector-field-social-icon"<?endif;?>><i></i></div>

								<div class="imconnector-field-social-list-info">
									<div class="imconnector-field-social-list-info-inner">
										<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
										<span class="imconnector-field-social-name">
										<?else:?>
										<a href="<?=$arResult['FORM']['PAGE']['INSTAGRAM']['URL']?>"
											target="_blank"
											class="imconnector-field-social-name">
										<?endif;?>
										<?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME']?> <?if(!empty($arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$arResult['FORM']['PAGE']['INSTAGRAM']['MEDIA_COUNT'];?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
										<?if(empty($arResult['FORM']['PAGE']['INSTAGRAM']['URL'])):?>
										</span>
										<?else:?>
										</a>
										<?endif;?>

										<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										<span class="imconnector-field-social-name">
										<?else:?>
										<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
										<a href="<?=$arResult['FORM']['PAGE']['URL']?>"
											target="_blank"
											class="imconnector-field-social-name">
										<?endif;?>
										<?=$arResult['FORM']['PAGE']['NAME']?>
										<?if(empty($arResult['FORM']['PAGE']['URL'])):?>
										</span>
										<?else:?>
										</a>
										<?endif;?>
										<span class="imconnector-field-social-account imconnector-field-social-account-instagram-direct imconnector-field-social-account-business">
										<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
										</span>
									</div>

									<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
										<?if ($arResult['FORM']['PAGE']['IS_ACTIVE_INSTAGRAM_COMMENTS'] === true):?>
											<span class="imconnector-indicator-comments-instagram-direct"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_ACTIVE_COMMENTS')?>
										<?else:?>
											<span class="imconnector-indicator-no-comments-instagram-direct"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_NO_ACTIVE_COMMENTS')?>
										<?endif;?>
										<span class="imconnector-ui-hint-icon-instagram-direct" onclick="top.BX.Helper.show('redirect=detail&code=<?=HELP_DESK_ACTIVATE_COMMENT?>');"></span>
									</div>
									<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
										<?if ($arResult['DATA_STATUS']['HUMAN_AGENT'] === true):?>
											<span class="imconnector-indicator-comments-instagram-direct"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT_ON')?>
										<?else:?>
											<span class="imconnector-indicator-no-comments-instagram-direct"></span><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT_OFF')?>
										<?endif;?>
									</div>
									<div class="imconnector-field-social-card-human-agent-instagram-direct imconnector-field-social-card-human-agent-instagram-direct-invert">
										<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
											'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-instagram-direct-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
											'#END_HELP_DESC#' => '</strong>',
										])?>
									</div>
									<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
										<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post">
											<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
											<input type="hidden" name="page_id" value="<?=$arResult['FORM']['PAGE']['ID']?>">
											<?=bitrix_sessid_post()?>
											<button class="ui-btn ui-btn-sm ui-btn-light-border imconnector-field-social-card-button"
													name="<?=$arResult['CONNECTOR']?>_del_page"
													type="submit"
													value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DEL_REFERENCE')?>">
												<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_DEL_REFERENCE')?>
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
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OTHER_PAGES')?>
							</div>

							<div class="imconnector-field-box imconnector-field-social-list-modifier imconnector-field-box-hidden"
								 id="hidden-list">
								<div class="imconnector-field-main-title">
									<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_OTHER_PAGES')?>
								</div>
								<div class="imconnector-field-social-list">
									<?
									foreach ($arResult['FORM']['PAGES'] as $cell=>$page)
									{
										if(empty($page['ACTIVE']))
										{
											?>
											<div class="imconnector-field-social-list-item">
												<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post">
												<div class="imconnector-field-social-list-inner">
													<div class="imconnector-field-social-icon imconnector-field-social-list-icon"<?if(!empty($page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL'])):?> style='background: url("<?=$page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL']?>"); background-size: cover'<?endif;?>></div>

													<div class="imconnector-field-social-list-info">
														<div class="imconnector-field-social-list-info-inner">
															<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
															<span class="imconnector-field-social-name">
															<?else:?>
															<a href="<?=$page['INFO']['INSTAGRAM']['URL']?>"
																target="_blank"
																class="imconnector-field-social-name">
															<?endif;?>
															<?=$page['INFO']['INSTAGRAM']['NAME']?> <?if(!empty($page['INFO']['INSTAGRAM']['MEDIA_COUNT'])):?> (<?=$page['INFO']['INSTAGRAM']['MEDIA_COUNT']?> <?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_MEDIA')?>)<?endif;?>
															<?if(empty($page['INFO']['INSTAGRAM']['URL'])):?>
															</span>
															<?else:?>
															</a>
															<?endif;?>

															<span class="imconnector-field-social-name imconnector-field-social-name-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PREFIX_NAMING_PAGE')?></span>

															<?if(empty($page['INFO']['URL'])):?>
															<span class="imconnector-field-social-name">
															<?else:?>
															<div class="imconnector-field-social-name-icon ui-icon ui-icon-service-instagram"><i></i></div>
															<a href="<?=$page['INFO']['URL']?>"
																target="_blank"
																class="imconnector-field-social-name">
															<?endif;?>
															<?=$page['INFO']['NAME']?>
															<?if(empty($page['INFO']['URL'])):?>
															</span>
															<?else:?>
															</a>
															<?endif;?>
															<?php
															if($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N')
															{?>
																<span class="imconnector-field-social-account imconnector-field-social-account-instagram-direct imconnector-field-social-account-business">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_BUSINESS_ACCOUNT')?>
															</span>
																<?php
															}
															else
															{?>
																<span class="imconnector-field-social-account imconnector-field-social-account-instagram-direct">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_PERSONAL_ACCOUNT')?>
															</span>
																<?php
															}
															?>
														</div>
														<?if ($page['INFO']['INSTAGRAM']['BUSINESS'] !== 'N'):?>

															<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
																<label for="comment_<?=$cell?>">
																	<input id="comment_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="comments" <?if(empty($arResult['FORM']['USER']['IS_USER_PERMISSION_COMMENTS'])):?>disabled<?else:?>value="Y" checked<?endif;?>>
																	<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_DIRECT_AND_BUSINESS')?></span>
																</label><span class="imconnector-ui-hint-icon-instagram-direct" onclick="top.BX.Helper.show('redirect=detail&code=<?=HELP_DESK_ACTIVATE_COMMENT?>');"></span>
															</div>
															<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
																<label for="human_agent_<?=$cell?>">
																	<input id="human_agent_<?=$cell?>" class="imconnector-public-link-settings-inner-option-field" type="checkbox" name="human_agent" value="Y" checked>
																	<span class="imconnector-public-link-settings-inner-option-text"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT')?></span>
																</label>
															</div>
															<div class="imconnector-field-social-card-human-agent-instagram-direct">
																<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_TO_CONNECT_HUMAN_AGENT_DESCRIPTION', [
																	'#START_HELP_DESC#' => '<strong class="imconnector-field-social-card-human-agent-instagram-direct-link" onclick="top.BX.Helper.show(\'redirect=detail&code=' . HELP_DESK_HUMAN_AGENT . '\');">',
																	'#END_HELP_DESC#' => '</strong>',
																])?>
															</div>
															<div class="imconnector-field-social-list-info-inner imconnector-public-link-settings-inner-option">
																<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
																<input type="hidden" name="page_id" value="<?=$page['INFO']['ID']?>">
																<?=bitrix_sessid_post();?>
																<button type="submit"
																		name="<?=$arResult['CONNECTOR']?>_authorization_page"
																		class="ui-btn ui-btn-sm ui-btn-light-border"
																		value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_PAGE')?>">
																	<?=Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAMDIRECT_CHANGE_PAGE')?>
																</button>
															</div>
														<?else:?>
															<div class="imconnector-field-social-list-info-inner">
																<?=$helpDeskLinkConvertToBusinessHelp?>
															</div>
														<?endif;?>
													</div>
												</div>

												</form>
											</div>
											<?php
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
}
