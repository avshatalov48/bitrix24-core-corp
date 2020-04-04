<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetPageProperty("BodyClass", "newpost-page");

if($arResult["delete_blog_post"] == "Y")
{
	$APPLICATION->RestartBuffer();
	if(strlen($arResult["ERROR_MESSAGE"])>0)
	{
		?>
		<script bxrunfirst="yes">
		top.deletePostEr = 'Y';
		</script>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div>
		<?
	}
	if(strlen($arResult["OK_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully">
			<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["OK_MESSAGE"]?></span>
		</div>
		<?
	}
	die();
}
else
{
	?>
	<div class="feed-wrap">
	<div class="feed-add-post-block blog-post-edit">
	<?
	if(strlen($arResult["OK_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["OK_MESSAGE"]?></span>
		</div>
		<?
	}
	if(strlen($arResult["ERROR_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div>
		<?
	}
	if(strlen($arResult["FATAL_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["FATAL_MESSAGE"]?></span>
		</div>
		<?
	}
	elseif(strlen($arResult["UTIL_MESSAGE"])>0)
	{
		?>
		<div class="feed-add-successfully">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["UTIL_MESSAGE"]?></span>
		</div>
		<?
	}
	else
	{
		// Frame with file input to ajax uploading in WYSIWYG editor dialog
		if($arResult["imageUploadFrame"] == "Y")
		{
			?>
			<script>
				<?if(!empty($arResult["Image"])):?>
					var imgTable = top.BX('blog-post-image');
					if (imgTable)
					{
						imgTable.innerHTML += '<span class="feed-add-photo-block"><span class="feed-add-img-wrap"><?=$arResult["ImageModified"]?></span><span class="feed-add-img-title"><?=$arResult["Image"]["fileName"]?></span><span class="feed-add-post-del-but" onclick="DeleteImage(\'<?=$arResult["Image"]["ID"]?>\', this)"></span><input type="hidden" id="blgimg-<?=$arResult["Image"]["ID"]?>" value="<?=$arResult["Image"]["source"]["src"]?>"></span>';
						imgTable.parentNode.parentNode.style.display = 'block';
					}

					top.arImagesId.push('<?=$arResult["Image"]["ID"]?>');
					top.arImagesSrc.push('<?=CUtil::JSEscape($arResult["Image"]["source"]["src"])?>');

					top.bxBlogImageId = '<?=$arResult["Image"]["ID"]?>';
					top.bxBlogImageIdWidth = '<?=CUtil::JSEscape($arResult["Image"]["source"]["width"])?>';
					top.bxBlogImageIdSrc = '<?=CUtil::JSEscape($arResult["Image"]["source"]["src"])?>';
				<?elseif(strlen($arResult["ERROR_MESSAGE"]) > 0):?>
					window.bxBlogImageError = top.bxBlogImageError = '<?=CUtil::JSEscape($arResult["ERROR_MESSAGE"])?>';
				<?endif;?>
			</script>
			<?
			die();
		}
		else
		{
			$formParams = Array(
					"FORM_ID" => "blogPostForm",
					"FORM_TARGET" => "_self"
				);

			$formParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"];
			$formParams["SOCNET_GROUP_ID"] = intval($arParams["SOCNET_GROUP_ID"]);
			$formParams["FORM_ACTION_URL"] = $arParams["PATH_TO_LOG"];

			$APPLICATION->IncludeComponent("bitrix:main.post.form", "mobile", $formParams, false, Array("HIDE_ICONS" => "Y"));
		}
	}
	?>
	</div>
	</div>
	<?
}
?>
