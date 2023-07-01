<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>
<div class="rpa-toolbar">
	<div class="rpa-toolbar-left">
		<?php
		if(!empty($arResult['views']))
		{
		?>
		<div class="rpa-toolbar-switcher">
			<div class="rpa-toolbar-switcher-list">
				<?php
				foreach($arResult['views'] as $view)
				{
					?>
					<a class="rpa-toolbar-switcher-item<?= (isset($view['isActive']) && $view['isActive'] === true ? ' rpa-toolbar-switcher-item-active' : '') ?>" href="<?=htmlspecialcharsbx($view['url']);?>"><?=htmlspecialcharsbx($view['title']);?></a>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		}
		$tasks = (int) $arResult['tasks'];
		?>
		<div class="rpa-toolbar-counter" id="rpa-toolbar-tasks-counter"<?=(($tasks <= 0) ? ' style="display: none;"' : '');?>>
			<div class="rpa-toolbar-counter-title">
				<span class="rpa-toolbar-counter-title-name"><?=Loc::getMessage('RPA_TOOLBAR_TASKS_COUNTER');?></span>
				<a class="rpa-toolbar-counter-container" href="<?=htmlspecialcharsbx($arResult['tasksUrl'])?>">
					<span class="rpa-toolbar-counter-inner">
						<?=Loc::getMessage('RPA_TOOLBAR_TASKS_NOT_COMPLETED', [
								'#TASKS#' => $tasks,
						]);?>
					</span>
				</a>
			</div>
		</div>
	</div>
	<?php
	if(isset($arResult['robotsUrl']) && $arResult['robotsUrl'])
	{
	?>
	<div class="rpa-toolbar-right">
		<a href="<?=htmlspecialcharsbx($arResult['robotsUrl'])?>" class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round rpa-toolbar-btn">
			<?=Loc::getMessage('RPA_AUTOMATION_ROBOTS')?>
		</a>
	</div>
	<?php
	}
	?>
</div>
<script>
BX.ready(function()
{
	if(!BX.Main || !BX.Main.filterManager || !BX.Main.Filter)
	{
		return;
	}
	var button = document.getElementById('rpa-toolbar-tasks-counter');
	if(button)
	{
		BX.bind(button, 'click', function(event)
		{
			var filter = BX.Main.filterManager.getById('<?=CUtil::JSEscape($arResult['tasksFilter']['filterId']);?>');

			if (!!filter && (filter instanceof BX.Main.Filter))
			{
				var api = filter.getApi();
				api.setFields(<?=CUtil::PhpToJSObject($arResult['tasksFilter']['fields']);?>);
				api.apply();

				event.preventDefault();
				event.stopPropagation();
			}
		});
	}

	var taskCountersPullTag = '<?=CUtil::JSEscape($arResult['taskCountersPullTag']);?>';
	if(taskCountersPullTag && BX.PULL)
	{
		BX.PULL.subscribe({
			moduleId: 'rpa',
			command: taskCountersPullTag,
			callback: function(params)
			{
				var update = params.counter;
				var changedTypeId = parseInt(params.typeId);
				if(changedTypeId > 0)
				{
					var toolbarTasksNode = document.getElementById('rpa-toolbar-tasks-counter');
					var toolbarCounterNode = document.querySelector('#rpa-toolbar-tasks-counter .rpa-toolbar-counter-number');
					if(toolbarTasksNode && toolbarCounterNode)
					{
						var counter = parseInt(toolbarCounterNode.innerText);
						var typeId = '<?=CUtil::JSEscape($arResult['typeId']);?>';
						if(typeId === 'all' ||  parseInt(typeId) === changedTypeId)
						{
							if(update === '+1')
							{
								counter++;
							}
							else if(update === '-1')
							{
								counter--;
							}
							toolbarCounterNode.innerText = counter;
							if(counter <= 0)
							{
								toolbarTasksNode.style.display = 'none';
							}
							else
							{
								toolbarTasksNode.style.display = 'block';
							}
						}
					}
				}
			}
		});

        BX.PULL.extendWatch(taskCountersPullTag);
	}
});
</script>