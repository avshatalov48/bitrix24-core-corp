<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

?>

		<?php if ($arResult["NAV_RESULT"] && $arResult["NAV_RESULT"]->NavPageCount > 1):?>
			<?php echo $arResult["NAV_STRING"]?><br />
		<?php endif?>
		<?php foreach ($arResult["COMMENTS"] as $res):?>
			<div class="timeman-comment">
				<span class="timeman-comment-avatar"<?php if ($res["AUTHOR_PHOTO"]):?> style="background:url('<?php echo $res["AUTHOR_PHOTO"]["CACHE"]["src"]?>') no-repeat center center; background-size: cover;"<?php endif?>></span>
				<span class="timeman-comment-body">
					<div class="timeman-comment-header">
						<span class="timeman-comment-createdby">
							<a href="<?=$res["AUTHOR_URL"];?>"><?=$res["AUTHOR_NAME"]?></a>
						</span>
						<span class="timeman-comment-time"><?=FormatDateFromDB($res["POST_DATE"])?></span>
					</div>
					<div class="timeman-comment-content">
						<div class="timeman-message-full timeman-message-show">
							<?php echo $res["POST_MESSAGE_HTML"]?>
						</div>
					</div>
				</span>
			</div>
		<?php endforeach?>
		<?php if (strlen($arResult["NAV_STRING"]) > 0 && $arResult["NAV_RESULT"]->NavPageCount > 1):?>
			<br /><?php echo $arResult["NAV_STRING"]?>
		<?php endif?>
