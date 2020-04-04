<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
?>

<?=CrmOrderPropsFormEditTemplate::getFieldJsTemplateAll()?>

<script type="text/html" id="tmpl_field_dependency">
	<?GetCrmOrderPropsFormFieldRelationTemplate(
		array(
			'ID' => '%ID%',
			'IF_FIELD_CODE' => '%IF_FIELD_CODE%',
			'IF_VALUE' => '%IF_VALUE%',
			'DO_FIELD_CODE' => '%DO_FIELD_CODE%',
			'DO_ACTION' => '%DO_ACTION%',
		)
	);?>
</script>
<script type="text/html" id="tmpl_field_preset">
	<?GetCrmOrderPropsFormPresetFieldTemplate(
		array(
			'CODE' => '%CODE%',
			'ENTITY_CAPTION' => '%ENTITY_CAPTION%',
			'ENTITY_FIELD_CAPTION' => '%ENTITY_FIELD_CAPTION%',
			'ENTITY_NAME' => '%ENTITY_NAME%',
			'ENTITY_FIELD_NAME' => '%ENTITY_FIELD_NAME%',
			'VALUE' => '%VALUE%',
		)
	);?>
</script>
<script type="text/html" id="tmpl_field_product_items_draw">
	<label class="crm-orderform-edit-task-options-account-setup-goods">
		<input disabled value="%name%" class="crm-orderform-edit-task-options-account-setup-goods-name">
		<input disabled value="%price%" class="crm-orderform-edit-task-options-account-setup-goods-price">
	</label>
</script>