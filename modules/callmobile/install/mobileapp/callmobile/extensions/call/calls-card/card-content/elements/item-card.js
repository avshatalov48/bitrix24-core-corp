/**
 * @module call/calls-card/card-content/elements/item-card
 */
jn.define('call/calls-card/card-content/elements/item-card', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { MoneyField } = require('layout/ui/fields/money');
	const { dayMonth, longDate } = require('utils/date/formats');

	const arrow = (color) => `<svg width="25" height="28" viewBox="0 0 25 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 6C0 2.68629 2.68629 0 6 0H12.4812C14.4374 0 16.2707 0.953603 17.3938 2.55525L24.2112 12.2776C24.9361 13.3115 24.9361 14.6885 24.2112 15.7224L17.3938 25.4448C16.2707 27.0464 14.4374 28 12.4812 28H6C2.68629 28 0 25.3137 0 22V6Z" fill="${color}"/></svg>`;

	const ItemCard = (props) => {
		return View(
			{
				style: {
					borderRadius: 12,
					flexDirection: 'column',
					backgroundColor: '#FEFEFE',
				},
				testId: 'calls-card-item-card',
				onClick: props.onClick,
			},
			View(
				{
					style: {
						paddingHorizontal: 20,
						paddingVertical: 10,
						paddingLeft: 0,
						marginLeft: 22,
						borderBottomWidth: 1,
						borderBottomColor: '#edeef0',
					},
				},
				Text({
					text: props.header,
					style: {
						fontSize: 14,
						color: '#525C69',
					},
				}),
			),
			View(
				{
					style: {
						flexDirection: 'column',
						paddingVertical: 14,
						paddingHorizontal: 22,
					},
				},
				Text({
					text: props.TITLE,
					style: {
						fontSize: 18,
						fontWeight: '700',
						color: '#333333',
						marginBottom: 3,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 6,
						},
					},
					renderDate(props.CREATED_TIME),
					renderSubtitleText(props.REPEATED_TEXT),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					MoneyField({
						readOnly: true,
						value: {
							amount: props.OPPORTUNITY_VALUE,
							currency: props.CURRENCY_ID,
						},
						title: props.sumTitle,
						config: {
							largeFont: true,
							deepMergeStyles: {
								externalWrapper: {
									flexDirection: 'column',
									flex: 1,
								},
								value: {
									color: '#333333',
									opacity: 0.6,
								},
							},
						},
					}),
					Stage({
						backgroundColor: props.STAGE_COLOR,
						text: props.STAGE,
					})
				)
			),
		);
	}

	const Stage = ({ backgroundColor = null, text = null}) => {
		if (backgroundColor === null || text === null)
		{
			return null
		}

		return View(
			{
				style: {
					flexDirection: 'column',
				},
			},
			View(
				{
					style: {
						height: 28,
						width: 128,
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							width: 110,
							height: 28,
							borderRadius: 5,
							backgroundColor,
						},
					},
				),
				Image({
					style: {
						width: 25,
						height: 28,
						marginLeft: -10,
					},
					svg: {
						content: arrow(backgroundColor),
					},
				}),
			),
			View(
				{
					style: {
						marginTop: -28,
						height: 28,
						width: 128,
						justifyContent: 'center',
					},
				},
				Text({
					text,
					style: {
						marginLeft: 8,
						marginRight: 8,
						color: '#FFFFFF',
						fontSize: 14,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			),
		);
	}

	const renderDate = (date) => {
		const moment = Moment.createFromTimestamp(date);
		const defaultFormat = moment.inThisYear ? dayMonth() : longDate();

		return (new FriendlyDate({
			moment,
			defaultFormat,
			showTime: true,
			useTimeAgo: true,
			style: {
				color: '#828B95',
				fontSize: 13
			},
		}))
	};

	const renderSubtitleText = (text = null) => {
		if (!text)
		{
			return null;
		}

		return Text({
			text: `, ${text}`,
			style: {
				color: '#828B95',
				fontSize: 13
			},
		});
	};

	module.exports = { ItemCard };
});