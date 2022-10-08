<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
	"ui.buttons",
	'salescenter.manager',
	'loader',
]);

function salescenterConnectTemplateRenderLogoBlock(array $data, \CBitrixComponentTemplate $template): string
{
	$imagePath = Path::combine($template->GetFolder(), 'images').'/';

	$result = '<div class="salescenter-wrapper">
	<div class="salescenter-main-section">
		<div class="salescenter-main-header">
			<div class="salescenter-main-header-left-block">
				<div class="salescenter-logo-container"';

	if(!empty($data['logoColor']))
	{
		$result .= ' style="background-color: #'.htmlspecialcharsbx($data['logoColor']).'"';
	}

	$result .= '>';
	if(!empty($data['logo']))
	{
		$result .= '<img class="salescenter-logo" src="'.$imagePath.htmlspecialcharsbx($data['logo']).'" alt="">';
	}
	$result .= '</div>
			</div>
			<div class="salescenter-main-header-right-block">';
	if(!empty($data['title']))
	{
		$result .= '<div class="salescenter-main-header-title">'.htmlspecialcharsbx(Loc::getMessage($data['title'])).'</div>';
	}
	if(!empty($data['description']))
	{
		$result .= '<div class="salescenter-description">'.htmlspecialcharsbx(Loc::getMessage($data['description'])).'</div>';
	}
	if(!empty($data['links']) && is_array($data['links']))
	{
		foreach($data['links'] as $link)
		{
			$result .= '<div class="salescenter-link-container">
					<a class="salescenter-link"';
			if(!empty($link['onclick']))
			{
				$result .= 'onclick="'.CUtil::JSEscape($link['onclick']).'"';
			}
			$result .= '>'.htmlspecialcharsbx(Loc::getMessage($link['text'])).'</a>
				</div>';
		}
	}

	$result .= '<div class="salescenter-button-container">
					<button class="ui-btn ui-btn-md ui-btn-primary" id="bx-salescenter-connect-button">'.Loc::getMessage('SALESCENTER_CONNECT').'</button>
				</div>
			</div>
		</div>
	</div>
</div>';

	return $result;
}

function salescenterConnectTemplateRenderBlock(array $data, \CBitrixComponentTemplate $template): string
{
	$imagePath = Path::combine($template->GetFolder(), 'images');

	$result = '<div class="salescenter-wrapper">
	<div class="salescenter-section">';
	if(!empty($data['title']))
	{
		$result .= '<div class="salescenter-header">
			<div class="salescenter-header-title">'.htmlspecialcharsbx(Loc::getMessage($data['title'])).'</div>
		</div>
		<hr class="salescenter-separator">';
	}
	if(!empty($data['description']))
	{
		$result .= '<div class="salescenter-description">'.htmlspecialcharsbx(Loc::getMessage($data['description'])).'</div>';
	}
	if(!empty($data['image']))
	{
		if($data['image'] === 'preview')
		{
			$language = mb_strtolower(Loc::getCurrentLang());
			$image = Path::combine($imagePath, 'preview_' . $language . '.png');
			if(!File::isFileExists(Path::combine($_SERVER['DOCUMENT_ROOT'], $image)))
			{
				if(in_array($language, ['ru', 'by', 'kz', 'ua'], true))
				{
					$image = Path::combine($imagePath, 'preview_ru.png');
				}
				else
				{
					$image = Path::combine($imagePath, 'preview_en.png');
				}
			}
		}
		else
		{
			$image = Path::combine($imagePath, $data['image']);
		}
		$result .= '<div class="salescenter-img-container">
			<img class="img-response" src="'.htmlspecialcharsbx($image).'" alt="">
		</div>';
	}
	$result .= '</div>
</div>';

	return $result;
}

if(!empty($arResult['blocks']) && is_array($arResult['blocks']))
{
	foreach($arResult['blocks'] as $block)
	{
		if(isset($block['isLogo']) && $block['isLogo'] === true)
		{
			echo salescenterConnectTemplateRenderLogoBlock($block, $this);
		}
		else
		{
			echo salescenterConnectTemplateRenderBlock($block, $this);
		}
	}
}
?>
<script>
	BX.ready(function()
	{
		var options = <?=CUtil::PhpToJSObject($arResult)?>;
		BX.Salescenter.Connection.init(options);
	});
</script>