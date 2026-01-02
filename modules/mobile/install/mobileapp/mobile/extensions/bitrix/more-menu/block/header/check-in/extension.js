/**
 * @module more-menu/block/header/check-in
 */
jn.define('more-menu/block/header/check-in', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { inAppUrl } = require('in-app-url');

	const { Text3, Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { PropTypes } = require('utils/validation');
	const { Moment } = require('utils/date/moment');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { FormatterTypes } = require('layout/ui/friendly-date/formatter-factory');
	const { shortTime } = require('utils/date/formats');
	const { capitalize } = require('utils/string');
	const { createTestIdGenerator } = require('utils/test');

	let StatusEnum = null;

	try
	{
		StatusEnum = require('stafftrack/model/shift').StatusEnum;
	}
	catch (error)
	{
		console.warn(error);

		return;
	}

	/**
	 * @class CheckIn
	 */
	class CheckIn extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {string} props.testId
		 * @param {object} props.currentShift
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				currentShift: props.currentShift,
			};

			this.subscribeToPullEvent = this.subscribeToPullEvent.bind(this);
			this.timeUpdateInterval = null;

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});
		}

		componentDidMount()
		{
			BX.addCustomEvent('onPullEvent-stafftrack', this.subscribeToPullEvent);
			this.startTimeUpdateInterval();
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('onPullEvent-stafftrack', this.subscribeToPullEvent);
			this.stopTimeUpdateInterval();
		}

		startTimeUpdateInterval()
		{
			this.timeUpdateInterval = setInterval(() => {
				this.setState({});
			}, 60000);
		}

		stopTimeUpdateInterval()
		{
			if (this.timeUpdateInterval)
			{
				clearInterval(this.timeUpdateInterval);
				this.timeUpdateInterval = null;
			}
		}

		/**
		 * @param {string} command
		 * @param params
		 * @param extra
		 * @param {string} moduleId
		 */
		subscribeToPullEvent(command, params, extra, moduleId)
		{
			const shift = params?.shift;
			if (shift.userId === Number(env.userId))
			{
				this.setState({
					currentShift: shift,
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						padding: Indent.XL2.toNumber(),
						alignItems: 'center',
					},
				},
				View(
					{
						testId: this.getTestId('content'),
						style: {
							flex: 2,
							marginRight: Indent.L.toNumber(),
							flexShrink: 2,
						},
						onClick: () => {
							inAppUrl.open('/check-in', {});
						},
					},
					this.renderHeader(),
					this.renderStatus(),
				),
				this.renderActions(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginBottom: Indent.S.toNumber(),
						alignItems: 'center',
					},
				},
				Text3({
					testId: this.getTestId('title'),
					text: Loc.getMessage('MORE_MENU_HEADER_CHECK_IN_TITLE'),
					color: Color.base1,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				IconView({
					testId: this.getTestId('chevron'),
					size: 20,
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Color.base1,
					resizeMode: 'cover',
					style: {
						marginTop: 2,
						width: 10,
						height: 16,
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		}

		renderStatus()
		{
			const { currentShift } = this.state;

			const status = currentShift?.status || StatusEnum.CLOSED;

			if (
				!currentShift
				|| status === StatusEnum.NOT_WORKING.getValue()
			)
			{
				return Text4({
					testId: this.getTestId('closed'),
					text: Loc.getMessage('MORE_MENU_HEADER_CHECK_IN_CLOSED'),
					color: Color.base3,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						flexShrink: 2,
					},
				});
			}

			if (status === StatusEnum.CANCEL_WORKING.toNumber())
			{
				const dateCancel = currentShift?.dateCancel || '';

				return Text4({
					testId: this.getTestId('cancel-time'),
					text: capitalize(this.getCancelTime(new Date(dateCancel))),
					color: Color.base3,
					numberOfLines: 1,
					ellipsize: 'middle',
					style: {
						flexShrink: 2,
					},
				});
			}

			const dateCreate = currentShift?.dateCreate || '';

			return new FriendlyDate({
				moment: Moment.createFromTimestamp(new Date(dateCreate) / 1000),
				defaultTimeFormat: (moment) => moment.format(shortTime()),
				formatType: FormatterTypes.HUMAN_DATE,
				renderContent: ({ state }) => Text4({
					testId: this.getTestId('create-time'),
					text: state.text,
					color: Color.accentMainPrimary,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						flexShrink: 2,
					},
				}),
			});
		}

		renderActions()
		{
			const { currentShift } = this.state;

			if (currentShift)
			{
				return Button({
					testId: 'MORE_MENU_HEADER_CHECK_IN_BUTTON',
					text: Loc.getMessage('MORE_MENU_HEADER_CHECK_IN_BUTTON_DEFAULT'),
					size: ButtonSize.M,
					design: ButtonDesign.OUTLINE,
					onClick: () => {
						inAppUrl.open('/check-in', {});
					},
					backgroundColor: Color.bgContentPrimary,
				});
			}

			return Button({
				testId: 'MORE_MENU_HEADER_CHECK_IN_BUTTON_START',
				text: Loc.getMessage('MORE_MENU_HEADER_CHECK_IN_BUTTON_START'),
				leftIcon: Icon.LOCATION,
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE,
				onClick: () => {
					inAppUrl.open('/check-in', {});
				},
				backgroundColor: Color.bgContentPrimary,
			});
		}

		getCancelTime(time)
		{
			const moment = Moment.createFromTimestamp(time / 1000);
			const timeString = moment.format(shortTime());

			return Loc.getMessage(
				'MORE_MENU_HEADER_CHECK_IN_CANCEL',
				{
					'#TIME#': timeString,
				},
			);
		}
	}

	CheckIn.propTypes = {
		testId: PropTypes.string,
		currentShift: PropTypes.object,
	};

	module.exports = {
		CheckIn,
	};
});
