<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if($arParams['USE_TITLE_TARGET'] !== 'N')
{
	$arParams["TITLE_TARGET"] = isset($arParams["TITLE_TARGET"]) && strlen($arParams["TITLE_TARGET"]) ? $arParams["TITLE_TARGET"] : "pagetitle";
	if(SITE_TEMPLATE_ID === "bitrix24")
	{
		$this->SetViewTarget($arParams["TITLE_TARGET"], 100);
	}
	$bitrix24 = defined("SITE_TEMPLATE_ID") && SITE_TEMPLATE_ID === "bitrix24";

	?>
	<div class="task-list-toolbar<?= (!$bitrix24 ? " task-list-toolbar-float" : "")?>">
		<div class="task-list-toolbar-actions">
			<?
//			if (!isset($_REQUEST['IFRAME'])
//				&& isset($arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON_ALT"]) && $arParams["SECTION_URL_PREFIX"])
//			{
//				?><!--<a-->
<!--					href="--><?//=htmlspecialcharsbx($arParams["SECTION_URL_PREFIX"]."?F_CANCEL=N")?><!--"-->
<!--					target="_top"-->
<!--					class="task-list-back">--><?//=GetMessage('TASKS_TASK_COMPONENT_TEMPLATE_TO_LIST')?><!--</a>-->
<!--				--><?//
//			}

			if ($arParams["SHOW_SEARCH_FIELD"] === "Y")
			{
				?><div class="task-list-toolbar-search">
					<form action="<?$arParams["PATH_TO_TASKS"]?>" method="GET" name="task-filter-title-form">
						<input class="task-list-toolbar-search-input" id="task-title-button-search-input"
							name="F_SEARCH" type="text"<?
								if($arResult["SEARCH_STRING"]):?>
									value="<? echo htmlspecialcharsbx($arResult["SEARCH_STRING"]); ?>"
								<?endif?>/>
						<input type="hidden" name="VIEW" value="<?
						if ($arParams["VIEW_TYPE"] == "list")
						{
							echo 1;
						}
						elseif ($arParams["VIEW_TYPE"] == "gantt")
						{
							echo 2;
						}
						else
						{
							echo 0;
						} ?>"
						/>
						<input type="hidden"  name="F_ADVANCED" value="Y" /><?

					if (isset($_GET["F_SEARCH"]) && $_GET["F_SEARCH"])
					{
						?><a href="<? echo $APPLICATION->GetCurPageParam(
							"F_CANCEL=Y",
							array(
								"F_TITLE", "F_RESPONSIBLE", "F_CREATED_BY", "F_ACCOMPLICE", "F_AUDITOR",
								"F_DATE_FROM", "F_DATE_TO", "F_TAGS", "F_STATUS", "F_SUBORDINATE",
								"F_ADVANCED", "F_SEARCH"
							)
						)?>" class="task-list-toolbar-search-reset"></a><?
					}
					else
					{
						?><span class="task-list-toolbar-search-icon" id="task-title-button-search-icon"></span><?
					}
					?>
					</form>
				</div><?
			}

			if (isset($arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]) && isset($arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["name"]))
			{
				$onclick = $url = "";
				if (isset($arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["url"]))
				{
					$url = $arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["url"];
				}

				if (isset($arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["onclick"]))
				{
					$onclick = $arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["onclick"];
				}

				?><a target="_top"
				style="margin-top: 7px;"
				<? if ($url) echo " href=\"" . $url . "\""; ?>
				class="task-title-button task-title-button-back"
				<? if ($onclick) echo " onclick=\"" . $onclick . "\"" ?>
				><i class="task-title-button-back-icon"></i
				><span class="task-title-button-back-text"><?
					echo $arParams["CUSTOM_ELEMENTS"]["BACK_BUTTON"]["name"];
					?></span></a>&nbsp;<?
			}

			if (isset($arParams["CUSTOM_ELEMENTS"]["TEMPLATES_TOOLBAR"]) || $arParams["SHOW_TEMPLATES_TOOLBAR"] === "Y")
			{
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("templateselector"),
						"TEMPLATE_DATA" => array(
							"ID" => "templateselector",
							"DATA" => array(
								"TEMPLATES" => 	$arParams["TEMPLATES"],
							),
							"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
							"PATH_TO_TASKS_TEMPLATES" => $arParams["PATH_TO_TASKS_TEMPLATES"],
							"BUTTON_LABEL" => $arParams['TEMPLATES_TOOLBAR_LABEL'],
							"USE_SLIDER" => $arParams['TEMPLATES_TOOLBAR_USE_SLIDER']
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);
			}
			?>
		</div>
		<?
		if (isset($arParams["CUSTOM_ELEMENTS"]["ADD_BUTTON"]) || $arParams["SHOW_ADD_TASK_BUTTON"] === "Y")
		{
			$id = "";
			$onclick = "";
			$title = "";
			$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
			$name = GetMessage("TASKS_ADD_TASK");

			if (isset($arParams["CUSTOM_ELEMENTS"]["ADD_BUTTON"]))
			{
				$button = $arParams["CUSTOM_ELEMENTS"]["ADD_BUTTON"];
				$id = isset($button["id"]) ? $button["id"] : "";
				$onclick = isset($button["onclick"]) ? $button["onclick"] : "";
				$title = isset($button["title"]) ? $button["title"] : "";
				$url = isset($button["url"]) ? $button["url"] : "";
				$name = isset($button["name"]) ? $button["name"] : $name;
				$popup = isset($button["popup"]) ? $button["popup"] : false;
			}

				if ($popup):?>
					<span class="webform-small-button-separate-wrap">
						<a
							href="<?=$url?>"
							target="_top"
							class="webform-small-button webform-small-button-blue"
							id="<?=$id?>-btn"><?=$name?>
						</a>
						<span id="<?=$id?>" class="webform-small-button-right-part"></span>
					</span><?
				else: ?>
					<a
						href="<?=$url?>"
						target="_top"
						class="webform-small-button webform-small-button-blue"
						id="<?=$id?>-btn"><?=$name?>
					</a>
				<?endif;
		}
		?>
	</div>
	<?
	if(SITE_TEMPLATE_ID === "bitrix24")
	{
		$this->EndViewTarget();
	}
}

$defaultMenuTarget = SITE_TEMPLATE_ID === "bitrix24" ? "above_pagetitle" : "task_menu";
$arParams["MENU_TARGET"] = isset($arParams["MENU_TARGET"]) && strlen($arParams["MENU_TARGET"]) ? $arParams["MENU_TARGET"] : $defaultMenuTarget;
$arParams["CONTROLS_TARGET"] = isset($arParams["CONTROLS_TARGET"]) && strlen($arParams["CONTROLS_TARGET"]) ? $arParams["CONTROLS_TARGET"] : "task_menu";

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget($arParams["MENU_TARGET"], 200);
}

if (
	($arParams["SHOW_SECTIONS_BAR"] === "Y")
	|| ($arParams["SHOW_FILTER_BAR"] === "Y")
	|| ($arParams["SHOW_COUNTERS_BAR"] === "Y")
)
{
	require_once($_SERVER["DOCUMENT_ROOT"] . $templateFolder . "/topnav.php");
}

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}?>


<script type="text/javascript">

	function showGanttFilter(bindElement)
	{
		BX.toggleClass(bindElement, "webform-small-button-active");
		TaskGanttFilterPopup.show(bindElement);
	};

	function showTaskListFilter(bindElement)
	{
		BX.toggleClass(bindElement, "webform-small-button-active");
		TaskListFilterPopup.show(bindElement);
	};

</script>
