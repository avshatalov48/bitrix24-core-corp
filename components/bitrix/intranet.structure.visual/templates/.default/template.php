<?php

GLOBAL $APPLICATION;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column flexible-layout");

Bitrix\Main\UI\Extension::load(["ui.tooltip", "ui.buttons", "ui.design-tokens"]);

$arParams['PAGE_URL'] = $APPLICATION->GetCurPageParam("", array('action', 'section', 'level', 'mr', 'mode', 'sessid', 'dpt_id', 'dpt_to', 'dpt_from', 'user_id', 'type', 'undo', 'dpt_before', 'dpt_after', 'dpt_parent'));
$arParams['PAGE_URL_JS'] = CUtil::JSEscape($arParams['PAGE_URL']);

if (!function_exists('__intr_vis_get_div')):

	function __intr_vis_get_div($arEntry, $arUsers, $arParams, $can_edit, $l)
	{
		global $APPLICATION;
		static $cssSuffix = array(1 => 'first', 2 => 'second', 3 => 'third');

		$arJSEmployees = array();

		$bHasHead = $arEntry['UF_HEAD'] && $arUsers[$arEntry['UF_HEAD']];
		$bHasChildren = ($arEntry['DEPTH_LEVEL'] == $arParams['MAX_DEPTH']) && ($arEntry['__children'] > 0);

		$name = $arEntry['NAME'];
		?>
		<span class="structure-dept-block structure-dept-<?=$cssSuffix[$l].($bHasChildren ? ' structure-dept-nesting' : '').($can_edit ? ' structure-dept-editable' : '')?><?=$arEntry['IBLOCK_SECTION_ID']>0?'':' structure-dept-top'?>" id="bx_str_<?=$arEntry['ID']?>">
	<div class="structure-dept-title">
		<div class="structure-dept-title-text">
<?
if ($arEntry['DETAIL_URL']):
	?>
	<a href="<?=htmlspecialcharsbx($arEntry['DETAIL_URL'])?>" data-role="department_name" title="<?echo htmlspecialcharsbx($arEntry['NAME'])?>"><?echo htmlspecialcharsEx($arEntry['NAME'])?></a>
<?
else: // $arEntry['DETAIL_URL']
	?>
	<span data-role="department_name" title="<?echo htmlspecialcharsbx($arEntry['NAME'])?>"><?=htmlspecialcharsEx($arEntry['NAME'])?></span>
<?
endif; // $arEntry['DETAIL_URL']
?>
		</div>
<?
if ($can_edit):
	?>
	<div class="structure-icon-box">
			<div class="structure-edit-icon" data-role="department_edit" title="<?=GetMessage('ISV_HINT_EDIT')?>"></div>
			<div class="structure-delete-icon" data-role="department_delete" title="<?=GetMessage('ISV_HINT_DELETE')?>"></div>
			<div class="structure-add-icon" data-role="department_add" title="<?=GetMessage('ISV_HINT_ADD')?>"></div>
		</div>
<?
endif; // $can_edit
?>
	</div>
	<div class="structure-boss-block" data-role="department_head" data-user="<?=$arEntry['UF_HEAD']?>" data-dpt="<?=$arEntry['ID']?>">
<?
if ($bHasHead):

	$headName = strip_tags(CUser::FormatName($arParams['NAME_TEMPLATE'], $arUsers[$arEntry['UF_HEAD']]));
	$arJSEmployees[] = array(
		'ID' => $arEntry['UF_HEAD'],
		'NAME' => strip_tags($headName),
		'POSITION' => $arUsers[$arEntry['UF_HEAD']]['WORK_POSITION'],
		'PHOTO' => Uri::urnEncode($arUsers[$arEntry['UF_HEAD']]['PERSONAL_PHOTO']['CACHE']['src'] ?? null),
		'PROFILE' => $arUsers[$arEntry['UF_HEAD']]['PROFILE_URL']
	);

	if ($arUsers[$arEntry['UF_HEAD']]['PROFILE_URL']):
		?>
		<a href="<?=htmlspecialcharsbx($arUsers[$arEntry['UF_HEAD']]['PROFILE_URL'])?>" class="structure-avatar"<?if ($arUsers[$arEntry['UF_HEAD']]['PERSONAL_PHOTO']):?> style="background: url('<?=Uri::urnEncode($arUsers[$arEntry['UF_HEAD']]['PERSONAL_PHOTO']['CACHE']['src'])?>') no-repeat scroll center center transparent; background-size: cover;"<?endif;?> bx-tooltip-user-id="<?=($arParams['USE_USER_LINK'] == 'Y' ? $arEntry['UF_HEAD'] : '')?>" bx-tooltip-classname="intrantet-user-selector-tooltip"></a>
		<a href="<?=htmlspecialcharsbx($arUsers[$arEntry['UF_HEAD']]['PROFILE_URL'])?>" class="structure-boss-name" bx-tooltip-user-id="<?=($arParams['USE_USER_LINK'] == 'Y' ? $arEntry['UF_HEAD'] : '')?>" bx-tooltip-classname="intrantet-user-selector-tooltip"><?=$headName?></a>

	<?
	else: // $arUsers[$arEntry['UF_HEAD']]['PROFILE_URL']
		?>
		<span class="structure-avatar"<?if ($arUsers[$arEntry['UF_HEAD']]['PERSONAL_PHOTO']):?> style="background: url('<?=Uri::urnEncode($arUsers[$arEntry['UF_HEAD']]['PERSONAL_PHOTO']['CACHE']['src'])?>') no-repeat scroll center center transparent; background-size: cover;"<?endif;?> bx-tooltip-user-id="<?=($arParams['USE_USER_LINK'] == 'Y' ? $arEntry['UF_HEAD'] : '')?>" bx-tooltip-classname="intrantet-user-selector-tooltip"></span>
		<div class="structure-boss-name" bx-tooltip-user-id="<?=($arParams['USE_USER_LINK'] == 'Y' ? $arEntry['UF_HEAD'] : '')?>" bx-tooltip-classname="intrantet-user-selector-tooltip"><?=$headName?></div>
	<?
	endif; // $arUsers[$arEntry['UF_HEAD']]['PROFILE_URL']
	if ($arUsers[$arEntry['UF_HEAD']]['WORK_POSITION']):
		?>
		<span class="structure-manager" title="<?=htmlspecialcharsbx($arUsers[$arEntry['UF_HEAD']]['WORK_POSITION'])?>"><?=htmlspecialcharsEx($arUsers[$arEntry['UF_HEAD']]['WORK_POSITION'])?></span>
	<?
	endif; // $arUsers[$arEntry['UF_HEAD']]['WORK_POSITION']
endif; // $bHasHead
if ($can_edit):
	?>
	<div class="structure-designate-text structure-add-boss-text"><i></i><?=GetMessage('ISV_set_head')?></div>
<?
endif; // $can_edit;
?>
	</div>
<?
$cnt = ($arEntry['EMPLOYEES'] ?? null) ? count($arEntry['EMPLOYEES']) : 0;
$real_cnt = $cnt;
$bFirst = true;
?>
	<div class="structure-employee-block">
<?
for ($i = 0; $i < $cnt; $i++):

if ($arEntry['EMPLOYEES'][$i]['ID'] == $arEntry['UF_HEAD'])
{
	$real_cnt--;
	continue;
}

if ($bFirst):
?>
		<div class="structure-employee-title"><?=GetMessage('ISV_EMPLOYEES');?></div>
		<div class="structure-employee-list" data-role="department_employee_images">
<?
$bFirst = false;
endif; // $bFirst

$name = strip_tags(CUser::FormatName($arParams['NAME_TEMPLATE'], $arEntry['EMPLOYEES'][$i]));

?><span class="structure-avatar"<?if ($arEntry['EMPLOYEES'][$i]['PERSONAL_PHOTO']):?> style="background: url('<?=Uri::urnEncode($arEntry['EMPLOYEES'][$i]['PERSONAL_PHOTO']['CACHE']['src'])?>') no-repeat scroll center center transparent; background-size: cover;"<?endif;?> data-user="<?=$arEntry['EMPLOYEES'][$i]['ID']?>" data-dpt="<?=$arEntry['ID']?>" bx-tooltip-user-id="<?=($arParams['USE_USER_LINK'] == 'Y' ? $arEntry['EMPLOYEES'][$i]['ID'] : '')?>" bx-tooltip-classname="intrantet-user-selector-tooltip"></span><?

			$arJSEmployees[] = array(
				'ID' => $arEntry['EMPLOYEES'][$i]['ID'],
				'NAME' => $name,
				'POSITION' => $arEntry['EMPLOYEES'][$i]['WORK_POSITION'],
				'PHOTO' => is_array($arEntry['EMPLOYEES'][$i]['PERSONAL_PHOTO']) ? Uri::urnEncode($arEntry['EMPLOYEES'][$i]['PERSONAL_PHOTO']['CACHE']['src']) : '',
				'PROFILE' => $arEntry['EMPLOYEES'][$i]['PROFILE_URL']
			);
			endfor;

			if (!$bFirst):
			?>
		</div>
<?
endif; // $bFirst
if ($real_cnt > 0):
	$title = GetMessage('ISV_EMP_COUNT_'.$real_cnt);
	if (!$title)
		$title = GetMessage('ISV_EMP_COUNT_MUL');
	?>
	<a href="javascript:void(0)" class="structure-more-employee" data-role="department_employee_count"><?=str_replace('#NUM#', $real_cnt, $title);?></a>
<?
endif;
if ($can_edit):
	?>
	<div class="structure-designate-text structure-add-empl-text"><i></i><?=GetMessage('ISV_add_emp')?></div>
<?
endif; // $can_edit
?>
	</div>
<?
if ($bHasChildren):
	$childrenNum = (int) $arEntry['__children'];
	$title = GetMessage('ISV_CHILDREN_COUNT_'.$childrenNum);
	if (!$title)
		$title = GetMessage('ISV_CHILDREN_COUNT_MUL');
	?>
	<div class="structure-nesting-block" data-role="department_next_link">
		<div class="structure-nesting-bottom1"></div>
		<div class="structure-nesting-bottom2"></div>
		<div class="structure-nesting-bottom3"></div>
		<span class="structure-nesting-text"><?=str_replace('#NUM#', $childrenNum, $title);?></span>
	</div>
<?
endif; // $bHasChildren
if ($can_edit):
	?>
	<div class="structure-designate-text structure-add-dept-text"><i></i><?=GetMessage('ISV_add_dep')?></div>
<?
endif; // $can_edit
?>
</span>
		<script>
			new BX.IntranetVSBlock({
				section_id: <?=intval($arEntry['ID'])?>,
				section_level: <?=intval($arEntry['DEPTH_LEVEL'])?>,
				section_parent: '<?=$arEntry['IBLOCK_SECTION_ID'] ? $arEntry['IBLOCK_SECTION_ID'] : 0?>',
				node: 'bx_str_<?=$arEntry['ID']?>',
				head: <?=intval($arEntry['UF_HEAD'])?>,
				employees: <?=CUtil::PhpToJsObject($arJSEmployees)?>,
				hasChildren: <?=$bHasChildren ? 'true' : 'false';?>,
				disableDrag: <?=$arEntry['IBLOCK_SECTION_ID'] > 0 ? 'false' : 'true';?>,
				disableDragDest: <?=($arEntry['DISABLE_DRAG_DEST'] ?? null) ? 'true' : 'false';?>

			});
		</script>
		<?
	} // function __intr_vis_get_div

	function __intr_vis_get_sorter($afterId, $beforeId, $depthLevel, $parentSection)
	{
		$r = RandString(8);
		?>
		<div class="structure-sorter<?=!$beforeId ? ' structure-sorter-last' : ''?>" id="vis_sorter_<?=$r?>"><div class="structure-sorter-inner"></div></div>
		<script>
			new BX.IntranetVSSorter({
				node: 'vis_sorter_<?=$r?>',
				afterId: <?=$afterId ? $afterId : 'null'?>,
				beforeId: <?=$beforeId ? $beforeId : 'null'?>,
				depthLevel: <?=$depthLevel?>,
				parentSection: <?=intval($parentSection)?>
			});
		</script>
		<?
	} // function __intr_vis_get_sorter
