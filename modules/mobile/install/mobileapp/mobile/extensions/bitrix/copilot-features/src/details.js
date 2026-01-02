/**
 * @module copilot-features/src/details
 */
jn.define('copilot-features/src/details', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const { H3 } = require('ui-system/typography/heading');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text4 } = require('ui-system/typography/text');
	const { BottomSheet } = require('bottom-sheet');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Box } = require('ui-system/layout/box');
	const { Loc } = require('loc');

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class CopilotFeatureDetails
	 *
	 * @typedef {object} CopilotFeatureDetailsProps
	 * @property {string} imageSrc
	 * @property {string} title
	 * @property {Array<{ icon: string | Icon, text: string }>} advantages
	 * @property {object} parentWidget
	 */
	class CopilotFeatureDetails extends LayoutComponent
	{
		render()
		{
			const {
				imageSrc,
				title,
			} = this.props;

			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
					withScroll: false,
				},
				AreaList(
					{},
					Area(
						{},
						View(
							{
								style: {
									marginHorizontal: Indent.XL3.toNumber(),
									flexDirection: 'column',
									alignItems: 'center',
								},
							},
							Image(
								{
									style: {
										width: 142,
										height: 142,
										marginBottom: Indent.XL3.toNumber(),
									},
									resizeMode: 'cover',
									svg: {
										uri: imageSrc,
									},
								},
							),
							H3({
								text: title,
								color: Color.base2,
								numberOfLines: 1,
								ellipsize: 'end',
								style: {
									marginBottom: Indent.XL3.toNumber(),
								},
							}),
							this.renderAdvantages(),
						),
					),
				),
			);
		}

		renderAdvantages()
		{
			const {
				advantages,
			} = this.props;

			return View(
				{
					style: {
						marginHorizontal: Indent.XL3.toNumber(),
						marginVertical: Indent.XL2.toNumber(),
						flexDirection: 'column',
					},
				},
				...advantages.map((advantage, index) => this.renderAdvantage(advantage, index === advantages.length - 1)),
			);
		}

		renderAdvantage(advantage, isLast = false)
		{
			const { icon, text } = advantage;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						marginBottom: !isLast && Indent.XL2.toNumber(),
					},
				},
				IconView({
					icon,
					size: 28,
					color: Color.copilotAccentLess1,
					style: {
						marginRight: Indent.L.toNumber(),
					},
				}),
				Text4({
					text,
					color: Color.base2,
					style: {
						flexShrink: 2,
					},
				}),
			);
		}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: !isAndroid,
					keyboardButton: {
						text: Loc.getMessage('COPILOT_FEATURE_ADVANTAGE_BUTTON_TEXT'),
						onClick: () => this.onButtonClick(),
						testId: 'copilot-feature-detail-button',
					},
				},
				Button(
					{
						design: ButtonDesign.COPILOT,
						size: ButtonSize.L,
						text: Loc.getMessage('COPILOT_FEATURE_ADVANTAGE_BUTTON_TEXT'),
						stretched: true,
						onClick: () => this.onButtonClick(),
						testId: 'copilot-feature-detail-button',
					},
				),
			);
		}

		onButtonClick()
		{
			this.props?.layout.close(() => {
				if (this.props.onButtonClick)
				{
					this.props.onButtonClick();
				}
			});
		}

		/**
		 * @param {CopilotFeatureDetailsProps} props
		 */
		static open(props)
		{
			const {
				parentWidget = PageManager,
			} = props;

			void new BottomSheet({
				titleParams: {
					type: 'dialog',
				},
				component: (layout) => {
					return new CopilotFeatureDetails({
						layout,
						...props,
					});
				},
			})
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.open();
		}
	}

	module.exports = {
		CopilotFeatureDetails,
	};
});
