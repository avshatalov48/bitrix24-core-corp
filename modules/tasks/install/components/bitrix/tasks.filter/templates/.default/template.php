<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>
<div class="task-filter-head">
	<div class="task-filter-title"><?php echo GetMessage("TASKS_FILTER")?></div>
	<div class="task-filter-mode"><a href="" onclick="return SwitchTaskFilter(this);"<?php if ($arParams["ADV_FILTER"]["F_ADVANCED"] != "Y"):?> class="task-filter-mode-selected"<?php endif?>><?php echo GetMessage("TASKS_FILTER_COMMON")?></a><span></span><a href="" onclick="return SwitchTaskFilter(this);"<?php if ($arParams["ADV_FILTER"]["F_ADVANCED"] == "Y"):?> class="task-filter-mode-selected"<?php endif?>><?php echo GetMessage("TASKS_FILTER_EXTENDED")?></a></div>
</div>

<div class="task-filter-body">
	<?php if ($arResult["TASK_TYPE"] != "group"):?>
		<ul class="task-filter-items">
			<?php foreach($arResult["PREDEFINED_FILTERS"]["ROLE"] as $key=>$filter):?>
			<li class="task-filter-item task-filter-<?php echo $filter["CLASS"]?><?php if (isset($filter["SELECTED"]) && $filter["SELECTED"] == true):?> task-filter-item-selected<?php endif?>">
				<a class="task-filter-item-link" href="<?php echo $arParams["PATH_TO_TASKS"]."?FILTERR=".$key.(isset($_GET["FILTERS"]) ? "&FILTERS=".htmlspecialcharsbx($_GET["FILTERS"]) : "").(isset($_GET["VIEW"]) ? "&VIEW=".intval($_GET["VIEW"]) : "")?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo $filter["TITLE"]?><?php if ($key == 4 && $arResult["EXTRA_COUNT"][0] > 0):?><span class="task-item-updates" title="<?php echo GetMessage("TASKS_FILTER_NOT_ACCEPTED")?>"><span class="task-item-updates-inner"><?php echo $arResult["EXTRA_COUNT"][0]?></span></span><?php endif?><?php if ($key == 4 && $arResult["EXTRA_COUNT"][1] > 0):?><span class="task-item-updates task-item-updates-waiting" title="<?php echo GetMessage("TASKS_FILTER_IN_CONTROL")?>"><span class="task-item-updates-inner"><?php echo $arResult["EXTRA_COUNT"][1]?></span></span><?php endif?></span><span class="task-filter-item-number"><?php echo $filter["COUNT"]?></span></a>
			</li>
			<?php endforeach?>
		</ul>
		<div class="task-filter-subtitle"><i></i><span><?php echo GetMessage("TASKS_FILTER_STATUSES")?></span></div>
	<?php endif?>
	<ul class="task-filter-items">
		<?php foreach($arResult["PREDEFINED_FILTERS"]["STATUS"] as $key=>$filter):?>
		<li class="task-filter-item task-filter-status-<?php echo $filter["CLASS"]?><?php if (isset($filter["SELECTED"]) && $filter["SELECTED"] == true):?> task-filter-item-selected<?php endif?>">
			<a class="task-filter-item-link" href="<?php echo $arParams["PATH_TO_TASKS"]."?FILTERS=".$key.(isset($_GET["FILTERR"]) ? "&FILTERR=".htmlspecialcharsbx($_GET["FILTERR"]) : "").(isset($_GET["VIEW"]) ? "&VIEW=".intval($_GET["VIEW"]) : "")?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo $filter["TITLE"]?></span><span class="task-filter-item-number"><?php echo $filter["COUNT"]?></span></a>
		</li>
		<?php endforeach?>
	</ul>
</div>

