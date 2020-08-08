<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CJSCore::RegisterExt('crm_entity_editor_rel', array(
	'js' => [
		'/bitrix/js/main/dd.js',
		'/bitrix/js/crm/interface_form.js',
		'/bitrix/js/crm/phase.js',
		'/bitrix/js/crm/dialog.js',
		'/bitrix/js/crm/entity_event.js',
	],
));

return array(
	"css" => "/bitrix/js/crm/entity-editor/css/style.css",
	"js" => [
		"/bitrix/js/crm/entity-editor/js/config.js",
		"/bitrix/js/crm/entity-editor/js/config-enum.js",
		"/bitrix/js/crm/entity-editor/js/control.js",
		"/bitrix/js/crm/entity-editor/js/dialog.js",
		"/bitrix/js/crm/entity-editor/js/editor.js",
		"/bitrix/js/crm/entity-editor/js/editor-enum.js",
		"/bitrix/js/crm/entity-editor/js/editor-controller.js",
		"/bitrix/js/crm/entity-editor/js/drag-drop.js",
		"/bitrix/js/crm/entity-editor/js/factory.js",
		"/bitrix/js/crm/entity-editor/js/field-selector.js",
		"/bitrix/js/crm/entity-editor/js/helper.js",
		"/bitrix/js/crm/entity-editor/js/model.js",
		"/bitrix/js/crm/entity-editor/js/scheme.js",
		"/bitrix/js/crm/entity-editor/js/tool-panel.js",
		"/bitrix/js/crm/entity-editor/js/user-field.js",
		"/bitrix/js/crm/entity-editor/js/validator.js",
		"/bitrix/js/crm/entity-editor/js/manager.js",
		"/bitrix/js/crm/entity-editor/js/client-editor.js",
		"/bitrix/js/crm/entity-editor/js/field-attr.js",
		"/bitrix/js/crm/entity-editor/js/multiple-user.js",
		"/bitrix/js/crm/entity-editor/js/order.js",
	],
	'rel' => [
		'main.polyfill.promise',
		'ajax',
		'date',
		'uf',
		'uploader',
		'avatar_editor',
		'core_money_editor',
		'tooltip',
		'phone_number',
		'spotlight',
		'helper',
		'dd',
		'ui.common',
		'ui.buttons',
		'ui.notification',
		'ui.dropdown',
		'crm_disk_uploader',
		'crm_common',
		'crm_entity_editor_rel'
	]
);