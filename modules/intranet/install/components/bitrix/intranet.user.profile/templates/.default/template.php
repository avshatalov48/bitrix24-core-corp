<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page;

$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');
\Bitrix\Main\UI\Extension::load(array("ui.buttons", "ui.alerts", "ui.tooltip", "ui.hint"));
\CJSCore::Init("loader");

Page\Asset::getInstance()->addJs($templateFolder.'/js/utils.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/stresslevel.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/grats.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/profilepost.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/tags.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/tags-users-popup.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/form-entity.js');
Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');

\Bitrix\Main\UI\Extension::load("ui.icons.b24");

if (!$arResult["Permissions"]['view'])
{
	?><div class="ui-alert ui-alert-danger">
		<span class="ui-alert-message"><?=Loc::getMessage('INTRANET_USER_PROFILE_VIEW_ACCESS_DENIED')?></span>
	</div><?
	return;
}

$this->SetViewTarget('inside_pagetitle');
$APPLICATION->includeComponent(
	'bitrix:intranet.binding.menu',
	'',
	array(
		'SECTION_CODE' => 'user_detail',
		'MENU_CODE' => 'top_menu',
		'CONTEXT' => [
			'USER_ID' => $arResult['User']['ID']
		]
	)
);
$this->EndViewTarget();

if (
	isset($arResult["Urls"]["CommonSecurity"])
	&& $arResult["User"]["STATUS"] !== "email"
)
{
	$this->SetViewTarget('inside_pagetitle');
	?>

	<?if ($arResult["Permissions"]['edit']):?>
		<span
			onclick="BX.SidePanel.Instance.open('<?=$arResult["Urls"]["CommonSecurity"]."?page=auth"?>', {width: 1100});"
			class="ui-btn ui-btn-light-border ui-btn-themes"
		>
			<?=Loc::getMessage("INTRANET_USER_PROFILE_PASSWORDS")?>
		</span>
	<?endif?>

	<?if (
		$arResult["OTP_IS_ENABLED"] == "Y"
		&& ($arResult["IsOwnProfile"] || $USER->CanDoOperation('security_edit_user_otp'))
	):?>
		<span
			onclick="BX.SidePanel.Instance.open('<?=$arResult["Urls"]["CommonSecurity"]."?page=security"?>', {width: 1100});"
			class="ui-btn ui-btn-light-border ui-btn-themes"
		>
			<?=Loc::getMessage("INTRANET_USER_PROFILE_SECURITY")?>
		</span>
	<?endif?>
	<?
	$this->EndViewTarget();
}
?>

<?
if (
	$arResult["IsOwnProfile"]
	&& file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.admin.set")
	&& ($arResult["User"]["SHOW_SONET_ADMIN"])
)
{
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.admin.set",
		"",
		Array(
			"PROCESS_ONLY" => "Y"
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);
}
?>

