<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

ob_start();
?>
<div class="feed-task-info-block">
	<div class="feed-task-info-label"><?=GetMessage("TASKS_SONET_LOG_LABEL_TITLE")?><div class="feed-task-info-label-icon"></div></div>
	<div class="feed-task-info-text">
		<div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?=$arParams['~MESSAGE_24_1']?></span>
		</div><?

		if ($arParams["TYPE"] !== 'comment')
		{
			if ($arParams["TYPE"] == "status")
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams['MESSAGE_24_2']?></span>
				</div><?
			}
			elseif (strlen($arParams["MESSAGE_24_2"]) > 0 && strlen($arParams["CHANGES_24"]) > 0)
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams["MESSAGE_24_2"]?>:</span><span class="feed-task-info-text-cont"><?=$arParams["CHANGES_24"]?></span>
				</div><?php
			}
		}

		?><div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?=GetMessage("TASKS_SONET_LOG_RESPONSIBLE_ID")?>:</span><span class="feed-task-info-text-cont"><a href="<?=$arResult["PATH_TO_USER"];?>" bx-tooltip-user-id="<?=(int) $arResult['USER']['ID']?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"], true);?></a></span>
		</div><?

		if (
			!empty($arResult['PATH_TO_LOG_TAG'])
			&& !empty($arParams['TASK']['TAGS'])
			&& is_array($arParams['TASK']['TAGS'])
		)
		{
			?><div class="feed-com-tags-block">
			<noindex>
				<div class="feed-com-files-title"><?=GetMessage("TASKS_SONET_LOG_TAGS")?></div>
				<div class="feed-com-files-cont" id="task-tags-<?=intval($arParams['TASK']['ID'])?>"><?
					$i=0;
					foreach($arParams['TASK']['TAGS'] as $tag)
					{
						if($i!=0)
						{
							echo ",";
						}
						?> <a href="<?=CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_LOG_TAG'], array("tag" => urlencode($tag)))?>" rel="nofollow" class="feed-com-tag" bx-tag-value="<?=htmlspecialcharsbx($tag)?>"><?=htmlspecialcharsEx($tag)?></a><?
						$i++;
					}
				?></div>
			</noindex>
			</div><?
		}
	?></div>
</div>
<?php

// This is because socialnetwork do htmlspecialcharsback();
echo htmlspecialcharsbx(ob_get_clean());
