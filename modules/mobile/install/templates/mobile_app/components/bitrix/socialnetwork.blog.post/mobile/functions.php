<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!function_exists("__blogUFfileShowMobile"))
{
	function __blogUFfileShowMobile($arResult, $arParams)
	{
		$result = false;
		if ($arParams['arUserField']['FIELD_NAME'] == 'UF_BLOG_POST_DOC' || strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_DOC') === 0)
		{
			if (sizeof($arResult['VALUE']) > 0)
			{
				?><div class="post-item-attached-file-wrap"><?

				foreach ($arResult['VALUE'] as $fileID)
				{
					$arFile = CFile::GetFileArray($fileID);
					if($arFile)
					{
						$name = $arFile['ORIGINAL_NAME'];
						$ext = '';
						$dotpos = strrpos($name, ".");
						if (($dotpos !== false) && ($dotpos+1 < strlen($name)))
							$ext = substr($name, $dotpos+1);
						if (strlen($ext) < 3 || strlen($ext) > 5)
							$ext = '';
						$arFile['EXTENSION'] = $ext;
						$arFile['LINK'] = SITE_DIR."mobile/ajax.php?mobile_action=blog_image&bp_fid=".$fileID;
						$arFile["FILE_SIZE"] = CFile::FormatSize($arFile["FILE_SIZE"]);
						?><div class="post-item-attached-file"><?
							?><a onclick="app.openDocument({'url' : '<?=$arFile['LINK']?>'});" href="javascript:void()" class="post-item-attached-file-link"><span><?=htmlspecialcharsbx($arFile['ORIGINAL_NAME'])?></span><span>(<?=$arFile['FILE_SIZE']?>)</span></a><?
						?></div><?
					}
				}

				?></div><?
			}
			$result = true;
		}
		
		return $result;
	}
}

if (!function_exists("ResizeMobileLogImages"))
{
	function ResizeMobileLogImages($res, $strImage, $db_img_arr, $f, $arDestinationSize)
	{
		$id = $f["IS_COMMENT"] == "Y" ? "blog-comm-inline-".$f["FILE_ID"] : "blog-post-inline-".$f["FILE_ID"];
		$res = '<img src="'.CMobileLazyLoad::getBase64Stub().'" data-src="'.$strImage.'" title="'.htmlspecialcharsbx($f["TITLE"]).'" alt="'.htmlspecialcharsbx($f["TITLE"]).'" border="0" width="'.round($arDestinationSize["width"]/2).'" height="'.round($arDestinationSize["height"]/2).'" id="'.$id.'" /><script>BitrixMobile.LazyLoad.registerImage("'.$id.'", oMSL.checkVisibility);</script>';
	}
}

if (!function_exists('__SMLFormatDate'))
{
	function __SMLFormatDate($timestamp)
	{
		$days_ago = intval((time() - $timestamp) / 60 / 60 / 24);
		$days_ago = ($days_ago <= 0 ? 1 : $days_ago);

		return str_replace("#DAYS#", $days_ago, GetMessage("BLOG_MOBILE_DATETIME_DAYS"));
	}
}
?>