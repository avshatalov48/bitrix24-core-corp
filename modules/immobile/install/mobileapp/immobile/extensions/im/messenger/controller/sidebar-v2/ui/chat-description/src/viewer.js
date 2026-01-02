/**
 * @module im/messenger/controller/sidebar-v2/ui/chat-description/src/viewer
 */
jn.define('im/messenger/controller/sidebar-v2/ui/chat-description/src/viewer', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography');

	class ChatDescriptionViewer extends LayoutComponent
	{
		static open({ text, testId, parentWidget })
		{
			const bottomSheet = new BottomSheet({
				component: (layoutWidget) => new ChatDescriptionViewer({
					text,
					testId,
					layoutWidget,
				}),
			});

			bottomSheet
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.setMediumPositionPercent(50)
				.disableHorizontalSwipe()
				.enableSwipe()
				.setTitleParams({
					text: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHAT_DESCRIPTION_POPUP_TITLE'),
					type: 'dialog',
				});

			void bottomSheet.open();
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
					style: {
						flex: 1,
					},
				},
				ScrollView(
					{
						style: {
							flex: 1,
						},
					},
					View(
						{
							style: {
								paddingHorizontal: Indent.XL3.toNumber(),
								paddingTop: Indent.XL.toNumber(),
								paddingBottom: Indent.XL2.toNumber(),
							},
						},
						Text4({
							testId: `${this.props.testId}-viewer-value`,
							text: this.props.text,
							color: Color.base2,
						}),
					),
				),
			);
		}
	}

	module.exports = { ChatDescriptionViewer };
});
