<?
IncludeModuleLangFile(__FILE__);
if(CModule::IncludeModule('crm')):

?>
<tr valign="top">
	<td><?echo GetMessage("CRM_MAIL_MAIL")?>:</td>
	<td valign="top">
		<?=COption::GetOptionString('crm', 'mail', ''); ?>	
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><?echo GetMessage("CRM_ENTITY_REGEXP")?><br>
	<?echo GetMessage("CRM_ENTITY_REGEXP_NOTES")?></td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("CRM_MAIL_ENTITY_LEAD")?></td>
	<td valign="top">
		<input type="text" id="W_CRM_ENTITY_REGEXP_LEAD" name="W_CRM_ENTITY_REGEXP_LEAD" value="<?=(!empty($W_CRM_ENTITY_REGEXP_LEAD) ? $W_CRM_ENTITY_REGEXP_LEAD : "\[LID#([0-9]+)\]");?>"  />	
	</td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("CRM_MAIL_ENTITY_CONTACT")?></td>
	<td valign="top">
		<input type="text" id="W_CRM_ENTITY_REGEXP_CONTACT" name="W_CRM_ENTITY_REGEXP_CONTACT" value="<?=(!empty($W_CRM_ENTITY_REGEXP_CONTACT) ? $W_CRM_ENTITY_REGEXP_CONTACT : "\[CID#([0-9]+)\]");?>"  />	
	</td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("CRM_MAIL_ENTITY_COMPANY")?></td>
	<td valign="top">
		<input type="text" id="W_CRM_ENTITY_REGEXP_COMPANY" name="W_CRM_ENTITY_REGEXP_COMPANY" value="<?=(!empty($W_CRM_ENTITY_REGEXP_COMPANY) ? $W_CRM_ENTITY_REGEXP_COMPANY : "\[COID#([0-9]+)\]");?>"  />	
	</td>
</tr>
<tr valign="top">
	<td><?echo GetMessage("CRM_MAIL_ENTITY_DEAL")?></td>
	<td valign="top">
		<input type="text" id="W_CRM_ENTITY_REGEXP_DEAL" name="W_CRM_ENTITY_REGEXP_DEAL" value="<?=(!empty($W_CRM_ENTITY_REGEXP_DEAL) ? $W_CRM_ENTITY_REGEXP_DEAL : "\[DID#([0-9]+)\]");?>" />	
	</td>
</tr>
<?endif;?>