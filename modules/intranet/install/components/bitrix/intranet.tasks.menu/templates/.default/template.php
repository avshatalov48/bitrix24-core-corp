<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="its-menu" id="its_menu_div">
<table cellpadding="0" cellspacing="0" border="0" class="its-menu">
	<thead><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td></tr></thead>
	<tbody>
		<tr>
			<td class="left"><div class="empty"></div></td>
			<td class="center">
				<table cellpadding="0" cellspacing="0" border="0" class="its-menu-inner">
					<tr>
						<?if ($arParams["PAGE_ID"] == "group_tasks_task" || $arParams["PAGE_ID"] == "user_tasks_task"):?>
							<td><div class="section-separator"></div></td>
							<td>
								<div class="controls controls-view up">
									<a href="<?= $arResult["Urls"]["TasksList"] ?>" title="<?= GetMessage("INTMT_BACK2LIST_DESCR") ?>">
										<?= GetMessage("INTMT_BACK2LIST") ?>
									</a>
								</div>
							</td>
							<td><div class="separator"></div></td>
							<?if ($arParams["ACTION"] == "view" && !$arResult["Perms"]["HideArchiveLinks"]):?>
								<td>
									<div class="controls controls-view tasks_add">
										<a href="<?= $arResult["Urls"]["EditTask"] ?>" title="<?= GetMessage("INTMT_EDIT_TASK_DESCR") ?>">
											<?= GetMessage("INTMT_EDIT_TASK") ?>
										</a>
									</div>
								</td>
							<?elseif ($arParams["ACTION"] == "edit"):?>
								<td>
									<div class="controls controls-view tasks_add">
										<a href="<?= $arResult["Urls"]["ViewTask"] ?>" title="<?= GetMessage("INTMT_VIEW_TASK_DESCR") ?>">
											<?= GetMessage("INTMT_VIEW_TASK") ?>
										</a>
									</div>
								</td>
							<?endif;?>

						<?elseif ($arParams["PAGE_ID"] == "group_tasks_view" || $arParams["PAGE_ID"] == "user_tasks_view"):?>

							<td><div class="section-separator"></div></td>
							<td>
								<div class="controls controls-view up">
									<a href="<?= $arResult["Urls"]["TasksList"] ?>" title="<?= GetMessage("INTMT_BACK2LIST_DESCR") ?>">
										<?= GetMessage("INTMT_BACK2LIST") ?>
									</a>
								</div>
							</td>

						<?else:?>

							<?if (StrLen($arResult["Urls"]["CreateTask"]) > 0 && !$arResult["Perms"]["HideArchiveLinks"]):?>
								<td><div class="section-separator"></div></td>
								<td>
									<div class="controls controls-view tasks_add">
										<a href="<?= $arResult["Urls"]["CreateTask"] ?>" id="intask_create_task_a" title="<?= GetMessage("INTMT_CREATE_TASK_DESCR") ?>">
											<?= GetMessage("INTMT_CREATE_TASK") ?>
										</a>
									</div>
								</td>
							<?endif;?>

							<?if ($arResult["Perms"]["modify_folders"] && $arParams["PAGE_ID"] == "group_tasks" && !$arResult["Perms"]["HideArchiveLinks"]):?>
								<td><div class="separator"></div></td>
								<td>
									<div class="controls controls-action sections_add">
										<a href="javascript:void(0);" id="intask_create_folder_a" title="<?= GetMessage("INTMT_CREATE_FOLDER_DESCR") ?>">
											<?= GetMessage("INTMT_CREATE_FOLDER") ?>
										</a>
									</div>
								</td>
							<?endif;?>

							<td><div class="separator"></div></td>
							<td>
								<div class="controls controls-action">
									<a href="javascript:void(0);">
										<?= GetMessage("INTMT_VIEW") ?>:
									</a>
								</div>
							</td>
							<td>
								<div class="controls">
									<select name="user_settings_id" onchange="window.location='<?= $arResult["Urls"]["ChangeView"] ?>' + this.options[this.selectedIndex].value">
										<option value="0"><?= GetMessage("INTMT_DEFAULT") ?></option>
										<?foreach ($arResult["Views"] as $view):?>
											<option value="<?= $view["ID"] ?>"<?= (($view["ID"] == $arResult["CurrentView"]) ? " selected" : "") ?>><?= $view["TITLE"] ?></option>
										<?endforeach;?>
									</select>
								</div>
							</td>
							<td><div class="separator"></div></td>

							<td>
								<div class="controls controls-action create_view">
									<a href="<?=$arResult["Urls"]["CreateView"]?>" title="<?= GetMessage("INTMT_CREATE_VIEW") ?>">
										&nbsp;
									</a>
								</div>
							</td>

							<?if ($arResult["CurrentView"] > 0):?>
								<td>
									<div class="controls controls-action edit_view">
										<a href="<?=$arResult["Urls"]["EditView"]?>" title="<?= GetMessage("INTMT_EDIT_VIEW") ?>">
											&nbsp;
										</a>
									</div>
								</td>

								<td>
									<div class="controls controls-action delete_view">
										<a href="javascript:if (confirm('<?= GetMessage("INTMT_DELETE_VIEW_CONF") ?>')) window.location='<?=$arResult["Urls"]["DeleteView"]?>'" title="<?= GetMessage("INTMT_DELETE_VIEW") ?>">
											&nbsp;
										</a>
									</div>
								</td>

							<?endif;?>
							<?if ($arParams['TASK_TYPE'] != 'group' && $USER->GetID() == $arParams['OWNER_ID']):?>
								<td><div class="separator"></div></td>
								<td>
									<div class="controls controls-action outlook">
										<a href="javascript:javascript:<?echo htmlspecialcharsbx(CIntranetUtils::GetStsSyncURL(array(
											'LINK_URL' => '/'.$USER->GetID().'/',
										), 'tasks'))?>" title="<?echo GetMessage('INTMT_OUTLOOK_TITLE')?>"><?echo GetMessage('INTMT_OUTLOOK')?></a>
									</div>
								</td>
							<?endif;?>

						<?endif;?>
					</tr>
				</table>
			</td>
			<td class="right"><div class="empty"></div></td></tr>
	</tbody>
	<tfoot><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr></tfoot>
</table>

</div>
