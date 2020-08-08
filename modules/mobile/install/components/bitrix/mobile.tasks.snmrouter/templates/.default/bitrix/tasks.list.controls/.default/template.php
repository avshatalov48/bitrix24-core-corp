<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 *
 */
// enabling application cache
$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "tasks.roles");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v1.3");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("LanguageId", LANGUAGE_ID);
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/tasks/logic.js');
?><?=CJSCore::Init(array("mobile_fastclick"), true);?><?
?><div id="bx-task"><?
$counters = array();
if (is_array($arResult['ITEMS']))
{
	$APPLICATION->SetPageProperty('BodyClass', 'tasks-list-controls');
	?><div id="tasks-all-items" class="mobile-task-list">
		<div class="mobile-task-list-title"><?=GetMessage("MB_TASKS_CONTROLS_TITLE")?></div><?

			// special presets
		foreach($arResult['ITEMS'] as $item)
		{
			$class = "";
			if (is_string($item["CODE"]))
			{
				$class = mb_strtolower($item["CODE"]);
			}
			else if (is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']) && array_key_exists($item["CODE"], $arResult['VIEW_STATE']['SPECIAL_PRESETS']))
			{
				$class = mb_strtolower($arResult['VIEW_STATE']['SPECIAL_PRESETS'][$item["CODE"]]["CODE"]);
			}
			?>
			<div class="mobile-grid-field" data-bx-id="taskgroups-group" data-group-id="<?=htmlspecialcharsbx($item["CODE"])?>">
				<div class="mobile-grid-field-counter-total<?if(!(intval($item['COUNTER']['TOTAL']['VALUE']) > 0)):?> hidden<?endif?>" data-bx-id="taskgroups-counter" data-counter-id="TOTAL">
					<?if(intval($item['COUNTER']['TOTAL']['VALUE']) > 0):?><?=intval($item['COUNTER']['TOTAL']['VALUE'])?><?endif?>
				</div>
				<div class="mobile-grid-field-item-icon mobile-grid-field-item-icon-<?=$class?>"></div>
				<div data-bx-id="taskgroups-group-url" data-url="<?=htmlspecialcharsbx($item['URL'])?>" class="mobile-grid-field-item"><?=htmlspecialcharsbx($item['TITLE'])?></div>
				<?
				$counters[$item["CODE"]] = $item["COUNTER"];
				$res = array();
				foreach ($item["COUNTER"] as $key => $val)
				{
					$name = null;
					$class = "";
					switch ($key)
					{
						case 'VIEW_TASK_CATEGORY_WO_DEADLINE':
							if($item['ID'] == 4096)
							{
								$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_WO_DEADLINE_FOR_ME");
							}
							else
							{
								$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_WO_DEADLINE");
							}

							$class = "mobile-grid-field-counter-wo-deadline";
							break;
						case 'VIEW_TASK_CATEGORY_NEW':
							$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_NEW");
							$class = "mobile-grid-field-counter-new";
							break;
						case 'VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES':
							$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES");
							$class = "mobile-grid-field-counter-expired-candidates";
							break;
						case 'VIEW_TASK_CATEGORY_EXPIRED':
							$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_EXPIRED");
							$class = "mobile-grid-field-counter-expired";
							break;
						case 'VIEW_TASK_CATEGORY_WAIT_CTRL':
							$name = GetMessage("MB_TASKS_ROLES_VIEW_TASK_CATEGORY_WAIT_CTRL");
							$class = "mobile-grid-field-counter-wait-ctrl";
							break;
					}
					if ($name === null)
					{
						continue;
					}
					$val["URL"] = htmlspecialcharsbx($val["URL"]?:"");
					$val["VALUE"] = ($val["VALUE"] > 0 ? $val["VALUE"] : "");
					$class1 = ($val["VALUE"] === "" ? " hidden" : "");
					$res[] = <<<HTML
					<div class="mobile-grid-field-sub-field{$class1}" data-bx-id="taskgroups-subgroup" data-url="{$val["URL"]}">
						<div class="mobile-grid-field-sub-counter {$class}" data-bx-id="taskgroups-counter" data-counter-id="{$key}" >{$val["VALUE"]}</div>
						<div class="mobile-grid-field-sub-item">{$name}</div>
					</div>
HTML;
				}
				if (count($res) > 0)
				{
					?><div class="mobile-grid-field-sub-container"><?=implode("", $res)?></div><?
				}
				?>
			</div>
			<?
		}
	?>
	</div>
	<?
	$frame->startDynamicWithID("mobile-tasks-roles");
	?>
</div>
<script>
BX.ready(function(){
	FastClick.attach(BX("bx-task"));
	app.hidePopupLoader();
	BX['taskgroups'] = new BX.Mobile.Tasks.View.Bitrix.taskgroups({scope: BX('tasks-all-items')});
	BX['taskroles'] = new BX.Mobile.Tasks.roles(<?=CUtil::PhpToJSObject(array( 'counterToRole' => $arResult['counterToRole'] ))?>);
	BX['taskroles'].instance('taskgroups', BX['taskgroups']);
	BX['taskroles'].dynamicActions(<?=CUtil::PhpToJsObject(array(
		'counters' => $counters,
		'userId' => intval($arParams['USER_ID'])
	))?>);
});
</script>
	<?
	$frame->finishDynamicWithID("mobile-tasks-roles", $stub = "", $containerId = null, $useBrowserStorage = true);
}