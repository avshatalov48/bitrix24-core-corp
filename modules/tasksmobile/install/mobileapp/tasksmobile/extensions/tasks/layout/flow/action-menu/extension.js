/**
 * @module tasks/layout/flow/action-menu
 */
jn.define('tasks/layout/flow/action-menu', (require, exports, module) => {
	const { UIMenu } = require('layout/ui/menu');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');
	const { showToast, Position } = require('toast');
	const { flowTogglePin, selectById } = require('tasks/statemanager/redux/slices/flows');
	const { dispatch, getState } = require('statemanager/redux/store');
	const { ajaxPublicErrorHandler } = require('error');

	const getMenuItems = ({ testId, flowId, isPinned }) => {
		return [
			!isPinned && {
				id: 'pin',
				testId: `${testId}-more-menu-item-pin`,
				title: Loc.getMessage('TASKSMOBILE_ACTION_MENU_ITEM_PIN'),
				iconName: Icon.PIN,
				onItemSelected: () => togglePin({ flowId, isPinned }),
			},
			isPinned && {
				id: 'unpin',
				testId: `${testId}-more-menu-item-unpin`,
				title: Loc.getMessage('TASKSMOBILE_ACTION_MENU_ITEM_UNPIN'),
				iconName: Icon.UNPIN,
				onItemSelected: () => togglePin({ flowId, isPinned }),
			},
		].filter(Boolean);
	};

	const getFlowFromRedux = (flowId) => {
		return selectById(getState(), flowId);
	};

	const togglePin = async ({ flowId, isPinned }) => {
		showToast({
			icon: isPinned ? Icon.UNPIN : Icon.PIN,
			message: isPinned
				? Loc.getMessage('TASKSMOBILE_ACTION_MENU_UNPINNED_TOAST_MESSAGE')
				: Loc.getMessage('TASKSMOBILE_ACTION_MENU_PINNED_TOAST_MESSAGE'),
			position: Position.BOTTOM,
		});
		const result = await dispatch(flowTogglePin({
			flowId,
		}));

		if (result.meta.rejectedWithValue)
		{
			await ajaxPublicErrorHandler(result.payload);
		}
	};

	/**
	 * @param props
	 * @param {string} props.testId
	 * @param {number} props.flowId
	 * @param {LayoutComponent} props.target
	 */
	const openActionMenu = (props) => {
		const { testId, target, flowId } = props;
		if (!flowId || !target)
		{
			return;
		}

		const flow = getFlowFromRedux(flowId);
		if (!flow)
		{
			return;
		}

		const { isPinned } = flow;
		new UIMenu(getMenuItems({
			testId,
			flowId,
			isPinned,
		}))
			.show({
				target,
			});
	};

	module.exports = { openActionMenu };
});
