<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

$messages = Loc::loadLanguageFile(__FILE__);

Extension::load([
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.hint',
	'ui.alerts',
	'salescenter.componentanimation',
	'salescenter.form',
	'sidepanel',
	'loader',
	'ui.switcher',
]);
?>

<div class="salescenter-cashbox-wrapper">

	<?php
	if(!$arResult['isFrame'])
	{
	?>
	<div class="cashbox-page-menu-sidebar">
		<?php $APPLICATION->ShowViewContent("left-panel");?>
	</div>
	<?php
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrappermenu',
		"",
		[
			'TITLE' => Loc::getMessage('SC_MENU_TITLE'),
			'ITEMS' => $arResult['menu'],
		],
		$this->getComponent()
	);

?>

	<div id="salescenter-wrapper" class="salescenter-wrapper">
		<form method="post" id="salescenter-form">
			<?
			foreach ($arResult['menu'] as $id => $page)
			{
				$cashboxPageClass = ($page['ACTIVE'] ? "salescenter-cashbox-page-show" : "salescenter-cashbox-page-hide salescenter-cashbox-page-invisible");
				?>
				<div data-cashbox-page="<?=$id?>" data-cashbox-title="<?=$page['NAME']?>" class="<?=$cashboxPageClass?>">
					<?php
					if($id === 'cashbox_params')
					{
						?>
						<div style="padding: 15px; margin-bottom: 15px;" class="ui-bg-color-white">
							<div class="salescenter-main-header">
								<div class="salescenter-main-header-left-block">
									<div class="salescenter-logo-container">
										<div class="salescenter-<?=$arResult['handlerDescription']['code'];?>-icon ui-icon"><i></i></div>
									</div>
								</div>
								<div class="salescenter-main-header-right-block">
									<div class="salescenter-main-header-title-container">
										<div class="ui-title-3" style="margin-bottom: 0;"><?=Loc::getMessage($arResult['handlerDescription']['title'])?></div>
										<div class="salescenter-main-header-feedback-container">
											<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
										</div>
										<div class="salescenter-main-header-switcher-container">
											<span data-switcher="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode([
												'id' => 'salescenter-cashbox-active',
												'checked' => ($arResult['data']['fields[ACTIVE]'] !== 'N'),
												'inputName' => "fields[ACTIVE]",
											]))?>" class="ui-switcher"></span>
										</div>
									</div>
									<hr class="ui-hr" style="margin-bottom: 15px;">
									<div class="ui-text-2" style="margin-bottom: 20px;"><?=Loc::getMessage($arResult['handlerDescription']['description'])?></div>
								</div>
							</div>
						</div>
						<div class="ui-alert ui-alert-danger" style="display: none;">
							<span class="ui-alert-message" id="salescenter-cashbox-error"></span>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>

			<div id="salescenter-cashbox-buttons">
				<?php
				$buttons = [
					[
						'TYPE' => 'save',
						'ONCLICK' => 'BX.Salescenter.Cashbox.save(event);',
					],
					'cancel' => '/saleshub/'
				];
				if($arResult['id'] > 0)
				{
					$buttons[] = [
						'TYPE' => 'remove',
						'ONCLICK' => 'BX.Salescenter.Cashbox.remove(event);',
					];
				}
				$APPLICATION->IncludeComponent(
					'bitrix:ui.button.panel',
					"",
					array(
						'BUTTONS' => $buttons,
						'ALIGN' => "center"
					),
					$this->getComponent()
				);
				?>
			</div>
		</form>
		<input id="salescenter-form-is-saved" type="hidden" value="n" />
	</div>

</div>

<script>
	BX.message(<?=CUtil::PhpToJSObject($messages)?>);
	BX.ready(function(){
		var container = document.getElementById('salescenter-form');
		var fields = <?=CUtil::PhpToJSObject($arResult['fields']);?>;
		var config = <?=CUtil::PhpToJSObject($arResult['config']);?>;
		var data = <?=CUtil::PhpToJSObject($arResult['data']);?>;
		var form = new BX.Salescenter.Form('cashbox-settings', {
			config: config,
			fields: fields,
			data: data,
		});

		BX.Salescenter.Cashbox.init({
			cashboxId: <?=intval($arResult['id']);?>,
			form: form,
			errorMessageNode: document.getElementById('salescenter-cashbox-error'),
			signedParameters: <?=CUtil::PhpToJSObject($arResult['signedParameters']);?>,
			container: container,
		});

		BX.UI.Hint.init(container);
		BX.UI.Switcher.initByClassName();
	});
</script>