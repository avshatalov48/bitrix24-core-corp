/**
 * @module tasks/layout/task/create/bottomPanel
 */
jn.define('tasks/layout/task/create/bottomPanel', (require, exports, module) => {
	const {Loc} = require('loc');

	class BottomPanel extends LayoutComponent
	{
		static getPanelHeight()
		{
			return 52;
		}

		constructor(props)
		{
			super(props);

			this.state = {
				isAttachmentLoading: false,
				attachmentCount: 0,
			};
		}

		updateState(newState)
		{
			this.setState({
				isAttachmentLoading: newState.isAttachmentLoading,
				attachmentCount: newState.attachmentCount,
			});
		}

		render()
		{
			return new UI.BottomToolbar({
				renderContent: () => View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							height: BottomPanel.getPanelHeight(),
						},
						testId: 'taskCreateToolbar',
					},
					View(
						{
							style: {
								flex: 1,
								justifyContent: 'center',
								marginLeft: 6,
							},
							testId: 'taskCreateToolbar_attachFileButton',
							onClick: this.props.onAttachmentButtonClick,
						},
						Image({
							style: {
								width: 28,
								height: 28,
							},
							uri: this.getImageUrl(`${this.props.pathToImages}/tasksmobile-layout-task-toolbar-attach-file.png`),
						}),
						this.renderAttachmentButtonCounter(),
					),
					View(
						{
							style: {
								justifyContent: 'center',
								paddingHorizontal: 28,
								borderLeftWidth: 1,
								borderLeftColor: '#eef2f4',
							},
							testId: 'taskCreateToolbar_allFieldsButton',
							onClick: this.props.onExpandButtonClick,
						},
						Text({
							style: {
								fontSize: 16,
								fontWeight: '500',
								color: '#bdc1c6',
							},
							text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_BOTTOM_PANEL_ALL_FIELDS'),
						}),
					),
					View(
						{
							style: {
								justifyContent: 'center',
								paddingHorizontal: 28,
								borderLeftWidth: 1,
								borderLeftColor: '#eef2f4',
							},
							testId: 'taskCreateToolbar_createButton',
							onClick: this.props.onCreateButtonClick,
						},
						Text({
							style: {
								fontSize: 18,
								fontWeight: '500',
								color: '#0b66c3',
							},
							text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_BOTTOM_PANEL_CREATE'),
						}),
					),
				),
			});
		}

		renderAttachmentButtonCounter()
		{
			const baseStyle = {
				position: 'absolute',
				left: 14,
				top: 8,
				width: 18,
				height: 18,
			};

			if (this.state.isAttachmentLoading)
			{
				return Loader({
					style: baseStyle,
					tintColor: '#2fc6f6',
					animating: true,
					size: 'small',
				});
			}

			if (!this.state.attachmentCount)
			{
				return View();
			}

			return View(
				{
					style: {
						...baseStyle,
						justifyContent: 'center',
						alignItems: 'center',
						backgroundColor: '#2fc6f6',
						borderRadius: 9,
					},
				},
				Text({
					style: {
						fontSize: 12,
						fontWeight: '500',
						color: '#ffffff',
					},
					text: String(this.state.attachmentCount),
				})
			);
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return encodeURI(imageUrl);
		}
	}

	module.exports = {BottomPanel};
});