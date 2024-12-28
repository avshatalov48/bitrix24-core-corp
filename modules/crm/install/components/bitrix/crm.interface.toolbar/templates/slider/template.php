<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var array $arParams */
global $APPLICATION;
CJSCore::RegisterExt('popup_menu', [
	'js' => [
		'/bitrix/js/main/popup_menu.js',
	],
]);
Extension::load('crm.client-selector');
Extension::load('ui.buttons');
Extension::load('ui.buttons.icons');

$toolbarID = $arParams['TOOLBAR_ID'];
$prefix =  $toolbarID.'_';

$items = array();
$moreItems = array();
$restAppButtons = array();
$communicationPanel = null;
$documentButton = null;
$enableMoreButton = false;

foreach($arParams['BUTTONS'] as $item)
{
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true)
	{
		$enableMoreButton = true;
		continue;
	}

	if(isset($item['TYPE']) && $item['TYPE'] === 'crm-communication-panel')
	{
		$communicationPanel = $item;
		continue;
	}

	if(isset($item['TYPE']) && $item['TYPE'] === 'crm-document-button')
	{
		$documentButton = $item;
		continue;
	}

	if(isset($item['TYPE']) && $item['TYPE'] === 'rest-app-toolbar')
	{
		$restAppButtons[] = $item;
		continue;
	}

	if($enableMoreButton)
	{
		$moreItems[] = $item;
	}
	else
	{
		$items[] = $item;
	}
}

$this->SetViewTarget('inside_pagetitle', 10000);

?><div id="<?=htmlspecialcharsbx($toolbarID)?>" class="pagetitle-container pagetitle-align-right-container crm-pagetitle-btn-box"><?

$bindingMenuMask = '/(lead|deal|invoice|quote|company|contact).*?([\d]+)/i';
if (preg_match($bindingMenuMask, $arParams['TOOLBAR_ID'], $bindingMenuMatches) &&
	\Bitrix\Main\Loader::includeModule('intranet'))
{
	Extension::load('bizproc.script');
	$APPLICATION->includeComponent(
		'bitrix:intranet.binding.menu',
		'',
		[
			'SECTION_CODE' => \Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode::DETAIL,
			'MENU_CODE' => $bindingMenuMatches[1],
			'CONTEXT' => [
				'ID' => $bindingMenuMatches[2],
			],
		]
	);
}

