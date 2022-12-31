<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/department.css");

CJSCore::Init(array('intranet_structure')); 

?><div class="department-profile"><?

$arSelectorParams = $arParams;
unset($arSelectorParams['AJAX_MODE']);
$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "sections", $arSelectorParams, $component);
$current_section = intval($arFilterValues[$arParams['FILTER_NAME'].'_UF_DEPARTMENT']);

?></div>

<?if ($arParams['bAdmin']):
$this->SetViewTarget("sidebar", 100);
?>

	<div class="department-profile-events">
		<div class="department-profile-events-title"><?=GetMessage("INTR_IS_TPL_ACTIONS")?></div>
		<div class="department-profile-events-cont">
			<?if(CModule::IncludeModule('bitrix24') && CBitrix24::isInvitingUsersAllowed() && CModule::IncludeModule('intranet')):
			$arInviteParams["UF_DEPARTMENT"] = $current_section;?>
			<a class="department-profile-events-item department-profile-add-sub" href="javascript:void(0)" onclick="<?=CIntranetInviteDialog::ShowInviteDialogLink($arInviteParams)?>"><i></i><?=GetMessage("INTR_IS_TPL_ACTION_INVITE")?></a>
			<?endif;        
			if (isset($_GET['structure_UF_DEPARTMENT']))
				$arStructureParams["IBLOCK_SECTION_ID"] = $current_section;  //parent department
			$arStructureParams["ACTION"] = "ADD";
			?>
			<a class="department-profile-events-item department-profile-subsection" href="" onclick="BX.IntranetStructure.ShowForm(<?=CUtil::PhpToJSObject($arStructureParams)?>); return false;"><i></i><?=GetMessage("INTR_IS_TPL_ACTION_ADD_DEP")?></a>
			<?
			if (intval($current_section) > 0):
				$arStructureParams["ACTION"] = "EDIT";
				$arStructureParams["UF_DEPARTMENT_ID"] = $current_section;
			?>
			<a class="department-profile-events-item department-profile-edit" href="" onclick="BX.IntranetStructure.ShowForm(<?=CUtil::PhpToJSObject($arStructureParams)?>); return false;"><i></i><?=GetMessage("INTR_IS_TPL_ACTION_EDIT_DEP")?></a>
				<?if ($arParams["TOP_DEPARTMENT"] != $current_section):?>
			<a class="department-profile-events-item department-profile-remove" onclick="
				if (confirm('<?=CUtil::JSEscape(GetMessage('ISV_confirm_delete_department'))?>'))
					BX.ajax.loadJSON(
						'<?="/bitrix/tools/intranet_structure.php"?>?action=delete_department&dpt_id=<?=$arStructureParams["UF_DEPARTMENT_ID"]?>&<?=bitrix_sessid_get()?>',
						function(result)
						{
							var parent_section_template='<?=$arParams["PATH_TO_CONPANY_DEPARTMENT"]?>';
							var intValue = parseInt(result.id);
							if (intValue)
							{
								var parent_section = parent_section_template.replace('#ID#', result.id);
								window.location.href = parent_section;
							}
							else if (result.errors)
							{
								alert(result.errors);
							}							
						}
					);
				else
					return false;"
			href="javascript:void(0)"><i></i><?=GetMessage("INTR_IS_TPL_ACTION_DELETE_DEP")?></a>
				<?endif?>
			<?endif?>		
		</div>
	</div>

<?
$this->EndViewTarget();
endif?>
