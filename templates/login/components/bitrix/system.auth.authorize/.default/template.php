<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetTitle(GetMessage("AUTH_TITLE"));

if ($arResult['ALLOW_QRCODE_AUTH'])
{
   \Bitrix\Main\UI\Extension::load(['qrcode', 'pull.client', 'loader']);
}
?>
<div class="log-popup-form-input --show" data-role="log-popup-form-input">
	<div class="log-popup-header"><?=$APPLICATION->GetTitle();?></div>

	<?if(!$arResult["AUTH_SERVICES"]):?>
	<div class="b_line_gray"></div>
	<?endif?>

	<?
	ShowMessage($arParams["~AUTH_RESULT"] ?? '');
	ShowMessage($arResult['ERROR_MESSAGE'] ?? '');
	?>

	<?if($arResult["AUTH_SERVICES"]):?>
	<div class="log-socservice-wrapper">
		<?$APPLICATION->IncludeComponent("bitrix:socserv.auth.form", "",
			array(
				"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
				"CURRENT_SERVICE" => $arResult["CURRENT_SERVICE"],
				"AUTH_URL" => $arResult["AUTH_URL"],
				"POST" => $arResult["POST"],
				"SHOW_TITLES" => 'N',
				"FOR_SPLIT" => 'Y',
				"AUTH_LINE" => 'N',
			),
			$component,
			array("HIDE_ICONS"=>"Y")
		);?>
	</div>
	<?endif?>

	<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>" <?php if (!$arResult['ALLOW_QRCODE_AUTH']): ?>class="log-popup-form-area"<?php endif ?>>
		<input type="hidden" name="AUTH_FORM" value="Y" />
		<input type="hidden" name="TYPE" value="AUTH" />
		<?if ($arResult["BACKURL"] <> ''):?>
		<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
		<?endif?>
		<?foreach ($arResult["POST"] as $key => $value):?>
		<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
		<?endforeach?>

			<div>
				<div class="login-wrapper">
					<div class="login-wrapper-inputs">
						<div class="login-item --auth">
							<!--[if IE]><span class="login-label"><?=GetMessage("AUTH_LOGIN")?></span><![endif]-->
							<input class="login-inp" type="text" name="USER_LOGIN" placeholder="<?=GetMessage("AUTH_LOGIN")?>" value="<?=$arResult["LAST_LOGIN"]?>" maxlength="255"/>
						</div>
						<div class="login-item --auth">
							<!--[if IE]><span class="login-label"><?=GetMessage("AUTH_PASSWORD")?></span><![endif]-->
							<input class="login-inp" type="password" name="USER_PASSWORD" placeholder="<?=GetMessage("AUTH_PASSWORD")?>" maxlength="255" autocomplete="new-password" />
						</div>
					</div>

					<?php if ($arResult['ALLOW_QRCODE_AUTH']): ?>

					<div class="login-wrapper-qr">
						<div class="login-wrapper-qr-link" data-role="login-wrapper-qr-link">
							<div class="login-wrapper-qr-icon"></div>
							<div class="login-wrapper-qr-text"><?=GetMessage("AUTH_AUTHORIZE_BY_QR")?></div>
						</div>
					</div>

					<?php endif ?>

				</div>
				<?if($arResult["CAPTCHA_CODE"]):?>
					<div class="login-item">
						<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
						<span class="login-label"></span>
						<img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
					</div>
					<div class="login-item">
						<span class="login-label"><?echo GetMessage("AUTH_CAPTCHA_PROMT")?></span>
						<input class="login-inp" type="text" name="captcha_word" maxlength="50" value="" size="15" autocomplete="off"/>
					</div>
				<?endif;?>

			<div class="login-text login-item --user-remember">
				<?if ($arResult["STORE_PASSWORD"] == "Y"):?>
				<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" class="login-checkbox-user-remember"/><label class="login-item-checkbox-label" for="USER_REMEMBER"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
				<?endif?>
				<?if($arParams["NOT_SHOW_LINKS"] != "Y" && $arResult["NEW_USER_REGISTRATION"] == "Y" && ($arParams["AUTHORIZE_REGISTRATION"] ?? null) != "Y"):?>
					<noindex>
						<div class="login-links"><a  href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow"><?=GetMessage("AUTH_REGISTER")?></a></div>
					</noindex>
				<?endif?>
			</div>
		</div>
		<div class="log-popup-footer <?if($arResult["AUTH_SERVICES"]):?>--auth<?endif?>">
			<input type="submit" value="<?=GetMessage("AUTH_AUTHORIZE")?>" class="login-btn" onclick="BX.addClass(this, 'wait');"/>
			<a class="login-link-forgot-pass" href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a>
		</div>
	</form>
</div>

<?php if ($arResult['ALLOW_QRCODE_AUTH']): ?>

