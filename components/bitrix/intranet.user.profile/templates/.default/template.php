<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var string $templateFolder */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page;
use Bitrix\Main\Web\Uri;
use Bitrix\UI;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-all-paddings no-background');

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.alerts',
	'ui.tooltip',
	'ui.hint',
	'ui.icons.b24',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.avatar-editor',
	'avatar_editor'
]);

CJSCore::Init("loader");

if (!$arResult['Permissions']['view'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:socialnetwork.entity.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'ENTITY' => 'USER',
			],
		]
	);

	return;
}

Page\Asset::getInstance()->addJs($templateFolder.'/js/utils.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/stresslevel.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/grats.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/profilepost.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/tags.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/tags-users-popup.js');
Page\Asset::getInstance()->addJs($templateFolder.'/js/form-entity.js');
Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');

if (!in_array($arResult["User"]["STATUS"], ['email', 'extranet']))
{
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
}

if (
	isset($arResult["Urls"]["CommonSecurity"])
	&& $arResult["User"]["STATUS"] !== "email"
)
{
	$this->SetViewTarget('inside_pagetitle');

	if ($arResult["Permissions"]['edit'])
	{
		?>
		<span
			onclick="BX.SidePanel.Instance.open('<?=$arResult["Urls"]["CommonSecurity"]."?page=auth"?>', {width: 1100});"
			class="ui-btn ui-btn-light-border ui-btn-themes"
		>
			<?=Loc::getMessage("INTRANET_USER_PROFILE_PASSWORDS")?>
		</span>
		<?php
	}

	if (
		$arResult["OTP_IS_ENABLED"] === "Y"
		&& ($arResult["IsOwnProfile"] || $USER->CanDoOperation('security_edit_user_otp'))
	)
	{
		?>
		<span
			onclick="BX.SidePanel.Instance.open('<?=$arResult["Urls"]["CommonSecurity"]."?page=otpConnected"?>', {width: 1100});"
			class="ui-btn ui-btn-light-border ui-btn-themes"
		>
			<?=Loc::getMessage("INTRANET_USER_PROFILE_SECURITY")?>
		</span>
		<?php
	}

	$this->EndViewTarget();
}

