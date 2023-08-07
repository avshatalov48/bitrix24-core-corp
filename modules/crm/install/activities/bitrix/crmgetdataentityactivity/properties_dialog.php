<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$listDefaultEntity = array(
	'LEAD' => GetMessage("CRM_ACTIVITY_ENTITY_NAME_LEAD"),
	'CONTACT' => GetMessage("CRM_ACTIVITY_ENTITY_NAME_CONTACT"),
	'COMPANY' => GetMessage("CRM_ACTIVITY_ENTITY_NAME_COMPANY"),
	'DEAL' => GetMessage("CRM_ACTIVITY_ENTITY_NAME_DEAL")
);

$currentEntityId = !empty($currentValues['EntityId']) ? $currentValues['EntityId'] : '';
$currentEntityType = !empty($currentValues['EntityType']) ? $currentValues['EntityType'] : '';
?>

<tbody id="crm_entity_base_form">
<tr>
	<td align="right" width="40%">
		<span style="font-weight: bold"><?=GetMessage("CRM_ACTIVITY_LABLE_ENTITY_ID")?></span>
	</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'EntityId', $currentEntityId, Array('size'=> 20))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%">
		<span style="font-weight: bold"><?=GetMessage("CRM_ACTIVITY_LABLE_ENTITY_TYPE")?></span>
	</td>
	<td width="60%">
		<select name="EntityType" onchange="BPCGDEA_getEntityFields(this.value)">
			<option value=""><?=GetMessage("CRM_ACTIVITY_SELECT_TYPE_ENTITY")?></option>
			<?foreach($listDefaultEntity as $entityType => $entityName):?>
				<option value="<?=htmlspecialcharsbx($entityType)?>"
					<?=($currentEntityType == $entityType) ? 'selected' : ''?>>
					<?=htmlspecialcharsbx($entityName)?>
				</option>
			<?endforeach;?>
		</select>
	</td>
</tr>
</tbody>

<tbody id="crm_entity_fields"><?=$renderEntityFields?></tbody>

<script>
	var BPCGDEA_getEntityFields = function(entityType)
	{
		if(!entityType)
			return;

		var container = BX('crm_entity_fields');
		container.innerHTML = '';

		BX.ajax.post(
			'/bitrix/tools/bizproc_activity_ajax.php',
			{
				'site_id': BX.message('SITE_ID'),
				'sessid' : BX.bitrix_sessid(),
				'document_type' : <?=Cutil::PhpToJSObject($documentType)?>,
				'activity': 'CrmGetDataEntityActivity',
				'entity_type': entityType,
				'content_type': 'html',
				'customer_action' : 'getEntityFields'
			},
			function(response) {
				if(response)
				{
					container.innerHTML = response;
				}
			}
		);
	};
</script>