if($communicationPanel)
{
	$data = isset($communicationPanel['DATA']) && is_array($communicationPanel['DATA']) ? $communicationPanel['DATA'] : array();
	$multifields = isset($data['MULTIFIELDS']) && is_array($data['MULTIFIELDS']) ? $data['MULTIFIELDS'] : array();

	$enableCall = !(isset($data['ENABLE_CALL']) && $data['ENABLE_CALL'] === false);

	$phones = isset($multifields['PHONE']) && is_array($multifields['PHONE']) ? $multifields['PHONE'] : array();
	$emails = isset($multifields['EMAIL']) && is_array($multifields['EMAIL']) ? $multifields['EMAIL'] : array();
	$messengers = isset($multifields['IM']) && is_array($multifields['IM']) ? $multifields['IM'] : array();

	$callButtonId = "{$toolbarID}_call" ;
	$messengerButtonId = "{$toolbarID}_messenger" ;
	$emailButtonId = "{$toolbarID}_email" ;

	$ownerInfo = isset($data['OWNER_INFO']) && is_array($data['OWNER_INFO']) ? $data['OWNER_INFO'] : array();
	$entityTypeName = $ownerInfo['ENTITY_TYPE_NAME'] ?? null;
	?>
	<div class="crm-entity-actions-container">
		<?if(!$enableCall || empty($phones))
		{
			if (empty($phones))
			{
				$title = null;
				if ($entityTypeName)
				{
					$title = Loc::getMessage('CRM_TOOLBAR_ADD_CLIENT_FOR_CALL_' . $entityTypeName);
				}

				if (!$title)
				{
					$title = Loc::getMessage('CRM_TOOLBAR_ADD_CLIENT_FOR_CALL');
				}
			}
			else
			{
				$title = Loc::getMessage('CRM_TOOLBAR_INSTALL_CALENDAR_FOR_CALL');
			}
			?>
				<div
					id="<?= htmlspecialcharsbx($callButtonId) ?>"
					class="ui-btn ui-btn-light-border ui-btn-icon-phone-call ui-btn-disabled ui-btn-themes"
					title="<?= htmlspecialcharsbx($title) ?>"
				></div>
			<?
		}
		else
		{
			?><div id="<?=htmlspecialcharsbx($callButtonId)?>" class="ui-btn ui-btn-light-border ui-btn-icon-phone-call ui-btn-themes"></div><?
		}?>
		<script>
			BX.ready(
				function()
				{
					BX.InterfaceToolBarPhoneButton.messages =
						{
							telephonyNotSupported: "<?=GetMessageJS('CRM_TOOLBAR_TELEPHONY_NOT_SUPPORTED')?>"
						};
					BX.InterfaceToolBarPhoneButton.create(
						this._id + "_call",
						{
							button: BX("<?=CUtil::JSEscape($callButtonId)?>"),
							data: <?=CUtil::PhpToJSObject($phones)?>,
							ownerInfo: <?= CUtil::PhpToJSObject($ownerInfo) ?>,
							useClientSelector: true,
						}
					);
				}
			);
		</script>
		<!--<div class="webform-small-button webform-small-button-transparent crm-contact-menu-sms-icon-not-available"></div>-->
		<?if(empty($emails))
		{
			$title = null;
			if ($entityTypeName)
			{
				$title = Loc::getMessage('CRM_TOOLBAR_ADD_CLIENT_FOR_EMAIL_SEND_' . $entityTypeName);
			}

			if (!$title)
			{
				$title = Loc::getMessage('CRM_TOOLBAR_ADD_CLIENT_FOR_EMAIL_SEND');
			}

			?>
			<div
				id="<?= htmlspecialcharsbx($emailButtonId) ?>"
				class="ui-btn ui-btn-light-border ui-btn-icon-mail ui-btn-disabled ui-btn-themes"
				title="<?= htmlspecialcharsbx($title) ?>"
			></div>
			<?
		}
		else
		{
			?><div id="<?=htmlspecialcharsbx($emailButtonId)?>" class="ui-btn ui-btn-light-border ui-btn-icon-mail ui-btn-themes"></div><?
		}?>
		<script>
			BX.ready(
				function()
				{
					BX.InterfaceToolBarEmailButton.create(
						this._id + "_email",
						{
							button: BX("<?=CUtil::JSEscape($emailButtonId)?>"),
							data: <?=CUtil::PhpToJSObject($emails)?>,
							ownerInfo: <?= CUtil::PhpToJSObject($ownerInfo) ?>,
							useClientSelector: true,
						}
					);
				}
			);
		</script>
		<?if(empty($messengers))
		{
			?><div id="<?=htmlspecialcharsbx($messengerButtonId)?>" class="ui-btn ui-btn-light-border ui-btn-icon-chat ui-btn-disabled ui-btn-themes"></div><?
		}
		else
		{
			?><div id="<?=htmlspecialcharsbx($messengerButtonId)?>" class="ui-btn ui-btn-light-border ui-btn-icon-chat ui-btn-themes"></div><?
		}?>
		<script>
			BX.ready(
				function()
				{
					BX.InterfaceToolBarMessengerButton.create(
						this._id + "_im",
						{
							button: BX("<?=CUtil::JSEscape($messengerButtonId)?>"),
							data: <?=CUtil::PhpToJSObject($messengers)?>,
							ownerInfo: <?=CUtil::PhpToJSObject($ownerInfo)?>
						}
					);
				}
			);
		</script>
	</div>
	<?
}
if($enableMoreButton)
{
	?><button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting crm-entity-actions-button-margin-left ui-btn-themes"></button>
	<script>
		BX.ready(
			function ()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarID)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
							"items": <?=CUtil::PhpToJSObject($moreItems)?>,
							"moreButtonClassName": "ui-btn-icon-setting"
						}
					)
				);
			}
		);
	</script><?
}

