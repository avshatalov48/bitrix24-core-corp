<?php

use Bitrix\Crm\Decorator\JsonSerializable\ClearNullValues;
use Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Dto\RouterAnchor;
use Bitrix\Crm\Tour\Base;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arResult */
/** @var Page $page */
/** @var RouterAnchor[] $anchors */

global $APPLICATION;

$page = $arResult['page'];
$anchors = $arResult['anchors'];
$isBindAnchors = !$arResult['isIframe'];

/** @see \Bitrix\Crm\Component\Base::addJsRouter() */
$this->getComponent()->addJsRouter($this);

if ($isBindAnchors)
{
	Extension::load(['sidepanel']);
	$jsonAnchors = Json::encode(ClearNullValues::decorateList($anchors));

	echo <<<HTML
		<script>
			BX.ready(() => {
				const anchors = ({$jsonAnchors});
				anchors.forEach((anchor) => {
					BX.Crm.Component.Router.bindAnchor(anchor.roots, anchor.rule);
				});
			});
		</script>
	HTML;
}

$page->render($this->getComponent());

if ($page->title() !== null)
{
	$APPLICATION->SetTitle(htmlspecialcharsbx($page->title()));
}

if ($page->canUseFavoriteStar() !== null)
{
	if ($page->canUseFavoriteStar())
	{
		Toolbar::addFavoriteStar();
	}
	else
	{
		Toolbar::deleteFavoriteStar();
	}
}
