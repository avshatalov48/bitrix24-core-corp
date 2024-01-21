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
		}

		get rule()
		{
			return this.props.rule;
		}

		get range()
		{
			return this.props.range;
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
						flexDirection: 'row',
						alignItems: 'center',
						marginVertical: 9,
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
			const isFull = true;
			const weekdaysLoc = this.range.getWeekdaysLoc(isFull);

			const items = this.range.sortWeekdays([1, 2, 3, 4, 5, 6, 0]).map((weekday) => {
				return {
					value: weekday,
					name: weekdaysLoc[weekday],
				};
			});

			return View(
				{
					style: {
						flex: 1,
						marginRight: 10,
						marginLeft: 18,
					},
				},
				new MultipleSelectField({
					title: Loc.getMessage('M_CALENDAR_SETTINGS_SELECT_WEEKDAYS'),
					layoutWidget: this.props.layoutWidget,
					items,
					selected: this.range.getWeekDays(),
					onChange: (value) => this.onWeekdaysSelectedHandler(value),
					formatValue: () => this.range.getWeekdaysFormatted(),
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
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
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
				onChange: (value) => this.onTimeFromSelectedHandler(value),
				renderValue: () => this.renderTimeValue(this.range.getFromFormatted()),
			});
		}

		onTimeFromSelectedHandler(value)
		{
			this.range.setFrom(value);
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
				onChange: (value) => this.onTimeToSelectedHandler(value),
				renderValue: () => this.renderTimeValue(this.range.getToFormatted()),
			});
		}

		onTimeToSelectedHandler(value)
		{
			this.range.setTo(value);
			this.redraw();
			this.props.onRuleUpdated();
		}

		renderTimeValue(value)
		{
			const amPm = (value.match(/(am|pm)/) ?? [])[0] ?? '';
			const timeWithoutAmPm = value.replace(/( am| pm)/, '');

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						width: isAmPmMode() ? 53 : 48,
					},
				},
				Text(
					{
						style: {
							fontSize: 15,
							color: AppTheme.colors.accentMainLinks,
						},
						text: timeWithoutAmPm,
					},
				),
				Text(
					{
						style: {
							fontSize: 10,
							color: AppTheme.colors.accentMainLinks,
						},
						text: amPm.toUpperCase(),
					},
				),
			);
		}

		renderDash()
		{
			return View(
				{
					style: {
						height: 1,
						width: 5,
						marginHorizontal: 5,
						backgroundColor: AppTheme.colors.base3,
					},
				},
			);
		}

		renderCloseButton()
		{
			return View(
				{
					style: {
						opacity: this.rule.canRemoveRange() ? 1 : 0,
						height: '100%',
						justifyContent: 'center',
						paddingRight: 18,
					},
					clickable: true,
					onClick: this.onCloseButtonClickHandler.bind(this),
				},
				Image({
					tintColor: AppTheme.colors.base6,
					svg: {
						content: cross(),
					},
					style: {
						width: 24,
						height: 24,
						marginLeft: 10,
					},
				}),
			);
		}

		onCloseButtonClickHandler()
		{
			this.rule.removeRange(this.range);
			this.props.onRemove();
		}
	}

	module.exports = { RangeEditComponent };
});
