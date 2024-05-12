<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/im_mobile.js").'"></script>');
$APPLICATION->AddHeadString('<link href="'.CUtil::GetAdditionalFileURL(BX_PERSONAL_ROOT.'/js/im/css/common.css').'" type="text/css" rel="stylesheet" />');
CJSCore::Init("fx");
?>
<style>
.attach-inner {
	position: relative;
	word-break: break-word;
}

.bx-messenger-attach-message {
	color: var(--base0);
}

.bx-messenger-attach-user .bx-messenger-attach-user-name {
	color: var(--base2)!important;
}

.bx-messenger-file-image-src {
	border-color: var(--base6);
}

.bx-messenger-attach-delimiter {
	background-color: var(--bg-separator-secondary);
}

.bx-messenger-attach-blocks {
	color: var(--base1);
}

</style>
<script>
	function urlValidation(el)
	{
		let link = BX.util.htmlspecialcharsback(el.getAttribute('data-url'));

		try
		{
			var url = new URL(link, location.origin);
		}
		catch(e)
		{
			el.style="";
			el.onclick="";
			return false;
		}

		var allowList = [
			"http:",
			"https:",
			"ftp:",
			"file:",
			"tel:",
			"callto:",
			"mailto:",
			"skype:",
			"viber:",
		];
		if (allowList.indexOf(url.protocol) <= -1)
		{
			el.style="";
			el.onclick="";
			return false;
		}

		BXMobileApp.PageManager.loadPageBlank({url: url.href})
	}
</script>

<?php
if(empty($arResult['ATTACH'])):?>
	<div class="notif-block-empty"><?=GetMessage('IM_ATTACH_ACCESS_ERROR');?></div>
<?php else:?>
	<div class="notif-block-wrap" id="notif-block-wrap">
		<?php
		$params = ['ATTACH' => $arResult['ATTACH']];
		?>
		<div class="notif-block">
			<div class="notif-cont">
				<div class="attach-inner">
					<?=getNotifyParamsHtml($params)?>
				</div>
			</div>
		</div>
	</div>
<?php endif;?>

<?php
function decodeBbCode($text, $safe = true)
{
	$text = preg_replace("/<img.*?data-code=\"([^\"]*)\".*?>/i", "$1", $text);

	if ($safe)
	{
		$text = htmlspecialcharsbx($text);
	}

	$text = preg_replace("/\n/", "[BR]", $text);
	$text = preg_replace("/\t/", "&nbsp;&nbsp;&nbsp;&nbsp;", $text);

	$text = preg_replace("/\[USER=([0-9]+)( REPLACE)?](.*?)\[\/USER]/i", "$3", $text);
	$text = preg_replace("/\[RATING=([1-5]{1})\]/i", "$1", $text);
	$text = preg_replace("/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$3", $text);
	$text = preg_replace("/\[LIKE\]/i", '<span class="bx-smile bx-im-smile-like"></span>', $text);
	$text = preg_replace("/\[DISLIKE\]/i", '<span class="bx-smile bx-im-smile-dislike"></span>', $text);

	$text = str_replace(['[BR]', '[br]', '#br#'], '<br>', $text);

	$text = preg_replace_callback("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs", function($match) {
		return '<span data-url="'.$match[1].'" onclick="urlValidation(this)" style="color: var(--accent-main-links);font-weight: bold;">'.$match[2].'</span>';
	}, $text);

	$text = preg_replace_callback('/\[url\](.*?)\[\/url\]/i', function($match) {
		return '<span data-url="'.$match[1].'" onclick="urlValidation(this)" style="color: var(--accent-main-links);font-weight: bold;">'.$match[1].'</span>';
	}, $text);

	$text = preg_replace_callback('/\[([buis])\](.*?)\[(\/[buis])\]/i', function($match) {
		return '<'.$match[1].'>'.$match[2].'<'.$match[3].'>';
	}, $text);

	$text = preg_replace(
		["/\[color=#([0-9a-f]{3}|[0-9a-f]{6})](.*?)\[\/color]/u"],
		["<span style='color: #\\1'>\\2</span>"],
		$text
	);
	$text = preg_replace(
		["/\[size=(\d+)](.*?)\[\/size]/u"],
		["<span style='font-size: \\1px'>\\2</span>"],
		$text
	);

	$text = \Bitrix\Im\Text::removeBbCodes($text);

	return $text;
}

