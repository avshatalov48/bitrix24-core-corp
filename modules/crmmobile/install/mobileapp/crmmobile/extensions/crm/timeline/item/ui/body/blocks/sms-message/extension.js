/**
 * @module crm/timeline/item/ui/body/blocks/sms-message
 */
jn.define('crm/timeline/item/ui/body/blocks/sms-message', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');

	/**
	 * @class TimelineItemBodySmsMessageBlock
	 */
	class TimelineItemBodySmsMessageBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{},
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
								paddingHorizontal: 12,
								borderTopWidth: 1,
								borderTopColor: '#BFE7F9',
								borderBottomWidth: 1,
								borderBottomColor: '#BFE7F9',
								borderLeftWidth: 1,
								borderLeftColor: '#BFE7F9',
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
									backgroundColor: '#BFE7F9',
								},
							},
						),
						Image({
							style: {
								width: 16,
								height: 14,
							},
							svg: {
								content: '<svg width="16" height="14" xmlns="http://www.w3.org/2000/svg" version="1.1"><path opacity="0.41" d="m14.68975,0.52453l-15.13419,0l0,-4.5c0,-1.38071 -1.11929,-2.5 -2.5,-2.5l-4,0c-1.38071,0 -2.5,1.11929 -2.5,2.5l0,44c0,1.38071 1.11929,2.5 2.5,2.5l4,0c1.38071,0 2.5,-1.11929 2.5,-2.5l0,-26.70444l15.13419,-12.79556z" stroke="#2FC6F6" fill="none"/></svg>',
							},
						}),
						View(
							{
								style: {
									width: 1,
									flex: 1,
									backgroundColor: '#BFE7F9',
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
