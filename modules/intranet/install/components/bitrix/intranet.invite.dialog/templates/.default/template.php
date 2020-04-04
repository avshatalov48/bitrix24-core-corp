<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Loader;

$bExtranetInstalled = \Bitrix\Main\ModuleManager::IsModuleInstalled("extranet");
if ($bExtranetInstalled)
{
	$extranetSiteId = \Bitrix\Main\Config\Option::get("extranet", "extranet_site");
	if (empty($extranetSiteId))
	{
		$bExtranetInstalled = false;
	}
}

$licensePrefix = Loader::includeModule("bitrix24") ? \CBitrix24::getLicensePrefix() : "";
$SITE_ID = CSite::GetDefSite();

$arMailServices = array();
$bDomainUsersExist = false;
$bCreateDomainsExist = false;
$bConnectDomainsExist = false;
$bMailInstalled = false;

if (CModule::IncludeModule("mail"))
{
	$bMailInstalled = true;
	$dbService = \Bitrix\Mail\MailServicesTable::getList(array(
		'filter' => array(
			'ACTIVE' => 'Y',
			'=SITE_ID' => $SITE_ID,
			'=SERVICE_TYPE' => 'imap',
		),
		'order' => array(
			'SORT' => 'ASC',
			'NAME' => 'ASC'
		)
	));

	while ($arService = $dbService->fetch())
	{
		$arMailServices[$arService['ID']] = array(
			'id' => $arService['ID'],
			'type' => $arService['SERVICE_TYPE'],
			'name' => $arService['NAME'],
			'link' => $arService['LINK'],
			'icon' => \Bitrix\Mail\MailServicesTable::getIconSrc($arService['NAME'], $arService['ICON']),
			'server' => $arService['SERVER'],
			'port' => $arService['PORT'],
			'encryption' => $arService['ENCRYPTION'],
			'token' => $arService['TOKEN']
		);

		if ($arService['SERVICE_TYPE'] == 'controller')
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
			if (
				!empty($crDomains['result'])
				&& is_array($crDomains['result'])
			)
			{
				$arMailServices[$arService['ID']]['domains'] = $crDomains['result'];
				$bCreateDomainsExist = true;
			}

			$arMailServices[$arService['ID']]['users'] = array();
			$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetUsers', array());

			if (
				!empty($crUsers['result'])
				&& is_array($crUsers['result'])
			)
			{
				foreach ($crUsers['result'] as $email)
				{
					list($login, $domain) = explode('@', $email, 2);

					if (empty($arMailServices[$arService['ID']]['users'][$domain]))
					{
						$arMailServices[$arService['ID']]['users'][$domain] = array();
					}
					$arMailServices[$arService['ID']]['users'][$domain][] = $login;
				}

				$rsMailbox = CMailbox::getList(
					array(
						'TIMESTAMP_X' => 'ASC'
					),
					array(
						'ACTIVE' => 'Y',
						'!USER_ID' => 0,
						'SERVICE_ID' => $arMailServices[$arService['ID']]['id']
					)
				);
				while ($arMailbox = $rsMailbox->Fetch())
				{
					list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
					if (
						!empty($arMailServices[$arService['ID']]['users'][$domain])
						&& ($key = array_search($login, $arMailServices[$arService['ID']]['users'][$domain])) !== false
					)
					{
						array_splice($arMailServices[$arService['ID']]['users'][$domain], $key, 1);
					}
				}

				if (is_array($arMailServices[$arService['ID']]['users']))
				{
					foreach($arMailServices[$arService['ID']]['users'] as $domain => $arLogin)
					{
						if (empty($arLogin))
						{
							unset($arMailServices[$arService['ID']]['users'][$domain]);
						}
					}
				}

				if (
					!$bDomainUsersExist
					&& !empty($arMailServices[$arService['ID']]['users']))
				{
					$bConnectDomainsExist = true;
					$bDomainUsersExist = true;
				}
			}
		}
		else if ($arService['SERVICE_TYPE'] == 'crdomain')
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array());
			if (
				!empty($crDomains['result'])
				&& is_array($crDomains['result'])
			)
			{
				$arMailServices[$arService['ID']]['domains'] = $crDomains['result'];
				$bCreateDomainsExist = true;
			}

			$arMailServices[$arService['ID']]['users'] = array();
			$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetMemberUsers', array(
				'DOMAIN' => $arService['SERVER']
			));

			if (
				!empty($crUsers['result'])
				&& is_array($crUsers['result'])
			)
			{
				foreach ($crUsers['result'] as $login)
				{
					if (empty($arMailServices[$arService['ID']]['users'][$arService['SERVER']]))
					{
						$arMailServices[$arService['ID']]['users'][$arService['SERVER']] = array();
					}
					$arMailServices[$arService['ID']]['users'][$arService['SERVER']][] = $login;
				}

				$rsMailbox = CMailbox::getList(
					array(
						'TIMESTAMP_X' => 'ASC'
					),
					array(
						'ACTIVE' => 'Y',
						'!USER_ID' => 0,
						'SERVICE_ID' => $arMailServices[$arService['ID']]['id']
					)
				);
				while ($arMailbox = $rsMailbox->Fetch())
				{
					list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
					if (
						!empty($arMailServices[$arService['ID']]['users'][$domain])
						&& ($key = array_search($login, $arMailServices[$arService['ID']]['users'][$domain])) !== false
					)
					{
						array_splice($arMailServices[$arService['ID']]['users'][$domain], $key, 1);
					}
				}

				if (is_array($arMailServices[$arService['ID']]['users']))
				{
					foreach($arMailServices[$arService['ID']]['users'] as $domain => $arLogin)
					{
						if (empty($arLogin))
						{
							unset($arMailServices[$arService['ID']]['users'][$domain]);
						}
					}
				}

				if (
					!$bDomainUsersExist
					&& !empty($arMailServices[$arService['ID']]['users']))
				{
					$bDomainUsersExist = true;
				}

				if (!empty($arService['SERVER']))
				{
					$bConnectDomainsExist = true;
				}
			}
		}
		elseif ($arService['SERVICE_TYPE'] == 'domain')
		{
			$arMailServices[$arService['ID']]['users'] = CMailDomain2::getDomainUsers($arService['TOKEN'], $arService['SERVER'], $error);

			$rsMailbox = CMailbox::getList(
				array(
					'TIMESTAMP_X' => 'ASC'
				),
				array(
					'ACTIVE' => 'Y',
					'!USER_ID' => 0,
					'SERVER_TYPE' => 'domain',
					'SERVICE_ID' => $arService['ID']
				)
			);

			while ($arMailbox = $rsMailbox->fetch())
			{
				list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
				if (($key = array_search($login, $arMailServices[$arService['ID']]['users'])) !== false)
				{
					array_splice($arMailServices[$arService['ID']]['users'], $key, 1);
				}
			}

			if (
				!$bDomainUsersExist
				&& !empty($arMailServices[$arService['ID']]['users']))
			{
				$bDomainUsersExist = true;
			}

			if (!empty($arService['SERVER']))
			{
				$bCreateDomainsExist = true;
				$bConnectDomainsExist = true;
			}
		}
	}

	$arCreateMailServicesDomains = array();
	$arConnectMailServicesDomains = array();
	$iCreateDomainsCnt = 0;
	$iConnectDomainsCnt = 0;
	$arConnectMailServicesUsers = array();
	$arMailServicesUsers = array();

	foreach ($arMailServices as $service)
	{
		if (in_array($service['type'], array('controller', 'crdomain')))
		{
			if (!empty($service['domains']))
			{
				$arCreateMailServicesDomains[$service['id']] = array();
				foreach ($service['domains'] as $domain)
				{
					if (strlen($domain) > 0)
					{
						$iCreateDomainsCnt++;
					}
					$arCreateMailServicesDomains[$service['id']][] = $domain;

					if (
						is_array($service['users'])
						&& array_key_exists($domain, $service['users'])
						&& !empty($service['users'][$domain])
					)
					{
						$arConnectMailServicesDomains[$service['id']][] = $domain;
						$arMailServicesUsers[$domain] = $service['users'][$domain];
						$iConnectDomainsCnt++;
					}
				}
			}
			elseif (strlen($service['server']) > 0)
			{
				$arCreateMailServicesDomains[$service['id']] = array($service['server']);
				$iCreateDomainsCnt++;
			}
		}
		elseif ($service['type'] == 'domain')
		{
			if (strlen($service['server']) > 0)
			{
				$iCreateDomainsCnt++;
			}

			$arCreateMailServicesDomains[$service['id']] = array($service['server']);

			if (
				is_array($service['users'])
				&& !empty($service['users'])
			)
			{
				$arConnectMailServicesDomains[$service['id']] = array($service['server']);
				$arMailServicesUsers[$service['server']] = $service['users'];
				$iConnectDomainsCnt++;
			}
		}
	}
}

