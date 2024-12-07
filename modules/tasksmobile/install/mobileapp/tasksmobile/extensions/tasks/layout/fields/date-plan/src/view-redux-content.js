/**
 * @module tasks/layout/fields/date-plan/view-redux-content
 */
jn.define('tasks/layout/fields/date-plan/view-redux-content', (require, exports, module) => {
	const { Loc } = require('loc');
	const { connect } = require('statemanager/redux/connect');
	const { selectDatePlan, selectExtraSettings } = require('tasks/statemanager/redux/slices/tasks/selector');
	const { Area } = require('ui-system/layout/area');
	const { Text2, Text4 } = require('ui-system/typography/text');
	const { Color, Indent, Component } = require('tokens');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { NumberField } = require('layout/ui/fields/number');
	const { DatesResolver } = require('tasks/layout/fields/date-plan/dates-resolver');
	const { getFormattedDateTime, getFormattedDate } = require('tasks/layout/fields/date-plan/formatter');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { DateTimeInput, InputSize, InputMode, InputDesign, DatePickerType } = require('ui-system/form/inputs/datetime');
	const { UIMenu } = require('layout/ui/menu');
	const { Type } = require('type');
	const { showToast } = require('toast/base');

	class DatePlanViewReduxContent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initializeDatesResolver();
			this.height = null;

			this.state = {
				startDatePlan: this.datesResolver.getStartDatePlan(),
				endDatePlan: this.datesResolver.getEndDatePlan(),
				duration: this.datesResolver.getDuration(),
				durationType: this.datesResolver.durationType,
				durationValue: this.datesResolver.durationValue,
			};
		}

		get parentWidget()
		{
			return this.props.parentWidget;
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);
			if (
				props.startDatePlan !== this.props.startDatePlan
				|| props.endDatePlan !== this.props.endDatePlan
			)
			{
				this.initializeDatesResolver();
				this.updateDatePlan();
			}
		}

		initializeDatesResolver()
		{
			const { startDatePlan, endDatePlan, isMatchWorkTime } = this.props;
			this.datesResolver = new DatesResolver(startDatePlan, endDatePlan, isMatchWorkTime, this.onFixDate);
		}

		componentDidMount()
		{
			if (this.props.onHidden)
			{
				this.parentWidget.on('onViewHidden', () => this.props?.onHidden());
			}

			Keyboard.on(Keyboard.Event.Hidden, () => {
				if (this.height)
				{
					this.parentWidget.setBottomSheetHeight(this.height + 50);
				}
			});
		}

		render()
		{
			return View(
				{
					onClick: () => {
						Keyboard.dismiss();
					},
					onLayout: ({ height }) => {
						this.height = height;
					},
				},
				Area(
					{
						isFirst: true,
					},
					Text4({
						text: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_SUBTITLE'),
						color: Color.base3,
					}),
					this.renderStartDatePlan(),
					this.renderEndDatePlan(),
					this.renderDuration(),
				),
				this.renderSaveButton(),
			);
		}

		renderStartDatePlan()
		{
			const error = !this.isDateInGroupRange(this.state.startDatePlan);
			const errorMessage = Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_DATE_OUT_OF_RANGE', {
				'#DATE#': getFormattedDate(this.props.groupPlan.dateStart),
			});

			return View(
				{
					style: {
						marginTop: Indent.XL4.toNumber(),
					},
				},
				DateTimeInput(
					{
						label: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_START_DATE_PLAN_INPUT_LABEL'),
						value: this.state.startDatePlan,
						testId: 'start-date-plan-input',
						size: InputSize.L,
						mode: InputMode.STROKE,
						design: InputDesign.GREY,
						rightStickContent: Icon.CALENDAR_WITH_SLOTS,
						erase: true,
						onErase: () => this.onChangeStartDatePlan(null),
						enableTime: true,
						parentWidget: this.parentWidget,
						datePickerType: DatePickerType.DATETIME,
						dateFormatter: getFormattedDateTime,
						defaultListTitle: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_START_DATE_PLAN_INPUT_LIST_TITLE'),
						checkTimezoneOffset: false,
						copyingOnLongClick: true,
						onChange: this.onChangeStartDatePlan,
						error,
						errorText: error ? errorMessage : null,
					},
				),
			);
		}

		renderEndDatePlan()
		{
			const error = !this.isDateInGroupRange(this.state.endDatePlan);
			const errorMessage = Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_DATE_OUT_OF_RANGE', {
				'#DATE#': getFormattedDate(this.props.groupPlan.dateFinish),
			});

			return View(
				{
					style: {
						marginTop: Indent.XL3.toNumber(),
					},
				},
				DateTimeInput(
					{
						label: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_END_DATE_PLAN_INPUT_LABEL'),
						value: this.state.endDatePlan,
						testId: 'end-date-plan-input',
						size: InputSize.L,
						mode: InputMode.STROKE,
						design: InputDesign.GREY,
						rightStickContent: Icon.CALENDAR_WITH_SLOTS,
						erase: true,
						onErase: () => this.onChangeEndDatePlan(null),
						enableTime: true,
						parentWidget: this.parentWidget,
						datePickerType: DatePickerType.DATETIME,
						dateFormatter: getFormattedDateTime,
						defaultListTitle: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_END_DATE_PLAN_INPUT_LIST_TITLE'),
						checkTimezoneOffset: false,
						copyingOnLongClick: true,
						onChange: this.onChangeEndDatePlan,
						error,
						errorText: error ? errorMessage : null,
					},
				),
			);
		}

		renderDuration()
		{
			return Card(
				{
					testId: 'duration-card',
					hideCross: true,
					border: false,
					selected: false,
					design: CardDesign.SECONDARY,
					style: {
						marginTop: Indent.XL3.toNumber(),
					},

				},
				Text4(
					{
						text: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_DURATION_TITLE'),
					},
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							alignContent: 'center',
						},
					},
					View(
						{ style: { flex: 1 } },
						this.renderDurationValueField(),
					),
					this.renderVerticalDivider(),
					View(
						{
							style: {
								flex: 1,
								justifyContent: 'center',
							},
						},
						this.renderDurationTypeField(),
					),
				),
			);
		}

		renderDurationValueField()
		{
			return NumberField(
				{
					testId: 'duration-value-field',
					required: false,
					value: this.state.durationValue || 0,
					forcedValue: this.state.durationValue || 0,
					shouldShowToolbar: false,
					onChange: this.onChangeDurationValue,
					title: '',
					placeholder: '0',
					config: {
						parentWidget: this.parentWidget,
						deepMergeStyles: {
							editableValue: {
								color: Color.base1.toHex(),
								fontSize: 16,
							},
						},
					},
				},
			);
		}

		renderDurationTypeField()
		{
			return View(
				{
					onClick: this.openDurationTypeMenu,
					ref: (ref) => {
						this.durationMenuTargetRef = ref;
					},
					testId: 'duration-type-field',
					style: {
						width: '100%',
						flexDirection: 'row',
						flexGrow: 1,
						justifyContent: 'space-between',
					},
				},
				Text2(
					{
						text: DatesResolver.durationTypeNamePlural(this.state.durationValue)[this.state.durationType],
						color: Color.base4,
					},
				),
				IconView(
					{
						icon: Icon.CHEVRON_DOWN,
						color: Color.base2,
						size: 20,
					},
				),
			);
		}

		renderVerticalDivider()
		{
			return View(
				{
					style: {
						height: 14,
						width: 1,
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
						marginHorizontal: Indent.XL2.toNumber(),
					},
				},
			);
		}

		renderSaveButton()
		{
			const disabled = (
				!this.isDateInGroupRange(this.state.startDatePlan)
				|| !this.isDateInGroupRange(this.state.endDatePlan)
				|| (!this.state.startDatePlan && !this.state.endDatePlan)
			);

			return View(
				{
					style: {
						marginVertical: Indent.XL.toNumber(),
						paddingHorizontal: Component.paddingLrMore.toNumber(),
					},
				},
				Button({
					text: Loc.getMessage('M_TASKS_DATE_PLAN_SAVE'),
					testId: 'date-plan-save-button',
					size: ButtonSize.L,
					design: ButtonDesign.FILLED,
					stretched: true,
					onClick: this.onSave,
					onDisabledClick: this.onSave,
					disabled,
				}),
			);
		}

		openDurationTypeMenu = () => {
			const menuItems = Object.values(DatesResolver.durationType).map((id) => this.createDurationMenuItem(id));
			this.menu = new UIMenu(menuItems);
			this.menu.show({ target: this.durationMenuTargetRef });
		};

		createDurationMenuItem(id)
		{
			return {
				id,
				testId: `duration-${id}`,
				title: DatesResolver.durationTypeNamePlural(this.state.durationValue)[id],
				onItemSelected: (event, item) => this.onChangeDurationType(item.id),
				sectionCode: 'default',
			};
		}

		onSave = () => {
			const { onSave, parentWidget } = this.props;

			if (!onSave)
			{
				return;
			}

			if (!this.isDateInGroupRange(this.state.startDatePlan))
			{
				showToast({
					message: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_START_DATE_OUT_OF_RANGE'),
					icon: Icon.CLOCK,
				}, parentWidget);

				return;
			}

			if (!this.isDateInGroupRange(this.state.endDatePlan))
			{
				showToast({
					message: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_END_DATE_OUT_OF_RANGE'),
					icon: Icon.CLOCK,
				}, parentWidget);

				return;
			}

			this.props.onSave(this.state.startDatePlan, this.state.endDatePlan);
			this.parentWidget.close();
		};

		isDateInGroupRange(date)
		{
			if (!Type.isNumber(date))
			{
				return true;
			}

			const { dateStart = null, dateFinish = null } = this.props.groupPlan;

			let dateAfterFinish = (new Date(dateFinish * 1000));
			dateAfterFinish.setDate(dateAfterFinish.getDate() + 1);
			dateAfterFinish.setHours(0, 0, 0, 0);
			dateAfterFinish = Math.floor(dateAfterFinish / 1000);

			return (
				(Type.isNil(dateStart) || date >= dateStart)
				&& (Type.isNil(dateFinish) || date < dateAfterFinish)
			);
		}

		onChangeDurationType = (durationType) => {
			this.datesResolver.updateDurationType(durationType);
			this.updateDatePlan();
		};

		onChangeDurationValue = (durationValue) => {
			this.datesResolver.updateDurationValue(durationValue);
			this.updateDatePlan();
		};

		onChangeStartDatePlan = (startDatePlan) => {
			this.datesResolver.updateStartDatePlan(startDatePlan);
			this.updateDatePlan();
		};

		onChangeEndDatePlan = (newEndDatePlan) => {
			this.datesResolver.updateEndDatePlan(newEndDatePlan);
			this.updateDatePlan();
		};

		updateDatePlan()
		{
			this.setState({
				startDatePlan: this.datesResolver.getStartDatePlan(),
				endDatePlan: this.datesResolver.getEndDatePlan(),
				duration: this.datesResolver.getDuration(),
				durationType: this.datesResolver.durationType,
				durationValue: this.datesResolver.durationValue,
			});
		}

		onFixDate = () => {
			const clearToast = () => {
				this.workTimeChangeToast = null;
			};

			if (!this.workTimeChangeToast)
			{
				this.workTimeChangeToast = showToast({
					message: Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_DATE_CHANGED_FROM_WORK_TIME'),
					icon: Icon.CLOCK,
					onTimerOver: clearToast,
					onTap: clearToast,
					position: 'top',
				});
			}
		};
	}

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.taskId;

		const {
			startDatePlan,
			endDatePlan,
		} = selectDatePlan(state, taskId);

		const { isMatchWorkTime } = selectExtraSettings(state, taskId);

		return {
			id: taskId,
			startDatePlan: startDatePlan || ownProps.startDatePlan,
			endDatePlan: endDatePlan || ownProps.endDatePlan,
			isMatchWorkTime: isMatchWorkTime || ownProps.isMatchWorkTime,
		};
	};

	module.exports = {
		DatePlanViewContent: connect(mapStateToProps)(DatePlanViewReduxContent),
	};
});
