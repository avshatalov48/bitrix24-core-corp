<?php

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
		"/bitrix/js/crm/entity-editor/js/product-list.js",
		"/bitrix/js/crm/entity-editor/js/field-configurator.js",
		"/bitrix/js/crm/entity-editor/js/product-list.js",
		"/bitrix/js/crm/entity-editor/js/store-document-product-list.js",
		'/bitrix/js/crm/entity-editor/js/entity-selector.js',
	],
	'rel' => [
		'ui.design-tokens',
		'ui.fonts.opensans',
		'main.polyfill.promise',
		'ajax',
		'date',
		'uf',
		'uploader',
		'avatar_editor',
		'core_money_editor',
		'currency',
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
		'crm_entity_editor_rel',
		'ui.forms',
		'ui.entity-editor',
		'ui.entity-selector',
		'ui.dialogs.messagebox',
		'crm.entity-editor.field.payment-documents',
		'crm.entity-editor.field.image',
		'crm.placement.detailsearch',
		'crm.entity-editor.field-configurator',
		'crm.entity-editor.field.phone-number-input',
	]
);
