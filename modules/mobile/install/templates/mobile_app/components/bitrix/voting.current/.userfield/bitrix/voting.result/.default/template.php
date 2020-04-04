<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2016 Bitrix
 *
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 */
$uid = $arParams["UID"];

if (!empty($arResult["ERROR_MESSAGE"])):?>
<div class="vote-note-box vote-note-error">
	<div class="vote-note-box-text"><?=ShowError($arResult["ERROR_MESSAGE"])?></div>
</div>
<?endif;

if (empty($arResult["VOTE"]) || empty($arResult["QUESTIONS"]) ):
	return true;
endif;

?>
	<ol class="bx-vote-question-list" id="vote-<?=$uid?>">
	<?foreach ($arResult["QUESTIONS"] as $arQuestion):?>
		<li id="question<?=$arQuestion["ID"]?>"<?if($arQuestion["REQUIRED"]=="Y"){?> class="bx-vote-question-required"<?}?>>
			<?if (!empty($arQuestion["IMAGE"]) && !empty($arQuestion["IMAGE"]["SRC"])) { ?><div class="bx-vote-question-image"><img src="<?=$arQuestion["IMAGE"]["SRC"]?>" /></div><? } ?>
			<div class="bx-vote-question-title"><?=$arQuestion["QUESTION"]?></div>
			<div class="bx-vote-answer-list-wrap">
				<table class="bx-vote-answer-list" cellspacing="0">
				<?foreach ($arQuestion["ANSWERS"] as $arAnswer):?>
					<tr id="answer<?=$arAnswer["ID"]?>" class="bx-vote-answer-item" bx-voters-count="<?=$arAnswer["COUNTER"]?>">
						<td>
							<div class="bx-vote-answer-wrap"><?
					?><span class="bx-vote-block-input-wrap"><?
						?><span class="bx-vote-block-input"></span><?
						?><label><?=$arAnswer["MESSAGE"]?></label><?
					?></span>
								<div class="bx-vote-answer-bg"></div>
								<div class="bx-vote-answer-bar" style="width:<?=$arAnswer["PERCENT"]?>%"></div>
							</div>
						</td>
						<td>
							<div class="bx-vote-data-percent"><span><?=$arAnswer["PERCENT"]?></span><span class="post-vote-color">%</span></div>
						</td>
					</tr>
				<?endforeach;?>
				</table>
			</div>
		</li>
	<?endforeach;?>
		<li class="bx-vote-answer-result">
			<div class="bx-vote-answer-list-wrap">
				<table class="bx-vote-answer-list" cellspacing="0">
					<tr>
						<td><?=GetMessage("VOTE_RESULTS")?></td>
						<td class="bx-vote-events-count"><span><?=$arResult["VOTE"]["COUNTER"]?></span><span class="post-vote-color">%</span></td>
					</tr>
				</table>
			</div>
		</li>
	</ol>
<?
$this->__component->arParams["RETURN"] = array(
	"uid" => $uid,
	"lastVote" => $arResult["LAST_VOTE"]);
?>