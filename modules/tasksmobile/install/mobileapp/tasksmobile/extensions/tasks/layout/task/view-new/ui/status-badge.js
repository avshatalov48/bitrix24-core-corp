/**
 * @module tasks/layout/task/view-new/ui/status-badge
 */
jn.define('tasks/layout/task/view-new/ui/status-badge', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const {
		selectByTaskIdOrGuid,
		selectIsExpired,
		selectIsDeferred,
		selectIsSupposedlyCompleted,
		selectIsCompleted,
	} = require('tasks/statemanager/redux/slices/tasks/selector');

	const { Loc } = require('tasks/loc');
	const { Indent } = require('tokens');
	const { ChipStatus, ChipStatusDesign, ChipStatusMode } = require('ui-system/blocks/chips/chip-status');

	const StatusBadge = (props) => {
		const { testId } = props;

		const statusInfo = getStatusInfo(props);
		const { text = '', design, mode, testId: badgeTestId } = statusInfo || {};

		return View(
			{
				style: {
					alignSelf: 'center',
					display: statusInfo ? 'flex' : 'none',
					flexShrink: 1,
					marginLeft: Indent.S.toNumber(),
				},
				onClick: () => Keyboard.dismiss(),
			},
			ChipStatus({
				testId: `${testId}_Status_${badgeTestId}`,
				text,
				design,
				mode,
			}),
		);
	};

	const getStatusInfo = ({
		isDeferred,
		isSupposedlyCompleted,
		isCompleted,
		isExpired,
		isResultRequired,
		isOpenResultExists,
	}) => {
		if (isDeferred)
		{
			return {
				text: Loc.getMessage('M_TASKS_STATUS_DEFERRED'),
				testId: 'Deferred',
				design: ChipStatusDesign.NEUTRAL,
				mode: ChipStatusMode.TINTED,
			};
		}

		if (isSupposedlyCompleted)
		{
			return {
				text: Loc.getMessage('M_TASKS_STATUS_SUPPOSEDLY_COMPLETED'),
				testId: 'SupposedlyCompleted',
				design: ChipStatusDesign.NEUTRAL,
				mode: ChipStatusMode.TINTED,
			};
		}

		if (isCompleted)
		{
			return {
				text: Loc.getMessage('M_TASKS_STATUS_COMPLETED'),
				testId: 'Completed',
				design: ChipStatusDesign.SUCCESS,
				mode: ChipStatusMode.TINTED,
			};
		}

		if (isExpired)
		{
			return {
				text: Loc.getMessage('M_TASKS_STATUS_EXPIRED'),
				testId: 'Expired',
				design: ChipStatusDesign.ALERT,
				mode: ChipStatusMode.TINTED,
			};
		}

		if (isResultRequired && !isOpenResultExists)
		{
			return {
				text: Loc.getMessage('M_TASKS_STATUS_NEED_RESULT'),
				testId: 'NeedResult',
				design: ChipStatusDesign.PRIMARY,
				mode: ChipStatusMode.TINTED,
			};
		}

		return null;
	};

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId);
		const isDeferred = selectIsDeferred(task);
		const isSupposedlyCompleted = selectIsSupposedlyCompleted(task);
		const isCompleted = selectIsCompleted(task);
		const isExpired = selectIsExpired(task);
		const { isResultRequired, isOpenResultExists } = task;

		return {
			isDeferred,
			isSupposedlyCompleted,
			isCompleted,
			isExpired,
			isResultRequired,
			isOpenResultExists,
		};
	};

	module.exports = {
		StatusBadge: connect(mapStateToProps)(StatusBadge),
	};
});
