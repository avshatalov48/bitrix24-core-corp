/**
 * @module calendar/layout/sharing-settings/dialog/range-edit
 */
jn.define('calendar/layout/sharing-settings/dialog/range-edit', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { SelectField, MultipleSelectField } = require('calendar/layout/fields');
	const { Loc } = require('loc');
	const { cross } = require('assets/common');
	const { isAmPmMode } = require('utils/date/formats');

	/**
	 * @class RangeEditComponent
	 */
	class RangeEditComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.rangeHeight = props.rangeHeight;
			this.sortedWeekdays = this.range.sortWeekdays([...Array(7).keys()]);
			this.weekdaysLoc = this.range.getWeekdaysLoc(true);

			this.onWeekdaysSelectedHandler = this.onWeekdaysSelectedHandler.bind(this);
			this.onTimeFromSelectedHandler = this.onTimeFromSelectedHandler.bind(this);
			this.onTimeToSelectedHandler = this.onTimeToSelectedHandler.bind(this);
			this.onCloseButtonClickHandler = this.onCloseButtonClickHandler.bind(this);
		}

		get rule()
		{
			return this.props.rule;
		}

		get range()
		{
			return this.props.range;
		}

		get isCrmContext()
		{
			return this.props.isCrmContext;
		}

		redraw()
		{
			this.setState({ time: Date.now() });
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (this.range.isNew)
			{
				this.rangeRef.animate({ height: this.rangeHeight, duration: 100 }, () => {
					this.range.isNew = false;
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						...styles.container,
						height: this.range.isNew ? 0 : 'auto',
					},
					onLayout: ({ height }) => {
						if (this.rangeHeight === 0)
						{
							this.props.onHeightCalculated(height);
							this.rangeHeight = height;
						}
					},
					ref: (ref) => {
						this.rangeRef = ref;
					},
				},
				this.renderWeekdaysSelect(),
				this.renderTime(),
				this.renderCloseButton(),
			);
		}

		renderWeekdaysSelect()
		{
			const items = this.sortedWeekdays.map((weekday) => ({
				value: weekday,
				name: this.weekdaysLoc[weekday],
			}));

			return View(
				{
					style: styles.weekdaysSelectContainer,
				},
				new MultipleSelectField({
					title: Loc.getMessage('M_CALENDAR_SETTINGS_SELECT_WEEKDAYS'),
					layoutWidget: this.props.layoutWidget,
					items,
					selected: this.range.getWeekDays(),
					onChange: this.onWeekdaysSelectedHandler,
					formatValue: () => this.range.getWeekdaysFormatted(),
					style: {
						field: this.isCrmContext ? styles.field : null,
						text: this.isCrmContext ? styles.fieldText : null,
						checkedBackground: this.isCrmContext ? null : AppTheme.colors.accentSoftBlue2,
						checkColor: this.isCrmContext ? AppTheme.colors.accentMainLinks : null
					},
				}),
			);
		}

		onWeekdaysSelectedHandler(value)
		{
			this.range.setWeekDays(value);
			this.redraw();
			this.props.onRuleUpdated();
		}

		renderTime()
		{
			return View(
				{
					style: styles.timeContainer,
				},
				this.renderTimeFromSelect(),
				this.renderDash(),
				this.renderTimeToSelect(),
			);
		}

		renderTimeFromSelect()
		{
			const items = this.range.getAvailableTimeFrom();

			return new SelectField({
				title: Loc.getMessage('M_CALENDAR_SETTINGS_FROM'),
				items,
				currentItem: {
					value: this.range.getFrom(),
					name: this.range.getFromFormatted(),
				},
				onChange: this.onTimeFromSelectedHandler,
				renderValue: () => this.renderTimeValue(this.range.getFromFormatted()),
				style: {
					field: this.isCrmContext ? styles.field : null,
				},
			});
		}

		onTimeFromSelectedHandler(value)
		{
			this.range.setFrom(parseInt(value, 10));
			this.redraw();
			this.props.onRuleUpdated();
		}

		renderTimeToSelect()
		{
			const items = this.range.getAvailableTimeTo();

			return new SelectField({
				title: Loc.getMessage('M_CALENDAR_SETTINGS_TO'),
				items,
				currentItem: {
					value: this.range.getTo(),
					name: this.range.getToFormatted(),
				},
				onChange: this.onTimeToSelectedHandler,
				renderValue: () => this.renderTimeValue(this.range.getToFormatted()),
				style: {
					field: this.isCrmContext ? styles.field : null,
				},
			});
		}

		onTimeToSelectedHandler(value)
		{
			this.range.setTo(parseInt(value, 10));
			this.redraw();
			this.props.onRuleUpdated();
		}

		renderTimeValue(value)
		{
			const amPm = (value.match(/(am|pm)/) ?? [])[0] ?? '';
			const timeWithoutAmPm = value.replace(/( am| pm)/, '');

			return View(
				{
					style: styles.timeValueContainer,
				},
				Text({
					text: timeWithoutAmPm,
					style: {
						...styles.timeValue,
						...(this.isCrmContext ? styles.fieldText : {}),
					},
				}),
				Text({
					text: amPm.toUpperCase(),
					style: {
						...styles.timeValueAmPm,
						...(this.isCrmContext ? styles.fieldText : {}),
					},
				}),
			);
		}

		renderDash()
		{
			return View({ style: styles.timeDash });
		}

		renderCloseButton()
		{
			return View(
				{
					style: {
						...styles.closeButton,
						opacity: this.rule.canRemoveRange() ? 1 : 0,
					},
					clickable: true,
					onClick: this.onCloseButtonClickHandler,
				},
				Image({
					tintColor: AppTheme.colors.base6,
					svg: {
						content: cross(),
					},
					style: styles.closeIcon,
				}),
			);
		}

		onCloseButtonClickHandler()
		{
			this.rule.removeRange(this.range);
			this.props.onRemove();
		}
	}

	const styles = {
		container: {
			flexDirection: 'row',
			alignItems: 'center',
			marginVertical: 9,
		},
		weekdaysSelectContainer: {
			flex: 1,
			marginRight: 10,
			marginLeft: 18,
		},
		timeContainer: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		timeValueContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'center',
			width: isAmPmMode() ? 53 : 48,
		},
		timeValue: {
			fontSize: 15,
			color: AppTheme.colors.accentMainLinks,
		},
		timeValueAmPm: {
			fontSize: 10,
			color: AppTheme.colors.accentMainLinks,
		},
		timeDash: {
			height: 1,
			width: 5,
			marginHorizontal: 5,
			backgroundColor: AppTheme.colors.base3,
		},
		closeButton: {
			height: '100%',
			justifyContent: 'center',
			paddingRight: 18,
		},
		closeIcon: {
			width: 24,
			height: 24,
			marginLeft: 10,
		},
		field: {
			borderColor: AppTheme.colors.base6,
			borderWidth: 1,
			borderRadius: 6,
			backgroundColor: undefined,
			paddingHorizontal: 10,
		},
		fieldText: {
			color: AppTheme.colors.base1,
		},
	};

	module.exports = { RangeEditComponent };
});
