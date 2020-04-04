<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.hint");

CJSCore::Init(['sidepanel']);

?>
	<div class="docs-config-wrap" id="docs-config">
		<div id="config-alert-container"></div>
		<form>
			<div class="docs-config-block-wrap">
				<div class="docs-config-check-container">
					<?
					if(Bitrix24Manager::isRestrictionsActive())
					{
						?>
						<input class="docs-config-input" type="checkbox" name="document_enable_public_b24_sign" value="Y" id="document_enable_public_b24_sign" checked disabled>
						<label class="docs-config-title" for="document_enable_public_b24_sign"><?=Loc::getMessage('DOCGEN_CONFIG_ENABLE_PUBLIC_SIGN');?></label>
						<span data-hint="<?=Loc::getMessage('DOCGEN_CONFIG_ENABLE_PUBLIC_SIGN_BITRIX_24_FREE');?>"></span>
						<?
					}
					else
					{
						?>
						<input class="docs-config-input" type="checkbox" name="document_enable_public_b24_sign" value="Y" id="document_enable_public_b24_sign" <?if($arResult['enablePublicSign']){?> checked<?}?>>
						<label class="docs-config-title" for="document_enable_public_b24_sign"><?=Loc::getMessage('DOCGEN_CONFIG_ENABLE_PUBLIC_SIGN');?></label>
					<?}?>
				</div>
			</div>
		</form>
	</div>
<script>
	BX.ready(function() {
		BX.UI.Hint.init(BX('docs-config'));
	})
</script>
<?

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'BX.DocumentGenerator.Config.save()',
		],
		[
			'TYPE' => 'close',
			'LINK' => '',
		]
	]
]);