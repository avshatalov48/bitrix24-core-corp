<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):
	?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<? $filedType = $dialog->getFieldTypeObject($field);

			echo $filedType->renderControl(array(
				'Form' => $dialog->getFormName(),
				'Field' => $field['FieldName']
			), $dialog->getCurrentValue($field['FieldName']), true, 0);
			?>
		</td>
	</tr>
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
