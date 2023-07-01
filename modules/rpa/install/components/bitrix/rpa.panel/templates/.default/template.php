<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}

$APPLICATION->SetAdditionalCSS("/bitrix/css/main/font-awesome.css");
\Bitrix\Main\UI\Extension::load([
	'ui.tilegrid',
	'popup',
	'ui.alerts',
	'ajax',
	'ui.dialogs.messagebox',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$this->getComponent()->addToolbar($this);
\Bitrix\Rpa\Driver::getInstance()->getBitrix24Manager()->addFeedbackButtonToToolbar('panel');

$grid = $arResult['grid'] ?? null;

?>
<script>
	BX.ready(function()
	{
		var taskCountersPullTag = '<?=CUtil::JSEscape($arResult['taskCountersPullTag']);?>';
		if(taskCountersPullTag && BX.PULL)
		{
			BX.PULL.subscribe({
				moduleId: 'rpa',
				command: taskCountersPullTag,
				callback: function(params)
				{
					var update = params.counter;
					var changedTypeId = parseInt(params.typeId);
					if(changedTypeId > 0)
					{
						var counterNode = document.getElementById('rpa-type-list-' + changedTypeId + '-counter');
						var counterContainerNode = document.getElementById('rpa-type-list-' + changedTypeId + '-counter-container');
						if(counterNode)
						{
							if(!counterContainerNode)
							{
								counterContainerNode = counterNode;
							}
							var counter = parseInt(counterNode.innerText);
							if(update === '+1')
							{
								counter++;
							}
							else if(update === '-1')
							{
								counter--;
							}
							counterNode.innerText = counter;
							if(counter <= 0)
							{
								counterContainerNode.style.display = 'none';
							}
							else
							{
								counterContainerNode.style.display = 'inline-block';
							}
						}
					}
				}
			});

            BX.PULL.extendWatch(taskCountersPullTag);
		}

		BX.message(<?=CUtil::PhpToJSObject($arResult['messages'])?>);
		<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
		<?php
		if(!$grid)
		{
		?>
		var panelParams = <?=CUtil::PhpToJSObject($arResult['panelParams']);?>;
		panelParams.container = document.getElementById('rpa-panel-container');
		panelParams.sizeRatio = "55%";
		panelParams.itemMinWidth = 300;
		panelParams.tileMargin = 10;
		panelParams.itemType = 'BX.Rpa.PanelItem';

		var panelGrid = new BX.TileGrid.Grid(panelParams);
		panelGrid.draw();

		BX.addCustomEvent('BX.Main.Filter:beforeApply', function(filterId)
		{
			if(filterId !== panelParams.id)
			{
				return;
			}
			panelGrid.setHeightContainer();
			panelGrid.setFadeContainer();
			var loader = panelGrid.getLoader();
			if(loader)
			{
				loader.show();
			}
		});

		BX.addCustomEvent("BX.Main.Filter:apply", function(filterId, data, filter, promise, params)
		{
			if(filterId !== panelParams.id)
			{
				return;
			}

			params.autoResolve = false;

			BX.ajax.runComponentAction('bitrix:rpa.panel', 'preparePanel', {
				analyticsLabel: 'rpaPanelTilesFilter',
				mode: 'class'
			}).then(function(response) {
				panelGrid.redraw(response.data.items);
                var loader = panelGrid.getLoader();
				if(loader)
				{
                    loader.hide();
				}
				panelGrid.unSetFadeContainer();
				panelGrid.unSetHeightContainer();
				promise.fulfill();
			}).catch(function(response) {
                var loader = panelGrid.getLoader();
				if(loader && loader.isShown())
				{
					loader.hide();
				}
				var message = '';
				response.errors.forEach(function(error)
				{
					message += error.message
				});
				BX.Rpa.PanelItem.showError(message);
			});
		});

		<?php
		}
		?>
	});
</script>
<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="rpa-panel-error-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>
<div class="rpa-panel-wrapper" id="rpa-panel-wrapper">
	<div class="rpa-panel-container<?php
		if($grid)
		{
			echo ' rpa-panel-grid';
		}
		?>" id="rpa-panel-container">
		<?php
		if($grid)
		{
			$APPLICATION->IncludeComponent(
				"bitrix:main.ui.grid",
				"",
				$grid
			);
		}
		?>
	</div>
	<?php
	if(!$grid && $arResult['pageNavigation']->getPageCount() > 1)
	{
	?>
		<div class="rpa-navigation">
			<?php $APPLICATION->IncludeComponent(
				'bitrix:main.pagenavigation',
				'',//grid
				array(
					'NAV_OBJECT' => $arResult['pageNavigation'],
					'SEF_MODE' => 'N',
					'BASE_LINK' => $arResult['baseUrl'],
				),
				$this->getComponent()
			);?>
		</div>
	<?php
	}
	?>
</div>