endif; // function_exists


/******************************* OUTPUT START **********************************************/


if (($arResult['__SKIP_ROOT'] ?? null) != 'Y'):
	if ($arResult['UNDO_ID'] ?? null):
		?>
		<span class="structure-undo" id="bx_undo_block">
	<span class="structure-undo-inner">
		<span class="structure-undo-close" onclick="BX.IntranetVS.get().CloseUndo();"></span>
		<span class="structure-undo-text"><?=$arResult['UNDO_TEXT']?></span>
		<a class="structure-undo-link" href="javascript:void(0)" onclick="BX.IntranetVS.get().Undo('<?=$arResult['UNDO_ID']?>')"><?=GetMessage('ISV_UNDO')?></a>
	</span>
</span>
	<?
	endif; // UNDO_ID
	?>

	<script>
		BX.message({
			confirm_move_department: '<?=CUtil::JSEscape(GetMessage('ISV_confirm_move_department'))?>',
			confirm_delete_department: '<?=CUtil::JSEscape(GetMessage('ISV_confirm_delete_department'))?>',
			confirm_set_head: '<?=CUtil::JSEscape(GetMessage('ISV_confirm_set_head'))?>',
			confirm_change_department_0: '<?=CUtil::JSEscape(GetMessage('ISV_confirm_change_department_0'))?>',
			confirm_change_department_1: '<?=CUtil::JSEscape(GetMessage('ISV_confirm_change_department_1'))?>',
			set_head: '<?=CUtil::JSEscape(GetMessage('ISV_set_head'))?>',
			undo_link_text: '<?=CUtil::JSEscape(GetMessage('ISV_UNDO'))?>'
		});

		new BX.IntranetVS('bx_visual_structure', {
			EDIT: <?=$arResult['CAN_EDIT'] ? 'true' : 'false';?>,
			URL: '<?=$arParams['PAGE_URL_JS']?>',
			USE_USER_LINK: <?=$arParams['USE_USER_LINK'] == 'Y' ? 'true' : 'false'?>,
			MAX_DEPTH: <?=intval($arParams['MAX_DEPTH'])?>,
			<?
			if ($arResult['UNDO_ID'] ?? null):
			?>
			UNDO: {
				ID: '<?=$arResult['UNDO_ID']?>',
				CONT: 'bx_undo_block',
				TEXT: '<?=CUtil::JSEscape($arResult['UNDO_TEXT'])?>'
			},
			<?
			endif;
			?>
			SKIP_CONFIRM: true
		});

		window.BXSTRUCTURECALLBACK = function()
		{
			this.close();
			BX.IntranetVS.get().Reload();
		}
	</script>
	<?
	$arTopEntry = array_shift($arResult['ENTRIES']);
