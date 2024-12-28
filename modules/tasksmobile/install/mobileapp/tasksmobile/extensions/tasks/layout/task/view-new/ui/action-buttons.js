/**
 * @module tasks/layout/task/view-new/ui/action-buttons
 */
jn.define('tasks/layout/task/view-new/ui/action-buttons', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { Color, Indent } = require('tokens');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');
	const { ActionMenu, TopMenuEngine } = require('tasks/layout/action-menu');

	const { connect } = require('statemanager/redux/connect');
	const {
		selectByTaskIdOrGuid,
		selectActions,
		selectInProgress,
		selectIsDeferred,
		selectIsSupposedlyCompleted,
		selectIsAccomplice,
		selectIsResponsible,
		selectIsCompleted,
	} = require('tasks/statemanager/redux/slices/tasks');

	/**
	 * @typedef {object} ActionButtonsProps
	 * @property {string} taskId
	 * @property {boolean} showDivider
	 * @property {string} testId
	 * @property {object} layout
	 * @property {object} actions
	 * @property {boolean} inProgress
	 * @property {boolean} isDeferred
	 * @property {boolean} isSupposedlyCompleted
	 * @property {boolean} isCompleted
	 * @property {boolean} isResponsibleOrAccomplice
	 * @property {boolean} isOnlyResponsible
	 */

	/**
	 * @class ActionButtonsView
	 * @typedef {LayoutComponent<ActionButtonsProps>}
	 */
	class ActionButtonsView extends LayoutComponent
	{
		/**
		 * @param {TaskReduxModel} task
		 * @return {Partial<ActionButtonsProps>}
		 */
		static prepareActionProps(task)
		{
			const actions = selectActions(task);
			const inProgress = selectInProgress(task);
			const isDeferred = selectIsDeferred(task);
			const isSupposedlyCompleted = selectIsSupposedlyCompleted(task);
			const isCompleted = selectIsCompleted(task);
			const isOnlyResponsible = selectIsResponsible(task) && task.accomplices.length === 0;
			const isResponsibleOrAccomplice = selectIsResponsible(task) || selectIsAccomplice(task);

			return {
				actions,
				inProgress,
				isDeferred,
				isSupposedlyCompleted,
				isCompleted,
				isOnlyResponsible,
				isResponsibleOrAccomplice,
			};
		}

		/**
		 * @public
		 * @param {TaskReduxModel} task
		 * @return {boolean}
		 */
		static hasAllowedActions(task)
		{
			const actionProps = this.prepareActionProps(task);

			return this.getAllowedActions(actionProps).length > 0;
		}

		/**
		 * @public
		 * @param {ActionButtonsProps} actionProps
		 * @return {string[]}
		 */
		static getAllowedActions(actionProps)
		{
			const { actions } = actionProps;

			return (
				this
					.#getCompactActions(actionProps)
					.filter((actionId) => this.#shouldShowAction(actions, actionId))
			);
		}

		static #shouldShowAction(actions, actionId)
		{
			return Boolean(actionId) && actions[actionId] && ActionMeta[actionId];
		}

		/**
		 * @param {ActionButtonsProps} actionProps
		 * @returns {string[]}
		 */
		static #getCompactActions(actionProps)
		{
			const {
				actions: {
					take,
				},
				inProgress,
				isDeferred,
				isSupposedlyCompleted,
				isCompleted,
			} = actionProps;

			if (inProgress)
			{
				return this.#addPingAction(
					[
						ActionId.COMPLETE,
						ActionId.START_TIMER,
						ActionId.PAUSE,
						ActionId.PAUSE_TIMER,
						ActionId.DEFER,
					],
					actionProps,
				);
			}

			if (isDeferred)
			{
				return this.#addPingAction(
					[
						ActionId.RENEW,
						ActionId.COMPLETE,
					],
					actionProps,
				);
			}

			if (isSupposedlyCompleted)
			{
				return [
					ActionId.APPROVE,
					ActionId.DISAPPROVE,
					ActionId.RENEW,
				];
			}

			if (isCompleted)
			{
				return [
					ActionId.RENEW,
				];
			}

			if (take)
			{
				return [
					ActionId.TAKE,
				];
			}

			return this.#addPingAction(
				[
					ActionId.START,
					ActionId.START_TIMER,
					ActionId.PAUSE,
					ActionId.PAUSE_TIMER,
					ActionId.DEFER,
					ActionId.COMPLETE,
				],
				actionProps,
			);
		}

		static #addPingAction(compactActions, { actions, isDeferred, isOnlyResponsible, isResponsibleOrAccomplice })
		{
			if (isOnlyResponsible)
			{
				return compactActions;
			}

			const visibleActions = compactActions.filter((actionId) => this.#shouldShowAction(actions, actionId));
			if (visibleActions.length === 0)
			{
				return visibleActions;
			}

			if (isResponsibleOrAccomplice)
			{
				const [firstAction, ...restActions] = visibleActions;

				return [
					firstAction,
					ActionId.PING,
					...restActions,
				];
			}

			return [
				ActionId.PING,
				...visibleActions,
			];
		}

		constructor(props)
		{
			super(props);

			this.chipRootViewRef = null;
		}

		render()
		{
			const { showDivider } = this.props;

			const allowedActions = ActionButtonsView.getAllowedActions(this.props);
			if (allowedActions.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				this.renderFirstChipButton(allowedActions),
				this.renderSecondChipButton(allowedActions),
				showDivider && this.renderDivider(),
			);
		}

		renderFirstChipButton(allowedActions)
		{
			const firstActionId = allowedActions[0];

			return this.renderActionChipButton(firstActionId, true);
		}

		renderSecondChipButton(allowedActions)
		{
			const restActions = allowedActions.slice(1);
			if (restActions.length === 0)
			{
				return null;
			}

			if (restActions.length === 1)
			{
				return this.renderActionChipButton(restActions[0], false);
			}

			return this.renderMoreMenuChipButton(restActions);
		}

		renderActionChipButton(actionId, isFirst)
		{
			const { taskId, testId, layout, analyticsLabel: analyticsLabelParams = {} } = this.props;
			const { handleAction, title, getData } = ActionMeta[actionId];

			return ChipButton({
				testId: `${testId}_Action_${actionId}`,
				style: {
					marginRight: Indent.M.toNumber(),
				},
				compact: false,
				mode: ChipButtonMode.OUTLINE,
				design: this.getChipDesign(actionId, isFirst),
				text: this.getTitle(actionId, isFirst, title({ id: taskId })),
				icon: getData().outlineIconContent ?? '',
				onClick: () => {
					const analyticsLabel = {
						...analyticsLabelParams,
						c_element: `${actionId}_button`,
						c_sub_section: 'task_card',
					};

					handleAction({ taskId, layout, analyticsLabel });
				},
			});
		}

		getChipDesign(actionId, isFirst)
		{
			const { isSupposedlyCompleted, isCompleted } = this.props;

			if (actionId === ActionId.APPROVE)
			{
				return ChipButtonDesign.SUCCESS;
			}

			if (
				isFirst
				&& !isSupposedlyCompleted
				&& !isCompleted
				&& (
					actionId === ActionId.START
					|| actionId === ActionId.COMPLETE
					|| actionId === ActionId.RENEW
					|| actionId === ActionId.TAKE
				)
			)
			{
				return ChipButtonDesign.PRIMARY;
			}

			return ChipButtonDesign.GREY;
		}

		getTitle(actionId, isFirst, title)
		{
			if (isFirst)
			{
				if (actionId === ActionId.START || actionId === ActionId.START_TIMER)
				{
					return Loc.getMessage('M_TASK_DETAILS_START_BUTTON');
				}

				if (actionId === ActionId.APPROVE)
				{
					return Loc.getMessage('M_TASK_DETAILS_APPROVE_BUTTON');
				}

				return title;
			}

			return null;
		}

		renderMoreMenuChipButton(actions)
		{
			const { testId } = this.props;

			return ChipButton({
				testId: `${testId}_Action_More_Menu`,
				compact: false,
				mode: ChipButtonMode.OUTLINE,
				design: ChipButtonDesign.GREY,
				icon: Icon.MORE,
				style: {
					marginRight: Indent.M.toNumber(),
				},
				forwardRef: (ref) => {
					this.chipRootViewRef = ref;
				},
				onClick: () => {
					this.showMenuActions(actions);
				},
			});
		}

		showMenuActions(actions)
		{
			const { taskId, layout, analyticsLabel = {} } = this.props;

			(new ActionMenu({
				actions,
				taskId,
				layoutWidget: layout,
				engine: new TopMenuEngine(),
				allowSuccessToasts: true,
				analyticsLabel,
			}))
				.show({ target: this.chipRootViewRef });
		}

		renderDivider()
		{
			return View({
				style: {
					alignSelf: 'center',
					marginHorizontal: Indent.XS.toNumber(),
					height: 18,
					width: 1,
					backgroundColor: Color.bgSeparatorPrimary.toHex(),
				},
			});
		}
	}

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId);

		return ActionButtonsView.prepareActionProps(task);
	};

	module.exports = {
		ActionButtons: connect(mapStateToProps)(ActionButtonsView),
		ActionButtonsView,
	};
});
