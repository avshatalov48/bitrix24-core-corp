/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flow-promo-content
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flow-promo-content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { CardDesign } = require('ui-system/layout/card');
	const { FlowContent } = require('tasks/flow-list/simple-list/items/flow-redux/src/flow-content');
	const { H4 } = require('ui-system/typography/heading');
	const { Text6 } = require('ui-system/typography/text');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');

	class FlowPromoContent extends FlowContent
	{
		get shouldShowAiAdviceFooter()
		{
			return false;
		}

		get testId()
		{
			return `flow-promo-content-${this.props.id}`;
		}

		getCardDesign()
		{
			return CardDesign.SECONDARY;
		}

		cardClickHandler = () => {};

		renderHeader()
		{
			return View(
				{},
				H4({
					testId: `${this.testId}-name`,
					text: this.flowName,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				this.description !== '' && Text6({
					testId: `${this.testId}-planned-completion-time`,
					text: this.description,
					color: Color.base4,
					numberOfLines: 4,
					ellipsize: 'end',
					style: {
						marginTop: Indent.XS.toNumber(),
					},
				}),
			);
		}

		renderStrikethrough()
		{
			return View(
				{
					style: {
						position: 'absolute',
						height: 1,
						left: 35,
						right: 35,
						top: 47,
						backgroundColor: Color.bgSeparatorSecondary.toHex(),
					},
				},
			);
		}

		getEfficiencySvgUri()
		{
			return `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/flow-list/simple-list/items/flow-redux/images/${AppTheme.id}/neutral.png`;
		}

		renderEfficiency()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						alignItems: 'flex-start',
						justifyContent: 'flex-start',
						flexGrow: 1,
						paddingRight: Indent.XS2.toNumber(),
						paddingTop: Indent.XS2.toNumber(),
					},
				},
				Text6({
					text: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_PROGRESS_STATUS_EFFICIENCY'),
					color: this.getStageHeaderColor(),
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						width: '100%',
						textAlign: 'center',
					},
				}),
				View(
					{
						style: {
							width: '100%',
							marginVertical: Indent.M.toNumber(),
							alignItems: 'center',
						},
					},
					Image({
						style: {
							alignSelf: 'center',
							width: 56,
							height: 30,
						},
						uri: this.getEfficiencySvgUri(),
					}),
				),
			);
		}

		renderProgressStat({
			title,
			value,
			titleAlign = 'center',
			paddingRight = Indent.XS2.toNumber(),
		})
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						alignItems: 'flex-start',
						flexGrow: 1,
						paddingRight,
						paddingTop: Indent.XS2.toNumber(),
					},
				},
				Text6({
					text: title,
					color: Color.base4,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						width: '100%',
						textAlign: titleAlign,
					},
				}),
				View(
					{
						style: {
							width: '100%',
							alignItems: 'center',
						},
					},
					View(
						{
							style: {
								height: 30,
								width: 30,
								marginVertical: Indent.M.toNumber(),
								borderRadius: 15,
								borderWidth: 1,
								borderColor: Color.base4.toHex(),
								borderStyle: 'dash',
								borderDashSegmentLength: 3,
								borderDashGapLength: 3,
								backgroundColor: Color.bgContentSecondary.toHex(),
							},
						},
					),
				),
			);
		}

		renderFooter()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: Indent.XL3.toNumber(),
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Button({
						testId: `${this.testId}-create-flow`,
						text: Loc.getMessage('TASKSMOBILE_FLOW_CONTENT_ENABLE_FLOW_BUTTON_TEXT'),
						size: ButtonSize.M,
						design: ButtonDesign.FILLED,
						onClick: () => {
							qrauth.open({
								redirectUrl: this.enableFlowUrl,
								showHint: true,
								analyticsSection: 'tasks',
							});
						},
					}),
				),
			);
		}
	}

	module.exports = {
		FlowPromoContent,
	};
});