function inviteDialogDrawTabContentHeader($params)
{
	global $APPLICATION;

	$action = $params['action'];
	$iStructureCount = $params['iStructureCount'];
	$iDepartmentID = $params['iDepartmentID'];
	$bExtranetInstalled = $params['bExtranetInstalled'];
	$extranetSiteId = $params['extranetSiteId'];
	$arStructure = $params['arStructure'];

	?><div id="invite-dialog-<?=$action?>-usertype-block-employee" class="invite-dialog-inv-block" style="display: block;"><?=GetMessage(
		'BX24_INVITE_DIALOG_'.($action == 'invite' || $action == 'invite_phone' ? 'INVITE' : 'ADD').'_DEPARTMENT_PATTERN',
		array(
			'#TITLE#' => ($bExtranetInstalled ? '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-usertype-employee-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_EMPLOYEE').'</a>' : GetMessage('BX24_INVITE_DIALOG_EMPLOYEE')),
			'#DEPARTMENT#' => (
			$iStructureCount > 1
				? '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-structure-link" class="invite-dialog-inv-link">'.htmlspecialcharsbx($arStructure["DATA"][$iDepartmentID > 0 ? $iDepartmentID : $arStructure["TREE"][0][0]]["NAME"]).'</a>'
				: htmlspecialcharsbx($arStructure["DATA"][$arStructure["TREE"][0][0]]["NAME"])
			),
			'#SONETGROUP#' => '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-sonetgroup-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_SONETGROUP').'</a>'
		)
	)?></div><?
	?><input name="DEPARTMENT_ID" type="hidden" value="<?=($iDepartmentID > 0 ? $iDepartmentID : $arStructure["DATA"][$arStructure["TREE"][0][0]]["ID"])?>" id="invite-dialog-<?=$action?>-department-id"><?

	if ($bExtranetInstalled)
	{
		?><div id="invite-dialog-<?=$action?>-usertype-block-extranet" class="invite-dialog-inv-block" style="display: none;"><?
		?><?=GetMessage(
		'BX24_INVITE_DIALOG_'.($action == 'invite' || $action == 'invite_phone' ? 'INVITE' : 'ADD').'_GROUP_PATTERN',
		array(
			'#TITLE#' => '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-usertype-extranet-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_EXTRANET').'</a>'
		)
	)?><?
		?></div><?
	}

	$arUserTypeSuffix = array("");
	if ($bExtranetInstalled)
	{
		$arUserTypeSuffix[] = "-extranet";
	}

	foreach($arUserTypeSuffix as $userTypeSuffix)
	{
		$selectorName = $action.$userTypeSuffix.'_'.randString(6);

		?><div id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post" class="invite-dialog-sonetgroup-wrap" style="display: none;" data-selector-name="<?=$selectorName?>"><?
			$APPLICATION->IncludeComponent(
				"bitrix:main.user.selector",
				"",
				[
					"ID" => $selectorName,
					"LAZYLOAD" => 'Y',
					"INPUT_NAME" => 'SONET_GROUPS[]',
					"USE_SYMBOLIC_ID" => true,
					"BUTTON_SELECT_CAPTION" => GetMessage('BX24_INVITE_DIALOG_DEST_LINK_1'),
					"BUTTON_SELECT_CAPTION_MORE" => GetMessage('BX24_INVITE_DIALOG_DEST_LINK_2'),
					'API_VERSION' => '3',
					"SELECTOR_OPTIONS" => array(
						'lazyLoad' => 'Y',
						'context' => 'USER_INVITE',
						'contextCode' => 'SG',
						'enableUsers' => 'N',
						'enableSonetgroups' => 'Y',
						'socNetGroupsSiteId' => ($userTypeSuffix == "-extranet" ? $extranetSiteId : ''),
						'enableDepartments' => 'N',
						'allowAddSocNetGroup' => 'N',
						'departmentSelectDisable' => 'Y'
					)
				]
			);
		?></div><?

	}
}

$APPLICATION->ShowAjaxHead();
CModule::IncludeModule("socialnetwork");
\Bitrix\Main\UI\Extension::load("ui.selector");

$APPLICATION->AddHeadScript("/bitrix/js/intranet/invite-dialog.js");
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/main.post.form/templates/.default/style.css");

