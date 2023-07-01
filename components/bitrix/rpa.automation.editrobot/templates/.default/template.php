<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;

$bodyClass = false; // $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-background no-hidden");

\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.buttons', 'ui.switcher', 'ui.alerts']);

CUtil::InitJSCore(
	['bp_field_type']
);

//load Bizproc Automation API
$APPLICATION->includeComponent(
	'bitrix:bizproc.automation',
	'',
	[
		'API_MODE' => 'Y',
		'DOCUMENT_TYPE' => $arResult['DOCUMENT_TYPE'],
	]
);

if (isset($_POST['save_action']) && $_POST['save_action'] === 'Y'):
	if ($errors = $this->getComponent()->getErrors()):?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?= reset($errors)->getMessage() ?></span>
		</div>
	<?php
	else:

		$data = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();
		$data['isTask'] = true;
		if(!empty($arParams['robotName']))
		{
			$data['robotName'] = $arParams['robotName'];
			Driver::getInstance()->getPullManager()->sendRobotUpdatedEvent((int) $arParams['typeId'], (int) $arParams['stage'], $data);
		}
		else
		{
			Driver::getInstance()->getPullManager()->sendRobotAddedEvent((int) $arParams['typeId'], (int) $arParams['stage'], $data);
		}
	//todo put response to slider data as well
	?>
	<!-- .ui-alert.ui-alert-success -->
	<div class="ui-alert ui-alert-success">
		<span class="ui-alert-message"><?= Loc::getMessage("RPA_AUTOMATION_EDITROBOT_TPL_SAVE_SUCCESS") ?></span>
	</div>
	<script>
		BX.ready(function()
		{
			var slider = BX.getClass('BX.SidePanel.Instance');
			if (slider)
			{
				setTimeout(function()
				{
					slider.close();
				}, 50);
			}
		});
	</script>
<?php
	endif;
endif;
?>
	<div class="rpa-edit-robot">
		<?php
		$menu = [];
		$propsMap = $arResult['dialog']->getMap();

		$menu["test"] = [
			"PAGE" => "test",
			"NAME" => Loc::getMessage('RPA_AUTOMATION_EDITROBOT_TPL_SECTION_MAIN'),
			"ACTIVE" => true,
			"ATTRIBUTES" => [
				"data-role" => "menu-item",
				"data-page" => "general"
			]
		];

		if (isset($propsMap['FieldsToShow']))
		{
			$menu["test2"] = [
				"PAGE" => $propsMap['FieldsToShow']['FieldName'],
				"NAME" => $propsMap['FieldsToShow']['Name'],
				"ACTIVE" => false,
				"ATTRIBUTES" => [
					"data-role" => "menu-item",
					"data-page" => $propsMap['FieldsToShow']['FieldName']
				]
			];
		}
		if (isset($propsMap['FieldsToSet']))
		{
			$menu["test3"] = [
				"PAGE" => $propsMap['FieldsToSet']['FieldName'],
				"NAME" => $propsMap['FieldsToSet']['Name'],
				"ACTIVE" => false,
				"ATTRIBUTES" => [
					"data-role" => "menu-item",
					"data-page" => $propsMap['FieldsToSet']['FieldName']
				]
			];
		}

		$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", [
			"ID" => 'rpa-edit-robot-menu',
			"ITEMS" => $menu,
			'VIEW_TARGET' => 'left-panel-robot-edit'
		]);
		?>

		<?php if ($arResult['AVAILABLE_ROBOTS']):
		$this->setViewTarget("below_pagetitle", 100); ?>
		<div class="rpa-edit-robot-menu-block">
			<?php if(!empty($arParams['robotName'])):?>
				<span class="rpa-edit-robot-menu-title"><?=htmlspecialcharsbx($arResult['AVAILABLE_ROBOT_CURRENT_NAME'])?></span>
			<?php else:?>
			<span class="rpa-edit-robot-menu-title"><?=Loc::getMessage('RPA_AUTOMATION_EDITROBOT_SCENARIO')?></span>
			<a class="rpa-edit-robot-menu-link" data-role="rpa-edit-robot-menu-scenario"><?=htmlspecialcharsbx($arResult['AVAILABLE_ROBOT_CURRENT_NAME'])?></a>
			<?php endif?>
		</div>
		<?php $this->endViewTarget();
		endif?>

		<div class="rpa-edit-robot-menu">
			<?php $APPLICATION->ShowViewContent("left-panel-robot-edit"); ?>
		</div>
		<form id="rpa-robot-edit" method="post" enctype="multipart/form-data" action="" class="rpa-edit-robot-content">
			<input type="hidden" name="save_action" value="Y">
			<?=$arResult['dialog'];?>
		</form>
	</div>
<?php
$buttons = [
	[
		'TYPE' => 'save',
		'ONCLICK' => 'BX(\'rpa-robot-edit\').submit();'
	],
	'cancel'
];

$APPLICATION->IncludeComponent(
	'bitrix:ui.button.panel',
	"",
	[
		'BUTTONS' => $buttons,
		'ALIGN' => 'center'
	],
	$this->getComponent()
);
?>
<script>
	BX.ready(function()
	{
		var errors = <?=\Bitrix\Main\Web\Json::encode($this->getComponent()->getErrors())?>;
		var editForm = document.getElementById('rpa-robot-edit');
		var handler = function(page)
		{
			Array.from(editForm.querySelectorAll('[data-section]')).forEach(function(el)
			{
				if (el.getAttribute('data-section') === page)
				{
					BX.removeClass(el, 'rpa-automation-block-hidden');
				}
				else
				{
					BX.addClass(el, 'rpa-automation-block-hidden');
				}
			});
		};

		var scenarioLink = document.querySelector('[data-role="rpa-edit-robot-menu-scenario"]');
		if (scenarioLink)
		{
			BX.bind(scenarioLink, 'click', function()
			{
				var robots = <?=\Bitrix\Main\Web\Json::encode($arResult['AVAILABLE_ROBOTS'])?>;
				var menuItems = [];

				robots.forEach(function(robot)
				{
					menuItems.push({
						text: BX.util.htmlspecialchars(robot.name),
						robot: robot,
						href: BX.util.add_url_param(robot.url, {IFRAME: 'Y'}),
						dataset: {
							sliderIgnoreAutobinding: true
						},
					});
				});

				BX.PopupMenu.show(
					'rpa-edit-robot-menu-scenario',
					this,
					menuItems
				);
			});
		}

		Array.from(document.getElementById('rpa-edit-robot-menu').querySelectorAll('[data-role="menu-item"]')).forEach(function(el)
		{
			BX.bind(el, 'click', handler.bind(el, el.getAttribute('data-page')));
		});

		if (errors && errors.length)
		{
			var error = errors.find(function(error)
			{
				if (error.customData && error.customData.parameter === 'FieldsToSet')
				{
					return error
				}
			});

			if (error)
			{
				var menuItem = document.getElementById('rpa-edit-robot-menu')
					.querySelector('[data-role="menu-item"][data-page="fields_to_set"]');

				if (menuItem && BX.type.isFunction(menuItem.click))
				{
					menuItem.click();
				}
			}
		}
	});
</script>