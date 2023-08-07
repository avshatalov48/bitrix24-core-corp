/**
 * @module crm/timeline/item/ui/body/blocks/sms-message
 */
jn.define('crm/timeline/item/ui/body/blocks/sms-message', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');
	const { get } = require('utils/object');

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
						this.hasClipboardContent() && Image({
							svg: {
								content: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.77089 5.79683H15.7537C16.8582 5.79683 17.7537 6.69226 17.7537 7.79683V16.0345H17.9674C19.072 16.0345 19.9674 15.1391 19.9674 14.0345V6.04102C19.9674 4.93645 19.072 4.04102 17.9675 4.04102H9.75616C8.73326 4.04102 7.89114 4.80851 7.77089 5.79683Z" fill="#D5D7DB"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.02295 9.95065C4.02295 8.84608 4.91838 7.95065 6.02295 7.95065H14.0283C15.1329 7.95065 16.0283 8.84608 16.0283 9.95065V17.956C16.0283 19.0606 15.1329 19.956 14.0283 19.956H6.02295C4.91838 19.956 4.02295 19.0606 4.02295 17.956V9.95065ZM13.9505 10.0287H6.10085V17.8784H13.9505V10.0287Z" fill="#D5D7DB"/></svg>',
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