function getNotifyParamsHtml($params)
{
	$result = '';
	if (empty($params['ATTACH']))
		return $result;

	foreach ($params['ATTACH'] as $attachBlock)
	{
		$blockResult = '';
		foreach ($attachBlock['BLOCKS'] as $attach)
		{
			if (isset($attach['USER']))
			{
				$subResult = '';
				foreach ($attach['USER'] as $userNode)
				{
					$subResult .= '<span class="bx-messenger-attach-user">
						<span class="bx-messenger-attach-user-avatar">
							'.($userNode['AVATAR']? '<img src="'.htmlspecialcharsbx($userNode['AVATAR']).'" class="bx-messenger-attach-user-avatar-img">': '<span class="bx-messenger-attach-user-avatar-img bx-messenger-attach-user-avatar-default">').'
						</span>
						<span class="bx-messenger-attach-user-name">'.htmlspecialcharsbx($userNode['NAME']).'</span>
					</span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-users">'.$subResult.'</span>';
			}
			else if (isset($attach['LINK']))
			{
				$subResult = '';
				foreach ($attach['LINK'] as $linkNode)
				{
					$subResult .= '<span class="bx-messenger-attach-link bx-messenger-attach-link-with-preview">
						<a class="bx-messenger-attach-link-name" href="'.htmlspecialcharsbx($linkNode['LINK']).'">'.($linkNode['NAME']? htmlspecialcharsbx($linkNode['NAME']): htmlspecialcharsbx($linkNode['LINK'])).'</a>
						'.(!$linkNode['PREVIEW']? '': '<span class="bx-messenger-file-image-src"><img src="'.htmlspecialcharsbx($linkNode['PREVIEW']).'" class="bx-messenger-file-image-text"></span>').'
					</span>';
					if (isset($linkNode['DESC']) && !empty($linkNode['DESC']))
					{
						$subResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($linkNode['DESC']).'</span>';
					}
					if (isset($linkNode['HTML']) && !empty($linkNode['HTML']))
					{
						$subResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($linkNode['HTML']).'</span>';
					}
				}
				$blockResult .= '<span class="bx-messenger-attach-links">'.$subResult.'</span>';
			}
			else if (isset($attach['MESSAGE']))
			{
				$blockResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($attach['MESSAGE']).'</span>';
			}
			else if (isset($attach['HTML']))
			{
				$blockResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($attach['HTML']).'</span>';
			}
			else if (isset($attach['GRID']))
			{
				$subResult = '';
				foreach ($attach['GRID'] as $gridNode)
				{
					$width = $gridNode['WIDTH'] ? 'width: '.$gridNode['WIDTH'].'px' : '';

					$blockValue = '';
					if ($gridNode['LINK'])
					{
						$link = htmlspecialcharsbx($gridNode['LINK']);
						$linkTitle = $gridNode['VALUE'] ? htmlspecialcharsbx($gridNode['VALUE']) : $link;
						$blockValue =
							'<span class="bx-messenger-attach-link">'
								.'<a class="bx-messenger-attach-link-name" href="'.$link.'">' .$linkTitle .'</a>'
							.'</span>'
						;
					}
					else if (isset($gridNode['VALUE']) && !empty($gridNode['VALUE']))
					{
						$blockValue =
							'<div class="bx-messenger-attach-block-value" style="'.($gridNode['COLOR'] ? 'color: '.$gridNode['COLOR'] : '').'">'
								.decodeBbCode($gridNode['VALUE'])
							.'</div>'
						;
					}
					else
					{
						$gridNode['DISPLAY'] = 'BLOCK';
					}

					$subResult .=
						'<span class="bx-messenger-attach-block bx-messenger-attach-block-'.(mb_strtolower($gridNode['DISPLAY'])).'" style="'.($gridNode['DISPLAY'] == 'LINE' ? $width : '').'">'
							.'<div class="bx-messenger-attach-block-name" style="'.($gridNode['DISPLAY'] == 'ROW' ? $width : '').'">'.htmlspecialcharsbx($gridNode['NAME']).'</div>'
								.$blockValue
						.'</span>'
					;
				}
				$blockResult .= '<span class="bx-messenger-attach-blocks">'.$subResult.'</span>';
			}
			else if (isset($attach['DELIMITER']))
			{
				$style = "";
				if ($attach['DELIMITER']['SIZE'])
				{
					$style .= "width: ".$attach['DELIMITER']['SIZE']."px;";
				}
				if ($attach['DELIMITER']['COLOR'])
				{
					$style .= "background-color: ".($attach['DELIMITER']['COLOR']);
				}
				if ($style)
				{
					$style = 'style="'.$style.'"';
				}
				$blockResult .= '<span class="bx-messenger-attach-delimiter" '.$style.'></span>';
			}
			else if (isset($attach['IMAGE']))
			{
				$subResult = '';
				foreach ($attach['IMAGE'] as $imageNode)
				{
					$imageNode['PREVIEW'] = $imageNode['PREVIEW']? $imageNode['PREVIEW']: $imageNode['LINK'];
					$subResult .= '<span class="bx-messenger-file-image-src"><img src="'.htmlspecialcharsbx($imageNode['PREVIEW']).'" class="bx-messenger-file-image-text"></span>';
				}
				$blockResult .= '<span class="bx-messenger-attach-images">'.$subResult.'</span>';
			}
			else if (isset($attach['FILE']))
			{
				$subResult = '';
				foreach ($attach['FILE'] as $fileNode)
				{
					$subResult .=
						'<div class="bx-messenger-file">
							<div class="bx-messenger-file-attrs">
								<span class="bx-messenger-file-title">
									<span class="bx-messenger-file-title-name">'.htmlspecialcharsbx($fileNode['NAME']).'</span>
								</span>
								'.($fileNode['SIZE']? '<span class="bx-messenger-file-size">'.CFile::FormatSize($fileNode['SIZE']).'</span>':'').'
							</div>
						</div>';
				}
				$blockResult .= '<span class="bx-messenger-attach-files">'.$subResult.'</span>';
			}
			else if (isset($attach['RICH_LINK']))
			{
				$subResult = '';
				foreach ($attach['RICH_LINK'] as $linkNode)
				{
					$subResult .= '<span class="bx-messenger-attach-link bx-messenger-attach-link-with-preview">
						<a class="bx-messenger-attach-link-name" href="'.htmlspecialcharsbx($linkNode['LINK']).'">'.($linkNode['NAME']? htmlspecialcharsbx($linkNode['NAME']): htmlspecialcharsbx($linkNode['LINK'])).'</a>
						'.(!$linkNode['PREVIEW']? '': '<span class="bx-messenger-file-image-src"><img src="'.htmlspecialcharsbx($linkNode['PREVIEW']).'" class="bx-messenger-file-image-text"></span>').'
					</span>';
					if (isset($linkNode['DESC']) && !empty($linkNode['DESC']))
					{
						$blockResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($linkNode['DESC']).'</span>';
					}
					if (isset($linkNode['HTML']) && !empty($linkNode['HTML']))
					{
						$blockResult .= '<span class="bx-messenger-attach-message">'.decodeBbCode($linkNode['HTML']).'</span>';
					}
				}
				$blockResult .= '<span class="bx-messenger-attach-links">'.$subResult.'</span>';
			}
			else
			{
				var_export($attach);
			}
		}
		if ($blockResult)
		{
			$color = $attachBlock['COLOR']? htmlspecialcharsbx($attachBlock['COLOR']): 'var(--base3)';
			$result .= '<div class="bx-messenger-attach" style="border-color:'.$color.'">'.$blockResult.'</div>';
		}
	}
	if ($result)
	{
		$result = '<div class="bx-messenger-attach-box">'.$result.'</div>';
	}
	return $result;
}
?>