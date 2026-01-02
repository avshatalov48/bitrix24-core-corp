/**
 * @module calendar/event-edit-form/pages/custom-time-page
 */
jn.define('calendar/event-edit-form/pages/custom-time-page', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Text4 } = require('ui-system/typography/text');
	const { DatePickerType, DateTimeInput, InputDesign, InputSize } = require('ui-system/form/inputs/datetime');
	const { Button, ButtonSize, ButtonDesign, Icon } = require('ui-system/form/buttons/button');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { Card } = require('ui-system/layout/card');

	const { DateHelper } = require('calendar/date-helper');
	const { State, observeState } = require('calendar/event-edit-form/state');

	/**
	 * @class CustomTimePage
	 */
	class CustomTimePage extends LayoutComponent
	{
		get layoutWidget()
		{
			return this.props.layoutWidget;
		}

		get isFullDay()
		{
			return this.props.fullDay;
		}

		get selectedDate()
		{
			return this.props.selectedDate;
		}

		get customDateFrom()
		{
			return this.props.customDateFrom;
		}

		get customDateTo()
		{
			return this.props.customDateTo;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return Area(
				{
					isFirst: true,
				},
				this.renderTitle(),
				this.renderDateFromInput(),
				this.renderDateToInput(),
				this.renderFullDaySwitcher(),
			);
		}

		renderTitle()
		{
			return Text4({
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_CUSTOM_TITLE'),
				color: Color.base1,
			});
		}

		renderDateFromInput()
		{
			return this.renderDateInput({
				testId: 'calendar-event-edit-form-custom-time-from-input',
				value: this.getDateFrom(),
				label: this.getDateFromTitle(),
				onChange: this.selectDateFrom,
				defaultListTitle: this.getDateFromTitle(),
			});
		}

		renderDateToInput()
		{
			return this.renderDateInput({
				testId: 'calendar-event-edit-form-custom-time-to-input',
				value: this.getDateTo(),
				label: this.getDateToTitle(),
				onChange: this.selectDateTo,
				defaultListTitle: this.getDateToTitle(),
			});
		}

		renderDateInput({ value, testId, label, defaultListTitle, onChange })
		{
			return DateTimeInput({
				testId,
				value,
				label,
				onChange,
				defaultListTitle,
				emptyEditableValue: ' ',
				size: InputSize.L,
				design: InputDesign.GREY,
				enableTime: true,
				dateFormat: this.isFullDay ? DateHelper.getDateFormat(value) : DateHelper.getDatetimeFormat(value),
				datePickerType: this.isFullDay ? DatePickerType.DATE : DatePickerType.DATETIME,
				rightContent: Icon.CLOCK,
				style: {
					marginTop: Indent.XL2.toNumber(),
				},
			});
		}

		renderFullDaySwitcher()
		{
			return Card(
				{
					testId: 'calendar-event-edit-form-custom-full-day',
					style: {
						marginTop: Indent.XL2.toNumber(),
						borderColor: Color.base5.toHex(),
						borderWidth: 1,
					},
				},
				SettingSelector({
					testId: 'calendar-event-edit-form-custom-full-day-switcher',
					title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_FULL_DAY'),
					checked: this.isFullDay,
					onClick: this.setFullDay,
				}),
			);
		}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SAVE'),
					stretched: true,
					onClick: this.onButtonClick,
					testId: 'calendar-event-edit-form-custom-time-btn',
				}),
			);
		}

		getDateFromTitle()
		{
			return this.isFullDay
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATE_FROM')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATETIME_FROM')
			;
		}

		getDateToTitle()
		{
			return this.isFullDay
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATE_TO')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATETIME_TO')
			;
		}

		getDateFrom()
		{
			if (this.customDateFrom > 0)
			{
				return this.customDateFrom / 1000;
			}

			return this.getDefaultCurrentTimestamp();
		}

		getDateTo()
		{
			if (this.customDateTo > 0)
			{
				return this.customDateTo / 1000;
			}

			return this.getDefaultCurrentTimestamp(3600);
		}

		getDefaultCurrentTimestamp(additionalTime = 0)
		{
			const date = new Date();
			const round = 10 * 60 * 1000;
			date.setFullYear(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), this.selectedDate.getDate());

			return ((Math.ceil(date.getTime() / round) * round) / 1000) + additionalTime;
		}

		selectDateFrom = (timestamp) => {
			State.setCustomDateFrom(timestamp * 1000);
		};

		selectDateTo = (timestamp) => {
			if (this.customDateFrom === 0)
			{
				State.setCustomDateFrom(this.getDateFrom() * 1000);
			}

			State.setCustomDateTo(timestamp * 1000);
		};

		setFullDay = (checked) => {
			State.setFullDay(checked);
		};

		onButtonClick = () => {
			if (this.customDateFrom === 0)
			{
				State.setCustomDateFrom(this.getDefaultCurrentTimestamp() * 1000);
			}

			State.setIsCustomSelected(true);
			this.layoutWidget?.close();
		};

		static open(parentLayout)
		{
			const component = (layoutWidget) => new this({
				layoutWidget,
			});

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.enableOnlyMediumPosition()
				.disableSwipe()
				.setMediumPositionPercent(60)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_CUSTOM_SLOT'),
					type: 'wizard',
				})
				.open()
			;
		}
	}

	const mapStateToProps = (state, props) => ({
		selectedDate: state.selectedDate,
		customDateFrom: state.customDateFrom,
		customDateTo: state.customDateTo,
		hasCustomPeriod: state.hasCustomPeriod,
		eventLength: state.eventLength,
		fullDay: state.fullDay,
	});

	module.exports = { CustomTimePage: observeState(CustomTimePage, mapStateToProps) };
});
