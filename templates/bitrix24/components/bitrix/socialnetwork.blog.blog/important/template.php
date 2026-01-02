<?

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arParams["OPTIONS"] = (is_array($arParams["OPTIONS"]) ? $arParams["OPTIONS"] : []);

$arRes = [
	"data" => [],
	"page_settings" => [
		"NavPageCount" => (int)$arResult["NAV_RESULT"]->NavPageCount,
		"NavPageNomer" => (int)$arResult["NAV_RESULT"]->NavPageNomer,
		"NavPageSize" => (int)$arResult["NAV_RESULT"]->NavPageSize,
		"NavRecordCount" => (int)$arResult["NAV_RESULT"]->NavRecordCount,
		"bDescPageNumbering" => $arResult["NAV_RESULT"]->bDescPageNumbering,
		"nPageSize" => (int)$arResult["NAV_RESULT"]->NavPageSize,
	],
];

foreach ($arResult["POST"] as $id => $res)
{
	$res = [
		"id" => $res["ID"],
		"post_text" => TruncateText(
			($res["MICRO"] != "Y" ? $res["TITLE"] . " " . $res["CLEAR_TEXT"] : $res["CLEAR_TEXT"]),
			$arParams["MESSAGE_LENGTH"]
		),
		"post_url" => $res["urlToPost"],
		"author_name" => $res["AUTHOR_NAME"],
		"author_avatar_style" => (!empty($res["AUTHOR_AVATAR"]["src"]) ? "url('" . Uri::urnEncode(
				$res["AUTHOR_AVATAR"]["src"]
			) . "')" : ""),
		"author_avatar" => (!empty($res["AUTHOR_AVATAR"]["src"]) ? "style=\"background:url('" . Uri::urnEncode(
				$res["AUTHOR_AVATAR"]["src"]
			) . "') no-repeat center; background-size: cover;\"" : ""),
		"author_url" => $res["urlToAuthor"],
	];

	if (!trim($res['post_text']))
	{
		$res['post_text'] = getMessage('SBB_READ_EMPTY');
	}

	$arRes["data"][] = $res;
}

if ($_REQUEST["AJAX_POST"] ?? null == "Y")
{
	$APPLICATION->RestartBuffer();
	echo \Bitrix\Main\Web\Json::encode($arRes);
	die();
}

$arUser = (is_array($arResult["USER"]) ? $arResult["USER"] : []);
$btnTitle = GetMessage("SBB_READ_" . $arUser["PERSONAL_GENDER"]);
$btnTitle = (!empty($btnTitle) ? $btnTitle : GetMessage("SBB_READ_"));
$res = reset($arRes["data"])
	? : [
		'author_avatar' => '',
		'author_url' => null,
		'author_name' => null,
		'post_url' => null,
		'post_text' => null,
	];
$this->SetViewTarget("sidebar", 80);
$frame = $this->createFrame()->begin();
$widgetContainerId = 'blog-' . $this->randString();
?>
	<div class="sidebar-widget --imp-messages<?= (empty($arRes["data"]) ? ' --hidden' : '') ?>" id="<?= $widgetContainerId ?>">
		<div class="sidebar-widget-top">
			<div class="sidebar-widget-top-title"><?= GetMessage("SBB_IMPORTANT") ?></div>
		</div>
		<div class="sidebar-widget-content">
			<div class="sidebar-imp-mess-list" id="sidebar-imp-mess-list"></div>
			<div class="sidebar-imp-mess-bottom">
				<button id="sidebar-imp-mess-read-button" class="sidebar-imp-mess-btn"><?= $btnTitle ?></button>
				<div class="sidebar-imp-mess-nav-block">
					<button class="sidebar-imp-mess-nav-arrow-l" disabled id="sidebar-imp-mess-prev"></button>
					<span id="sidebar-imp-mess-current-mess-number" class="sidebar-imp-mess-nav-current-page">1</span>
					<span class="sidebar-imp-mess-nav-separator">/</span>
					<span id="sidebar-imp-mess-total" class="sidebar-imp-mess-nav-total-page"><?= $arResult["NAV_RESULT"]->NavRecordCount ?></span>
					<button class="sidebar-imp-mess-nav-arrow-r" disabled id="sidebar-imp-mess-next"></button>
				</div>
			</div>
		</div>
	</div>
<?
$filter = $arParams["FILTER"];
// For composite mode
unset($filter["<=DATE_PUBLISH"]);
foreach ($filter as $filterKey => $filterValues)
{
	if (is_numeric($filterKey) && is_array($filterValues))
	{
		foreach ($filterValues as $complexFilterKey => $complexFilterValue)
		{
			if ($complexFilterKey == ">=UF_IMPRTANT_DATE_END")
			{
				unset($filter[$filterKey][$complexFilterKey]);
			}
		}
	}
}
?>
	<script>
		new BX.Intranet.ImportantMessagesWidget({
			widgetContainerId: '<?= $widgetContainerId ?>',
			messages: <?= Json::encode($arRes["data"]) ?>,
			options: <?= Json::encode($arParams["OPTIONS"])?>,
			postInfo: {
				'template': '<?=$this->__name?>',
				'filter': <?=Json::encode($filter)?>,
				'avatar_size': <?=(int)$arParams["AVATAR_SIZE"]?>,
			},
			pageSettings: <?= Json::encode($arRes["page_settings"]) ?>,
			url: '<?=CUtil::JSEscape($arResult["urlToPosts"])?>',
		});
	</script>
<?
$frame->end();
$this->EndViewTarget();
