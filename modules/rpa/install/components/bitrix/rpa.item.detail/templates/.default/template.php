<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-hidden no-background");

\Bitrix\Main\UI\Extension::load([
	'ui.stageflow',
	'rpa.timeline',
	'main.loader',
	'ui.notification',
	'ui.timeline',
	'ui.icons',
	'ui.dialogs.messagebox',
	'ui.buttons',
	'ui.alerts',
	'ui.fonts.opensans',
]);

?><div class="rpa-item-detail" id="<?=$arResult['jsParams']['containerId'];?>"><?php

if($this->getComponent()->getErrors())
{
	?><div class="ui-alert ui-alert-danger">
	<?php foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<span class="ui-alert-message"><?=htmlspecialcharsbx($error->getMessage());?></span>
		<?php
	}
	?></div>
	<?php return;
}

$messages = array_merge($this->getComponent()::loadBaseLanguageMessages(), Loc::loadLanguageFile(__FILE__));
?>
	<div class="rpa-stageflow-wrap" data-role="stageflow-wrap"></div>
	<div class="rpa-item-detail-tabs">
		<?php
		/*<ul class="rpa-item-detail-tabs-container" data-role="tab-menu">
			<li class="rpa-item-detail-tabs-item rpa-item-detail-tabs-item-current" data-tab-id="main">
				<a class="rpa-item-detail-tabs-item-link"><?=Loc::getMessage('RPA_ITEM_DETAIL_TAB_MAIN');?></a>
			</li>
			<li class="rpa-item-detail-tabs-item" data-tab-id="robots">
				<a class="rpa-item-detail-tabs-item-link"><?=Loc::getMessage('RPA_ITEM_DETAIL_TAB_ROBOTS');?></a>
			</li>
		</ul>
		*/
		?>
	</div>
	<div class="rpa-item-detail-tabs-content">
		<div class="rpa-item-detail-tab-content" data-tab-content="main">
			<div class="rpa-item-detail-editor-container ui-entity-section">
			<?php
				global $APPLICATION;
				$APPLICATION->IncludeComponent(
					"bitrix:ui.form",
					"",
					$arResult['formParams']
				);
			?>
			</div>
			<div id="rpa-item-detail-timeline"></div>
			<div style="clear: both;"></div>
		</div>
		<div class="rpa-item-detail-tab-content rpa-item-detail-tab-content-hidden" data-tab-content="robots">
		</div>
	</div>
<script>
	BX.ready(function()
	{
		BX.message(<?=\Bitrix\Main\Web\Json::encode($messages)?>);
		var params = <?=CUtil::PhpToJSObject($arResult['jsParams'], false, false, true);?>;
		var commentEditor = new BX.UI.Timeline.CommentEditor({
			typeId: params.typeId,
			itemId: params.id,
			id: 'RpaNewCommentEditor',
		});
		var itemClasses = [
			['task_complete', BX.Rpa.Timeline.TaskComplete],
		];
		var stream = new BX.UI.Timeline.Stream({
			items: params.history,
			users: params.users,
			nameFormat: params.nameFormat,
			pageSize: params.timelinePageSize,
			typeId: params.typeId,
			itemId: params.id,
			tasks: params.tasks,
			editors: [
				commentEditor,
			],
			itemClasses: itemClasses,
		});
		var timelineNode = document.getElementById('rpa-item-detail-timeline');
		timelineNode.appendChild(stream.render());
		params.stream = stream;
		(new BX.Rpa.ItemDetailComponent(params)).init();
	});
</script>
</div>