<div class="intranet-user-profile" id="intranet-user-profile-wrap">
	<div class="intranet-user-profile-column-left">
		<div class="intranet-user-profile-column-block">
			<div class="intranet-user-profile-rank">
				<?if (
					isset($arResult["User"]["STATUS"]) && !empty($arResult["User"]["STATUS"])
					&& (
						!(
							$arResult["User"]["STATUS"] === "employee"
							&& ($arResult["IsOwnProfile"] || !$arResult["Permissions"]['edit'])
						)
						|| $arResult["User"]["SHOW_SONET_ADMIN"]
					)
				):?>
					<div class="intranet-user-profile-rank-item intranet-user-profile-rank-<?=$arResult["User"]["STATUS"]?> <?if ($arResult["Permissions"]['edit']):?>intranet-user-profile-rank-item-pointer<?endif?>" data-role="user-profile-actions-button">
						<span><?=ToUpper(Loc::getMessage("INTRANET_USER_PROFILE_".$arResult["User"]["STATUS"]))?></span>
						<?if ($arResult["Permissions"]['edit']):?>
						<span class="intranet-user-profile-rank-item-config"></span>
						<?endif?>
					</div>
				<?endif?>
			</div>

			<div class="intranet-user-profile-status-info">
				<div class="intranet-user-profile-status intranet-user-profile-status-<?=$arResult["User"]["ONLINE_STATUS"]["STATUS"]?>">
					<?=ToUpper($arResult["User"]["ONLINE_STATUS"]["STATUS_TEXT"])?>
				</div>
				<div class="intranet-user-profile-last-time">
					<?if ($arResult["User"]["ONLINE_STATUS"]['STATUS'] == 'idle'):?>
						<?echo ($arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'] ? Loc::getMessage('INTRANET_USER_PROFILE_LAST_SEEN_IDLE_'.($arResult["User"]["PERSONAL_GENDER"] == 'F'? 'F': 'M'), Array('#LAST_SEEN#' => $arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'])): '');?>
					<?else:?>
						<?echo ($arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'] ? Loc::getMessage('INTRANET_USER_PROFILE_LAST_SEEN_'.($arResult["User"]["PERSONAL_GENDER"] == 'F'? 'F': 'M'), Array('#LAST_SEEN#' => $arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'])): '');?>
					<?endif; ?>
				</div>
			</div>

			<div class="intranet-user-profile-userpic ui-icon ui-icon-common-user
				<?if ($arResult["IsOwnProfile"] || $arResult["Permissions"]['edit']):?>
					intranet-user-profile-userpic-edit
				<?endif?>"
			>
				<i
					id="intranet-user-profile-photo"
					<?if (isset($arResult["User"]["PHOTO"]) && !empty($arResult["User"]["PHOTO"])):?>
						style="background-image: url('<?=\CHTTP::urnEncode($arResult["User"]["PHOTO"])?>'); background-size: cover"
					<?endif?>
				></i>
				<?if ($arResult["IsOwnProfile"] || $arResult["Permissions"]['edit']):?>
				<div class="intranet-user-profile-userpic-load">
					<div class="intranet-user-profile-userpic-create" id="intranet-user-profile-photo-camera"><?=Loc::getMessage("INTRANET_USER_PROFILE_AVATAR_CAMERA")?></div>
					<div class="intranet-user-profile-userpic-upload" id="intranet-user-profile-photo-file"><?=Loc::getMessage("INTRANET_USER_PROFILE_AVATAR_LOAD")?></div>
				</div>
				<div class="intranet-user-profile-userpic-remove" id="intranet-user-profile-photo-remove"></div>
				<?endif?>
			</div>

			<?
			if (!$arResult["IsOwnProfile"] && $arResult["User"]["STATUS"] !== "email")
			{
				?>
				<div class="intranet-user-profile-actions">
					<?if ($arResult["User"]["ACTIVE"] == "N"):?>
						<a
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
							href="javascript:void(0)"
							onclick="if (top.BXIM) { top.BXIM.openHistory(<?=$arResult["User"]['ID']?>); }"
						>
							<?=Loc::getMessage("INTRANET_USER_PROFILE_CHAT_HISTORY")?>
						</a>
					<?else:?>
						<a
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
							href="javascript:void(0)"
							onclick="if (top.BXIM) { top.BXIM.openMessenger(<?=$arResult["User"]['ID']?>); }"
						>
							<?=Loc::getMessage("INTRANET_USER_PROFILE_CHAT")?>
						</a>
						<a
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
							href="javascript:void(0)"
							onclick="if (top.BXIM) { top.BXIM.callTo(<?=$arResult["User"]['ID']?>); }"
						>
							<?=Loc::getMessage("INTRANET_USER_PROFILE_VIDEOCALL")?>
						</a>
					<?endif?>
				</div>
			<?
			}
			?>
			<?
			$APPLICATION->IncludeComponent(
				"bitrix:intranet.absence.user",
				"profile",
				array(
					"ID" => $arResult["User"]['ID'],
				),
				false,
				array("HIDE_ICONS"=>"Y")
			);
			?>
		</div>
		<?

		if (
			$arResult["StressLevel"]['AVAILABLE'] == 'Y'
			&& !in_array($arResult["User"]["STATUS"], ['email', 'extranet'])
			&& !$arResult["isExtranetSite"]
		)
		{
			?>
			<div class="intranet-user-profile-column-block intranet-user-profile-column-block-inline" id="intranet-user-profile-stresslevel-noresult" style="display: none;">
				<div id="intranet-user-profile-stresslevel-noresult-widget" class="intranet-user-profile-stresslevel-widget"></div>
				<div class="intranet-user-profile-stresslevel-info intranet-user-profile-stresslevel-info-withoiut-padding">
					<div class="intranet-user-profile-stresslevel-status-text"><?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_TITLE')?></div>
					<span id="intranet-user-profile-stresslevel-check" class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON')?></span>
				</div>
			</div>
			<div class="intranet-user-profile-column-block intranet-user-profile-column-block-inline" id="intranet-user-profile-stresslevel-result" style="display: none;">
				<div id="intranet-user-profile-stresslevel-result-perms-close" class="intranet-user-profile-stresslevel-invisible" data-hint="<?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_HINT_VISIBLE')?>" data-hint-no-icon style="display: none;"></div>
				<div id="intranet-user-profile-stresslevel-result-perms-open" class="intranet-user-profile-stresslevel-visible" data-hint="<?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_HINT_INVISIBLE')?>" data-hint-no-icon style="display: none;"></div>
				<div id="intranet-user-profile-stresslevel-widget" class="intranet-user-profile-stresslevel-widget"></div>
				<div class="intranet-user-profile-stresslevel-info">
					<div class="intranet-user-profile-stresslevel-info-block">
						<?
						foreach($arResult["StressLevel"]["TYPES_LIST"] as $type => $description)
						{
							?>
							<div id="intranet-user-profile-stresslevel-status-<?=htmlspecialcharsbx($type)?>" class="intranet-user-profile-stresslevel-status intranet-user-profile-stresslevel-status-<?=htmlspecialcharsbx($type)?>" style="display: none;"></div>
							<?
						}
						?>
						<div id="intranet-user-profile-stresslevel-status-info" class="intranet-user-profile-stresslevel-status-info" data-hint="" data-hint-no-icon></div>
					</div>
					<div id="intranet-user-profile-stresslevel-comment" class="intranet-user-profile-stresslevel-status-text"></div>
					<?
					if (
						$USER->getId() == $arResult["User"]["ID"]
						&& $arResult["StressLevel"]["IMAGE_SUPPORT"] == 'Y'
					)
					{
						?>
						<div id="intranet-user-profile-stresslevel-status-copy" class="intranet-user-profile-stresslevel-status-copy"><?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_SHARE_LINK')?></div>
						<?
					}
					?>
				</div>
			</div>
			<?
		}

		if ($arResult["IsOwnProfile"] && $arResult["User"]["STATUS"] !== "email")
		{
		?>
		<div class="intranet-user-profile-column-block">
			<div class="intranet-user-profile-apps">
				<div class="intranet-user-profile-apps-item">
					<?/*
						.intranet-user-profile-apps-icon-active
						.intranet-user-profile-apps-icon-complete
						.intranet-user-profile-apps-icon-download
					*/
					?>
					<div class="intranet-user-profile-apps-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_MOBILE_APP")?></div>
					<span data-role="profile-android-app"
						class="intranet-user-profile-apps-icon  intranet-user-profile-apps-icon-android
						<?if ($arResult["User"]["APP_ANDROID_INSTALLED"]):?>
							intranet-user-profile-apps-icon-active
						<?else:?>
							intranet-user-profile-apps-icon-download
						<?endif?>"
					></span>
					<span data-role="profile-ios-app"
						class="intranet-user-profile-apps-icon intranet-user-profile-apps-icon-appstore
						<?if ($arResult["User"]["APP_IOS_INSTALLED"]):?>
							intranet-user-profile-apps-icon-active
						<?else:?>
							intranet-user-profile-apps-icon-download
						<?endif?>"
					></span>
				</div>
				<div class="intranet-user-profile-apps-item">
					<div class="intranet-user-profile-apps-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_DESKTOP_APP")?></div>
					<a href="https://dl.bitrix24.com/b24/bitrix24_desktop.exe" target="_blank"
						class="intranet-user-profile-apps-icon intranet-user-profile-apps-icon-windows
						<?if ($arResult["User"]["APP_WINDOWS_INSTALLED"]):?>
							intranet-user-profile-apps-icon-active
						<?else:?>
							intranet-user-profile-apps-icon-download
						<?endif?>"
					></a>
					<a href="https://dl.bitrix24.com/b24/bitrix24_desktop.dmg" target="_blank"
						class="intranet-user-profile-apps-icon intranet-user-profile-apps-icon-iphone
						<?if ($arResult["User"]["APP_MAC_INSTALLED"]):?>
							intranet-user-profile-apps-icon-active
						<?else:?>
							intranet-user-profile-apps-icon-download
						<?endif?>"
					></a>
				</div>
			</div>
		</div>
		<?
		}

		if (!empty($arResult["DISK_INFO"]))
		{
		?>
		<div class="intranet-user-profile-column-block">
			<div class="intranet-user-profile-apps">
				<div class="intranet-user-profile-desktop-block">
					<?=Loc::getMessage("INTRANET_USER_PROFILE_DISK_INSTALLED")?>
					<?
					if (
						isset($arResult["DISK_INFO"]["INSTALLATION_DATE"])
						&& !empty(($arResult["DISK_INFO"]["INSTALLATION_DATE"]))
					)
					{
						echo $arResult["DISK_INFO"]["INSTALLATION_DATE"];
					}

					if (
						isset($arResult["DISK_INFO"]["SPACE"])
						&& !empty(($arResult["DISK_INFO"]["SPACE"]))
					)
					{
						echo ", ".Loc::getMessage("INTRANET_USER_PROFILE_DISK_SPACE", array("#VALUE#" => $arResult["DISK_INFO"]["SPACE"]));
					}
					?>
				</div>
			</div>
		</div>
		<?
		}

		if (
			!empty($arResult["Gratitudes"])
			&& !in_array($arResult["User"]["STATUS"], ['email', 'extranet'])
			&& !$arResult["isExtranetSite"]
		)
		{
			?><div class="intranet-user-profile-column-block">
				<div class="intranet-user-profile-column-block-title">
					<span class="intranet-user-profile-column-block-title-text"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_TITLE')?></span><?
					if (
						!empty($arResult["Gratitudes"]['URL_ADD'])
						&& $USER->getId() != $arResult["User"]["ID"]
					)
					{
						?><div  onclick="BX.SidePanel.Instance.open('<?=$arResult['Gratitudes']['URL_ADD']?>', {
							cacheable: false,
							data: {
								entityType: 'gratPost',
								entityId: '<?=intval($arResult["User"]["ID"])?>'
							},
							width: 1000
						}); return event.preventDefault();" class="intranet-user-profile-column-block-title-like" data-role="intranet-user-profile-column-block-title-like"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_ADD')?></div><?
					}
				?></div>

				<div id="intranet-user-profile-thanks" class="intranet-user-profile-thanks" data-bx-grat-url="<?=htmlspecialcharsbx($arResult['Gratitudes']['URL_LIST'])?>"><?
					foreach($arResult['Gratitudes']['BADGES'] as $badge)
					{
						?><div class="intranet-user-profile-thanks-item intranet-user-profile-thanks-item-<?=htmlspecialcharsbx($badge['CODE'])?>" title="<?=htmlspecialcharsbx($badge['NAME'])?>" data-bx-grat-code="<?=htmlspecialcharsbx($badge['CODE'])?>" data-bx-grat-enum="<?=intval($badge['ID'])?>"></div><?
					}
				?></div>

				<div class="intranet-user-profile-thanks-users">
					<div class="intranet-user-profile-thanks-users-wrapper" id="intranet-user-profile-thanks-users-wrapper"></div>
					<div class="intranet-user-profile-thanks-users-loader" id="intranet-user-profile-thanks-users-loader"></div>
					<div class="intranet-user-profile-load-users-link" style="display: none;" id="intranet-user-profile-load-users-link"><?=Loc::getMessage('INTRANET_USER_PROFILE_MORE', array('#NUM#' => $arParams['GRAT_POST_LIST_PAGE_SIZE']))?></div>
				</div><?

				if (
					!empty($arResult["Gratitudes"]['URL_ADD'])
					&& $USER->getId() != $arResult["User"]["ID"]
				)
				{
					?><div class="intranet-user-profile-thanks-info">
						<a href="javascript:void(0);" onclick="BX.SidePanel.Instance.open('<?=$arResult['Gratitudes']['URL_ADD']?>', {
							cacheable: false,
							data: {
								entityType: 'gratPost',
								entityId: '<?=intval($arResult["User"]["ID"])?>'
							},
							width: 1000
						}); return event.preventDefault();" class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_ADD')?></a>
					</div><?
				}
			?></div><?
		}
	?></div>
	<div class="intranet-user-profile-column-right">
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:ui.form",
			"",
			array(
				"GUID" => $arResult["FormId"],
				"INITIAL_MODE" => "view",
				"ENTITY_TYPE_NAME" => "USER",
				"ENTITY_ID" => $arResult["User"]["ID"],
				"ENTITY_FIELDS" => $arResult["FormFields"],
				"ENTITY_CONFIG" => $arResult["FormConfig"],
				"ENTITY_DATA" => $arResult["FormData"],
				"ENABLE_SECTION_EDIT" => false,
				"ENABLE_SECTION_CREATION" => false,
				"ENABLE_SECTION_DRAG_DROP" => false,
				"FORCE_DEFAULT_SECTION_NAME" => true,
				"ENABLE_PERSONAL_CONFIGURATION_UPDATE" => $arResult["EnablePersonalConfigurationUpdate"],
				"ENABLE_COMMON_CONFIGURATION_UPDATE" => $arResult["EnableCommonConfigurationUpdate"],
				"ENABLE_SETTINGS_FOR_ALL" => $arResult["EnableSettingsForAll"],
				"READ_ONLY" => $arResult["Permissions"]['edit'] ? false : true,
				"ENABLE_USER_FIELD_CREATION" => $arResult["EnableUserFieldCreation"],
				"ENABLE_USER_FIELD_MANDATORY_CONTROL" => $arResult["EnableUserFieldMandatoryControl"],
				"USER_FIELD_ENTITY_ID" => $arResult["UserFieldEntityId"],
				"USER_FIELD_PREFIX" => $arResult["UserFieldPrefix"],
				"USER_FIELD_CREATE_SIGNATURE" => $arResult["UserFieldCreateSignature"],
				"SERVICE_URL" => POST_FORM_ACTION_URI.'&'.bitrix_sessid_get(),
				"COMPONENT_AJAX_DATA" => array(
					"COMPONENT_NAME" => $this->getComponent()->getName(),
					"ACTION_NAME" => "save",
					"SIGNED_PARAMETERS" => $this->getComponent()->getSignedParameters()
				)
			)
		);
		?>

		<?if (!empty($arResult["User"]["SUBORDINATE"]) || !empty($arResult["User"]["MANAGERS"]))
		{
		?>
			<div class="intranet-user-profile-container">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_ADDITIONAL_INFO")?></div>
				</div>

				<?
				if (!empty($arResult["User"]["SUBORDINATE"]))
				{
				?>
					<div class="intranet-user-profile-container-body">
						<div class="intranet-user-profile-container-body-title">
							<?=Loc::getMessage("INTRANET_USER_PROFILE_FIELD_SUBORDINATE")?>
						</div>
						<div class="intranet-user-profile-grid">
							<?
							$i = 0;
							foreach ($arResult["User"]["SUBORDINATE"] as $key => $subUser)
							{
								$i++;
							?>
								<a class="intranet-user-profile-grid-item" href="<?=$subUser["LINK"]?>"
									<?if ($i > 4):?>style="display: none" data-role="user-profile-item"<?endif?>
								>
									<div
										class="ui-icon ui-icon-common-user intranet-user-profile-user-avatar">
										<i <?if (isset($subUser["PHOTO"]) && !empty($subUser["PHOTO"])): ?>
											style="background-image: url('<?=\CHTTP::urnEncode($subUser["PHOTO"])?>');"
										<?endif?>></i>
									</div>
									<div class="intranet-user-profile-user-container">
										<div class="intranet-user-profile-user-name"><?=$subUser["FULL_NAME"]?></div>
										<div class="intranet-user-profile-user-position"><?=$subUser["WORK_POSITION"]?></div>
									</div>
								</a>
							<?
							}
							?>
						</div>
						<?if ($i > 4):?>
							<div class="intranet-user-profile-grid-loadmore" id="intranet-user-profile-subordinate-more">
								<?=Loc::getMessage("INTRANET_USER_PROFILE_MORE", array("#NUM#" => "(<span>".($i-4)."</span>)"))?>
							</div>
						<?endif?>
					</div>
				<?
				}
				if (!empty($arResult["User"]["MANAGERS"]))
				{
				?>
					<div class="intranet-user-profile-container-body">
						<div class="intranet-user-profile-container-body-title">
							<?=Loc::getMessage("INTRANET_USER_PROFILE_FIELD_MANAGERS")?>
						</div>
						<div class="intranet-user-profile-grid">
							<?
							$i = 0;
							foreach ($arResult["User"]["MANAGERS"] as $manager)
							{
								$i++;
							?>
								<a class="intranet-user-profile-grid-item" href="<?=$manager["LINK"]?>">
									<div href="<?=$manager["LINK"]?>"
										class="ui-icon ui-icon-common-user intranet-user-profile-user-avatar">
										<i <?if (isset($manager["PHOTO"]) && !empty($manager["PHOTO"])):?>
											style="background-image: url('<?=\CHTTP::urnEncode($manager["PHOTO"])?>');"
										<?endif?>></i>
									</div>
									<div class="intranet-user-profile-user-container">
										<div class="intranet-user-profile-user-name"><?=$manager["FULL_NAME"]?></div>
										<div class="intranet-user-profile-user-position"><?=$manager["WORK_POSITION"]?></div>
									</div>
								</a>
							<?
							}
							?>
						</div>
						<?if ($i > 4):?>
							<div class="intranet-user-profile-grid-loadmore" id="intranet-user-profile-manages-more">
								<?=Loc::getMessage("INTRANET_USER_PROFILE_MORE", array("#NUM#" => "(<span>".($i-4)."</span>)"))?>
							</div>
						<?endif?>
					</div>
				<?
				}
				?>
			</div>
		<?
		}

		if (
			!empty($arResult["ProfileBlogPost"])
			&& (
				!empty($arResult["ProfileBlogPost"]["POST_ID"])
				|| $arResult["Permissions"]['edit']
				|| $USER->getId() == $arResult["User"]["ID"]
			)
			&& $arResult["User"]["STATUS"] !== "email"
		)
		{
			?><div id="intranet-user-profile-post-container" class="intranet-user-profile-container<?=(empty($arResult["ProfileBlogPost"]["POST_ID"]) ? ' intranet-user-profile-container-empty' : '')?>">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_BLOG_POST_TITLE")?></div><?

					if (
						$arResult["Permissions"]['edit']
						|| $USER->getId() == $arResult["User"]["ID"]
					)
					{
						?><a id="intranet-user-profile-post-edit-link" class="intranet-user-profile-container-edit"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_MODIFY');?></a><?
					}

				?></div>
				<div class="intranet-user-profile-container-body intranet-user-profile-about-wrapper">
					<div id="intranet-user-profile-about-wrapper"></div>

					<div id="intranet-user-profile-post-edit-stub" class="intranet-user-profile-about intranet-user-profile-empty-stub intranet-user-profile-about-profile">
						<div class="intranet-user-profile-post-edit-stub-default"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_STUB_TEXT')?></div>
						<a id="intranet-user-profile-post-edit-stub-button" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_STUB_BUTTON')?></a>
					</div><?

					if (
						(
							$arResult["Permissions"]['edit']
							|| $USER->getId() == $arResult["User"]["ID"]
						)
					)
					{
						?><div id="intranet-user-profile-about-form-wrapper" style="display: block;">
							<form action="" id="<?=htmlspecialcharsbx($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"])?>" name="<?=htmlspecialcharsbx($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"])?>" method="POST" enctype="multipart/form-data" target="_self" class="profile-post-form">
								<?=bitrix_sessid_post();?>
								<?$APPLICATION->IncludeComponent(
									"bitrix:main.post.form",
									".default",
									$arResult["ProfileBlogPost"]["formParams"],
									false,
									[
										"HIDE_ICONS" => "Y"
									]
								);?>
								<input type="hidden" name="cuid" id="upload-cid" value="" />
							</form>
						</div><?
					}

					?><div class="intranet-user-profile-about-loader" id="intranet-user-profile-about-loader"></div>
				</div>
			</div>
			<?
		}

		if (
			$arResult["CurrentUser"]["STATUS"] != 'extranet'
			&& !empty($arResult["Tags"])
			&& (
				!empty($arResult["Tags"]["COUNT"])
				|| $arResult["Permissions"]['edit']
				|| $USER->getId() == $arResult["User"]["ID"]
			)
			&& !in_array($arResult["User"]["STATUS"], [ 'extranet', 'email', 'invited' ])
		)
		{
			?><div id="intranet-user-profile-tags-container" class="intranet-user-profile-container<?=(empty($arResult["Tags"]["COUNT"]) ? ' intranet-user-profile-container-empty' : '')?>">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage('INTRANET_USER_PROFILE_TAGS_TITLE')?></div><?

					if (
						$arResult["Permissions"]['edit']
						|| $USER->getId() == $arResult["User"]["ID"]
					)
					{
						?><div id="intranet-user-profile-add-tags" class="intranet-user-profile-container-edit"><?=Loc::getMessage('INTRANET_USER_PROFILE_TAGS_MODIFY')?></div><?
					}

				?></div>
				<div class="intranet-user-profile-container-body intranet-user-profile-tags-wrapper"><?

					if (
						$arResult["Permissions"]['edit']
						|| $USER->getId() == $arResult["User"]["ID"]
					)
					{
						?><div id="intranet-user-profile-tags-input" class="intranet-user-profile-tags-area"></div><?
					}

					?><div class="intranet-user-profile-tags" id="intranet-user-profile-tags"></div>

					<div id="intranet-user-profile-interests-stub" class="intranet-user-profile-about intranet-user-profile-empty-stub intranet-user-profile-about-interests">
						<div class="intranet-user-profile-post-edit-stub-default"><?=Loc::getMessage('INTRANET_USER_PROFILE_INTERESTS_STUB_TEXT')?></div>
						<a id="intranet-user-profile-interests-stub-button" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_INTERESTS_STUB_BUTTON_2')?></a>
					</div>

					<div class="intranet-user-profile-thanks-users-loader" id="intranet-user-profile-tags-loader"></div>
				</div>
			</div><?
		}
	?>
	</div>
