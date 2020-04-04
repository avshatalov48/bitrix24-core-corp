<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/utils.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/forum.interface/templates/popup/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/forum.interface/templates/.default/script.js");

$arParams["FILES_COUNT"] = intVal(intVal($arParams["FILES_COUNT"]) > 0 ? $arParams["FILES_COUNT"] : 1);
$arParams["IMAGE_SIZE"] = (intVal($arParams["IMAGE_SIZE"]) > 0 ? $arParams["IMAGE_SIZE"] : 100);

if (LANGUAGE_ID == 'ru')
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/ru/script.php");
	include($path);
}
?>
<script>
	BX.message({
		TASKS_COMMENTS_CONFIRM_REMOVE : '<?php echo GetMessageJS('TASKS_COMMENTS_CONFIRM_REMOVE'); ?>'
	});
</script>
<a name="postform"></a>
<div class="task-comments"></div>
<div id="form-comment-0">
	<div id="task-comments-add-new-0" class="task-comments-add-new"<?php if (!empty($arResult["MESSAGE_VIEW"]) || !empty($arResult["ERROR_MESSAGE"])):?> style="display: none;"<?php endif?>>
		<a id="task-comments-add-new-btn-add" style="display:none;" href="javascript: void(0);" class="task-comments-add-new-link" onclick="ShowCommentForm('0'); return false;"><?php echo GetMessage("F_ADD_COMMENT")?></a>
	</div>
</div>
<?php if (!empty($arResult["MESSAGE_VIEW"])):?>
	<div class="reviews-header-box">
		<div class="reviews-header-title">
			<span><?php echo GetMessage("F_PREVIEW")?> </span>
		</div>
	</div>
	<div class="reviews-info-box reviews-post-preview">
		<div class="reviews-info-box-inner">
			<div class="reviews-post-entry">
				<div class="reviews-post-text">
				<?php echo $arResult["MESSAGE_VIEW"]["POST_MESSAGE_TEXT"]?>
				</div>
				<?php if (!empty($arResult["REVIEW_FILES"])):?>
					<div class="reviews-post-attachments">
						<label><?php echo GetMessage("F_ATTACH_FILES")?> </label>
						<?php foreach ($arResult["REVIEW_FILES"] as $arFile):?>
							<div class="reviews-post-attachment">
							<?php
								$GLOBALS["APPLICATION"]->IncludeComponent(
									"bitrix:forum.interface",
									"show_file",
									Array(
										"FILE" => $arFile,
										"WIDTH" => $arResult["PARSER"]->image_params["width"],
										"HEIGHT" => $arResult["PARSER"]->image_params["height"],
										"CONVERT" => "N",
										"FAMILY" => "FORUM",
										"SINGLE" => "Y",
										"RETURN" => "N",
										"SHOW_LINK" => "Y"
									),
									null,
									array("HIDE_ICONS" => "Y")
								);
							?>
							</div>
						<?php endforeach?>
					</div>
				<?php endif?>
			</div>
		</div>
	</div>
	<div class="reviews-br"></div>
<?php endif?>
<?php if (!empty($arResult["ERROR_MESSAGE"])):?>
<div class="reviews-note-box reviews-note-error">
	<div class="reviews-note-box-text">
	<?php echo ShowError($arResult["ERROR_MESSAGE"], "reviews-note-error");?>
	</div>