if ($arParams['MODE'] != 'reload'):
	if ($arResult['CAN_EDIT']):
		$this->SetViewTarget("pagetitle", 100);

		if (CModule::IncludeModule('bitrix24')):
			if (CBitrix24::isInvitingUsersAllowed()):
				?><button
				class="ui-btn ui-btn-primary"
				onclick="<?=CIntranetInviteDialog::ShowInviteDialogLink([
					'analyticsLabel' => [
						'analyticsLabel[source]' => 'visualStructure',
					]
				])?>"
				><?=GetMessage("ISV_B24_INVITE")?></button><?
			endif;

			?><button
			class="ui-btn ui-btn-primary"
			onclick="BX.IntranetStructure.ShowForm({IBLOCK_SECTION_ID: <?=intval($arTopEntry['ID'])?>})"
			><?=GetMessage('ISV_ADD_DEPARTMENT')?></button><?
		else:
			?><button
			class="ui-btn ui-btn-primary"
			onclick="BX.IntranetStructure.ShowForm({IBLOCK_SECTION_ID: <?=intval($arTopEntry['ID'])?>})"
			><?=GetMessage('ISV_ADD_DEPARTMENT')?></button><?
		endif;

		$this->EndViewTarget();

	endif;
	?>
	<div id="bx_visual_structure" class="structure-wrap" style="overflow: auto; position: relative;">
		<?
		endif; // MODE!=reload
		?>

		<table cellpadding="0" cellspacing="0" border="0" align="center" id="bx_str_level1_table">
			<tr>
				<td class="bx-str-top" align="center">
					<?
					$arTopEntry['DISABLE_DRAG_DEST'] = $arResult['HAS_MULTIPLE_ROOTS'];
					__intr_vis_get_div($arTopEntry, $arResult['USERS'], $arParams, $arResult['HAS_MULTIPLE_ROOTS'] ? false : $arResult['CAN_EDIT'], 1);
					?>
				</td>
			</tr>
		</table>
		<?
		endif; // __SKIP_ROOT

		$arEntries = array();
		$arSubEntries = array();

		$q = ($arResult['__SKIP_ROOT'] ?? null) != 'Y' ? 2 : 1;
		foreach ($arResult['ENTRIES'] as $key => $arEntry)
		{

			if ($arEntry['DEPTH_LEVEL']-$arParams['LEVEL'] > $q)
			{
				if (!isset($arSubEntries[$arEntry['IBLOCK_SECTION_ID']]))
					$arSubEntries[$arEntry['IBLOCK_SECTION_ID']] = array($arEntry);
				else
					$arSubEntries[$arEntry['IBLOCK_SECTION_ID']][] = $arEntry;
			}
			else
			{
				$arEntries[] = $arEntry;
			}
		}

		if (($cnt = count($arEntries)) > 0)
		{
			?>
			<table cellpadding="0" cellspacing="0" border="0" align="center" id="bx_str_level<?=$arParams['LEVEL']+2?>_table">
				<tr class="bx-str-l2">
					<?
					$arPrevEntry = null;
					foreach ($arEntries as $key => $arEntry)
					{
						$bSingle = $cnt == 1;

						$bFirst = !$bSingle && ($key == 0);
						$bLast = !$bSingle && ($key == $cnt-1);
						?>
						<td <?echo $bFirst ? 'class="bx-str-first"' : ($bLast ? 'class="bx-str-last"' : ($bSingle ? 'class="bx-str-single"' : ''))?>>
							<?
							if (!$bSingle && $arResult['CAN_EDIT'])
								__intr_vis_get_sorter($arPrevEntry ? $arPrevEntry['ID'] : null, $arEntry['ID'], 2, $arEntry['IBLOCK_SECTION_ID']);

							if (!$bSingle && $arResult['CAN_EDIT'] && $bLast)
								__intr_vis_get_sorter($arEntry['ID'], null, 2, $arEntry['IBLOCK_SECTION_ID']);

							__intr_vis_get_div($arEntry, $arResult['USERS'], $arParams, $arResult['CAN_EDIT'], 2);
							?>
						</td>
						<?
						$arPrevEntry = $arEntry;
					}
					?>
				</tr><tr>
					<?
					$bFirst = true;
					foreach ($arEntries as $key => $arEntry)
					{
						?>
						<td valign="top">
							<?
							if (isset($arSubEntries[$arEntry['ID']]))
							{
								?>
								<table id="bx_str_children_<?=$arEntry['ID']?>" cellspacing="0" cellpadding="0" border="0">
									<?
									$cnt1 = count($arSubEntries[$arEntry['ID']]);
									$bSingle = $cnt1 < 2;
									$arPrevEntry = null;
									foreach ($arSubEntries[$arEntry['ID']] as $key => $arSubEntry)
									{
										$bLast = $key==$cnt1-1;
										?>
										<tr class="bx-str-l3">
											<td class="bx-str-l3-connector<?=$bFirst ? ' bx-str-first' : ''?><?=$bLast ? ' bx-str-last' : ''?>"><img src="/bitrix/images/1.gif" height="1" width="17" border="0" /></td>
											<td>
												<?
												if (!$bSingle && $arResult['CAN_EDIT'])
													__intr_vis_get_sorter($arPrevEntry ? $arPrevEntry['ID'] : null, $arSubEntry['ID'], 3, $arSubEntry['IBLOCK_SECTION_ID']);

												if (!$bSingle && $arResult['CAN_EDIT'] && $bLast)
													__intr_vis_get_sorter($arSubEntry['ID'], null, 3, $arSubEntry['IBLOCK_SECTION_ID']);

												__intr_vis_get_div($arSubEntry, $arResult['USERS'], $arParams, $arResult['CAN_EDIT'], 3);
												?>
											</td>
										</tr>
										<?
										$arPrevEntry = $arSubEntry;
									}
									?>
								</table>
								<?
							}
							?>
						</td>
						<?
						$bFirst = false;
					}
					?>
				</tr>
			</table>
			<?
		}
		?>
		<div style="height: 30px;"></div>
		<?
		if ($arParams['MODE'] != 'reload'):
		?>
	</div>
<?
endif;
?>