if (
	$arResult["IsOwnProfile"]
	&& ($arResult["User"]["SHOW_SONET_ADMIN"])
	&& file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.admin.set")
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
				<?php
				if (
					isset($arResult["User"]["STATUS"]) && !empty($arResult["User"]["STATUS"])
					&& (
						!(
							$arResult["User"]["STATUS"] === "employee"
							&& ($arResult["IsOwnProfile"] || !$arResult["Permissions"]['edit'])
						)
						|| $arResult["User"]["SHOW_SONET_ADMIN"]
					)
				)
				{
					$classList = [
						'intranet-user-profile-rank-item',
						'intranet-user-profile-rank-' . $arResult["User"]["STATUS"],
					];
					if ($arResult["Permissions"]['edit'])
					{
						$classList[] = 'intranet-user-profile-rank-item-pointer';
					}
					?>
					<div class="<?= implode(' ', $classList) ?>" data-role="user-profile-actions-button">
						<span><?=ToUpper(Loc::getMessage("INTRANET_USER_PROFILE_".$arResult["User"]["STATUS"]))?></span>
						<?php
						if ($arResult["Permissions"]['edit'])
						{
							?>
							<span class="intranet-user-profile-rank-item-config"></span>
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
			</div>

			<div class="intranet-user-profile-status-info">
				<div class="intranet-user-profile-status intranet-user-profile-status-<?= $arResult["User"]["ONLINE_STATUS"]["STATUS"] ?>">
					<?=ToUpper($arResult["User"]["ONLINE_STATUS"]["STATUS_TEXT"])?>
				</div>
				<div class="intranet-user-profile-last-time">
					<?php
					if ($arResult["User"]["ONLINE_STATUS"]['STATUS'] === 'idle')
					{
						?>
						<?= (
							$arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT']
								? Loc::getMessage(
									'INTRANET_USER_PROFILE_LAST_SEEN_IDLE_'.($arResult["User"]["PERSONAL_GENDER"] === 'F'? 'F': 'M'),
									[
										'#LAST_SEEN#' => $arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'],
									]
								)
								: ''
						) ?>
						<?php
					}
					else
					{
						?>
						<?= (
							$arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT']
								? Loc::getMessage(
									'INTRANET_USER_PROFILE_LAST_SEEN_'.($arResult["User"]["PERSONAL_GENDER"] === 'F'? 'F': 'M'),
									[
										'#LAST_SEEN#' => $arResult["User"]["ONLINE_STATUS"]['LAST_SEEN_TEXT'],
									]
								)
								: ''
						) ?>
						<?php
					}
					?>
				</div>
			</div><?php

			$classList = [
				'intranet-user-profile-userpic',
				'ui-icon',
				'ui-icon-common-user',
			];
			if ($arResult["IsOwnProfile"] || $arResult["Permissions"]['edit'])
			{
				$classList[] = 'intranet-user-profile-userpic-edit';
			}
			?><div class="<?= implode(' ', $classList) ?>">
				<?php
				$style = (
					isset($arResult["User"]["PHOTO"]) && !empty($arResult["User"]["PHOTO"])
						? 'style="background-image: url(\'' . Uri::urnEncode($arResult["User"]["PHOTO"]) . '\'); background-size: cover"'
						: ''
				);
				?>
				<i id="intranet-user-profile-photo" <?= $style ?>></i>
				<?php
				if ($arResult["IsOwnProfile"] || $arResult["Permissions"]['edit'])
				{
					?>
					<div class="intranet-user-profile-userpic-load">
						<div class="intranet-user-profile-userpic-create" id="intranet-user-profile-photo-camera"><?=Loc::getMessage("INTRANET_USER_PROFILE_AVATAR_CAMERA")?></div>
						<div class="intranet-user-profile-userpic-upload" id="intranet-user-profile-photo-file" <?=(
							class_exists(UI\Avatar\Mask\Helper::class)
							? UI\Avatar\Mask\Helper::getHTMLAttribute($arResult["User"]["PERSONAL_PHOTO"]) : ''
						)?>><?=Loc::getMessage("INTRANET_USER_PROFILE_AVATAR_LOAD")?></div><?php

						if (class_exists(Bitrix\UI\Avatar\Mask\Helper::class)
							&&
							\Bitrix\Main\Config\Option::get('ui', 'avatar-editor-availability-delete-after-10.2022', 'N') === 'Y'
						)
						{
							?><div class="intranet-user-profile-userpic-mask" id="intranet-user-profile-photo-mask">
								<?=Loc::getMessage("INTRANET_USER_PROFILE_AVATAR_MASK")?>
							</div><?php
						}
						?>
					</div>
					<div class="intranet-user-profile-userpic-remove" id="intranet-user-profile-photo-remove"></div>
					<?php
				}
				?>
			</div>

			<?php
			if (!$arResult["IsOwnProfile"] && $arResult["User"]["STATUS"] !== "email")
			{
				?>
				<div class="intranet-user-profile-actions">
					<?php
					if ($arResult["User"]["ACTIVE"] === "N")
					{
						?>
						<a
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
							href="javascript:void(0)"
							onclick="if (top.BXIM) { top.BXIM.openHistory(<?=$arResult["User"]['ID']?>); }"
						>
							<?=Loc::getMessage("INTRANET_USER_PROFILE_CHAT_HISTORY")?>
						</a>
						<?php
					}
					else
					{
						?>
						<a
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
							href="javascript:void(0)"
							onclick="if (top.BXIM) { top.BXIM.openMessenger(<?= $arResult["User"]['ID'] ?>); }"
						>
							<?= Loc::getMessage("INTRANET_USER_PROFILE_CHAT") ?>
						</a>
						<?php
						if (!in_array($arResult['User']['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes(), true))
						{
							?>
							<a
								class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
								href="javascript:void(0)"
								onclick="if (top.BXIM) { top.BXIM.callTo(<?= $arResult["User"]['ID'] ?>); }"
							>
								<?= Loc::getMessage("INTRANET_USER_PROFILE_VIDEOCALL") ?>
							</a>
							<?php
						}
					}
					?>
				</div>
				<?php
			}

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
		<?php

		if (
			$arResult["StressLevel"]['AVAILABLE'] === 'Y'
			&& !$arResult["isExtranetSite"]
			&& !in_array($arResult["User"]["STATUS"], ['email', 'extranet'])
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
						<?php
						foreach($arResult["StressLevel"]["TYPES_LIST"] as $type => $description)
						{
							?>
							<div id="intranet-user-profile-stresslevel-status-<?=htmlspecialcharsbx($type)?>" class="intranet-user-profile-stresslevel-status intranet-user-profile-stresslevel-status-<?=htmlspecialcharsbx($type)?>" style="display: none;"></div>
							<?php
						}
						?>
						<div id="intranet-user-profile-stresslevel-status-info" class="intranet-user-profile-stresslevel-status-info" data-hint="" data-hint-no-icon></div>
					</div>
					<div id="intranet-user-profile-stresslevel-comment" class="intranet-user-profile-stresslevel-status-text"></div>
					<?php
					if (
						$arResult["StressLevel"]["IMAGE_SUPPORT"] === 'Y'
						&& (int)$USER->getId() === (int)$arResult["User"]["ID"]
					)
					{
						?>
						<div id="intranet-user-profile-stresslevel-status-copy" class="intranet-user-profile-stresslevel-status-copy"><?=Loc::getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_SHARE_LINK')?></div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		if ($arResult["IsOwnProfile"] && $arResult["User"]["STATUS"] !== "email")
		{
			?>
			<div class="intranet-user-profile-column-block">
				<?php
				$APPLICATION->IncludeComponent(
					"bitrix:intranet.apps",
					'',
					[
						'USER_ID' => $arResult['User']['ID'],
					]
				);
				?>
			</div>
			<?php
		}

		if (!empty($arResult["DISK_INFO"]))
		{
			?>
			<div class="intranet-user-profile-column-block">
				<div class="intranet-user-profile-apps">
					<div class="intranet-user-profile-desktop-block">
						<?=Loc::getMessage("INTRANET_USER_PROFILE_DISK_INSTALLED")?>
						<?php
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
			<?php
		}

		if (
			!empty($arResult["Gratitudes"])
			&& !$arResult["isExtranetSite"]
			&& !in_array($arResult["User"]["STATUS"], ['email', 'extranet'])
		)
		{
			?><div class="intranet-user-profile-column-block">
				<div class="intranet-user-profile-column-block-title">
					<span class="intranet-user-profile-column-block-title-text"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_TITLE')?></span><?php
					if (
						!empty($arResult["Gratitudes"]['URL_ADD'])
						&& (int)$USER->getId() !== (int)$arResult["User"]["ID"]
					)
					{
						?><div  onclick="BX.SidePanel.Instance.open('<?=$arResult['Gratitudes']['URL_ADD']?>', {
							cacheable: false,
							data: {
								entityType: 'gratPost',
								entityId: '<?= (int)$arResult["User"]["ID"] ?>'
							},
							width: 1000
						}); return event.preventDefault();" class="intranet-user-profile-column-block-title-like" data-role="intranet-user-profile-column-block-title-like"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_ADD')?></div><?php
					}
				?></div>

				<div id="intranet-user-profile-thanks" class="intranet-user-profile-thanks" data-bx-grat-url="<?=htmlspecialcharsbx($arResult['Gratitudes']['URL_LIST'])?>"><?php
					foreach($arResult['Gratitudes']['BADGES'] as $badge)
					{
						$classList = [
							'intranet-user-profile-thanks-item',
							'intranet-user-profile-thanks-item-' . htmlspecialcharsbx($badge['CODE']),
						];

						?><div
						 class="<?= implode(' ', $classList) ?>"
						 title="<?=htmlspecialcharsbx($badge['NAME'])?>"
						 data-bx-grat-code="<?=htmlspecialcharsbx($badge['CODE'])?>"
						 data-bx-grat-enum="<?= (int)$badge['ID'] ?>"></div><?php
					}
				?></div>

				<div class="intranet-user-profile-thanks-users">
					<div class="intranet-user-profile-thanks-users-wrapper" id="intranet-user-profile-thanks-users-wrapper"></div>
					<div class="intranet-user-profile-thanks-users-loader" id="intranet-user-profile-thanks-users-loader"></div>
					<div class="intranet-user-profile-load-users-link" style="display: none;" id="intranet-user-profile-load-users-link"><?=Loc::getMessage('INTRANET_USER_PROFILE_MORE', array('#NUM#' => $arParams['GRAT_POST_LIST_PAGE_SIZE']))?></div>
				</div><?php

				if (
					!empty($arResult["Gratitudes"]['URL_ADD'])
					&& (int)$USER->getId() !== (int)$arResult["User"]["ID"]
				)
				{
					?><div class="intranet-user-profile-thanks-info">
						<a href="javascript:void(0);" onclick="BX.SidePanel.Instance.open('<?=$arResult['Gratitudes']['URL_ADD']?>', {
							cacheable: false,
							data: {
								entityType: 'gratPost',
								entityId: '<?= (int)$arResult["User"]["ID"] ?>'
							},
							width: 1000
						}); return event.preventDefault();" class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_GRAT_ADD')?></a>
					</div><?php
				}
			?></div><?php
		}
	    ?>
	</div>
	<div class="intranet-user-profile-column-right">
		<?php
		//region Ru-zone notification
		$showRuNotification = false;
		if (
			LANGUAGE_ID === 'ru'
			&&
			method_exists(\Bitrix\Main\Application::getInstance(), 'getLicense')
			&&
			($region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion())
			&&
			(is_null($region) || mb_strtolower($region) === 'ru')
		)
		{
			array_walk($arResult['FormFields'], function(&$item) use (&$showRuNotification) {
				if (is_array($item) && $item['name'] === 'UF_FACEBOOK')
				{
					$showRuNotification = true;
					$item['title'] = '*'.$item['title'];
				}
			});
		}
		//endregion
		$uiRes = $APPLICATION->IncludeComponent(
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
				"ENABLE_SECTION_DRAG_DROP" => true,
				"FORCE_DEFAULT_SECTION_NAME" => true,
				"ENABLE_PERSONAL_CONFIGURATION_UPDATE" => $arResult["EnablePersonalConfigurationUpdate"],
				"ENABLE_COMMON_CONFIGURATION_UPDATE" => $arResult["EnableCommonConfigurationUpdate"],
				"ENABLE_SETTINGS_FOR_ALL" => $arResult["EnableSettingsForAll"],
				"READ_ONLY" => !$arResult["Permissions"]['edit'],
				"ENABLE_USER_FIELD_CREATION" => $arResult["EnableUserFieldCreation"],
				"ENABLE_USER_FIELD_MANDATORY_CONTROL" => $arResult["EnableUserFieldMandatoryControl"],
				"USER_FIELD_ENTITY_ID" => $arResult["UserFieldEntityId"],
				"USER_FIELD_PREFIX" => $arResult["UserFieldPrefix"],
				"ENABLE_FIELD_DRAG_DROP" => true,
				"USER_FIELD_CREATE_SIGNATURE" => $arResult["UserFieldCreateSignature"],
				"SERVICE_URL" => POST_FORM_ACTION_URI.'&'.bitrix_sessid_get(),
				"COMPONENT_AJAX_DATA" => array(
					"COMPONENT_NAME" => $this->getComponent()->getName(),
					"ACTION_NAME" => "save",
					"SIGNED_PARAMETERS" => $this->getComponent()->getSignedParameters()
				)
			)
		);
		if (
			$showRuNotification
			&& !$arResult['EnableCommonConfigurationUpdate']
			&& in_array('UF_FACEBOOK', array_column($uiRes['ENTITY_AVAILABLE_FIELDS'], 'name'))
		)
		{
			$showRuNotification = false;
		}
		if (!empty($arResult["User"]["SUBORDINATE"]) || !empty($arResult["User"]["MANAGERS"]))
		{
			?>
			<div class="intranet-user-profile-container">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_ADDITIONAL_INFO")?></div>
				</div>

				<?php
				if (!empty($arResult["User"]["SUBORDINATE"]))
				{
					?>
					<div class="intranet-user-profile-container-body">
						<div class="intranet-user-profile-container-body-title">
							<?=Loc::getMessage("INTRANET_USER_PROFILE_FIELD_SUBORDINATE")?>
						</div>
						<div class="intranet-user-profile-grid">
							<?php
							$i = 0;
							foreach ($arResult["User"]["SUBORDINATE"] as $key => $subUser)
							{
								$i++;
								$attributes = (
									$i > 4
										? ' style="display: none" data-role="user-profile-item"'
										: ''
								);
								?>
								<a class="intranet-user-profile-grid-item" href="<?=$subUser["LINK"]?>"
									<?= $attributes ?>
								>
									<div
										class="ui-icon ui-icon-common-user intranet-user-profile-user-avatar">
										<?php
										$style = (
											isset($subUser["PHOTO"]) && !empty($subUser["PHOTO"])
												? 'style="background-image: url(\'' . Uri::urnEncode($subUser["PHOTO"]) . '\');"'
												: ''
										);
										?>
										<i <?= $style ?>></i>
									</div>
									<div class="intranet-user-profile-user-container">
										<div class="intranet-user-profile-user-name"><?=$subUser["FULL_NAME"]?></div>
										<div class="intranet-user-profile-user-position"><?=$subUser["WORK_POSITION"]?></div>
									</div>
								</a>
								<?php
							}
							?>
						</div>
						<?php
						if ($i > 4)
						{
							?>
							<div class="intranet-user-profile-grid-loadmore" id="intranet-user-profile-subordinate-more">
								<?=Loc::getMessage("INTRANET_USER_PROFILE_MORE", array("#NUM#" => "(<span>".($i-4)."</span>)"))?>
							</div>
							<?php
						}
						?>
					</div>
					<?php
				}
				if (!empty($arResult["User"]["MANAGERS"]))
				{
					?>
					<div class="intranet-user-profile-container-body">
						<div class="intranet-user-profile-container-body-title">
							<?=Loc::getMessage("INTRANET_USER_PROFILE_FIELD_MANAGERS")?>
						</div>
						<div class="intranet-user-profile-grid">
							<?php
							$i = 0;
							foreach ($arResult["User"]["MANAGERS"] as $manager)
							{
								$i++;
								?>
								<a class="intranet-user-profile-grid-item" href="<?=$manager["LINK"]?>">
									<?php
									$style = (
										isset($manager["PHOTO"]) && !empty($manager["PHOTO"])
											? 'style="background-image: url(\'' . Uri::urnEncode($manager["PHOTO"]) . '\');"'
											: ''
									);
									?>
									<div href="<?=$manager["LINK"]?>"
										class="ui-icon ui-icon-common-user intranet-user-profile-user-avatar">
										<i <?= $style ?>></i>
									</div>
									<div class="intranet-user-profile-user-container">
										<div class="intranet-user-profile-user-name"><?=$manager["FULL_NAME"]?></div>
										<div class="intranet-user-profile-user-position"><?=$manager["WORK_POSITION"]?></div>
									</div>
								</a>
								<?php
							}
							?>
						</div>
						<?php
						if ($i > 4)
						{
							?>
							<div class="intranet-user-profile-grid-loadmore" id="intranet-user-profile-manages-more">
								<?=Loc::getMessage("INTRANET_USER_PROFILE_MORE", array("#NUM#" => "(<span>".($i-4)."</span>)"))?>
							</div>
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

		if (
			!empty($arResult["ProfileBlogPost"])
			&& $arResult["User"]["STATUS"] !== "email"
			&& (
				!empty($arResult["ProfileBlogPost"]["POST_ID"])
				|| $arResult["Permissions"]['edit']
				|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
			)
		)
		{
			$classList = [
				'intranet-user-profile-container',
			];
			if (empty($arResult["ProfileBlogPost"]["POST_ID"]))
			{
				$classList[] = 'intranet-user-profile-container-empty';
			}
			?><div id="intranet-user-profile-post-container" class="<?= implode(' ', $classList) ?>">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage("INTRANET_USER_PROFILE_BLOG_POST_TITLE")?></div><?php

					if (
						$arResult["Permissions"]['edit']
						|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
					)
					{
						?><a id="intranet-user-profile-post-edit-link" class="intranet-user-profile-container-edit"><?= Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_MODIFY') ?></a><?php
					}

				?></div>
				<div class="intranet-user-profile-container-body intranet-user-profile-about-wrapper">
					<div id="intranet-user-profile-about-wrapper"></div>

					<div id="intranet-user-profile-post-edit-stub" class="intranet-user-profile-about intranet-user-profile-empty-stub intranet-user-profile-about-profile">
						<div class="intranet-user-profile-post-edit-stub-default"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_STUB_TEXT')?></div>
						<a id="intranet-user-profile-post-edit-stub-button" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_BLOG_POST_STUB_BUTTON')?></a>
					</div><?php

					if (
						(
							$arResult["Permissions"]['edit']
							|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
						)
					)
					{
						?><div id="intranet-user-profile-about-form-wrapper" style="display: block;">
							<form action="" id="<?=htmlspecialcharsbx($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"])?>" name="<?=htmlspecialcharsbx($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"])?>" method="POST" enctype="multipart/form-data" target="_self" class="profile-post-form">
								<?= bitrix_sessid_post() ?>
								<?php
								$APPLICATION->IncludeComponent(
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
						</div><?php
					}

					?><div class="intranet-user-profile-about-loader" id="intranet-user-profile-about-loader"></div>
				</div>
			</div>
			<?php
		}

		if (
			$arResult["CurrentUser"]["STATUS"] !== 'extranet'
			&& !empty($arResult["Tags"])
			&& (
				!empty($arResult["Tags"]["COUNT"])
				|| $arResult["Permissions"]['edit']
				|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
			)
			&& !in_array($arResult["User"]["STATUS"], [ 'extranet', 'email', 'invited' ])
		)
		{
			?><div id="intranet-user-profile-tags-container" class="intranet-user-profile-container<?=(empty($arResult["Tags"]["COUNT"]) ? ' intranet-user-profile-container-empty' : '')?>">
				<div class="intranet-user-profile-container-header">
					<div class="intranet-user-profile-container-title"><?=Loc::getMessage('INTRANET_USER_PROFILE_TAGS_TITLE')?></div><?php

					if (
						$arResult["Permissions"]['edit']
						|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
					)
					{
						?><div id="intranet-user-profile-add-tags" class="intranet-user-profile-container-edit"><?=Loc::getMessage('INTRANET_USER_PROFILE_TAGS_MODIFY')?></div><?php
					}

				?></div>
				<div class="intranet-user-profile-container-body intranet-user-profile-tags-wrapper"><?php

					if (
						$arResult["Permissions"]['edit']
						|| (int)$USER->getId() === (int)$arResult["User"]["ID"]
					)
					{
						?><div id="intranet-user-profile-tags-input" class="intranet-user-profile-tags-area"></div><?php
					}

					?><div class="intranet-user-profile-tags" id="intranet-user-profile-tags"></div>

					<div id="intranet-user-profile-interests-stub" class="intranet-user-profile-about intranet-user-profile-empty-stub intranet-user-profile-about-interests">
						<div class="intranet-user-profile-post-edit-stub-default"><?=Loc::getMessage('INTRANET_USER_PROFILE_INTERESTS_STUB_TEXT')?></div>
						<a id="intranet-user-profile-interests-stub-button" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"><?=Loc::getMessage('INTRANET_USER_PROFILE_INTERESTS_STUB_BUTTON_2')?></a>
					</div>

					<div class="intranet-user-profile-thanks-users-loader" id="intranet-user-profile-tags-loader"></div>
				</div>
			</div><?php
		}
		if ($showRuNotification === true)
		{
			?>
				<div id="show-ru-meta-notification" class="intranet-user-profile-container" style="display: none;">
						<div class="intranet-user-profile-container-body-title">
							<?=Loc::getMessage("INTRANET_USER_PROFILE_FACEBOOK_RESTRICTIONS_META_RU")?>
						</div>
				</div>
			<?php
		}
		?>
	</div>
</div>
<div class="intranet-user-profile-bottom-controls">
	<?php
	if ($arResult["IsOwnProfile"])
	{
		?>
		<a class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-themes" onclick="BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog()">
			<?=Loc::getMessage("INTRANET_USER_PROFILE_THEME")?>
		</a>
		<?php
	}
	?>
</div>
<?php

$moveRightsConfirmText = '';

if ($arResult["adminRightsRestricted"])
{
	$moveRightsConfirmText = Loc::getMessage("INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM");
	$moveRightsConfirmText.= "<br/><br/><span style='color: #acb1b7'>".Loc::getMessage("INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM_PROMO", array(
		"#LINK_START#" => "<a href=\"javascript:void(0)\" 
			onclick=\"top.BX.UI.InfoHelper.show('limit_admin_quantity_restriction')\"	
		>",
		"#LINK_END#" => "</a>"
	))."</span>";
	$issetTfTariff = \Bitrix\Main\Loader::includeModule("bitrix24") && in_array(CBitrix24::getLicensePrefix(), array('ru', 'kz', 'by', 'ua'));
	?>
	<div style="display: none">
		<div id="adminRestrContent" >
			<?php
			if ($arResult["IS_COMPANY_TARIFF"])
			{
				?>
				<div style="padding-bottom: 20px;"><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_COMPANY_TEXT")?></div>
				<?php
			}
			else
			{
				?>
				<div style='font-size: 20px; padding-bottom: 20px;'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_TEXT1")?></div>
				<div style='padding-bottom: 20px;'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_TEXT2")?></div>
				<table width='100%'>
					<tr align='center' style='font-weight: bold'>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_PROJECT")?></td>
						<?php
						if ($issetTfTariff)
						{
							?>
							<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_TF")?></td>
							<?php
						}
						?>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_TEAM")?></td>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_TARIFF_COMPANY")?></td>
					</tr>
					<tr align='center'>
						<td>1</td>
						<?php
						if ($issetTfTariff)
						{
							?>
							<td>2</td>
							<?php
						}
						?>
						<td>5</td>
						<td><?=Loc::getMessage("INTRANET_USER_PROFILE_UNLIM")?></td>
					</tr>
				</table>
				<!--<br>
				<div>
					<a href='javascript:void' onclick='BX.Helper.show("redirect=detail&code=5869717");'><?=Loc::getMessage("INTRANET_USER_PROFILE_RIGHTS_RESTR_MORE")?></a>
				</div>-->
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
?>

<script>
	BX.message({
		"INTRANET_USER_PROFILE_REINVITE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REINVITE")) ?>",
		"INTRANET_USER_PROFILE_DELETE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_DELETE")) ?>",
		"INTRANET_USER_PROFILE_FIRE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE")) ?>",
		"INTRANET_USER_PROFILE_MOVE_TO_INTRANET" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET")) ?>",
		"INTRANET_USER_PROFILE_HIRE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_HIRE")) ?>",
		"INTRANET_USER_PROFILE_ADMIN_MODE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_ADMIN_MODE")) ?>",
		"INTRANET_USER_PROFILE_QUIT_ADMIN_MODE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_QUIT_ADMIN_MODE")) ?>",
		"INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS")) ?>",
		"INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS")) ?>",
		"INTRANET_USER_PROFILE_SYNCHRONIZE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SYNCHRONIZE")) ?>",
		"INTRANET_USER_PROFILE_FIRE_CONFIRM" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE_CONFIRM")) ?>",
		"INTRANET_USER_PROFILE_DELETE_CONFIRM" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_DELETE_CONFIRM")) ?>",
		"INTRANET_USER_PROFILE_HIRE_CONFIRM" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_HIRE_CONFIRM")) ?>",
		"INTRANET_USER_PROFILE_YES" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_YES")) ?>",
		"INTRANET_USER_PROFILE_NO" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_NO")) ?>",
		"INTRANET_USER_PROFILE_MOVE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE")) ?>",
		"INTRANET_USER_PROFILE_CLOSE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_CLOSE")) ?>",
		"INTRANET_USER_PROFILE_SAVE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SAVE")) ?>",
		"INTRANET_USER_PROFILE_MOVE_TO_INTRANET_TITLE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_TITLE")) ?>",
		"INTRANET_USER_PROFILE_REINVITE_SUCCESS" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_REINVITE_SUCCESS")) ?>",
		"INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM")) ?>",
		"INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM" : "<?= CUtil::JSEscape($moveRightsConfirmText) ?>",
		"INTRANET_USER_PROFILE_FIELD_NAME" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_NAME")) ?>",
		"INTRANET_USER_PROFILE_FIELD_LAST_NAME" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_LAST_NAME")) ?>",
		"INTRANET_USER_PROFILE_FIELD_SECOND_NAME" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIELD_SECOND_NAME")) ?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_ADD" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_ADD")) ?>",
		"INTRANET_USER_PROFILE_CONFIRM_PASSWORD" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_CONFIRM_PASSWORD")) ?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_TITLE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_TITLE")) ?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_SEARCH_TITLE" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_SEARCH_TITLE")) ?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_HINT_3" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_HINT_3")) ?>",
		"INTRANET_USER_PROFILE_TAGS_POPUP_HINT_2" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_TAGS_POPUP_HINT_2")) ?>",
		"INTRANET_USER_FIILDS_SETTINGS" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_FIILDS_SETTINGS")) ?>",
		"INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS")) ?>",
		"INTRANET_USER_PROFILE_FIRE_INVITED_USER" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_FIRE_INVITED_USER")) ?>",
		"INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM" : "<?= CUtil::JSEscape(
			Loc::getMessage("INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM", array(
				"#NAME#" => "<b>".$arResult["User"]["FULL_NAME"]."</b>",
				"#LINK_START#" => "<a href=\"javascript:void(0)\" onclick='top.BX.Helper.show(\"redirect=detail&code=7725333\");'>",
				"#LINK_END#" => "</a>"
			))
		) ?>",
		"INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT" : "<?= CUtil::JSEscape(Loc::getMessage("INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT")) ?>"
	});

	new BX.Intranet.UserProfile.Manager({
		signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
		componentName: '<?=$this->getComponent()->getName() ?>',
		canEditProfile: '<?=$arResult["Permissions"]['edit'] ? "Y" : "N"?>',
		badgesData: <?= (CUtil::phpToJSObject(!empty($arResult['Gratitudes']['BADGES']) ? $arResult['Gratitudes']['BADGES'] : [])) ?>,
		gratPostListPageSize: <?= (int)$arParams['GRAT_POST_LIST_PAGE_SIZE'] ?>,
		userId: <?= (int)$arResult["User"]["ID"] ?>,
		userStatus: <?= CUtil::PhpToJSObject($arResult["User"]["STATUS"]) ?>,
		isOwnProfile: '<?=$arResult["IsOwnProfile"] ? "Y" : "N"?>',
		urls: <?= CUtil::PhpToJSObject($arResult["Urls"]) ?>,
		isSessionAdmin: "<?=($arResult["User"]["IS_SESSION_ADMIN"] ?? null) ? "Y" : "N"?>",
		showSonetAdmin: "<?=$arResult["User"]["SHOW_SONET_ADMIN"] ? "Y" : "N"?>",
		isExtranetUser: "<?=$arResult["User"]["IS_EXTRANET"] ? "Y" : "N"?>",
		isCurrentUserIntegrator: "<?=$arResult["IS_CURRENT_USER_INTEGRATOR"] ? "Y" : "N"?>",
		languageId: "<?=LANGUAGE_ID?>",
		siteId: "<?=SITE_ID?>",
		isCloud: "<?=$arResult["isCloud"] ? "Y" : "N"?>",
		isRusCloud: "<?= (isset($arResult["isRusCloud"]) && $arResult["isRusCloud"]) ? "Y" : "N"?>",
		adminRightsRestricted: "<?=$arResult["adminRightsRestricted"] ? "Y" : "N"?>",
		delegateAdminRightsRestricted: "<?=$arResult["delegateAdminRightsRestricted"] ? "Y" : "N"?>",
		isFireUserEnabled: "<?=$arResult["isFireUserEnabled"] ? "Y" : "N"?>",
		profilePostData: {
			formId: '<?= (!empty($arResult["ProfileBlogPost"]["formParams"]) ? CUtil::JSEscape($arResult["ProfileBlogPost"]["formParams"]["FORM_ID"]) : '') ?>',
			lheId: '<?= (!empty($arResult["ProfileBlogPost"]["formParams"]) ? CUtil::JSEscape($arResult["ProfileBlogPost"]["formParams"]["LHE"]["id"]) : '') ?>'
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
