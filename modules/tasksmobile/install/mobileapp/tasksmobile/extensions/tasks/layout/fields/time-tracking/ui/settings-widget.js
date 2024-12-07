/**
 * @module tasks/layout/fields/time-tracking/ui/settings-widget
 */
jn.define('tasks/layout/fields/time-tracking/ui/settings-widget', (require, exports, module) => {
	const { Loc } = require('tasks/loc');
	const { Card } = require('ui-system/layout/card');
	const { Switcher, SwitcherSize } = require('ui-system/blocks/switcher');
	const { H4 } = require('ui-system/typography/heading');
	const { Text5 } = require('ui-system/typography/text');
	const { StringInput, InputDesign } = require('ui-system/form/inputs/string');
	const { Component, Indent, Color } = require('tokens');
	const { TimeTrackingSettingsWidgetSaveButton } = require('tasks/layout/fields/time-tracking/ui/save-button');
	const { toHours, toMinutes, sumSeconds } = require('tasks/layout/fields/time-tracking/time-utils');

	const toNumber = (val) => {
		const num = Number(val);

		return Number.isNaN(num) ? 0 : num;
	};

	class TimeTrackingSettingsWidget extends LayoutComponent
	{
		static open(props = {})
		{
			const parentWidget = (props.parentWidget || PageManager);

			parentWidget.openWidget('layout', {
				titleParams: {
					text: Loc.getMessage('M_TASKS_FIELDS_TIME_TRACKING'),
					type: 'dialog',
				},
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionHeight: 450,
					bounceEnable: true,
					swipeAllowed: true,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					adoptHeightByKeyboard: true,
				},
			}).then((layoutWidget) => {
				layoutWidget.showComponent(new TimeTrackingSettingsWidget({
					...props,
					layoutWidget,
					parentWidget,
				}));
			}).catch(() => {});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				allowTimeTracking: Boolean(props.allowTimeTracking),
				useTimeLimit: Boolean(props.timeEstimate),
				timeEstimateHours: toHours(props.timeEstimate),
				timeEstimateMinutes: toMinutes(props.timeEstimate),
				hasChanges: false,
			};

			this.onHoursChanged = this.onHoursChanged.bind(this);
			this.onMinutesChanged = this.onMinutesChanged.bind(this);

			/** @type {TimeTrackingSettingsWidgetSaveButton} */
			this.saveButtonRef = null;

			/** @type {StringInput} */
			this.hoursRef = null;
		}

		#toggleTimeTracking()
		{
			const allowTimeTracking = !this.state.allowTimeTracking;
			const useTimeLimit = allowTimeTracking === false ? false : this.state.useTimeLimit;

			this.setState({
				allowTimeTracking,
				useTimeLimit,
				hasChanges: true,
			});
		}

		#toggleTimeLimit()
		{
			const useTimeLimit = !this.state.useTimeLimit;
			const allowTimeTracking = useTimeLimit === true ? true : this.state.allowTimeTracking;

			this.setState({
				allowTimeTracking,
				useTimeLimit,
				hasChanges: true,
			}, () => {
				if (useTimeLimit)
				{
					void this.hoursRef?.setFocused(true);
				}
			});
		}

		onHoursChanged(val)
		{
			this.setState({
				timeEstimateHours: toNumber(val),
				hasChanges: true,
			});
		}

		onMinutesChanged(val)
		{
			this.setState({
				timeEstimateMinutes: toNumber(val),
				hasChanges: true,
			});
		}

		#save()
		{
			const { allowTimeTracking, useTimeLimit, timeEstimateHours, timeEstimateMinutes } = this.state;

			const timeEstimate = sumSeconds(timeEstimateHours, timeEstimateMinutes);

			this.props.onChange?.({
				allowTimeTracking,
				timeEstimate: useTimeLimit ? timeEstimate : 0,
			});

			this.props.layoutWidget?.close(() => {
				this.props.onClose?.();
			});
		}

		render()
		{
			return View(
				{
					style: {
						paddingHorizontal: Component.areaPaddingLr.getValue(),
						paddingVertical: Component.cardListPaddingTb.getValue(),
					},
					safeArea: { bottom: true },
					resizableByKeyboard: true,
					onClick: () => Keyboard.dismiss(),
				},
				this.#renderTimeTrackingOption(),
				this.#renderTimeLimitOption(),
				this.#renderSaveButton(),
			);
		}

		#renderTimeTrackingOption()
		{
			return Option({
				testId: 'TimeTrackingSettingsWidget_EnableTimeTracking',
				onClick: () => this.#toggleTimeTracking(),
				title: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_ENABLE_TIME_TRACKING'),
				subtitle: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_ENABLE_TIME_TRACKING_HINT'),
				checked: this.state.allowTimeTracking,
			});
		}

		#renderTimeLimitOption()
		{
			return Option(
				{
					testId: 'TimeTrackingSettingsWidget_SetTimeLimit',
					onClick: () => this.#toggleTimeLimit(),
					title: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_ENABLE_TIME_LIMIT'),
					subtitle: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_ENABLE_TIME_LIMIT_HINT'),
					checked: this.state.useTimeLimit,
				},
				this.state.useTimeLimit && this.#renderTimeEditForm(),
			);
		}

		#renderTimeEditForm()
		{
			const { timeEstimateHours: hours, timeEstimateMinutes: minutes } = this.state;

			return Form(
				Field({
					ref: (ref) => {
						this.hoursRef = ref;
					},
					testId: 'TimeTrackingSettingsWidget_Hours',
					keyboardType: 'number-pad',
					value: hours === 0 ? '' : String(hours),
					placeholder: '0',
					label: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_HOURS'),
					onChange: this.onHoursChanged,
					design: InputDesign.GREY,
				}),
				Spacer(),
				Field({
					testId: 'TimeTrackingSettingsWidget_Minutes',
					keyboardType: 'number-pad',
					value: minutes === 0 ? '' : String(minutes),
					placeholder: '0',
					label: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_MINUTES'),
					onChange: this.onMinutesChanged,
					design: InputDesign.GREY,
				}),
			);
		}

		#renderSaveButton()
		{
			return new TimeTrackingSettingsWidgetSaveButton({
				ref: (ref) => {
					this.saveButtonRef = ref;
				},
				testId: 'TimeTrackingSettingsWidget_SaveBtn',
				disabled: !this.state.hasChanges,
				text: Loc.getMessage('M_TASKS_SAVE'),
				onClick: () => this.#save(),
			});
		}
	}

	const Option = (props = {}, ...children) => {
		const {
			title,
			subtitle,
			onClick,
			testId,
			checked = false,
		} = props;

		return Card(
			{
				onClick,
				testId: `${testId}_Container`,
				border: true,
				style: {
					marginBottom: Component.cardListGap.getValue(),
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginBottom: Indent.XS2.getValue(),
					},
				},
				H4({
					text: title,
					color: Color.base1,
				}),
				Switcher({
					checked,
					onClick,
					testId: `${testId}_Switcher`,
					size: SwitcherSize.L,
					useState: false,
				}),
			),
			Text5({
				text: subtitle,
				color: Color.base3,
			}),
			...children,
		);
	};

	const Form = (...children) => View({
		style: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			marginTop: Indent.XL.getValue(),
		},
	}, ...children);

	const Field = (props) => View(
		{
			style: {
				flexGrow: 1,
			},
		},
		StringInput(props),
	);

	const Spacer = () => View({ style: { width: Indent.XL.getValue() } });

	module.exports = { TimeTrackingSettingsWidget };
});
