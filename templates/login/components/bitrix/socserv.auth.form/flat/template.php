<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Bitrix\Main\UI\Extension::load(['popup', 'ui-icon']);

$arAuthServices = [];
$arPost = [];

if(is_array($arParams['~AUTH_SERVICES']))
{
	$arAuthServices = $arParams['~AUTH_SERVICES'];
}

if(is_array($arParams['~POST']))
{
	$arPost = $arParams['~POST'];
}

$hiddens = '';
foreach($arPost as $key => $value)
{
	if(!preg_match('|OPENID_IDENTITY|', $key))
	{
		$hiddens .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />'."\n";
	}
}
?>
<script>
function BxSocServPopup(id)
{
	var content = BX('bx_socserv_form_' + id);
	if(content)
	{
		var popup = BX.PopupWindowManager.create('socServPopup' + id, BX('bx_socserv_icon_' + id), {
			autoHide: true,
			closeByEsc: true,
			angle: {offset: 24},
			content: content,
			offsetTop: 3
		});

		popup.show();

		var input = BX.findChild(content, {'tag':'input', 'attribute':{'type':'text'}}, true);
		if(input)
		{
			input.focus();
		}

		var button = BX.findChild(content, {'tag':'input', 'attribute':{'type':'submit'}}, true);
		if(button)
		{
			button.className = 'btn btn-primary';
		}
	}
}
</script>

<div class="bx-authform-social">
	<ul>
	<?php
	foreach($arAuthServices as $service):
		$onclick = (!empty($service['ONCLICK']) ? $service['ONCLICK'] : "BxSocServPopup('".$service["ID"]."')");
	?>
		<li>
			<button
				id="bx_socserv_icon_<?=$service['ID']?>"
				class="
					b24net-icon-btn
					b24net-icon-btn--social-<?=\Bitrix\Main\Text\HtmlFilter::encode($service['ICON'])?>
					ui-icon
					b24net-icon-btn--social-btn
					b24net-login-enter-form__social-btn
				"
				onclick="<?=\Bitrix\Main\Text\HtmlFilter::encode($onclick)?>"
				title="<?=\Bitrix\Main\Text\HtmlFilter::encode($service['NAME'])?>"
				type="button"
			>
				<i class="b24net-icon-btn__icon"></i>
				<?php if(empty($service['ONCLICK']) && !empty($service['FORM_HTML'])): ?>
					<div id="bx_socserv_form_<?=$service['ID']?>" class="bx-authform-social-popup">
						<form action="<?=$arParams['AUTH_URL']?>" method="post">
							<?=$service['FORM_HTML']?>
							<?=$hiddens?>
							<input type="hidden" name="auth_service_id" value="<?=$service['ID']?>" />
						</form>
					</div>
				<?php endif ?>
			</button>
		</li>
	<?php
	endforeach;
	?>
	</ul>
</div>
