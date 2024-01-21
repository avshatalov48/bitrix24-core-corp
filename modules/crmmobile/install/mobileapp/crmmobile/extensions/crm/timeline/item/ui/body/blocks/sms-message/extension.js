/**
 * @module crm/timeline/item/ui/body/blocks/sms-message
 */
jn.define('crm/timeline/item/ui/body/blocks/sms-message', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');
	const { get } = require('utils/object');
	const { copy } = require('assets/common');
	const AppTheme = require('apptheme');

	/**
	 * @class TimelineItemBodySmsMessageBlock
	 */
	class TimelineItemBodySmsMessageBlock extends TimelineItemBodyBlock
	{
		/**
		 * @return {boolean}
		 */
		hasClipboardContent()
		{
			const event = get(this, 'props.action.value');

			return event === 'Clipboard:Copy';
		}

		render()
		{
			return View(
				{
					onClick: () => this.emitAction(this.props.action),
				},
				View(
					{
						style: {
							paddingRight: 20,
							marginTop: 8,
							flexDirection: 'row',
						},
					},
					View(
						{
							style: {
								paddingVertical: 10,
								paddingLeft: 12,
								paddingRight: this.hasClipboardContent() ? 36 : 12,
								borderTopWidth: 1,
								borderTopColor: AppTheme.colors.accentSoftBlue1,
								borderBottomWidth: 1,
								borderBottomColor: AppTheme.colors.accentSoftBlue1,
								borderLeftWidth: 1,
								borderLeftColor: AppTheme.colors.accentSoftBlue1,
								flex: 1,
								flexGrow: 2,
							},
						},
						Text({
							text: this.props.text,
							style: {
								fontSize: TimelineFontSize.get(this.props.size),
								color: TimelineFontColor.get(this.props.color),
								fontWeight: TimelineFontWeight.get(this.props.weight),
							},
						}),
						this.hasClipboardContent() && Image({
							tintColor: AppTheme.colors.base3,
							svg: {
								content: copy(),
							},
							style: {
								width: 24,
								height: 24,
								position: 'absolute',
								top: 6,
								right: 8,
							},
						}),
					),
					View(
						{
							style: {
								flex: 1,
								flexDirection: 'column',
								marginLeft: -1,
								maxWidth: 16,
							},
						},
						View(
							{
								style: {
									height: 7,
									width: 1,
									backgroundColor: AppTheme.colors.accentSoftBlue1,
								},
							},
						),
						Image({
							style: {
								width: 16,
								height: 14,
							},
							svg: {
								content: `<svg width="16" height="14" xmlns="http://www.w3.org/2000/svg" version="1.1"><path opacity="0.41" d="m14.68975,0.52453l-15.13419,0l0,-4.5c0,-1.38071 -1.11929,-2.5 -2.5,-2.5l-4,0c-1.38071,0 -2.5,1.11929 -2.5,2.5l0,44c0,1.38071 1.11929,2.5 2.5,2.5l4,0c1.38071,0 2.5,-1.11929 2.5,-2.5l0,-26.70444l15.13419,-12.79556z" stroke="${AppTheme.colors.accentBrandBlue}" fill="none"/></svg>`,
							},
						}),
						View(
							{
								style: {
									width: 1,
									flex: 1,
									backgroundColor: AppTheme.colors.accentSoftBlue1,
									marginTop: -1,
								},
							},
						),
					),
				),
			);
		}
	}

	module.exports = { TimelineItemBodySmsMessageBlock };
});
