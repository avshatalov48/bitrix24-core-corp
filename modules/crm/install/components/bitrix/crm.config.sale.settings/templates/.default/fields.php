<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\UI\Extension::load("popup");

$settings = $arResult["SETTINGS"];
if ($settings['MODE'] === 'edit' && !is_null($settings['FIELD_ID']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.config.fields.edit',
			'',
		array(
			'FIELS_ENTITY_ID' => 'ORDER',
			'FIELS_FIELD_ID' => $settings['FIELD_ID'],
			'FIELDS_LIST_URL' => $settings['LIST_URL'],
			'FIELD_EDIT_URL' => $settings['EDIT_URL']
		),
		null
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.config.fields.list',
		'',
		array(
			'FIELS_ENTITY_ID' => 'ORDER',
			'FIELDS_LIST_URL' => $settings['LIST_URL'],
			'FIELD_EDIT_URL' => $settings['EDIT_URL'],
			'SHOW_TYPE_TOOLBAR_BUTTON' => false
		),
		null
	);
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event){
					if (event.getEventId() === 'Crm.Config.Fields.Edit:onChange')
					{
						location.reload();
					}
				}, this));
			}
		);
	</script>
	<?
	return;
}