?>
<script type="text/javascript">
	BX.message({
		inviteDialogTitleEmployee: '<?=GetMessageJS('BX24_INVITE_DIALOG_EMPLOYEE')?>',
		inviteDialogTitleExtranet: '<?=GetMessageJS('BX24_INVITE_DIALOG_EXTRANET')?>',
		inviteDialogDestLink1: '<?=GetMessageJS('BX24_INVITE_DIALOG_DEST_LINK_1')?>',
		inviteDialogDestLink2: '<?=GetMessageJS('BX24_INVITE_DIALOG_DEST_LINK_2')?>',
		inviteDialogSubmitUrl: '<?=CUtil::JSEscape(BX_ROOT."/tools/intranet_invite_dialog.php")?>'
	});

	var inviteDialogDepartmentPopup = null;

	onInviteDialogSectionsSelect = function(oData)
	{
		var inviteDialogStructureLink = (
			BX.InviteDialog.lastTab == 'invite'
				? inviteDialogInviteStructureLink
				: (
				BX.InviteDialog.lastTab == 'invite-phone'
					? inviteDialogInvitePhoneStructureLink
					: inviteDialogAddStructureLink
				)
		);
		var inviteDialogDepartmentIdField = BX('invite-dialog-' + BX.InviteDialog.lastTab + '-department-id');

		if (
			typeof oData.id != 'undefined'
			&& oData.id != null
			&& typeof oData.name != 'undefined'
		)
		{
			inviteDialogStructureLink.innerHTML = oData.name;
			inviteDialogDepartmentIdField.value = oData.id;

			inviteDialogDepartmentPopup.close();
		}
	}
</script>
<?

$iDepartmentID = (is_array($_POST) && array_key_exists("arParams", $_POST) && is_array($_POST["arParams"]) && array_key_exists("UF_DEPARTMENT", $_POST["arParams"]) ? intval($_POST["arParams"]["UF_DEPARTMENT"]) : 0);
$arStructure = CIntranetUtils::getSubStructure(0, ($iDepartmentID > 0 ? false : 1));
if (!array_key_exists($iDepartmentID, $arStructure["DATA"]))
{
	$iDepartmentID = 0;
}

$iStructureCount = count(CIntranetUtils::GetDeparmentsTree());

CModule::IncludeModule('socialnetwork');

$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", ".default", array(
		"MULTIPLE" => "N",
		"NAME" => "INVITE_DEPARTMENT",
		"VALUE" => 0,
		"POPUP" => "Y",
		"INPUT_NAME" => "UF_DEPARTMENT",
		"ON_SECTION_SELECT" => "onInviteDialogSectionsSelect",
		"SITE_ID" => $SITE_ID,
		"SHOW_STRUCTURE_ONLY" => "Y",
		"SHOW_EXTRANET_USERS" => "NONE"
	), null, array("HIDE_ICONS" => "Y")
);
?>

