<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$logoLang = LANGUAGE_ID;
if (!in_array($logoLang, array('ru', 'ua', 'en')))
	$logoLang = \Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID);
if (!in_array($logoLang, array('ru', 'ua', 'en')))
	$logoLang = 'en';

?>

					<div id="pub-template-error" class="error-block" style="display: none; ">
						<div id="pub-template-error-title" class="error-block-title"></div>
						<div id="pub-template-error-text" class="error-block-text"></div>
					</div>
				</div>
			</div>
		</td>
	</tr>
	<tr class="main-wrapper-footer">
		<td class="main-wrapper-footer-cell">
			<div class="content-wrap">
				<? if (isModuleInstalled('bitrix24')) :
					\Bitrix\Main\UI\Extension::load("ui.buttons"); ?>
					<?
					$b24Languages = [];
					include_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/languages.php';
					if (!\Bitrix\Main\Application::getInstance()->isUtfMode())
					{
						array_walk($b24Languages, function(&$lang) {
							$lang["NAME"] = mb_convert_encoding($lang["NAME"], "HTML-ENTITIES", "UTF-8");
						});
					}
					?>
					<button class="ui-btn pub-btn-lang ui-btn-light-border ui-btn-sm ui-btn-themes ui-btn-dropdown ui-btn-no-caps" id="bx-lang-btn" onclick="pubLanguage.showSelector(this, <?=CUtil::PhpToJSObject($b24Languages)?>); "><?=$b24Languages[LANGUAGE_ID]["NAME"]?></button>
				<? endif; ?>
				<? if (!defined('SKIP_TEMPLATE_B24_SIGN') || !SKIP_TEMPLATE_B24_SIGN): ?>
					<a class="footer-logo <?=$logoLang ?>" target="_blank" href="<?=CIntranetUtils::getB24Link('pub') ?>">
						<span class="footer-logo-text"><?=getMessage('POWERED_BY') ?></span>
						<span class="footer-logo-img"></span>
					</a>
				<? endif ?>
			</div>
		</td>
	</tr>
</table>
<div class="bottom-cloud"></div>
<div class="left-cloud"></div>
<div class="right-cloud"></div>

<script type="text/javascript">

var pubTemplate = {

	showError: function(error, params)
	{
		switch (error.toString())
		{
			case '204':
				var error = {
					title: '<?=CUtil::jsEscape(getMessage('ERR_NO_CONTENT_TITLE')); ?>',
					text: '<?=CUtil::jsEscape(getMessage('ERR_NO_CONTENT_TEXT')); ?>'
				};
				break;
			case '401':
				var error = {
					title: '<?=CUtil::jsEscape(getMessage('ERR_UNAUTHORIZED_TITLE')); ?>',
					text: '<?=CUtil::jsEscape(getMessage('ERR_UNAUTHORIZED_TEXT')); ?>'
				};
				break;
			case '403':
				var error = {
					title: '<?=CUtil::jsEscape(getMessage('ERR_FORBIDDEN_TITLE')); ?>',
					text: '<?=CUtil::jsEscape(getMessage('ERR_FORBIDDEN_TEXT')); ?>'
				};
				break;
			case '400':
			case '404':
				var error = {
					title: '<?=CUtil::jsEscape(getMessage('ERR_NOT_FOUND_TITLE')); ?>',
					text: '<?=CUtil::jsEscape(getMessage('ERR_NOT_FOUND_TEXT')); ?>'
				};
				break;
			default:
				var error = {
					title: '<?=CUtil::jsEscape(getMessage('ERR_DEFAULT')); ?>',
					text: error
				};
		}

		if (params)
		{
			for (var key in params)
				error.text = error.text.replace('#'+key.toUpperCase()+'#', params[key]);
		}

		BX.adjust(BX('pub-template-error-title'), { html: error.title });
		BX.adjust(BX('pub-template-error-text'), { html: error.text });

		BX.show(BX('pub-template-error'), 'block');
	}

};

<?

$pageError = false;

if (!defined('SKIP_TEMPLATE_AUTH_ERROR') || !SKIP_TEMPLATE_AUTH_ERROR and !$USER->isAuthorized())
{
	$pageError = 401;
}
else if (!empty($arReturn['ERROR']) || !empty($arReturn['ERROR_CODE']))
{
	switch ($arReturn['ERROR_CODE'])
	{
		case 'NO_AUTH':
			$pageError = 401;
			break;
		case 'NO_BLOG':
		case 'NO_POST':
			$pageError = 404;
			break;
		case 'NO_RIGHTS':
			$pageError = 403;
			break;
		default:
			$pageError = $arReturn['ERROR'];
	}
}

if ($pageError) : ?>
BX.ready(function()
{
	pubTemplate.showError('<?=CUtil::jsEscape($pageError); ?>');
});
<? endif; ?>

<? if (isModuleInstalled('bitrix24')) : ?>

var pubLanguage = {
	items: [],

	showSelector: function(button, langs)
	{
		if (pubLanguage.items.length == 0)
		{
			for (var i in langs)
				pubLanguage.items.push({ lang: i, onclick: pubLanguage.change, text: langs[i].NAME + (langs[i].IS_BETA ? ", beta" : "")});
		}

		BX.PopupMenu.show('language-selector', button, pubLanguage.items, {
			offsetTop: 0, offsetLeft: 6,
			angle: { position: 'top', offset: 10 }
		});
	},

	change: function(event, item)
	{
		var location = document.createElement('a');
			location.href = window.location;

		location.search = '?' + location.search.replace(/^\?*/ig, '')
			.replace(/(^|&)(logout|login|back_url_pub|user_lang)(=[^&]*)?(&|$)/ig, '&')
			.replace(/&{2,}/ig, '&').replace(/^&/ig, '').replace(/([^&])$/ig, '$1&');

		location.search += 'user_lang=' + item.lang;

		window.location.href = location.href;
	}

};

<? endif; ?>

</script>

</body>
</html>
