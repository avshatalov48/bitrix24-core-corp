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
	'tasks.result',
	'ui.alerts',
]);

if (is_array($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
	$isUiIncluded = Loader::includeModule('ui');
	foreach ($arResult['ERRORS'] as $error)
	{
		$message = $error['MESSAGE'];
		if ($isUiIncluded)
		{
			?>
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message"><?= htmlspecialcharsbx($message) ?></span>
			</div>
			<?php
		}
		else
		{
			ShowError($message);
		}
	}
	return;
}

$taskId = (int)$arResult['TASK']['ID'];
?>

<div style="display: none">
	<?php $APPLICATION->IncludeComponent(
		'bitrix:rating.vote',
		'like_react',
		[
			'MOBILE' => 'Y',
			'ENTITY_TYPE_ID' => 'TASK',
			'ENTITY_ID' => $taskId,
			'OWNER_ID' => $arResult['TASK']['CREATED_BY'],
			'VOTE_ID' => HtmlFilter::encode("TASK_{$taskId}-" . (time() + random_int(0, 1000))),
			'TYPE' => 'POST',
		],
		$component->__parent,
		['HIDE_ICONS' => 'Y']
	);
	?>
</div>

<div id="task-comments-block">
	<?php $APPLICATION->IncludeComponent(
		'bitrix:forum.comments',
		'',
		[
			'FORUM_ID' => $arResult['FORUM_ID'],
			'ENTITY_TYPE' => 'TK',
			'ENTITY_ID' => $taskId,
			'ENTITY_XML_ID' => "TASK_{$taskId}",
			'POST_CONTENT_TYPE_ID' => 'TASK',
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

<div class="task-detail-no-comments --full-height" id="commentsStub">
	<div class="task-detail-no-comments-inner">
		<div class="task-detail-no-comments-top-image-container">
			<div class="task-detail-no-comments-top-image"></div>
		</div>
		<div class="task-detail-no-comments-text">
			<?= Loc::getMessage('TASKSMOBILE_COMMENTS_STUB_TEXT') ?>
		</div>
		<div class="task-detail-no-comments-arrow-container">
			<div class="task-detail-no-comments-arrow"></div>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			new BX.TasksMobile.Comments(<?= Json::encode([
				'userId' => $arResult['USER_ID'],
				'taskId' => $taskId,
				'guid' => $arResult['GUID'],
				'logId' => $arResult['LOG_ID'],
				'currentTs' => time(),
				'resultComments' => $arResult['RESULT_COMMENTS'],
				'isClosed' => in_array(
					(int)$arResult['TASK']['STATUS'],
					[CTasks::STATE_SUPPOSEDLY_COMPLETED, CTasks::STATE_COMPLETED],
					true
				),
			]) ?>);
		}
	);
</script>
