<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
$title = $map['DealTitle'];
$category = $map['CategoryId'];
$selected = $dialog->getCurrentValue($category['FieldName']);

foreach ($map as $propertyKey => $property):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?=htmlspecialcharsbx($property['Name'])?>:
		</span>
		<?=$dialog->renderFieldControl($property)?>
	</div>
<?endforeach;?>
<script>
	BX.ready(function()
	{
		var formName = '<?=CUtil::JSEscape($dialog->getFormName())?>';

		var categorySelect = document.forms[formName]['category_id'];
		var stageSelect = document.forms[formName]['stage_id'];

		if (!categorySelect || !stageSelect)
		{
			return false;
		}

		var filter = function(catId)
		{
			var prefix = catId === '0' ? '' : 'C' + catId + ':';
			var currentValue = stageSelect.value;

			for (var i = 0; i < stageSelect.options.length; ++i)
			{
				var opt = stageSelect.options[i];

				if (opt.value === '' || opt.getAttribute('data-role') === 'expression')
				{
					continue;
				}

				opt.disabled = (prefix && opt.value.indexOf(prefix) < 0 || !prefix && opt.value.indexOf(':') > -1);

				if (opt.disabled && opt.value === currentValue)
				{
					opt.selected = false;
				}

				BX[opt.disabled ? 'hide' : 'show'](opt);
			}
		};

		var handler = function()
		{
			filter(this.value);
		};

		BX.bind(categorySelect, 'change', handler);

		filter(categorySelect.value);
	});
</script>