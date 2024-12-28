/**
 * @module tasks/layout/fields/user-fields/edit/field/input
 */
jn.define('tasks/layout/fields/user-fields/edit/field/input', (require, exports, module) => {
	const { Icon } = require('ui-system/blocks/icon');
	const { InputSize, InputDesign } = require('ui-system/form/inputs/string');
	const { Indent } = require('tokens');
	const { useCallback } = require('utils/function');

	const getBaseInputFieldProps = (value, index, field) => {
		const props = {
			value,
			style: {
				marginTop: Indent.M.toNumber(),
				marginBottom: Indent.S.toNumber(),
			},
			parentWidget: field.parentWidget,
			readOnly: field.isReadOnly,
			size: InputSize.L,
			design: InputDesign.GREY,
			error: field.shouldShowErrors && !field.isValueValid(value),
			testId: `${field.testId}_VALUE_${index}`,
			ref: useCallback((ref) => field.refsMap.set(index, ref)),
		};

		if (!field.isReadOnly)
		{
			const removeValue = useCallback(() => field.removeValue(index));

			props.onErase = removeValue;
			props.onClickRightStickContent = removeValue;

			if (!field.isMultiple || field.state.value.length === 1)
			{
				props.erase = true;
			}
			else
			{
				props.rightStickContent = Icon.TRASHCAN;
			}
		}

		return props;
	};

	module.exports = { getBaseInputFieldProps };
});
