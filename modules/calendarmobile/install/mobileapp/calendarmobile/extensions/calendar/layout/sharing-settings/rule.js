/**
 * @module calendar/layout/sharing-settings/rule
 */
jn.define('calendar/layout/sharing-settings/rule', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { SharingSettingsRange } = require('calendar/layout/sharing-settings/range');

	/**
	 * @class SharingSettingsRule
	 */
	class SharingSettingsRule extends LayoutComponent
	{
		get model()
		{
			return this.props.model;
		}

		get rule()
		{
			return this.model.getSettings().getRule();
		}

		render()
		{
			return View(
				{
					style: {
						paddingHorizontal: 40,
					},
				},
				this.renderRanges(),
				this.renderDivider(),
				this.renderSlotSizeInfo(),
			);
		}

		renderRanges()
		{
			return View(
				{},
				...this.rule.getSortedRanges().map((range) => SharingSettingsRange({
					range,
				})),
			);
		}

		renderDivider()
		{
			return View(
				{
					style: {
						borderTopWidth: 1,
						flex: 1,
						borderTopColor: AppTheme.colors.base6,
						marginVertical: 15,
					},
				},
			);
		}

		renderSlotSizeInfo()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginLeft: 1,
					},
				},
				this.renderDurationTitle(),
				this.renderDuration(),
			);
		}

		renderDurationTitle()
		{
			return View(
				{
					style: {
						width: '50%',
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image(
					{
						tintColor: AppTheme.colors.accentBrandBlue,
						svg: {
							content: icons.hourglass,
						},
						style: {
							width: 14,
							height: 14,
							marginRight: 6,
						},
					},
				),
				Text(
					{
						style: {
							color: AppTheme.colors.base3,
						},
						text: Loc.getMessage('M_CALENDAR_SETTINGS_DURATION'),
					},
				),
			);
		}

		renderDuration()
		{
			return View(
				{
					style: {
						width: '50%',
					},
				},
				Text(
					{
						style: {
							color: AppTheme.colors.base3,
						},
						text: this.rule.getFormattedSlotSize(),
					},
				),
			);
		}
	}

	const icons = {
		hourglass: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.33813 10.3598L4.33823 8.82226C4.33945 8.72065 4.48943 8.52334 4.56828 8.45692L6.19249 7.00027L4.56828 5.54757C4.48821 5.48036 4.33823 5.1515 4.33823 5.0487V3.64066H4.02078C3.96331 3.64066 3.91672 3.59535 3.91672 3.53944V3.03335C3.91672 2.97745 3.96331 2.93213 4.02078 2.93213H9.47557C9.53304 2.93213 9.57963 2.97745 9.57963 3.03335V3.53944C9.57963 3.59535 9.53304 3.64066 9.47557 3.64066H9.15812V5.0487C9.15689 5.15031 9.00651 5.4776 8.92724 5.54363L7.30377 7.00027L8.92797 8.45368C9.00764 8.52089 9.15762 8.71938 9.15803 8.82218V10.3598H9.47548C9.53295 10.3598 9.57954 10.4051 9.57954 10.461V10.9671C9.57954 11.023 9.53295 11.0683 9.47548 11.0683H4.02068C3.96321 11.0683 3.91663 11.023 3.91663 10.9671V10.461C3.91663 10.4051 3.96321 10.3598 4.02068 10.3598H4.33813Z" fill="#2FC6F6"/></svg>',
	};

	module.exports = { SharingSettingsRule };
});
