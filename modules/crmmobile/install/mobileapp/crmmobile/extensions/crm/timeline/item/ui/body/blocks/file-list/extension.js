/**
 * @module crm/timeline/item/ui/body/blocks/file-list
 */
jn.define('crm/timeline/item/ui/body/blocks/file-list', (require, exports, module) => {

	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');

	class TimelineItemBodyFileList extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderTitle(),
			);
		}

		renderTitle()
		{
			const title = this.props.numberOfFiles
				? `${this.props.title} (${this.props.numberOfFiles})`
				: `${this.props.title}`;

			const borderNone = {};
			const borderDashed = {
				borderStyle: 'dash',
				borderBottomWidth: 1,
				borderBottomColor: '#A8ADB4',
			};

			return View(
				{},
				View(
					{
						style: {
							flexDirection: 'row',
						},
						onClick: () => this.openFeatureNotSupportedStub(),
					},
					View(
						{
							style: this.canOpenFileManager() ? borderDashed : borderNone,
						},
						Text({
							text: title,
							style: {
								color: '#828B95',
								fontSize: 13,
							}
						})
					),
					this.canOpenFileManager() && View(
						{
							style: {
								marginHorizontal: 6,
							}
						},
						Image({
							svg: {
								content: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3269 3.77612L14.2398 5.70918L6.75656 13.1723L4.84363 11.2392L12.3269 3.77612ZM3.76918 14.0045C3.75109 14.0729 3.77047 14.1453 3.81957 14.1956C3.86996 14.246 3.94231 14.2654 4.01079 14.246L6.14919 13.6699L4.34544 11.8667L3.76918 14.0045Z" fill="black" fill-opacity="0.2"/></svg>`
							},
							style: {
								width: 18,
								height: 18,
							}
						})
					),
				)
			);
		}

		canOpenFileManager()
		{
			return this.props.hasOwnProperty('updateParams') && !this.isReadonly;
		}

		openFeatureNotSupportedStub()
		{
			this.timelineScopeEventBus.emit('Crm.Timeline::onFeatureNotSupported');
		}
	}

	module.exports = { TimelineItemBodyFileList };
});