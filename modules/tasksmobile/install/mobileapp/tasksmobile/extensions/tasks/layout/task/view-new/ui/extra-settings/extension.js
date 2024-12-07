/**
 * @module tasks/layout/task/view-new/ui/extra-settings
 */
jn.define('tasks/layout/task/view-new/ui/extra-settings', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent } = require('tokens');
	const { Card } = require('ui-system/layout/card');
	const { Icon } = require('ui-system/blocks/icon');
	const { isEqual } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { showToast } = require('toast');
	const { Box } = require('ui-system/layout/box');
	const { Loc } = require('tasks/loc');
	const { DatesResolver } = require('tasks/task/datesResolver');
	const { DatesResolver: DatePlanResolver } = require('tasks/layout/fields/date-plan/dates-resolver');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { connect } = require('statemanager/redux/connect');
	const { selectByTaskIdOrGuid, update } = require('tasks/statemanager/redux/slices/tasks');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { FeatureId } = require('tasks/enum');
	const { getFeatureRestriction } = require('tariff-plan-restriction');

	class ExtraSettings extends LayoutComponent
	{
		static open({ taskId, layoutWidget = PageManager })
		{
			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('M_TASKS_EXTRA_SETTINGS'),
					type: 'dialog',
				},
				component: (childWidget) => new ExtraSettings({ taskId, layoutWidget: childWidget }),
			})
				.setParentWidget(layoutWidget)
				.setMediumPositionPercent(70)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.open()
			;
		}

		render()
		{
			return Box(
				{
					withScroll: true,
					withPaddingHorizontal: true,
				},
				ExtraSettingsContent({
					taskId: this.props.taskId,
					parentWidget: this.props.layoutWidget,
					onChange: this.#onChangeOption,
				}),
			);
		}

		#onChangeOption = (id, enabled) => ((id === 'isMatchWorkTime')
			? this.#onChangeMatchWorkTimeOption(enabled)
			: this.#onChangeGenericOption(id, enabled));

		#onChangeMatchWorkTimeOption = (isMatchWorkTime) => {
			const task = selectByTaskIdOrGuid(store.getState(), this.props.taskId);
			const originalDates = {
				deadline: task.deadline ? task.deadline * 1000 : task.deadline,
				startDatePlan: task.startDatePlan ? task.startDatePlan * 1000 : task.startDatePlan,
				endDatePlan: task.endDatePlan ? task.endDatePlan * 1000 : task.endDatePlan,
			};

			const datesResolver = new DatesResolver({
				id: task.id,
				guid: task.guid ? String(task.guid) : String(task.id),
				isMatchWorkTime: task.isMatchWorkTime,
				...originalDates,
			});

			const datePlanResolver = new DatePlanResolver(
				originalDates.startDatePlan ? originalDates.startDatePlan / 1000 : null,
				originalDates.endDatePlan ? originalDates.endDatePlan / 1000 : null,
				task.isMatchWorkTime,
			);
			datePlanResolver.setIsMatchWorkTime(isMatchWorkTime);

			datesResolver.setIsMatchWorkTime(isMatchWorkTime);
			const actualDates = {
				startDatePlan: datePlanResolver.getStartDatePlan(),
				endDatePlan: datePlanResolver.getEndDatePlan(),
				deadline: datesResolver.getDeadlineTs(),
			};

			const reduxFields = {
				isMatchWorkTime,
				...actualDates,
			};

			const toServerDate = (ts) => (ts ? (new Date(ts * 1000)).toISOString() : '');

			const serverFields = {
				MATCH_WORK_TIME: isMatchWorkTime ? 'Y' : 'N',
				START_DATE_PLAN: toServerDate(actualDates.startDatePlan),
				END_DATE_PLAN: toServerDate(actualDates.endDatePlan),
				DEADLINE: toServerDate(actualDates.deadline),
			};

			dispatch(update({
				taskId: this.props.taskId,
				reduxFields,
				serverFields,
			}));

			if (!isEqual(originalDates, actualDates))
			{
				showToast({
					message: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_DEADLINE_AUTO_CHANGED'),
					position: 'bottom',
					icon: Icon.CLOCK,
				});
			}
		};

		#onChangeGenericOption = (id, enabled) => {
			const reduxFields = {
				[id]: enabled,
			};

			const serverFields = {};

			// eslint-disable-next-line default-case
			switch (id)
			{
				case 'allowChangeDeadline':
					serverFields.ALLOW_CHANGE_DEADLINE = (enabled ? 'Y' : 'N');
					break;

				case 'isResultRequired':
					serverFields.SE_PARAMETER = [{ CODE: 3, VALUE: (enabled ? 'Y' : 'N') }];
					break;

				case 'allowTaskControl':
					serverFields.TASK_CONTROL = (enabled ? 'Y' : 'N');
					break;
			}

			dispatch(
				update({
					taskId: this.props.taskId,
					reduxFields,
					serverFields,
				}),
			);
		};
	}

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId);
		const items = [
			{
				id: 'allowChangeDeadline',
				title: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_ALLOW_CHANGE_DEADLINE_TITLE'),
				subtitle: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_ALLOW_CHANGE_DEADLINE_SUBTITLE'),
				enabled: task?.allowChangeDeadline || false,
			},
			{
				id: 'isMatchWorkTime',
				title: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_IS_MATCH_WORK_TIME_TITLE'),
				subtitle: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_IS_MATCH_WORK_TIME_SUBTITLE'),
				enabled: task?.isMatchWorkTime || false,
				featureId: FeatureId.WORK_TIME_MATCH,
			},
			{
				id: 'isResultRequired',
				title: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_IS_RESULT_REQUIRED_TITLE'),
				subtitle: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_IS_RESULT_REQUIRED_SUBTITLE'),
				enabled: task?.isResultRequired || false,
				featureId: FeatureId.RESULT_REQUIREMENT,
			},
			{
				id: 'allowTaskControl',
				title: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_ALLOW_TASK_CONTROL_TITLE'),
				subtitle: Loc.getMessage('M_TASKS_EXTRA_SETTINGS_ALLOW_TASK_CONTROL_SUBTITLE'),
				enabled: task?.allowTaskControl || false,
				featureId: FeatureId.TASK_CONTROL,
			},
		];

		return { items };
	};

	const ExtraSettingsContent = connect(mapStateToProps)((props) => {
		const { items, parentWidget, onChange } = props;

		return View(
			{
				style: {
					paddingVertical: Indent.M.toNumber(),
				},
			},
			...items.map(({ id, title, subtitle, enabled, featureId }, index) => {
				const { isRestricted, showRestriction } = getFeatureRestriction(featureId);

				return Card(
					{
						style: {
							marginTop: (index === 0 ? 0 : Indent.XL.toNumber()),
						},
						testId: `${id}_setting_card`,
						border: true,
					},
					SettingSelector({
						title,
						subtitle,
						testId: `${id}_setting`,
						checked: enabled,
						locked: isRestricted(),
						onClick: useCallback((checked) => {
							if (isRestricted())
							{
								showRestriction({ parentWidget });
							}
							else
							{
								onChange?.(id, checked);
							}
						}, [id]),
					}),
				);
			}),
		);
	});

	module.exports = {
		ExtraSettings,
	};
});