<div class="log-popup-form-qr" data-role="log-popup-form-qr">
	<div class="log-popup-form-qr-header">
		<span class="log-popup-form-qr-header--top"><?=GetMessage("AUTH_AUTHORIZE_BY_QR_INFO_1")?></span>
		<span class="log-popup-form-qr-header--bottom"><?=GetMessage("AUTH_AUTHORIZE_BY_QR_INFO_2")?></span>
	</div>
	<div class="log-popup-form-qr-icon" data-role="log-popup-form-qr-icon">
		<div class="log-popup-form-qr-icon-status --success"></div>
		<div class="log-popup-form-qr-icon-status --loading" data-role="log-popup-form-qr-icon-loader"></div>
		<div class="log-popup-form-qr-icon-img" id="bx_auth_qr_code"></div>
	</div>
	<div class="login-text login-item">
		<?if ($arResult["STORE_PASSWORD"] == "Y"):?>
			<input tabindex="-1" type="checkbox" id="USER_REMEMBER_QR" name="USER_REMEMBER_QR" value="Y" class="login-checkbox-user-remember" checked="checked" /><label class="login-item-checkbox-label" for="USER_REMEMBER_QR"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
		<?endif?>
	</div>
	<div class="log-popup-form-qr-buttons">
		<div class="login-btn login-btn-transparent" data-role="log-popup-form-qr-button-back"><?=GetMessage("AUTH_BACK")?></div>
	</div>
</div>

<?php endif ?>

<script type="text/javascript">
	function fireEnterKey(event)
	{
		event = event || window.event;
		if (event.keyCode != 13)
			return true;

		var src = event.srcElement || event.target;
		if (!src || (src.tagName.toLowerCase() != "textarea"))
		{
			var form = document.forms["form_auth"];
			if (form)
			{
				var password = form.elements["USER_PASSWORD"];
				if (!password || BX.type.isNotEmptyString(password.value))
					form.submit();
			}

			BX.PreventDefault(event);
		}

		return true;
	}

	BX.ready(function() {
		BX.focus(document.forms["form_auth"]["<?=($arResult["LAST_LOGIN"] <> '' ? "USER_PASSWORD" : "USER_LOGIN" )?>"]);
		BX.bind(document.forms["form_auth"], "keypress", fireEnterKey);

		<?php if ($arResult['ALLOW_QRCODE_AUTH']): ?>

		new QRCode('bx_auth_qr_code', {
			text: 'https://b24.to/a/<?= SITE_ID ?>/<?= $arResult['QRCODE_UNIQUE_ID']?>/<?= $arResult['QRCODE_CHANNEL_TAG'] ?>/',
			width: 220,
			height: 220,
			colorDark : '#000000',
			colorLight : '#ffffff'
		});

		var pullConfig = <?= CUtil::PhpToJSObject($arResult['QRCODE_CONFIG']) ?>;
		if (pullConfig)
		{
			var Pull = new BX.PullClient();
			Pull.subscribe({
				moduleId: 'main',
				command: 'qrAuthorize',
				callback: function (params) {
					if (params.token)
					{
						blockQrImage.classList.add('--loading');
						loader.show();

						BX.ajax.runAction(
							'main.qrcodeauth.authenticate',
							{
								data: {
									token: params.token,
									remember: (BX('USER_REMEMBER_QR') && BX('USER_REMEMBER_QR').checked ? 1 : 0)
								}
							}
						).then(
							function (response)
							{
								blockQrImage.classList.remove('--loading');
								loader.hide();

								if(response.status === 'success')
								{
									blockQrImage.classList.add('--success');

									window.location = (params.redirectUrl != '' ?  params.redirectUrl : window.location);
								}
							}
						);
					}
				}.bind(this)
			});
			Pull.start(pullConfig);
		}

		// slide controller
		var buttonQr = document.querySelector('[data-role="login-wrapper-qr-link"]');
		var buttonBack = document.querySelector('[data-role="log-popup-form-qr-button-back"]');
		var blockInput = document.querySelector('[data-role="log-popup-form-input"]');
		var blockQr = document.querySelector('[data-role="log-popup-form-qr"]');
		var blockQrImage = document.querySelector('[data-role="log-popup-form-qr-icon"]');

		if(buttonQr)
		{
			buttonQr.addEventListener('click', function() {
				blockQr.classList.add('--show');
				blockInput.classList.remove('--show');
			});
		}

		if(buttonBack)
		{
			buttonBack.addEventListener('click', function() {
				blockQr.classList.remove('--show');
				blockInput.classList.add('--show');
			});
		}
		// slider controller end

		var loader = new BX.Loader({
			target: document.querySelector('[data-role="log-popup-form-qr-icon-loader"]'),
			size: 150
		});

		<?php endif ?>

	});
</script>