</div>
<div class="intranet-user-profile-bottom-controls">
	<?if ($arResult["IsOwnProfile"]):?>
	<a class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-themes" onclick="BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog()">
		<?=Loc::getMessage("INTRANET_USER_PROFILE_THEME")?>
	</a>
	<?endif?>
</div>

<?
if ($arResult["adminRightsRestricted"])
{
	$moveRightsConfirmText = Loc::getMessage("INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM");
	$moveRightsConfirmText.= "<br/><br/><span style='color: #acb1b7'>".Loc::getMessage("INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM_PROMO", array(
		"#LINK_START#" => "<a href=\"javascript:void(0)\" 
			onclick=\"B24.licenseInfoPopup.show('adminQuantityRestriction', '".Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_TITLE")."', BX.clone(BX('adminRestrContent')))\"	
		>",
		"#LINK_END#" => "</a>"
	))."</span>";
	$issetTfTariff = \Bitrix\Main\Loader::includeModule("bitrix24") && in_array(\CBitrix24::getLicensePrefix(), array('ru', 'kz', 'by', 'ua'));
?>
	<div style="display: none">
		<div id="adminRestrContent" >
			<?if ($arResult["IS_COMPANY_TARIFF"]):?>
				<div style="padding-bottom: 20px;"><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_COMPANY_TEXT")?></div>
			<?else:?>
				<div style='font-size: 20px; padding-bottom: 20px;'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_TEXT1")?></div>
				<div style='padding-bottom: 20px;'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_TEXT2")?></div>
				<table width='100%'>
					<tr align='center' style='font-weight: bold'>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_PROJECT")?></td>
						<?if ($issetTfTariff):?>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_TF")?></td>
						<?endif?>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_TEAM")?></td>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_COMPANY")?></td>
					</tr>
					<tr align='center'>
						<td>1</td>
						<?if ($issetTfTariff):?>
						<td>2</td>
						<?endif?>
						<td>5</td>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_UNLIM")?></td>
					</tr>
				</table>
				<!--<br>
				<div>
					<a href='javascript:void' onclick='BX.Helper.show("redirect=detail&code=5869717");'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_MORE")?></a>
				</div>-->
			<?endif?>
		</div>
	</div>