<div class="task-filter-advanced-body">
	<form action="<?php echo $arParams["PATH_TO_TASKS"]?>" method="GET" name="task-filter-advanced-form">
		<div class="filter-block">
			<input type="hidden" name="VIEW" value="<?php if ($arParams["VIEW_TYPE"] == "list") { echo 1; } elseif ($arParams["VIEW_TYPE"] == "gantt") { echo 2; } else { echo 0; }?>" />
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-title"><?php echo GetMessage("TASKS_FILTER_ID")?></label>
				<input class="filter-textbox" type="text" name="F_ID" id="filter-field-title" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_ID"])?>" />
			</div>
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-title"><?php echo GetMessage("TASKS_QUICK_TITLE")?></label>
				<input class="filter-textbox" type="text" name="F_TITLE" id="filter-field-title" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_TITLE"])?>" />
			</div>
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-responsible"><?php echo GetMessage("TASKS_RESPONSIBLE")?></label>
				<?php
					$userName = "";
					if (intval($arParams["ADV_FILTER"]["F_RESPONSIBLE"]) > 0)
					{
						$rsUser = CUser::GetById(intval($arParams["ADV_FILTER"]["F_RESPONSIBLE"]));
						if ($arUser = $rsUser->Fetch())
						{
							$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser);
						}
					}
				?>
				<span class="webform-field webform-field-textbox<?php if(!strlen($userName)):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
					<span class="webform-field-textbox-inner">
						<input type="text" id="filter-field-responsible" class="webform-field-textbox" value="<?php echo $userName?>" />
						<a class="webform-field-textbox-clear" href=""></a>
					</span>
				</span>
				<input type="hidden" name="F_RESPONSIBLE" value="<?php echo intval($arParams["ADV_FILTER"]["F_RESPONSIBLE"])?>" />
				<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:intranet.user.selector.new",
						".default",
						array(
							"MULTIPLE" => "N",
							"NAME" => "FILTER_RESPONSIBLE",
							"INPUT_NAME" => "filter-field-responsible",
							"VALUE" => intval($arParams["ADV_FILTER"]["F_RESPONSIBLE"]),
							"POPUP" => "Y",
							"ON_SELECT" => "onFilterResponsibleSelect",
							"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
							'SHOW_INACTIVE_USERS' => 'Y',
							'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
							'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
							'SHOW_LOGIN' => 'Y'
						),
						null,
						array("HIDE_ICONS" => "Y")
					);
				?>
			</div>
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-director"><?php echo GetMessage("TASKS_CREATOR")?></label>
				<?php
					$userName = "";
					if (intval($arParams["ADV_FILTER"]["F_CREATED_BY"]) > 0)
					{
						$rsUser = CUser::GetById(intval($arParams["ADV_FILTER"]["F_CREATED_BY"]));
						if ($arUser = $rsUser->Fetch())
						{
							$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser);
						}
					}
				?>
				<span class="webform-field webform-field-textbox<?php if(!strlen($userName)):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
					<span class="webform-field-textbox-inner">
						<input type="text" id="filter-field-director" class="webform-field-textbox" value="<?php echo $userName?>" />
						<a class="webform-field-textbox-clear" href=""></a>
					</span>
				</span>
				<input type="hidden" name="F_CREATED_BY" value="<?php echo intval($arParams["ADV_FILTER"]["F_CREATED_BY"])?>" />
				<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:intranet.user.selector.new",
						".default",
						array(
							"MULTIPLE" => "N",
							"NAME" => "FILTER_CREATED_BY",
							"INPUT_NAME" => "filter-field-director",
							"VALUE" => intval($arParams["ADV_FILTER"]["F_CREATED_BY"]),
							"POPUP" => "Y",
							"ON_SELECT" => "onFilterCreatedBySelect",
							"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
							'SHOW_INACTIVE_USERS' => 'Y',
							'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
							'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
							'SHOW_LOGIN' => 'Y'
						),
						null,
						array("HIDE_ICONS" => "Y")
					);
				?>
			</div>
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-assistant"><?php echo GetMessage("TASKS_ACCOMPLICE")?></label>
				<?php
					$userName = "";
					if (intval($arParams["ADV_FILTER"]["F_ACCOMPLICE"]) > 0)
					{
						$rsUser = CUser::GetById(intval($arParams["ADV_FILTER"]["F_ACCOMPLICE"]));
						if ($arUser = $rsUser->Fetch())
						{
							$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser);
						}
					}
				?>
				<span class="webform-field webform-field-textbox<?php if(!strlen($userName)):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
					<span class="webform-field-textbox-inner">
						<input type="text" id="filter-field-assistant" class="webform-field-textbox" value="<?php echo $userName?>" />
						<a class="webform-field-textbox-clear" href=""></a>
					</span>
				</span>
				<input type="hidden" name="F_ACCOMPLICE" value="<?php echo intval($arParams["ADV_FILTER"]["F_ACCOMPLICE"])?>" />
				<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:intranet.user.selector.new",
						".default",
						array(
							"MULTIPLE" => "N",
							"NAME" => "FILTER_ACCOMPLICE",
							"INPUT_NAME" => "filter-field-assistant",
							"VALUE" => intval($arParams["ADV_FILTER"]["F_ACCOMPLICE"]),
							"POPUP" => "Y",
							"ON_SELECT" => "onFilterAccompliceSelect",
							"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
							'SHOW_INACTIVE_USERS' => 'Y',
							'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
							'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
							'SHOW_LOGIN' => 'Y'
						),
						null,
						array("HIDE_ICONS" => "Y")
					);
				?>
			</div>
			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-auditor"><?php echo GetMessage("TASKS_AUDITOR")?></label>
				<?php
					$userName = "";
					if (intval($arParams["ADV_FILTER"]["F_AUDITOR"]) > 0)
					{
						$rsUser = CUser::GetById(intval($arParams["ADV_FILTER"]["F_AUDITOR"]));
						if ($arUser = $rsUser->Fetch())
						{
							$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser);
						}
					}
				?>
				<span class="webform-field webform-field-textbox<?php if(!strlen($userName)):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
					<span class="webform-field-textbox-inner">
						<input type="text" id="filter-field-auditor" class="webform-field-textbox" value="<?php echo $userName?>" />
						<a class="webform-field-textbox-clear" href=""></a>
					</span>
				</span>
				<input type="hidden" name="F_AUDITOR" value="<?php echo intval($arParams["ADV_FILTER"]["F_AUDITOR"])?>" />
				<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:intranet.user.selector.new",
						".default",
						array(
							"MULTIPLE" => "N",
							"NAME" => "FILTER_AUDITOR",
							"INPUT_NAME" => "filter-field-auditor",
							"VALUE" => intval($arParams["ADV_FILTER"]["F_AUDITOR"]),
							"POPUP" => "Y",
							"ON_SELECT" => "onFilterAuditorSelect",
							"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false)),
							'SHOW_INACTIVE_USERS' => 'Y',
							'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
							'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
							'SHOW_LOGIN' => 'Y'
						),
						null,
						array("HIDE_ICONS" => "Y")
					);
				?>
			</div>

			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-status"><?php echo GetMessage("TASKS_FILTER_STATUS")?></label>
				<select id="filter-field-status" name="F_STATUS" class="filter-dropdown">
					<?php foreach($arResult["ADVANCED_STATUSES"] as $key=>$filter):?>
						<option value="<?php echo $key?>"<?php if ($arParams["ADV_FILTER"]["F_STATUS"] == $key):?> selected<?php endif?>><?php echo $filter["TITLE"]?></option>
					<?php endforeach?>
				</select>
			</div>

			<?php if ($arResult["TASK_TYPE"] != "group"):?>
				<div class="filter-field">
					<label for="filter-field-employee" class="filter-field-title"><?php echo GetMessage("TASKS_FILTER_WORKGROUP")?></label>
					<?php
						$groupName = "";
						if (intval($arParams["ADV_FILTER"]["F_GROUP_ID"]) > 0)
						{
							$arGroup = CSocNetGroup::GetById(intval($arParams["ADV_FILTER"]["F_GROUP_ID"]));
							if ($arGroup)
							{
								$groupName = $arGroup["NAME"];
							}
						}
					?>
					<span class="webform-field webform-field-textbox<?php if(!strlen($groupName)):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
						<span class="webform-field-textbox-inner" id="task-report-filter-group">
							<input type="text" id="filter-field-group" class="webform-field-textbox" value="<?php echo $groupName?>" />
							<a class="webform-field-textbox-clear" href=""></a>
						</span>
					</span>
					<input type="hidden" name="F_GROUP_ID" value="<?php echo intval($arParams["ADV_FILTER"]["F_GROUP_ID"])?>" />
					<?php
						$name = $APPLICATION->IncludeComponent(
							"bitrix:socialnetwork.group.selector", ".default", array(
								"BIND_ELEMENT" => "task-report-filter-group",
								"JS_OBJECT_NAME" => "filterGroupsPopup",
								"ON_SELECT" => "onFilterGroupSelect",
								"SEARCH_INPUT" => "filter-field-group",
								"SELECTED" => $arParams["ADV_FILTER"]["F_GROUP_ID"] ? $arParams["ADV_FILTER"]["F_GROUP_ID"] : 0
							), null, array("HIDE_ICONS" => "Y")
						);
					?>
				</div>
			<?php endif?>

			<div class="filter-field">
				<label class="filter-field-title" for="filter-field-tags-link"><?php echo GetMessage("TASKS_FILTER_BY_TAG")?>: <a class="webform-field-action-link" href="" id="filter-field-tags-link"><?php echo GetMessage("TASKS_FILTER_SELECT")?></a><?php $name = $APPLICATION->IncludeComponent(
					"bitrix:tasks.tags.selector",
					".default",
					array(
						"NAME" => "TAGS",
						"VALUE" => $arParams["~ADV_FILTER"]["F_TAGS"],
						"SILENT" => "Y",
						"ON_UPDATE" => "onUpdateTagLine"
					),
					null,
					array("HIDE_ICONS" => "Y")
				);?></label>
				<span class="task-filter-tags-line" id="task-filter-tags-line"><?php echo $arParams["ADV_FILTER"]["F_TAGS"]?></span><input type="hidden" value="<?php echo $arParams["ADV_FILTER"]["F_TAGS"]?>" name="F_TAGS" />
			</div>

			<div class="filter-field filter-field-date-interval">
				<label class="filter-field-title"><?php echo GetMessage("TASKS_FILTER_CREAT_DATE")?></label>
				<input type="text" class="filter-date-interval-from" name="F_DATE_FROM" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_DATE_FROM"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" href="" class="filter-date-interval-calendar" id="filter-date-interval-calendar-from"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a><span class="filter-date-interval-hellip">&hellip;</span><input type="text" class="filter-date-interval-to" name="F_DATE_TO" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_DATE_TO"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" class="filter-date-interval-calendar" href="" id="filter-date-interval-calendar-to"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a>
			</div>
			<div class="filter-field filter-field-date-interval">
				<label class="filter-field-title"><?php echo GetMessage("TASKS_FILTER_CLOSE_DATE")?></label>
				<input type="text" class="filter-date-interval-from" name="F_CLOSED_FROM" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_CLOSED_FROM"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" href="" class="filter-date-interval-calendar" id="filter-closed-interval-calendar-from"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a><span class="filter-date-interval-hellip">&hellip;</span><input type="text" class="filter-date-interval-to" name="F_CLOSED_TO" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_CLOSED_TO"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" class="filter-date-interval-calendar" href="" id="filter-closed-interval-calendar-to"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a>
			</div>
			<div class="filter-field filter-field-date-interval">
				<label class="filter-field-title"><?php echo GetMessage("TASKS_FILTER_ACTIVE_DATE")?></label>
				<input type="text" class="filter-date-interval-from" name="F_ACTIVE_FROM" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_ACTIVE_FROM"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" href="" class="filter-date-interval-calendar" id="filter-active-interval-calendar-from"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a><span class="filter-date-interval-hellip">&hellip;</span><input type="text" class="filter-date-interval-to" name="F_ACTIVE_TO" value="<?php echo htmlspecialcharsbx($arParams["ADV_FILTER"]["F_ACTIVE_TO"])?>" /><a title="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" class="filter-date-interval-calendar" href="" id="filter-active-interval-calendar-to"><img border="0" alt="<?php echo GetMessage("TASKS_FILTER_PICK_DATE")?>" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" width="19" height="19" /></a>
			</div>
			<div class="filter-field filter-field-user-checkbox">
				<input class="filter-checkbox" type="checkbox" id="filter-marked" name="F_MARKED" value="Y"<?php if ($arParams["ADV_FILTER"]["F_MARKED"] == "Y"):?> checked<?php endif?> />&nbsp;<label for="filter-marked"><?php echo GetMessage("TASKS_FILTER_MARKED")?></label><br />
				<input class="filter-checkbox" type="checkbox" id="filter-in-report" name="F_IN_REPORT" value="Y"<?php if ($arParams["ADV_FILTER"]["F_IN_REPORT"] == "Y"):?> checked<?php endif?> />&nbsp;<label for="filter-in-report"><?php echo GetMessage("TASKS_FILTER_ADV_IN_REPORT")?></label><br />
				<input class="filter-checkbox" type="checkbox" id="filter-overdued" name="F_OVERDUED" value="Y"<?php if ($arParams["ADV_FILTER"]["F_OVERDUED"] == "Y"):?> checked<?php endif?> />&nbsp;<label for="filter-overdued"><?php echo GetMessage("TASKS_FILTER_OVERDUED")?></label><br />
				<input class="filter-checkbox" type="checkbox" id="user-from-my-office" name="F_SUBORDINATE" value="Y"<?php if ($arParams["ADV_FILTER"]["F_SUBORDINATE"] == "Y" || ($arParams["ADV_FILTER"]["F_ADVANCED"] != "Y" && $arResult["TASK_TYPE"] == "group")):?> checked<?php endif?> />&nbsp;<label for="user-from-my-office"><?php echo GetMessage("TASKS_FILTER_SHOW_SUBORDINATE")?></label><br />
			</div>
			<div class="filter-field-buttons">
				<input type="hidden"  name="F_ADVANCED" value="Y" />
				<input type="submit" class="filter-submit" value="<?php echo GetMessage("TASKS_FILTER_FIND")?>">&nbsp;&nbsp;<input type="button" onclick="jsUtils.Redirect([], '<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam("F_CANCEL=Y", array("F_TITLE", "F_RESPONSIBLE", "F_CREATED_BY", "F_ACCOMPLICE", "F_AUDITOR", "F_DATE_FROM", "F_DATE_TO", "F_TAGS", "F_STATUS", "F_SUBORDINATE", "F_ADVANCED", "F_SEARCH")))?>');" class="filter-submit" value="<?php echo GetMessage("TASKS_CANCEL")?>" name="del_filter_company_search">
			</div>
		</div>
	</form>
</div>

<script>tasksFilterDefaultTemplateInit()</script>