if($documentButton)
{
	$documentButtonId = $toolbarID.'_document';
	?>
	<button class="ui-btn ui-btn-md ui-btn-light-border ui-btn-dropdown ui-btn-themes crm-btn-dropdown-document" id="<?=htmlspecialcharsbx($documentButtonId);?>"><?=$documentButton['TEXT'];?></button>
	<script>
		BX.ready(function()
		{
			if(BX.DocumentGenerator && BX.DocumentGenerator.Button)
			{
				var button = new BX.DocumentGenerator.Button('<?=htmlspecialcharsbx($documentButtonId);?>', <?=CUtil::PhpToJSObject($documentButton['PARAMS']);?>);
				button.init();
			}
			else
			{
				console.warn('BX.DocumentGenerator.Button is not found')
			}
		});
	</script>
	<?
}

foreach($items as $item)
{
	$type = isset($item['TYPE']) ? $item['TYPE'] : '';
	$code = isset($item['CODE']) ? $item['CODE'] : '';
	$visible = isset($item['VISIBLE']) ? (bool)$item['VISIBLE'] : true;
	$text = isset($item['TEXT']) ? htmlspecialcharsbx(strip_tags($item['TEXT'])) : '';
	$title = isset($item['TITLE']) ? htmlspecialcharsbx(strip_tags($item['TITLE'])) : '';
	$link = isset($item['LINK']) ? htmlspecialcharsbx($item['LINK']) : '#';
	$icon = isset($item['ICON']) ? htmlspecialcharsbx($item['ICON']) : '';
	$onClick = isset($item['ONCLICK']) ? htmlspecialcharsbx($item['ONCLICK']) : '';

	if($type === 'crm-context-menu')
	{
		$menuItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : array();

		?><div class="webform-small-button webform-small-button-blue webform-button-icon-triangle-down crm-btn-toolbar-menu"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>>
		<span class="webform-small-button-text"><?=$text?></span>
		<span class="webform-button-icon-triangle"></span>
	</div><?

		if(!empty($menuItems))
		{
			?><script>
			BX.ready(
				function()
				{
					BX.InterfaceToolBar.create(
						"<?=CUtil::JSEscape($toolbarID)?>",
						BX.CrmParamBag.create(
							{
								"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
								"prefix": "",
								"menuButtonClassName": "crm-btn-toolbar-menu",
								"items": <?=CUtil::PhpToJSObject($menuItems)?>
							}
						)
					);
				}
			);
		</script><?
		}
	}
	elseif($type == 'toolbar-conv-scheme')
	{
		$params = isset($item['PARAMS']) ? $item['PARAMS'] : array();

		$containerID = $params['CONTAINER_ID'] ?? null; //not used now, but can be useful later
		$labelID = $params['LABEL_ID'] ?? null;
		$buttonID = $params['BUTTON_ID'] ?? null;
		$typeID = isset($params['TYPE_ID']) ? (int)$params['TYPE_ID'] : 0;
		$schemeName = isset($params['SCHEME_NAME']) ? $params['SCHEME_NAME'] : null;
		$schemeDescr = isset($params['SCHEME_DESCRIPTION']) ? $params['SCHEME_DESCRIPTION'] : null;
		$name = isset($params['NAME']) ? $params['NAME'] : $code;
		$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$isPermitted = isset($params['IS_PERMITTED']) ? (bool)$params['IS_PERMITTED'] : false;
		$lockScript = isset($params['LOCK_SCRIPT']) ? $params['LOCK_SCRIPT'] : '';

		$hintKey = 'enable_'.mb_strtolower($name).'_hint';
		$hint = isset($params['HINT']) ? $params['HINT'] : array();

		$enableHint = !empty($hint);
		if($enableHint)
		{
			$options = CUserOptions::GetOption("crm.interface.toobar", "conv_scheme_selector", array());
			$enableHint = !(isset($options[$hintKey]) && $options[$hintKey] === 'N');
		}

		$iconBtnClassName = $isPermitted ? 'crm-btn-convert' : 'crm-btn-convert crm-btn-convert-blocked';
		$originUrl = $APPLICATION->GetCurPage();

		$labelID = empty($labelID) ? "{$prefix}{$code}_label" : $labelID;
		$buttonID = empty($buttonID) ? "{$prefix}{$code}_button" : $buttonID;

		if($isPermitted && $entityTypeID === CCrmOwnerType::Lead)
		{
			Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/crm.js');
		}

		?><div class="ui-btn-split ui-btn-primary">
		<button id="<?=htmlspecialcharsbx($labelID);?>" class="ui-btn-main"><?=htmlspecialcharsbx($schemeDescr)?></button>
		<button id="<?=htmlspecialcharsbx($buttonID);?>" class="ui-btn-extra"></button>
	</div>
		<script>
			BX.ready(
				function()
				{
					//region Toolbar script
					<?$selectorID = CUtil::JSEscape($name);?>
					<?$originUrl = CUtil::JSEscape($originUrl);?>
					<?if($isPermitted):?>
					<?if($entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Deal):?>
					<?php //everything now is initialized in crm.lead.details/crm.deal.details ?>
					<?elseif($entityTypeID === CCrmOwnerType::Quote):?>
					BX.CrmQuoteConversionSchemeSelector.create(
						"<?=$selectorID?>",
						{
							entityId: <?=$entityID?>,
							scheme: "<?=$schemeName?>",
							containerId: "<?=$labelID?>",
							labelId: "<?=$labelID?>",
							buttonId: "<?=$buttonID?>",
							originUrl: "<?=$originUrl?>",
							enableHint: <?=CUtil::PhpToJSObject($enableHint)?>,
							hintMessages: <?=CUtil::PhpToJSObject($hint)?>
						}
					);

					BX.addCustomEvent(window,
						"CrmCreateDealFromQuote",
						function()
						{
							BX.CrmQuoteConverter.getCurrent().convert(
								<?=$entityID?>,
								BX.CrmQuoteConversionScheme.createConfig(BX.CrmQuoteConversionScheme.deal),
								"<?=$originUrl?>"
							);
						}
					);

					BX.addCustomEvent(window,
						"CrmCreateInvoiceFromQuote",
						function()
						{
							BX.CrmQuoteConverter.getCurrent().convert(
								<?=$entityID?>,
								BX.CrmQuoteConversionScheme.createConfig(BX.CrmQuoteConversionScheme.invoice),
								"<?=$originUrl?>"
							);
						}
					);
					<?endif;?>
					<?elseif($lockScript !== ''):?>
					var showLockInfo = function()
					{
						<?=$lockScript?>
					};
					BX.bind(BX("<?=$labelID?>"), "click", showLockInfo );
					<?if($entityTypeID === CCrmOwnerType::Deal):?>
					<?php //everything now is initialized in crm.deal.details ?>
					<?elseif($entityTypeID === CCrmOwnerType::Quote):?>
					BX.addCustomEvent(window, "CrmCreateDealFromQuote", showLockInfo);
					BX.addCustomEvent(window, "CrmCreateInvoiceFromQuote", showLockInfo);
					<?endif;?>
					<?endif;?>
					//endregion
				}
			);
		</script><?
	}
	else
	{
		?><a target="_top" class="webform-small-button webform-small-button-blue crm-top-toolbar-add<?=$icon !== '' ? " {$icon}" : ''?>" href="<?=$link?>" title="<?=$title?>"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>><?=$text?></a><?
	}
}
?></div><?php
$this->EndViewTarget();