<?
}
?>

<script>
	BX.message({
		"INTRANET_USER_PROFILE_REINVITE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REINVITE"))?>",
		"INTRANET_USER_PROFILE_DELETE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_DELETE"))?>",
		"INTRANET_USER_PROFILE_FIRE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE"))?>",
		"INTRANET_USER_PROFILE_MOVE_TO_INTRANET" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET"))?>",
		"INTRANET_USER_PROFILE_HIRE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_HIRE"))?>",
		"INTRANET_USER_PROFILE_ADMIN_MODE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_ADMIN_MODE"))?>",
		"INTRANET_USER_PROFILE_QUIT_ADMIN_MODE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_QUIT_ADMIN_MODE"))?>",
		"INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS"))?>",
		"INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS"))?>",
		"INTRANET_USER_PROFILE_SYNCHRONIZE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SYNCHRONIZE"))?>",
		"INTRANET_USER_PROFILE_FIRE_CONFIRM" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE_CONFIRM"))?>",
		"INTRANET_USER_PROFILE_DELETE_CONFIRM" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_DELETE_CONFIRM"))?>",
		"INTRANET_USER_PROFILE_HIRE_CONFIRM" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_HIRE_CONFIRM"))?>",
		"INTRANET_USER_PROFILE_YES" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_YES"))?>",
		"INTRANET_USER_PROFILE_NO" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_NO"))?>",
		"INTRANET_USER_PROFILE_MOVE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE"))?>",
		"INTRANET_USER_PROFILE_CLOSE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_CLOSE"))?>",
		"INTRANET_USER_PROFILE_SAVE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SAVE"))?>",
		"INTRANET_USER_PROFILE_MOVE_TO_INTRANET_TITLE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_TITLE"))?>",
		"INTRANET_USER_PROFILE_REINVITE_SUCCESS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REINVITE_SUCCESS"))?>",
		"INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM"))?>",
		"INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM" : "<?=\CUtil::JSEscape($moveRightsConfirmText)?>",
		"INTRANET_USER_PROFILE_FIELD_NAME" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_NAME"))?>",
		"INTRANET_USER_PROFILE_FIELD_LAST_NAME" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_LAST_NAME"))?>",
		"INTRANET_USER_PROFILE_FIELD_SECOND_NAME" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_SECOND_NAME"))?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_ADD" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_ADD"))?>",
		"INTRANET_USER_PROFILE_CONFIRM_PASSWORD" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_CONFIRM_PASSWORD"))?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_TITLE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_TITLE"))?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_SEARCH_TITLE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_SEARCH_TITLE"))?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_HINT_3" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_HINT_3"))?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_HINT_2" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_HINT_2"))?>",
		"INTRANET_USER_PROFILE_APP_INSTALL" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_APP_INSTALL"))?>",
		"INTRANET_USER_PROFILE_APP_SEND" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_APP_SEND"))?>",
		"INTRANET_USER_PROFILE_APP_INSTALL_TEXT" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_APP_INSTALL_TEXT"))?>",
		"INTRANET_USER_PROFILE_APP_PHONE" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_APP_PHONE"))?>",
		"INTRANET_USER_FIILDS_SETTINGS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_FIILDS_SETTINGS"))?>",
		"INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS"))?>",
		"INTRANET_USER_PROFILE_FIRE_INVITED_USER" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE_INVITED_USER"))?>",
		"INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM" : "<?=\CUtil::JSEscape(
			Loc::getMessage("INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM", array(
				"#NAME#" => "<b>".$arResult["User"]["FULL_NAME"]."</b>",
				"#LINK_START#" => "<a href=\"javascript:void(0)\" onclick='top.BX.Helper.show(\"redirect=detail&code=7725333\");'>",
				"#LINK_END#" => "</a>"
			))
		)?>",
		"INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT" : "<?=\CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT"))?>"
	});

	new BX.Intranet.UserProfile.Manager({
		signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
		componentName: '<?=$this->getComponent()->getName() ?>',
		canEditProfile: '<?=$arResult["Permissions"]['edit'] ? "Y" : "N"?>',
		badgesData: <?=(\CUtil::phpToJSObject(!empty($arResult['Gratitudes']['BADGES']) ? $arResult['Gratitudes']['BADGES'] : array()))?>,
		gratPostListPageSize: <?=intval($arParams['GRAT_POST_LIST_PAGE_SIZE'])?>,
		userId: <?=intval($arResult["User"]["ID"])?>,
		userStatus: <?=\CUtil::PhpToJSObject($arResult["User"]["STATUS"])?>,
		isOwnProfile: '<?=$arResult["IsOwnProfile"] ? "Y" : "N"?>',
		urls: <?=\CUtil::PhpToJSObject($arResult["Urls"])?>,
		isSessionAdmin: "<?=$arResult["User"]["IS_SESSION_ADMIN"] ? "Y" : "N"?>",
		showSonetAdmin: "<?=$arResult["User"]["SHOW_SONET_ADMIN"] ? "Y" : "N"?>",
		isExtranetUser: "<?=$arResult["User"]["IS_EXTRANET"] ? "Y" : "N"?>",
		isCurrentUserIntegrator: "<?=$arResult["IS_CURRENT_USER_INTEGRATOR"] ? "Y" : "N"?>",
		languageId: "<?=LANGUAGE_ID?>",
		siteId: "<?=SITE_ID?>",
		isCloud: "<?=$arResult["isCloud"] ? "Y" : "N"?>",
		isRusCloud: "<?=$arResult["isRusCloud"] ? "Y" : "N"?>",
		adminRightsRestricted: "<?=$arResult["adminRightsRestricted"] ? "Y" : "N"?>",
		delegateAdminRightsRestricted: "<?=$arResult["delegateAdminRightsRestricted"] ? "Y" : "N"?>",
		isFireUserEnabled: "<?=$arResult["isFireUserEnabled"] ? "Y" : "N"?>",
		profilePostData: {
			formId: '<?=(!empty($arResult["ProfileBlogPost"]["formParams"]) ? \CUtil::JSEscape($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"]) : '')?>',
			lheId: '<?=(!empty($arResult["ProfileBlogPost"]["formParams"]) ? \CUtil::JSEscape($arResult["ProfileBlogPost"]["formParams"]["LHE"]["id"]) : '')?>'
		},
		initialFields: <?=CUtil::PhpToJSObject($arResult["User"])?>,
		gridId: '<?='INTRANET_USER_GRID_'.SITE_ID?>',
		isCurrentUserAdmin: '<?=$arResult["IS_CURRENT_USER_ADMIN"] ? "Y" : "N"?>',
		voximplantEnablePhones: <?=CUtil::PhpToJSObject($arResult["User"]["VOXIMPLANT_ENABLE_PHONES"])?>
	});
</script>

<script>
	(function() {
		var likes = document.querySelectorAll('[data-role="intranet-user-profile-column-block-title-like"]');

		for (var i = 0; i < likes.length; i++)
		{
			likes[i].addEventListener('click', function() {
				this.classList.add('intranet-user-profile-column-block-title-like-animate');

				this.addEventListener('animationend', function() {
					this.classList.remove('intranet-user-profile-column-block-title-like-animate');
				})
			}, false);
		}
	})();
</script>
