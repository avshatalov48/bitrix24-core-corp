/**
 * @module tasks/layout/task/create/bottomPanel
 */
jn.define('tasks/layout/task/create/bottomPanel', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { clip } = require('assets/common');

	class BottomPanel extends LayoutComponent
	{
		static getPanelHeight()
		{
			return 52;
		}

		static getImageUrl(imageUrl)
		{
			let result = imageUrl;

			if (result.indexOf(currentDomain) !== 0)
			{
				result = result.replace(currentDomain, '');
				result = (result.indexOf('http') === 0 ? result : `${currentDomain}${result}`);
			}

			return encodeURI(result);
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
				safeArea: Application.getPlatform() === 'ios',
				shadow: true,
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
								width: 18,
								height: 18,
							},
							tintColor: AppTheme.colors.base3,
							svg: {
								content: clip,
							},
						}),
						this.renderAttachmentButtonCounter(),
					),
					View(
						{
							style: {
								justifyContent: 'center',
								paddingHorizontal: 28,
								borderLeftWidth: 1,
								borderLeftColor: AppTheme.colors.bgSeparatorSecondary,
							},
							testId: 'taskCreateToolbar_allFieldsButton',
							onClick: this.props.onExpandButtonClick,
						},
						Text({
							style: {
								fontSize: 16,
								fontWeight: '500',
								color: AppTheme.colors.base3,
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
								borderLeftColor: AppTheme.colors.bgSeparatorSecondary,
							},
							testId: 'taskCreateToolbar_createButton',
							onClick: this.props.onCreateButtonClick,
						},
						Text({
							style: {
								fontSize: 18,
								fontWeight: '500',
								color: AppTheme.colors.accentMainPrimary,
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
					tintColor: AppTheme.colors.accentBrandBlue,
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
						backgroundColor: AppTheme.colors.accentBrandBlue,
						borderRadius: 9,
					},
				},
				Text({
					style: {
						fontSize: 12,
						fontWeight: '500',
						color: AppTheme.colors.base8,
					},
					text: String(this.state.attachmentCount),
				}),
			);
		}
	}

	module.exports = { BottomPanel };
});