</div>
<?php endif?>
<div class="task-comments-form-wrap" id="task-comments-form-wrap"<?php if (!empty($arResult["MESSAGE_VIEW"]) || !empty($arResult["ERROR_MESSAGE"])):?> style="display: block;"<?php endif?>>
	<form name="REPLIER" id="REPLIER" action="<?php echo POST_FORM_ACTION_URI?><?php if(!isset($_GET["IFRAME"]) || $_GET["IFRAME"] != "Y"):?>#postform<?php endif?>" method="POST" enctype="multipart/form-data" class="reviews-form">
		<input type="hidden" name="back_page" value="<?php echo $arResult["CURRENT_PAGE"]?>" />
		<input type="hidden" name="ELEMENT_ID" value="<?php echo $arParams["TASK_ID"]?>" />
		<input type="hidden" name="COMMENT_ID" value="<?php
			if ($_REQUEST["remove_comment"] !== 'Y')
			{
				if (isset($_REQUEST["COMMENT_ID"]) && intval($_REQUEST["COMMENT_ID"]) > 0)
					echo intval($_REQUEST["COMMENT_ID"]);
			}
		?>" />
		<input type="hidden" name="preview_comment" value="N" />
		<input type="hidden" name="remove_comment" value="N" />
		<?php echo bitrix_sessid_post()?>
		<div id="task-comments-form" class="task-comments-form">
			<?php
				$arSmiles = array();
				if(!empty($arResult["SMILES"]))
				{
					foreach($arResult["SMILES"] as $arSmile)
					{
						$arSmiles[] = array(
							'name' => $arSmile["NAME"],
							'path' => $arSmile["IMAGE"],
							'code' => str_replace("\\\\","\\",$arSmile["TYPING"])
						);
					}
				}

				CModule::IncludeModule("fileman");
				$LHE = new CLightHTMLEditor();
				$LHE->Show(array(
					'id' => "REVIEW_TEXT",
					'content' => isset($arResult["~REVIEW_TEXT"]) ? $arResult["~REVIEW_TEXT"] : "",
					'inputName' => "REVIEW_TEXT",
					'inputId' => "",
					'width' => "100%",
					'height' => "200px",
					'bUseFileDialogs' => false,
					'BBCode' => true,
					'bBBParseImageSize' => true,
					'jsObjName' => "oLHE",
					'toolbarConfig' => Array(
						'Bold', 'Italic', 'Underline', 'Strike',
						'ForeColor','FontList', 'FontSizeList',
						'RemoveFormat',
						'Quote', 'Code',
						'Image',
						'CreateLink', 'DeleteLink',
						'Table',
						'InsertOrderedList',
						'InsertUnorderedList',
						'SmileList',
						'Source'
					),
					'smileCountInToolbar' => 1,
					'arSmiles' => $arSmiles,
					'ctrlEnterHandler' => 'tasksCommentCtrlEnterHandler',
					'bResizable' => true,
					'bAutoResize' => false,
					'bQuoteFromSelection' => true,
					'bBBParseImageSize' => true,
					'documentCSS' => '
						body blockquote.bx-quote { width: 90%; padding: 10px 17px !important; background:url("/bitrix/js/tasks/css/images/quote-gray.png") no-repeat left top #F7F7F7; color:#7D7D7D !important; margin:0 !important; }
						body pre.lhe-code { width: 90%; background-color:#F7F7F7 !important; color:#7D7D7D !important; font-family:"Courier New" !important; font-size:12px !important; padding:10px 17px !important; }
					'
				));
			?>
		</div>
		<span id="task-add_file" class="task-add_file"></span>
		<?php if ($arResult["SHOW_PANEL_ATTACH_IMG"] == "Y"):?>
			<div class="reviews-reply-field reviews-reply-field-upload">
				<?php
					$iCount = 0;
					if (!empty($arResult["REVIEW_FILES"])):
						foreach ($arResult["REVIEW_FILES"] as $key => $val):
							$iCount++;
							$iFileSize = intVal($val["FILE_SIZE"]);
							$size = array(
								"B" => $iFileSize,
								"KB" => round($iFileSize/1024, 2),
								"MB" => round($iFileSize/1048576, 2)
							);
							$sFileSize = $size["KB"].GetMessage("F_KB");
							if ($size["KB"] < 1)
							{
								$sFileSize = $size["B"].GetMessage("F_B");
							}
							elseif ($size["MB"] >= 1 )
							{
								$sFileSize = $size["MB"].GetMessage("F_MB");
							}
				?>
				<div class="reviews-uploaded-file">
					<input type="hidden" name="FILES[<?php echo $key?>]" value="<?php echo $key?>" />
					<input type="checkbox" name="FILES_TO_UPLOAD[<?php echo $key?>]" id="FILES_TO_UPLOAD_<?php echo $key?>" value="<?php echo $key?>" checked="checked" />
					<label for="FILES_TO_UPLOAD_<?php echo $key?>"><?php echo $val["ORIGINAL_NAME"]?> (<?php echo $val["CONTENT_TYPE"]?>)
					<?php echo $sFileSize?> ( <a href="/bitrix/components/bitrix/forum.interface/show_file.php?action=download&amp;fid=<?php echo $key?>"><?php echo GetMessage("F_DOWNLOAD")?> </a> ) </label>
				</div>
				<?php endforeach?>
				<?php endif?>

				<?php
				if ($iCount < $arParams["FILES_COUNT"]):
					$iFileSize = intVal(COption::GetOptionString("forum", "file_max_size", 50000));
					$size = array(
						"B" => $iFileSize,
						"KB" => round($iFileSize/1024, 2),
						"MB" => round($iFileSize/1048576, 2)
					);
					$sFileSize = $size["KB"].GetMessage("F_KB");
					if ($size["KB"] < 1)
					{
						$sFileSize = $size["B"].GetMessage("F_B");
					}
					elseif ($size["MB"] >= 1 )
					{
					$sFileSize = $size["MB"].GetMessage("F_MB");
					}
				?>
				<div class="reviews-upload-info" style="display: none;" id="upload_files_info_<?php echo $arParams["form_index"]?>">
					<?php if ($arResult["FORUM"]["ALLOW_UPLOAD"] == "F"):?>
						<span><?php echo str_replace("#EXTENSION#", $arResult["FORUM"]["ALLOW_UPLOAD_EXT"], GetMessage("F_FILE_EXTENSION"))?> </span>
					<?php endif?>
					<span><?php echo str_replace("#SIZE#", $sFileSize, GetMessage("F_FILE_SIZE"))?> </span>
				</div>
				<?php for ($ii = $iCount; $ii < $arParams["FILES_COUNT"]; $ii++):?>
					<div class="reviews-upload-file" style="display: none;" id="upload_files_<?php echo $ii?>_<?php echo $arParams["form_index"]?>">
						<input name="FILE_NEW_<?php echo $ii?>" type="file" value="" size="30" />
					</div>
				<?php endfor?>
				<a href="javascript:void(0);" class="task-show_input" onclick="AttachFile('<?php echo $iCount?>', '<?php echo ($ii - $iCount)?>', '<?php echo $arParams["form_index"]?>', this); return false;"><?php echo ($arResult["FORUM"]["ALLOW_UPLOAD"]=="Y") ? GetMessage("F_LOAD_IMAGE") : GetMessage("F_LOAD_FILE") ?></a>
				<?php endif?>
			</div>
		<?php endif?>
		<div class="task-blog-comment-buttons">
			<input name="send_button" type="submit" value="<?php echo GetMessage("OPINIONS_SEND")?>" 
				onclick="this.form.preview_comment.value = 'N';
					window.setTimeout(BX.proxy(function() { this.disabled = true; }, this), 100);" />
			<input name="view_button" type="submit" value="<?php echo GetMessage("OPINIONS_PREVIEW")?>" onclick="this.form.preview_comment.value = 'VIEW';" />
		</div>
	</form>
</div>
<div class="task-comments-wrap">
	<?php if (!empty($arResult["MESSAGES"])):?>
		<?php if ($arResult['NAV_PAGE_COUNT'] > 1):?>
			<?php echo $arResult["NAV_STRING"]?><br />
		<?php endif?>
		<?php

		if ($arResult['ORDER_DIRECTION'] === 'DESC')
			$editableCommentIndex = 1;	// if sort order DESC than we can edit only FIRST comment
		else
			$editableCommentIndex = count($arResult["MESSAGES"]);	// if sort order ASC than we can edit only LAST comment

		$bCommentsCanBeRemoved = COption::GetOptionString('tasks', 'task_comment_allow_remove');
		$bCommentsCanBeEdited  = COption::GetOptionString('tasks', 'task_comment_allow_edit');

		$i = 0;
		foreach ($arResult["MESSAGES"] as $res):?>
			<?php $i++;?>
			<div class="task-comments-list">
				<a name="message<?php echo $res["ID"]?>"></a>
				<div class="task-comment-info">
					<div class="task-comments-avatar"<?php if ($res["AUTHOR_PHOTO"]):?> style="background:url('<?php echo $res["AUTHOR_PHOTO"]?>') no-repeat center center;"<?php endif?>></div>
					<?php if (intval($res["AUTHOR_ID"]) > 0 && !empty($res["AUTHOR_URL"])):?><a class="task-comments-author" href="<?php 
							echo $res["AUTHOR_URL"];
							?>"><?php 
							echo tasksFormatName(
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['NAME'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LAST_NAME'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LOGIN'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['SECOND_NAME'], 
								$arParams['NAME_TEMPLATE'],
								true	// escape special chars
								);
						?></a><?php else:?><?php 
							echo tasksFormatName(
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['NAME'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LAST_NAME'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LOGIN'], 
								$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['SECOND_NAME'], 
								$arParams['NAME_TEMPLATE'],
								true	// escape special chars
								);
						?><?php endif?>
					<span class="task-comments-date"><?php echo $res["POST_DATE"]?></span>
					<?if ($arParams["SHOW_RATING"] == "Y") {?>
						<div class="task-comments-rating rating_vote_graphic">
							<?
							$arRatingParams = Array(
									"ENTITY_TYPE_ID" => "FORUM_POST",
									"ENTITY_ID" => $res["ID"],
									"OWNER_ID" => $res["AUTHOR_ID"],
									"PATH_TO_USER_PROFILE" => strlen($arParams["PATH_TO_USER"]) > 0? $arParams["PATH_TO_USER"]: $arParams["~URL_TEMPLATES_PROFILE_VIEW"]
								);
							if (!isset($res['RATING']))
								$res['RATING'] = array(
										"USER_VOTE" => 0,
										"USER_HAS_VOTED" => 'N',
										"TOTAL_VOTES" => 0,
										"TOTAL_POSITIVE_VOTES" => 0,
										"TOTAL_NEGATIVE_VOTES" => 0,
										"TOTAL_VALUE" => 0
									);
							$arRatingParams = array_merge($arRatingParams, $res['RATING']);
							$GLOBALS["APPLICATION"]->IncludeComponent( "bitrix:rating.vote", $arParams["RATING_TYPE"], $arRatingParams, $component, array("HIDE_ICONS" => "Y"));
							?>
						</div>
					<? } ?>
					<div class="task-blog-clear-float"></div>
				</div>
				<div class="task-comment-content">
					<?php echo $res["POST_MESSAGE_TEXT"]?>
					<?php foreach ($res["FILES"] as $arFile):?>
						<div class="reviews-message-img">
						<?php
							$GLOBALS["APPLICATION"]->IncludeComponent(
								"bitrix:forum.interface",
								"show_file",
								Array(
									"FILE" => $arFile,
									"CONVERT" => "N",
									"FAMILY" => "FORUM",
									"SINGLE" => "Y",
									"RETURN" => "N",
									"SHOW_LINK" => "Y"),
								null,
								array("HIDE_ICONS" => "Y")
							);
						?>
						</div>
					<?php endforeach?>
					<div class="task-comment-links">
						<?php 
						global $APPLICATION;
						if (
							$bCommentsCanBeEdited
							&&
							(
								$USER->IsAdmin() 
								|| CTasksTools::IsPortalB24Admin()
								||
								(
									($i == $editableCommentIndex) 
									&& ($USER->GetID() == $res["AUTHOR_ID"])
								)
							)
						)
						{
						?><a href="javascript: void(0);" 
							onclick="Edit('<?php echo $res["FOR_JS"]["POST_MESSAGE"]?>', '<?php echo $res["ID"]?>');return false;"><?php echo GetMessage("F_EDIT")?></a>&nbsp; | &nbsp;<?php 
						}

						if (
							$bCommentsCanBeRemoved
							&&
							(
								$USER->IsAdmin() 
								|| CTasksTools::IsPortalB24Admin()
								||
								(
									($i == $editableCommentIndex) 
									&& ($USER->GetID() == $res["AUTHOR_ID"])
								)
							)
						)
						{
						?><a href="javascript: void(0);" 
							onclick="Remove('<?php echo $res["ID"]?>');return false;"><?php echo GetMessage('F_REMOVE')?></a>&nbsp; | &nbsp;<?php 
						}

						?><a href="javascript: void(0);" 
							onclick="Reply('<?php echo $res["FOR_JS"]["AUTHOR_NAME"]?>', '<?php echo $res["FOR_JS"]["POST_MESSAGE"]?>', '<?php echo $res["ID"]?>');return false;"><?php 
								echo GetMessage("F_ANSWER")?></a>&nbsp; | &nbsp;<a 
									href="<?php echo $APPLICATION->GetCurPageParam('', array('IFRAME')); ?>#message<?php echo $res["ID"]?>" target="_blank"><?php echo GetMessage("F_LINK")?></a>
						<? if ($arParams["SHOW_RATING"] == "Y") { ?>
						<span class="rating_vote_text">
						&nbsp; | &nbsp;
							<?
							$arRatingParams = Array(
									"ENTITY_TYPE_ID" => "FORUM_POST",
									"ENTITY_ID" => $res["ID"],
									"OWNER_ID" => $res["AUTHOR_ID"],
									"PATH_TO_USER_PROFILE" => strlen($arParams["PATH_TO_USER"]) > 0? $arParams["PATH_TO_USER"]: $arParams["~URL_TEMPLATES_PROFILE_VIEW"]
								);
							if (!isset($res['RATING']))
								$res['RATING'] = array(
										"USER_VOTE" => 0,
										"USER_HAS_VOTED" => 'N',
										"TOTAL_VOTES" => 0,
										"TOTAL_POSITIVE_VOTES" => 0,
										"TOTAL_NEGATIVE_VOTES" => 0,
										"TOTAL_VALUE" => 0
									);
							$arRatingParams = array_merge($arRatingParams, $res['RATING']);
							$GLOBALS["APPLICATION"]->IncludeComponent( "bitrix:rating.vote", $arParams["RATING_TYPE"], $arRatingParams, $component, array("HIDE_ICONS" => "Y"));
							?>
						</span>
					<? } ?>
					</div>
				</div>
			</div>
			<div id="form-comment-<?php echo $res["ID"]?>"></div>
		<?php endforeach?>
		<?php if (strlen($arResult["NAV_STRING"]) > 0 && $arResult['NAV_PAGE_COUNT'] > 1):?>
			<br /><?php echo $arResult["NAV_STRING"]?>
		<?php endif?>
		<div id="form-comment-00"><div class="task-add-comment" id="task-comments-add-new-00"><a href="javascript: void(0);" onclick="ShowCommentForm('00');return false;"><?php echo GetMessage("F_ADD_COMMENT")?></a></div></div>
	<?php endif?>
</div>