/**
 * @module calendar/sync-page/settings/section
 */
jn.define('calendar/sync-page/settings/section', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BooleanField } = require('layout/ui/fields/boolean');

	/**
	 * @class SyncSettingsSection
	 */
	class SyncSettingsSection extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				value: props.section.ACTIVE !== 'N',
			};
		}

		render()
		{
			return View(
				{
					style: {
						marginTop: this.props.index === 0 ? 8 : 6,
					},
					testId: `sync_page_settings_section_${this.props.section.ID}`,
				},
				BooleanField({
					readOnly: false,
					showEditIcon: false,
					showTitle: false,
					value: this.state.value,
					config: {
						styles: {
							description: {
								color: AppTheme.colors.base1,
								fontSize: 17,
								lineHeight: 24,
								fontWeight: '400',

							},
							alignItems: 'flex-start',
							marginLeft: 10,
						},
						description: this.props.section.NAME,
					},
					onChange: (value) => {
						this.setState({ value });
						this.props.onChange(this.props.section.ID, value);
					},
					testId: `sync_page_settings_section_switcher_${this.props.section.ID}`,
				}),
			);
		}
	}

	module.exports = { SyncSettingsSection };
});
