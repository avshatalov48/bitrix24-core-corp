<?php

/** @global CMain $APPLICATION */
/** @global CUser $USER */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Public\Provider\Portal\LanguageProvider;

$languageProvider = new LanguageProvider();
$languageList = $languageProvider->getPublicArray();
?>
		</div>
	</div>

	<div class="intranet-body__footer intranet-auth-cover-footer">
		<div class="intranet-body__footer-left">
			<?php if ($languageProvider->isLanguageIdChangeAvailable()): ?>
			<div id="login-language-selector" class="intranet-backdrop-btn">
				<span class="intranet-backdrop-btn__content"><?= $languageList[LANGUAGE_ID]['NAME'] ?></span>
				<i class="intranet-language-selector-btn ui-icon-set --chevron-down"></i>
			</div>
			<?php endif ?>
		</div>
		<div class="intranet-body__footer-right">
			<!-- teleport -->
		</div>
	</div>
</div>

<script>
	(function()
	{
		BX.ready(function()
		{
			document.querySelector('#login-auth-container').classList.add("intranet-auth-animate");

			var languageSelector = document.querySelector('#login-language-selector');
			BX.bind(languageSelector, 'click', function()
			{
				BX.PopupMenu.show(
					'lang-popup',
					languageSelector,
					[
						<?php foreach($languageList as $langCode => $lang): ?>
						{
							text: "<?= CUtil::JSEscape($lang['NAME']) ?>",
							onclick: function()
							{
								function changeLanguage(langCode)
								{
									var url = window.location.href;
									url = url.replace(/(\?|\&)user_lang=[A-Za-z]{2}/, "");
									url += (url.indexOf("?") == -1 ? "?" : "&") + "user_lang=" + langCode;
									window.location.href = url;
								}

								changeLanguage('<?= $langCode ?>');
							}
						},
						<?php endforeach ?>
					],
					{
						offsetTop: 10,
						offsetLeft: 42,
						angle: {offset: 45},
						animationOptions: false,
						className: 'intranet-language-selector-popup',
						bindOptions: {
							position: 'top',
							forceTop: true,
						}
					}
				);
			});
		});
	})();
</script>

</body>
</html>