<div style="min-width: 497px; min-height: 200px; padding-left: 5px; padding-right: 5px; overflow-y: auto;">
	<div class="popup-window-tabs-box" id="intranet-dialog-tabs">
		<div class="webform-round-corners webform-error-block" id="invite-dialog-error-block" style="display: none;">
			<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			<div class="webform-content" id="invite-dialog-error-content"></div>
			<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
		</div>
		<div class="popup-window-tabs">
			<?if (IsModuleInstalled("bitrix24")):?>
			<span class="popup-window-tab<?=(IsModuleInstalled("bitrix24")  ? " popup-window-tab-selected" : "")?>" id="intranet-dialog-tab-self" data-action="self">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_SELF_TITLE')?>
			</span>
			<?endif?>

			<?if (IsModuleInstalled("bitrix24") && \Bitrix\Main\Config\Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y'):?>
			<span class="popup-window-tab" id="intranet-dialog-tab-invite-phone" data-action="invite-phone">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_INVITE_TITLE_PHONE')?>
			</span>
			<?endif?>

			<span class="popup-window-tab<?=(!IsModuleInstalled("bitrix24")  ? " popup-window-tab-selected" : "")?>" id="intranet-dialog-tab-invite" data-action="invite">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_INVITE_TITLE_NEW')?>
			</span>
			<span class="popup-window-tab<?=(($strAction == "add") ? " popup-window-tab-selected" : "")?>" id="intranet-dialog-tab-add" data-action="add">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_ADD_TITLE_NEW')?>
			</span>
			<?if (IsModuleInstalled("bitrix24")):?>
			<span class="popup-window-tab<?=(($strAction == "integrator")  ? " popup-window-tab-selected" : "")?>" id="intranet-dialog-tab-integrator" data-action="integrator">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_INTEGRATOR_TITLE')?>
			</span>
			<?endif?>
		</div>
		<div class="popup-window-tabs-content popup-window-tabs-content-invite">
			<?//fast registration
			if (IsModuleInstalled("bitrix24"))
			{
				$isUserAdmin = CBitrix24::IsPortalAdmin($USER->GetID());
				$registerSettings = array();
				if(\Bitrix\Main\Loader::includeModule("socialservices"))
				{
					$registerSettings = \Bitrix\Socialservices\Network::getRegisterSettings();
				}
				?>
				<div class="popup-window-tab-content<?=(IsModuleInstalled("bitrix24") ? " popup-window-tab-content-selected" : "")?>" id="intranet-dialog-tab-content-self" data-user-type="employee">
					<form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="SELF_DIALOG_FORM">
						<div class="invite-dialog-wrap">
							<div class="invite-dialog-inner">
								<div class = "invite-dialog-inv-text-bold">
									<input type="checkbox" name="allow_register" id="allow_register" value="Y" <?if ($registerSettings["REGISTER"] == "Y") echo "checked"?> onchange="BX('intranet-dialog-tab-content-self-block').style.display = this.checked ? 'block' : 'none'">
									<label for="allow_register"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_ALLOW_N")?></label>
								</div>
								<div class = "invite-dialog-inv-text-bold" style="margin-left: 25px" >
									<?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TEXT_N")?>
								</div>

								<div <?if ($registerSettings["REGISTER"] != "Y"):?>style="display: none"<?endif?> id="intranet-dialog-tab-content-self-block" class="invite-dialog-inv-link-block">
									<?
									$request = \Bitrix\Main\Context::getCurrent()->getRequest();
									$registerUrlBase = ($request->isHttps() ? "https://" : "http://").BX24_HOST_NAME."/?secret=";
									if(strlen($registerSettings["REGISTER_SECRET"]) > 0)
									{
										$registerUrl = $registerUrlBase.urlencode($registerSettings["REGISTER_SECRET"]);
									}
									else
									{
										$registerUrl = $registerUrlBase."yes";
									}
									?>
									<div class = "invite-dialog-inv-text-bold">
										<table class="invite-dialog-form-table">
											<tr>
												<td>
													<?=GetMessage("BX24_INVITE_DIALOG_REGISTER_LINK")?>
												</td>
												<td>
													<a href="javascript:void(0)" onclick="BX.InviteDialog.regenerateSecret('<?=CUtil::JSEscape($registerUrlBase);?>')" class="invite-dialog-update-link"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_NEW_LINK")?></a>
													<span class="bx-hint-help-icon" id="invite-dialog-register-new-link-help" data-text="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_REGISTER_NEW_LINK_HELP"))?>">?</span>
												</td>
											</tr>
										</table>
										<input type="text" class="invite-dialog-inv-form-inp" value="<?=\Bitrix\Main\Text\Converter::getHtmlConverter()->encode($registerUrl)?>" id="allow_register_url" readonly="readonly"/>
										<span class="invite-dialog-copy-link" onclick="BX.InviteDialog.copyRegisterUrl();"><?=GetMessage("BX24_INVITE_DIALOG_COPY_LINK")?></span>
										<input type="hidden" id="allow_register_secret" name="allow_register_secret" value="<?=htmlspecialcharsbx($registerSettings["REGISTER_SECRET"])?>">
									</div>

									<div class = "invite-dialog-inv-text-bold">
										<a href="javascript:void(0)" class="invite-dialog-settings" onclick="BX.InviteDialog.toggleSettings();"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_EXTENDED_SETTINGS")?></a>
									</div>
									<div id="intranet-dialog-tab-content-self-hidden-block" style="display: none;">
										<div class = "invite-dialog-inv-text-bold">
											<p><b><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_N")?></b></p>
											<input
												type="radio"
												name="allow_register_confirm"
												id="allow_register_confirm_y"
												value="N"
												<?if ($registerSettings["REGISTER_CONFIRM"] == "N") echo "checked"?>
												onchange="BX('intranet-dialog-tab-content-self-whitelist').style.display = this.checked ? 'none' : 'block'"
												<?if (!$isUserAdmin) echo "disabled";?>
											/>
											<label for="allow_register_confirm_y"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_OPEN_N")?></label>
											<span class="bx-hint-help-icon" id="invite-dialog-register-open-help" data-text="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_OPEN_HELP"))?>">?</span>
											&nbsp;&nbsp;
											<input
												type="radio"
												name="allow_register_confirm"
												id="allow_register_confirm_n"
												value="Y"
												<?if ($registerSettings["REGISTER_CONFIRM"] == "Y") echo "checked"?>
												onchange="BX('intranet-dialog-tab-content-self-whitelist').style.display = this.checked ? 'block' : 'none'"
												<?if (!$isUserAdmin) echo "disabled";?>
											>
											<label for="allow_register_confirm_n"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_CLOSE_N")?></label>
											<span class="bx-hint-help-icon" id="invite-dialog-register-close-help" data-text="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_CLOSE_HELP"))?>">?</span>
										</div>

										<div class = "invite-dialog-inv-text-bold" id="intranet-dialog-tab-content-self-whitelist" <?if ($registerSettings["REGISTER_CONFIRM"] == "N"):?>style="display: none" <?endif?>>
											<p><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS")?><span class="bx-hint-help-icon" id="invite-dialog-register-domains-help" data-text="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS_HELP"))?>">?</span></p>
											<input type="text" <?if (!$isUserAdmin) echo "disabled";?> class="invite-dialog-inv-form-inp" name="allow_register_whitelist" value="<?= $registerSettings["REGISTER_WHITELIST"]?>" placeholder="<?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS_PLACEHOLDER")?>"/>
										</div>

										<div class = "invite-dialog-inv-text-bold">
											<p><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TEXT_TITLE")?></p>
											<textarea <?if (!$isUserAdmin) echo "disabled";?> name="allow_register_text" placeholder="<?=GetMessage("BX24_INVITE_DIALOG_REGISTER_TEXT_PLACEHOLDER_N_1")?>" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active"><?
												?><?=$registerSettings["REGISTER_TEXT"] ? htmlspecialcharsbx($registerSettings["REGISTER_TEXT"]) : GetMessage("BX24_INVITE_DIALOG_REGISTER_TEXT_PLACEHOLDER_N_1")?><?
											?></textarea>
										</div>

										<?if (\Bitrix\Main\Loader::includeModule("bitrix24")):?>
										<div class="invite-dialog-settings-link"><?=GetMessage("BX24_INVITE_DIALOG_REGISTER_INVITE_ADD_INFO_N", array("#PATH_CONFIGS#" => CBitrix24::PATH_CONFIGS))?></div>
										<?endif?>
									</div>
								</div>
							</div>
						</div>
						<?=bitrix_sessid_post()?>
						<input type="hidden" name="action" value="self">
						<div class="popup-window-buttons">
							<span class="popup-window-button popup-window-button-accept" id="invite-dialog-self-button-submit">
								<span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_ACTION_SAVE")?></span>
							</span>
							<span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-self-button-close">
								<span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span>
							</span>
						</div>
					</form>
				</div>
				<script>
					BX.ready(function(){
						BX.InviteDialog.initHint('invite-dialog-register-new-link-help');
						BX.InviteDialog.initHint('invite-dialog-register-open-help');
						BX.InviteDialog.initHint('invite-dialog-register-close-help');
						BX.InviteDialog.initHint('invite-dialog-register-domains-help');
					});
				</script>
			<?
			}

			//invite by sms
			if(IsModuleInstalled('bitrix24') && \Bitrix\Main\Config\Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y')
			{
			?>
				<div class="popup-window-tab-content" id="intranet-dialog-tab-content-invite-phone" data-user-type="employee">
					<form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="INVITE_DIALOG_FORM_PHONE">
						<div class="invite-dialog-wrap">
							<div class="invite-dialog-inner">
								<?
									inviteDialogDrawTabContentHeader([
										'action' => 'invite-phone',
										'iStructureCount' => $iStructureCount,
										'iDepartmentID' => $iDepartmentID,
										'bExtranetInstalled' => $bExtranetInstalled,
										'extranetSiteId' => $extranetSiteId,
										'arStructure' => $arStructure,
									]);

									\CJSCore::Init(array('phone_number'));
								?>
								<div class="invite-dialog-inv-form-wrap">
									<div class="invite-dialog-inv-form">
										<table class="invite-dialog-inv-form-table">
											<tr>
												<td class="invite-dialog-inv-form-r">
													<label class="invite-dialog-phone-label" for="PHONE[]"><?echo GetMessage("BX24_INVITE_DIALOG_PHONE_SHORT")?></label>
													<div class="invite-dialog-phone-list" id="invite-dialog-phone-list"></div>
													<div class="invite-dialog-phone-list-add-wrap">
														<a href="javascript:void(0)" class="invite-dialog-phone-list-add" onclick="addPhoneRow()"><?=GetMessage('BX24_INVITE_DIALOG_PHONE_ADD')?></a>
													</div>
													<script>
													(function(){
														var count = 0;
														var index = 0;
														var maxCount = 5;
														var inputStack = [];
														window.addPhoneRow = function()
														{
															if(count >= maxCount)
															{
																return;
															}

															BX('invite-dialog-phone-list').appendChild(BX.create(
																'div', {
																	props: {
																		'className': 'invite-dialog-phone-item'
																	},
																	html: '<input type="hidden" name="PHONE_COUNTRY[]" id="phone_country_'+index+'" value=""><input type="hidden" name="PHONE[]" id="phone_number_'+index+'" value=""><div class="invite-dialog-phone-flag-block" onclick="showCountrySelector(' + index + ');"><span id="phone_flag_'+index+'" style="pointer-events: none;"></span></div><input class="invite-dialog-phone-input" type="text" id="phone_input_'+index+'" value="">&nbsp;<span class="invite-dialog-phone-delete" onclick="removePhoneRow(this.parentNode, '+index+')"></span>'
																}
															));

															var changeCallback = function(i)
															{
																return function(e)
																{
																	BX('phone_number_' + i).value = e.value;
																	BX('phone_country_' + i).value = e.country;
																}
															};

															inputStack[index] = new BX.PhoneNumber.Input({
																node: BX('phone_input_' + index),
																flagNode: BX('phone_flag_' + index),
																countryPopupClassName: 'invite-dialog-country-selector',
																flagSize: 16,
																onChange: changeCallback(index)
															});

															index++;
															count++;
														};

														window.showCountrySelector = function(i)
														{
															inputStack[i]._onFlagClick();
														};

														window.removePhoneRow = function(row, i)
														{
															if(count > 1)
															{
																inputStack[i] = null;
																row.parentNode.removeChild(row);
																count--;
															}
														};

														addPhoneRow();
													})();
													</script>
												</td>
											</tr>
										</table>
									</div>
									<?
									$messageTextDisabled = true; /*(
										\Bitrix\Main\Loader::includeModule('bitrix24')
										&& (
											!CBitrix24::IsLicensePaid()
											|| CBitrix24::IsDemoLicense()
										)
										&& !CBitrix24::IsNfrLicense()
											? " disabled readonly"
											: ""
									);*/

									?>
									<div class="invite-dialog-inv-text">
										<div class="invite-dialog-inv-text-bold"><label class="invite-dialog-inv-label" for="MESSAGE_TEXT"><?echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TITLE")?></label></div><?
											if (!$messageTextDisabled)
										{
										?><textarea type="text" name="MESSAGE_TEXT" id="MESSAGE_TEXT" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active" <?=$messageTextDisabled?>><?
											if (isset($_POST["MESSAGE_TEXT"]))
											{
												echo htmlspecialcharsbx($_POST["MESSAGE_TEXT"]);
											}
											elseif ($userMessage = CUserOptions::GetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text"))
											{
												echo htmlspecialcharsbx($userMessage);
											}
										?></textarea><?
										}
										else
										{
											?><div class="invite-dialog-inv-text-bold invite-dialog-inv-text-field">
												<?=GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_PHONE", ['#PORTAL#' => BX24_HOST_NAME, '#URL#' => 'https://b24.to/i/xxxxx/xxxxxxxx/']);?>
												<input type="hidden" name="MESSAGE_TEXT" value="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_PHONE", ['#PORTAL#' => BX24_HOST_NAME, '#URL#' => 'https://b24.to/i/xxxxx/xxxxxxxx/']))?>">
											</div>
											<?
										}
										?>
									</div>
								</div>
							</div>
						</div>
						<?=bitrix_sessid_post()?>
						<input type="hidden" name="action" value="invite-phone">
						<div class="popup-window-buttons">
							<span class="popup-window-button popup-window-button-accept" id="invite-dialog-invite-phone-button-submit">
								<span class="popup-window-button-left"></span>
								<span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_INVITE")?></span>
								<span class="popup-window-button-right"></span>
							</span>
							<span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-invite-phone-button-close">
								<span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span>
							</span>
						</div>
					</form>
				</div>
			<?
			}
			?>

			<!-- invite by email-->
			<div
				class="popup-window-tab-content<?=(!IsModuleInstalled("bitrix24") ? " popup-window-tab-content-selected" : "")?>"
				id="intranet-dialog-tab-content-invite"
				data-user-type="employee"
			>
				<form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="INVITE_DIALOG_FORM">
					<div class="invite-dialog-wrap">
						<div class="invite-dialog-inner">
						<?
						inviteDialogDrawTabContentHeader(array(
							'action' => 'invite',
							'iStructureCount' => $iStructureCount,
							'iDepartmentID' => $iDepartmentID,
							'bExtranetInstalled' => $bExtranetInstalled,
							'extranetSiteId' => $extranetSiteId,
							'arStructure' => $arStructure,
						));
						?>
						<div class="invite-dialog-inv-form">
							<table class="invite-dialog-inv-form-table">
								<tr>
									<td class="invite-dialog-inv-form-l" style="vertical-align: top;">
										<label for="EMAIL"><?echo GetMessage("BX24_INVITE_DIALOG_EMAIL_SHORT")?></label>
									</td>
									<td class="invite-dialog-inv-form-r">
										<textarea
											type="text"
											name="EMAIL"
											id="EMAIL"
											class="invite-dialog-inv-form-textarea"
											placeholder="<?=GetMessage("BX24_INVITE_DIALOG_EMAILS_DESCR")?>"
										><?=(strlen($_POST["EMAIL"]) > 0 ? htmlspecialcharsbx($_POST["EMAIL"]) : "");?></textarea>
									</td>
								</tr>
							</table>
						</div>
					<?
					$messageTextDisabled = (
						\Bitrix\Main\Loader::includeModule('bitrix24')
						&& (
							!CBitrix24::IsLicensePaid()
							|| CBitrix24::IsDemoLicense()
						)
						&& !CBitrix24::IsNfrLicense()
							? " disabled readonly"
							: ""
					);
					?>
					<div class="invite-dialog-inv-text-bold">
						<label for="MESSAGE_TEXT"><?echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TITLE")?></label>
					</div><?
					if (!$messageTextDisabled)
					{
						$userMessage = CUserOptions::GetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text", GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1"))

						?><textarea type="text" name="MESSAGE_TEXT" id="MESSAGE_TEXT" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active" <?=$messageTextDisabled?>><?
							if (isset($_POST["MESSAGE_TEXT"]))
							{
								echo htmlspecialcharsbx($_POST["MESSAGE_TEXT"]);
							}
							elseif ($userMessage)
							{
								echo htmlspecialcharsbx($userMessage);
							}
						?></textarea><?
						?><input type="hidden" name="MESSAGE_TEXT_DEFAULT" value="<?=htmlspecialcharsbx(GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1"))?>"><?
					}
					else
					{
						?><div style="width: 500px;" class="invite-dialog-inv-text-bold">
							<?=GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1");?>
							<input type="hidden" name="MESSAGE_TEXT" value="<?=GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT_1")?>">
						</div><?
					}
					?>
						</div>
					</div>
					<?=bitrix_sessid_post()?>
					<input type="hidden" name="action" value="invite">
					<div class="popup-window-buttons">
						<span class="popup-window-button popup-window-button-accept" id="invite-dialog-invite-button-submit">
							<span class="popup-window-button-left"></span>
							<span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_INVITE")?></span>
							<span class="popup-window-button-right"></span>
						</span>
						<span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-invite-button-close">
							<span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span>
						</span>
					</div>
				</form>
			</div>

			<!-- add by email-->
			<div class="popup-window-tab-content<?=(in_array($strAction, array("add")) ? " popup-window-tab-content-selected" : "")?>" id="intranet-dialog-tab-content-add" data-user-type="employee">
				<form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="ADD_DIALOG_FORM" name="ADD_DIALOG_FORM">
					<div class="invite-dialog-wrap">
						<div class="invite-dialog-inner">
							<?
							inviteDialogDrawTabContentHeader(array(
								'action' => 'add',
								'iStructureCount' => $iStructureCount,
								'iDepartmentID' => $iDepartmentID,
								'bExtranetInstalled' => $bExtranetInstalled,
								'extranetSiteId' => $extranetSiteId,
								'arStructure' => $arStructure,
							));
							?>
							<div class="invite-dialog-inv-form">
								<table class="invite-dialog-inv-form-table">
									<tr>
										<td class="invite-dialog-inv-form-l">
											<label for="ADD_EMAIL"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_EMAIL_TITLE")?></label>
										</td>
										<td class="invite-dialog-inv-form-r"><?
											?><input type="text" name="ADD_EMAIL" id="ADD_EMAIL" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_EMAIL"])?>"><?
											if (
												!empty($arMailServices)
												&& (
													$bCreateDomainsExist
													|| $bConnectDomainsExist
													|| $bDomainUsersExist
												)
											)
											{
											?>
												<div id="invite-dialog-mailbox-container" class="invite-dialog-box-info-set invite-dialog-box-info-set-inactive">
													<div class="invite-dialog-box-info-block">
														<?
														if ($bCreateDomainsExist)
														{
															?><span id="invite-dialog-mailbox-action-create" onclick="BX.InviteDialog.onMailboxAction('create');" class="invite-dialog-box-info-btn"><?
															?><span class="invite-dialog-box-info-btn-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_CREATE')?></span><?
															?></span><?
														}

														if (
															$bConnectDomainsExist
															&& $bDomainUsersExist
														)
														{
															?>
															<span class="invite-dialog-box-info-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_OR')?></span>
															<span id="invite-dialog-mailbox-action-connect" onclick="BX.InviteDialog.onMailboxAction('connect');" class="invite-dialog-box-info-btn">
																<span class="invite-dialog-box-info-btn-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_CONNECT')?></span>
															</span>
															<?
														}
													?>
													</div>
												<?
												if (
													$bCreateDomainsExist
													|| $bConnectDomainsExist
												)
												{
												?>
													<div id="invite-dialog-mailbox-content-create" style="display: none;">
														<div class="invite-dialog-box-info-block invite-dialog-box-info-block-body">
															<span class="invite-dialog-box-info-left">
																<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_NAME')?></span>
																<input type="text" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_USER_create" name="ADD_MAILBOX_USER">
															</span>
															<span class="invite-dialog-box-info-right">
																<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_DOMAIN')?></span>
																<?
																if ($iCreateDomainsCnt > 1)
																{
																	?><select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_DOMAIN_create" name="ADD_MAILBOX_DOMAIN"><?
																	foreach($arCreateMailServicesDomains as $serviceID => $arDomainsTmp)
																	{
																		if (
																			is_array($arDomainsTmp)
																			&& !empty($arDomainsTmp)
																		)
																		{
																			foreach ($arDomainsTmp as $strDomain)
																			{
																				?><option value="<?=$strDomain?>" data-service-id="<?=$serviceID?>">@<?=$strDomain?></option><?
																			}
																		}
																	}
																	?></select><?
																}
																else
																{
																	foreach($arCreateMailServicesDomains as $serviceID => $arDomainsTmp)
																	{
																		?><input type="hidden" id="ADD_MAILBOX_SERVICE_create" name="ADD_MAILBOX_SERVICE" value="<?=$serviceID?>"><?
																		break;
																	}
																	?><input type="hidden" id="ADD_MAILBOX_DOMAIN_create" name="ADD_MAILBOX_DOMAIN" value="<?=$arCreateMailServicesDomains[$serviceID][0]?>"><?
																	?><div class="invite-dialog-inv-form-hidden-text">@<?=$arCreateMailServicesDomains[$serviceID][0]?></div><?
																}
																?>
															</span>
														</div>
														<div class="invite-dialog-box-info-block invite-dialog-box-info-block-body">
															<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_PASSWORD')?></span>
															<input type="password" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_PASSWORD" name="ADD_MAILBOX_PASSWORD">
														</div>
														<div class="invite-dialog-box-info-block invite-dialog-box-info-block-body">
															<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_PASSWORD_CONFIRM')?></span>
															<input type="password" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_PASSWORD_CONFIRM" name="ADD_MAILBOX_PASSWORD_CONFIRM">
														</div>
													</div>
												<?
												}

												if (
													$bConnectDomainsExist
													&& $bDomainUsersExist
												)
												{
													?>
													<div id="invite-dialog-mailbox-content-connect" style="display: none;">
														<div class="invite-dialog-box-info-block invite-dialog-box-info-block-body">
															<span class="invite-dialog-box-info-left">
																<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_SELECT')?></span>

																<script>
																	var arMailServicesUsers = [];
																	var arConnectMailServicesDomains = [];
																	<?
																	foreach($arConnectMailServicesDomains as $serviceID => $arDomainsTmp)
																	{
																	?>
																		arConnectMailServicesDomains[<?=$serviceID?>] = '<?=$arConnectMailServicesDomains[$serviceID][0]?>';
																	<?
																	}
																	?>
																	arMailServicesUsers = [];
																	<?
																	foreach ($arMailServicesUsers as $domain => $arUsersTmp)
																	{
																		if (
																			is_array($arUsersTmp)
																			&& !empty($arUsersTmp)
																		)
																		{
																			?>
																			arMailServicesUsers['<?=$domain?>'] = [];
																			<?
																			foreach ($arUsersTmp as $strUser)
																			{
																				?>
																			arMailServicesUsers['<?=$domain?>'].push('<?=$strUser?>');
																			<?
																			}
																		}
																	}
																?>
																</script>

																<select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_USER_connect" name="ADD_MAILBOX_USER">
																<?
																foreach($arMailServicesUsers as $domain => $arUsersTmp)
																{
																	if (
																		is_array($arUsersTmp)
																		&& !empty($arUsersTmp)
																	)
																	{
																		foreach ($arUsersTmp as $strUser)
																		{
																			?><option value="<?=$strUser?>"><?=$strUser?></option><?
																		}
																	}
																	break;
																}
																?>
																</select>
															</span>
													<span class="invite-dialog-box-info-right">
														<span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_DOMAIN')?></span><?
														if ($iConnectDomainsCnt > 1)
														{
															?><select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_DOMAIN_connect" name="ADD_MAILBOX_DOMAIN" onchange="BX.InviteDialog.onMailboxServiceSelect(this);"><?
															foreach($arConnectMailServicesDomains as $serviceID => $arDomainsTmp)
															{
																if (
																	is_array($arDomainsTmp)
																	&& !empty($arDomainsTmp)
																)
																{
																	foreach ($arDomainsTmp as $strDomain)
																	{
																		?><option value="<?=$strDomain?>" data-service-id="<?=$serviceID?>" data-domain="<?=$strDomain?>">@<?=$strDomain?></option><?
																	}
																}
															}
															?></select><?
														}
														else
														{
															foreach($arConnectMailServicesDomains as $serviceID => $arDomainsTmp)
															{
																?><input type="hidden" id="ADD_MAILBOX_SERVICE_connect" name="ADD_MAILBOX_SERVICE" value="<?=$serviceID?>"><?
																break;
															}
															?><input type="hidden" id="ADD_MAILBOX_DOMAIN_connect" name="ADD_MAILBOX_DOMAIN" value="<?=$arConnectMailServicesDomains[$serviceID][0]?>"><?
															?><div class="invite-dialog-inv-form-hidden-text">@<?=$arConnectMailServicesDomains[$serviceID][0]?></div><?
														}
														?>
													</span>
													</div>
													</div><?
												}
												?>
													<div class="invite-dialog-box-info-block invite-dialog-box-info-block-body">
														<span class="invite-dialog-box-info-close-open invite-dialog-box-info-open" onclick="BX.InviteDialog.onMailboxRollup();"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ROLLUP')?></span>
													</div>
												</div>
												<input type="hidden" name="ADD_MAILBOX_ACTION" id="ADD_MAILBOX_ACTION" value=""><?
											}
											?>
										</td>
									</tr>
									<tr>
										<td class="invite-dialog-inv-form-l">
											<label for="ADD_NAME"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE")?></label>
										</td>
										<td class="invite-dialog-inv-form-r">
											<input type="text" name="ADD_NAME" id="ADD_NAME" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_NAME"])?>">
										</td>
									</tr>
									<tr>
										<td class="invite-dialog-inv-form-l">
											<label for="ADD_LAST_NAME"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE")?></label>
										</td>
										<td class="invite-dialog-inv-form-r">
											<input type="text" name="ADD_LAST_NAME" id="ADD_LAST_NAME" class="invite-dialog-inv-form-inp" value="<?=htmlspecialcharsbx($_POST["ADD_LAST_NAME"])?>">
										</td>
									</tr>
									<tr class="invite-dialog-inv-form-footer">
										<td class="invite-dialog-inv-form-l">
											<label for="ADD_POSITION"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_POSITION_TITLE")?></label>
										</td>
										<td class="invite-dialog-inv-form-r">
											<input type="text" name="ADD_POSITION" id="ADD_POSITION" class="invite-dialog-inv-form-inp" value="<?=htmlspecialcharsbx($_POST["ADD_POSITION"])?>">
										</td>
									</tr>
									<tr>
										<td class="invite-dialog-inv-form-l">&nbsp;</td>
										<td class="invite-dialog-inv-form-r"><?
											?><div class="invite-dialog-inv-form-checkbox-wrap"><?
												?><input type="checkbox" name="ADD_SEND_PASSWORD" id="ADD_SEND_PASSWORD" value="Y" class="invite-dialog-inv-form-checkbox"<?=($_POST["ADD_SEND_PASSWORD"] == "Y" ? " checked" : "")?><?=(empty($_POST["ADD_EMAIL"]) ? " disabled" : "")?>><?
												?><label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE")?><span id="ADD_SEND_PASSWORD_EMAIL"></span></label><?
												?></div>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>

					<?=bitrix_sessid_post()?>
					<input type="hidden" name="action" value="add">
					<div class="popup-window-buttons">
						<span class="popup-window-button popup-window-button-accept" id="invite-dialog-add-button-submit">
							<span class="popup-window-button-left"></span>
							<span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_ADD")?></span>
							<span class="popup-window-button-right"></span>
						</span>
						<span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-add-button-close">
							<span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span>
						</span>
					</div>
				</form>
			</div>

			<? // integrator tab
			if (IsModuleInstalled("bitrix24"))
			{
			?>
				<div class="popup-window-tab-content<?=(in_array($strAction, array("integrator")) ? " popup-window-tab-content-selected" : "")?>" id="intranet-dialog-tab-content-integrator" data-user-type="itegrator">
					<form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="INTEGRATOR_DIALOG_FORM" name="INTEGRATOR_DIALOG_FORM">
						<div class="invite-dialog-wrap">
							<div class="invite-dialog-inner">
								<?=bitrix_sessid_post()?>
								<input type="hidden" name="action" value="integrator">

								<div class="invite-dialog-inv-form">
									<table class="invite-dialog-inv-form-table">
										<tr>
											<td class="invite-dialog-inv-form-l" style="vertical-align: top;" colspan="2">
												<?=GetMessage("BX24_INVITE_DIALOG_INTEGRATOR_TEXT")?>
												<a href="javascript:void(0)" onclick='top.BX.Helper.show("redirect=detail&code=7725333");'><?=GetMessage("BX24_INVITE_DIALOG_INTEGRATOR_MORE")?></a>
											</td>
										</tr>
										<tr>
											<td class="invite-dialog-inv-form-l" style="vertical-align: top;">
												<label for="integrator_email">Email</label>
											</td>
											<td class="invite-dialog-inv-form-r">
												<input type="text" class="invite-dialog-inv-form-inp" value="" name="integrator_email" id="integrator_email">
											</td>
										</tr>
									</table>
								</div>

								<div class="invite-dialog-inv-text-bold"><label for="INTEGRATOR_MESSAGE_TEXT"><?echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TITLE")?></label></div>
								<?if (!$messageTextDisabled):?>
									<textarea type="text" name="INTEGRATOR_MESSAGE_TEXT" id="INTEGRATOR_MESSAGE_TEXT" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active" <?=$messageTextDisabled?>><?
										if (isset($_POST["INTEGRATOR_MESSAGE_TEXT"]))
										{
											echo htmlspecialcharsbx($_POST["INTEGRATOR_MESSAGE_TEXT"]);
										}
										elseif ($userMessage = CUserOptions::GetOption("bitrix24", "integrator_message_text", GetMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT")))
										{
											echo htmlspecialcharsbx($userMessage);
										}
										?></textarea>
								<?else:?>
									<div style="width: 500px;" class="invite-dialog-inv-text-bold">
										<?echo GetMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT");?>
									</div>
								<?endif?>
							</div>
						</div>
						<div class="popup-window-buttons">
							<span class="popup-window-button popup-window-button-accept" id="invite-dialog-integrator-button-submit">
								<?=GetMessage("BX24_INVITE_DIALOG_BUTTON_INVITE")?>
							</span>
							<span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-integrator-button-close">
								<?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?>
							</span>
						</div>
					</form>
				</div>
			<?
			}
			?>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.message({
		BX24_INVITE_DIALOG_CONTINUE_INVITE_BUTTON: "<?=GetMessageJS("BX24_INVITE_DIALOG_CONTINUE_INVITE_BUTTON")?>",
		BX24_INVITE_DIALOG_CONTINUE_ADD_BUTTON: "<?=GetMessageJS("BX24_INVITE_DIALOG_CONTINUE_ADD_BUTTON")?>",
		BX24_INVITE_DIALOG_COPY_URL: "<?=GetMessageJS("BX24_INVITE_DIALOG_COPY_URL")?>",
		BX24_INVITE_DIALOG_USERS_LIMIT_TITLE: "<?=GetMessageJS("BX24_INVITE_DIALOG_USERS_LIMIT_TITLE")?>",
		BX24_INVITE_DIALOG_USERS_LIMIT_TEXT: "<?=GetMessageJS("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT", array(
			"#NUM#" => COption::GetOptionString("main", "PARAM_MAX_USERS")))?>"
	});

	var inviteDialogInviteStructureLink = BX("invite-dialog-invite-structure-link");
	var inviteDialogInvitePhoneStructureLink = BX("invite-dialog-invite-phone-structure-link");
	var inviteDialogAddStructureLink = BX("invite-dialog-add-structure-link");

	var arTabs = BX.findChildren(BX('intranet-dialog-tabs'), {className: 'popup-window-tab'}, true);
	var arTabsContent = BX.findChildren(BX('intranet-dialog-tabs'), {className: 'popup-window-tab-content'}, true);

	BX.ready(function() {

		var departmentPopup = BX.PopupWindowManager.getPopupById("invite-dialog-department-popup");
		if (departmentPopup)
		{
			departmentPopup.destroy();
		}

		BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-usertype-employee-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
		BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-usertype-extranet-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
		BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-add-usertype-employee-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
		BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-add-usertype-extranet-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
		if (BX("intranet-dialog-tab-invite-phone"))
		{
			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-phone-usertype-employee-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-phone-usertype-extranet-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
		}

		BX.InviteDialog.bindInviteDialogSonetGroupLink(BX("invite-dialog-invite-sonetgroup-link"));
		BX.InviteDialog.bindInviteDialogSonetGroupLink(BX("invite-dialog-add-sonetgroup-link"));
		if (BX("intranet-dialog-tab-invite-phone"))
		{
			BX.InviteDialog.bindInviteDialogSonetGroupLink(BX("invite-dialog-invite-phone-sonetgroup-link"));
		}

		<?if ($iStructureCount > 1):?>
			BX.InviteDialog.bindInviteDialogStructureLink(BX("invite-dialog-invite-structure-link"));
			BX.InviteDialog.bindInviteDialogStructureLink(BX("invite-dialog-add-structure-link"));
			if (BX("intranet-dialog-tab-invite-phone"))
			{
				BX.InviteDialog.bindInviteDialogStructureLink(BX("invite-dialog-invite-phone-structure-link"));
			}
		<?endif;?>

		if (BX("intranet-dialog-tab-self"))
		{
			BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-self"));
		}
		BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-invite"));
		BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-add"));
		if (BX("intranet-dialog-tab-integrator"))
		{
			BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-integrator"));
		}
		if (BX("intranet-dialog-tab-invite-phone"))
		{
			BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-invite-phone"));
		}

		if (BX("invite-dialog-self-button-submit"))
		{
			BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-self-button-submit"));
		}
		BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-invite-button-submit"));
		BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-add-button-submit"));
		if (BX("invite-dialog-integrator-button-submit"))
		{
			BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-integrator-button-submit"));
		}
		if (BX("invite-dialog-invite-phone-button-submit"))
		{
			BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-invite-phone-button-submit"));
		}

		if (BX("invite-dialog-self-button-close"))
		{
			BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-self-button-close"));
		}
		BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-invite-button-close"));
		BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-add-button-close"));
		if (BX("invite-dialog-integrator-button-close"))
		{
			BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-invite-phone-button-close"));
		}
		if (BX("invite-dialog-invite-phone-button-close"))
		{
			BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-integrator-button-close"));
		}

		BX.InviteDialog.bindSendPasswordEmail();

		BX.InviteDialog.sonetGroupSelector = BX('invite-dialog-invite-sonetgroup-container-post').getAttribute('data-selector-name');

		BX.InviteDialog.Init({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
		});
	});
</script>