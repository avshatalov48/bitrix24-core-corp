<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

ob_start();
?>
<div class="feed-task-info-block">
	<div class="feed-task-info-label-cont">
		<div class="feed-task-info-label">
			<?= GetMessage("TASKS_SONET_LOG_LABEL_TITLE") ?>
			<div class="feed-task-info-label-icon"></div>
		</div>
	</div>
	<div class="feed-task-info-text">
		<div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?= $arParams['~MESSAGE_24_1'] ?></span>
		</div><?php

		if ($arParams["TYPE"] !== 'comment')
		{
			if ($arParams["TYPE"] == "status")
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams['MESSAGE_24_2']?></span>
				</div><?php
			}
			elseif ($arParams["MESSAGE_24_2"] <> '' && $arParams["CHANGES_24"] <> '')
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams["MESSAGE_24_2"]?>:</span><span class="feed-task-info-text-cont"><?=$arParams["CHANGES_24"]?></span>
				</div><?php
			}
		}

		?><div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?=GetMessage("TASKS_SONET_LOG_RESPONSIBLE_ID")?>:</span><span class="feed-task-info-text-cont"><a href="<?=$arResult["PATH_TO_USER"];?>" bx-tooltip-user-id="<?=(int) $arResult['USER']['ID']?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"], true);?></a></span>
		</div><?php

		if (
			!empty($arResult['PATH_TO_LOG_TAG'])
			&& !empty($arParams['TASK']['TAGS'])
			&& is_array($arParams['TASK']['TAGS'])
		)
		{
			?><div class="feed-com-tags-block">
			<noindex>
				<div class="feed-com-files-title"><?=GetMessage("TASKS_SONET_LOG_TAGS")?></div>
				<div class="feed-com-files-cont" id="task-tags-<?=intval($arParams['TASK']['ID'])?>"><?php
					$i=0;
					foreach($arParams['TASK']['TAGS'] as $tag)
					{
						if($i!=0)
						{
							echo ",";
						}
						?> <a href="<?=CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_LOG_TAG'], array("tag" => urlencode($tag)))?>" rel="nofollow" class="feed-com-tag" bx-tag-value="<?= htmlspecialcharsbx($tag) ?>"><?= htmlspecialcharsEx($tag) ?></a><?php
						$i++;
					}
				?></div>
			</noindex>
			</div><?php
		}
	?></div>
</div>
<?php

// This is because socialnetwork do htmlspecialcharsback();
echo htmlspecialcharsbx(ob_get_clean());
