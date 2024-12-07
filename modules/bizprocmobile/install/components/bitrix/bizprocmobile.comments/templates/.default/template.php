<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . '/log_mobile.js');
Asset::getInstance()->addCss($this->GetFolder() . '/style.css');

Extension::load([
	'mobile.diskfile',
	'ui.alerts',
]);

if (is_array($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
	$isUiIncluded = Loader::includeModule('ui');
	/** @var \Bitrix\Main\Error $error */
	foreach ($arResult['ERRORS'] as $error)
	{
		if ($isUiIncluded)
		{
			?>
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
			</div>
			<?php
		}
		else
		{
			ShowError($error->getMessage());
		}
	}
	return;
}
$workflowId = $arResult['WORKFLOW']['ID'];
$workflowIdInt = (int)$arResult['WORKFLOW']['ID_INT'];
?>

<div style="display: none">
	<?php $APPLICATION->IncludeComponent(
		'bitrix:rating.vote',
		'like_react',
		[
			'MOBILE' => 'Y',
			'ENTITY_TYPE_ID' => 'WF',
			'ENTITY_ID' => $workflowIdInt,
			'OWNER_ID' => $arResult['WORKFLOW']['STARTED_BY'],
			'VOTE_ID' => HtmlFilter::encode("WF_{$workflowIdInt}-" . (time() + random_int(0, 1000))),
			'TYPE' => 'POST',
		],
		$component->__parent,
		['HIDE_ICONS' => 'Y']
	);
	?>
</div>

<div id="workflow-comments-block">
	<?php $APPLICATION->IncludeComponent(
		'bitrix:forum.comments',
		'',
		[
			'FORUM_ID' => $arResult['FORUM_ID'],
			'ENTITY_TYPE' => 'WF',
			'ENTITY_ID' => $workflowIdInt,
			'ENTITY_XML_ID' => "WF_{$workflowId}",
			'POST_CONTENT_TYPE_ID' => 'WF',
			'URL_TEMPLATES_PROFILE_VIEW' => $arResult['PATH_TEMPLATE_TO_USER_PROFILE'],
			'CACHE_TYPE' => 'Y',
			'CACHE_TIME' => 3600,
			'IMAGE_HTML_SIZE' => 400,
			'DATE_TIME_FORMAT' => $arResult['DATE_TIME_FORMAT'],
			'SHOW_RATING' => 'Y',
			'RATING_TYPE' => 'like',
			'PREORDER' => 'N',
			'PERMISSION' => 'M',
			'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'],
			'SKIP_USER_READ' => 'Y',
		],
		null,
		['HIDE_ICONS' => 'Y']
	);
	?>
</div>

<div class="workflow-detail-no-comments --full-height" id="commentsStub">
	<div class="workflow-detail-no-comments-inner">
		<div class="workflow-detail-no-comments-top-image-container">
			<div class="workflow-detail-no-comments-top-image"></div>
		</div>
		<div class="workflow-detail-no-comments-text">
			<?= Loc::getMessage('BIZPROCMOBILE_COMMENTS_STUB_TEXT') ?>
		</div>
		<div class="workflow-detail-no-comments-arrow-container">
			<div class="workflow-detail-no-comments-arrow"></div>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			new BX.BizprocMobile.Comments(<?= Json::encode([
				'userId' => $arResult['USER_ID'],
				'workflowId' => $workflowId,
				'workflowIdInt' => $workflowIdInt,
				'guid' => $arResult['GUID'],
				'logId' => $arResult['LOG_ID'],
				'currentTs' => time(),
			]) ?>);
		}
	);
</script>
