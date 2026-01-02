/**
 * @module settings-v2/ui/items/src/toggle
 */
jn.define('settings-v2/ui/items/src/toggle', (require, exports, module) => {
	const { SettingSelector, SettingMode } = require('ui-system/blocks/setting-selector');
	const { createTestIdGenerator } = require('utils/test');

	class ToggleItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-toggle-item',
				context: this,
			});
		}

		render()
		{
			const { id, value } = this.props;

			return SettingSelector({
				...this.props,
				testId: this.getTestId(id),
				mode: SettingMode.TOGGLE,
				onClick: this.onChange,
				checked: value,
			});
		}

		onChange = (checked) => {
			const { id, controller, onChange } = this.props;

			if (onChange)
			{
				onChange(id, controller, checked);
			}
		};
	}

	module.exports = {
		ToggleItem,
	};
});
