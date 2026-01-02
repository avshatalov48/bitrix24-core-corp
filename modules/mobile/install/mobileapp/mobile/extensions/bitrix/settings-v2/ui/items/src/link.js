/**
 * @module settings-v2/ui/items/src/link
 */
jn.define('settings-v2/ui/items/src/link', (require, exports, module) => {
	const { SettingSelector, SettingMode } = require('ui-system/blocks/setting-selector');
	const { createTestIdGenerator } = require('utils/test');

	class LinkItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-link-item',
				context: this,
			});
		}

		render()
		{
			const { id } = this.props;

			return SettingSelector({
				...this.props,
				testId: this.getTestId(id),
				mode: SettingMode.PARAMETER,
				modeParams: {
					chevron: true,
				},
			});
		}
	}

	module.exports = {
		LinkItem,
	};
});
