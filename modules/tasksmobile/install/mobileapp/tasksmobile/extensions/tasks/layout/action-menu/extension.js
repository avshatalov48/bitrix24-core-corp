/**
 * @module tasks/layout/action-menu
 */
jn.define('tasks/layout/action-menu', (require, exports, module) => {
	const { WarnLogger } = require('utils/logger/warn-logger');

	const { BaseEngine } = require('tasks/layout/action-menu/engines/base');
	const { BottomMenuEngine } = require('tasks/layout/action-menu/engines/bottom-menu');
	const { TopMenuEngine } = require('tasks/layout/action-menu/engines/top-menu');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');

	const store = require('statemanager/redux/store');
	const { selectActions, selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');

	class ActionMenu
	{
		static get action()
		{
			return {
				read: ActionId.READ,
				remove: ActionId.REMOVE,
				startTimer: ActionId.START_TIMER,
				pauseTimer: ActionId.PAUSE_TIMER,
				start: ActionId.START,
				pause: ActionId.PAUSE,
				complete: ActionId.COMPLETE,
				renew: ActionId.RENEW,
				approve: ActionId.APPROVE,
				disapprove: ActionId.DISAPPROVE,
				defer: ActionId.DEFER,
				delegate: ActionId.DELEGATE,
				follow: ActionId.FOLLOW,
				unfollow: ActionId.UNFOLLOW,
				pin: ActionId.PIN,
				unpin: ActionId.UNPIN,
				mute: ActionId.MUTE,
				unmute: ActionId.UNMUTE,
				ping: ActionId.PING,
				share: ActionId.SHARE,
				copy: ActionId.COPY,
				copyId: ActionId.COPY_ID,
				extraSettings: ActionId.EXTRA_SETTINGS,
			};
		}

		constructor(options)
		{
			this.layoutWidget = options.layoutWidget;
			this.actions = options.actions.filter((action) => Object.values(ActionMenu.action).includes(action));
			this.task = options.task ?? selectByTaskIdOrGuid(store.getState(), options.taskId);
			this.analyticsLabel = options.analyticsLabel;
			this.shouldBackOnRemove = options.shouldBackOnRemove;
			this.allowSuccessToasts = Boolean(options.allowSuccessToasts);

			/** @type {BaseEngine} */
			this.menu = (options.engine && options.engine instanceof BaseEngine)
				? options.engine
				: new BottomMenuEngine({
					title: this.task.name,
					testId: 'taskViewActionMenu',
					analyticsLabel: this.analyticsLabel,
				});

			this.actionsMap = this.prepareActionsMap();
		}

		prepareActionsMap()
		{
			const actions = {};

			const actionsToCloseMenu = new Set([
				ActionId.COPY_ID,
				ActionId.REMOVE,
				ActionId.SHARE,
			]);

			Object.keys(ActionMeta).forEach((actionId) => {
				const { title, getData, handleAction } = ActionMeta[actionId];

				actions[actionId] = {
					...ActionMeta[actionId],
					title: title(this.task),
					data: getData(),
					onClickCallback: async () => {
						if (actionsToCloseMenu.has(actionId))
						{
							await new Promise((resolve) => {
								this.menu.close(resolve);
							});
						}

						await handleAction({
							task: this.task,
							analyticsLabel: {
								...this.analyticsLabel,
								c_element: 'context_menu',
							},
							layout: this.layoutWidget,
							options: { shouldBackOnRemove: this.shouldBackOnRemove },
						});
					},
				};
			});

			return actions;
		}

		show(options)
		{
			if (this.actions.length === 0)
			{
				(new WarnLogger()).warn('There is no actions to show!');
			}

			const has = Object.prototype.hasOwnProperty;
			const taskActions = selectActions(this.task);
			const actions = (
				this.actions
					.filter((action) => !has.call(taskActions, action) || taskActions[action])
					.map((action) => this.actionsMap[action])
			);

			this.menu.show(actions, options);
		}

		getTaskNameForTitle()
		{
			if (this.menu instanceof TopMenuEngine)
			{
				return this.task.name.length > 25 ? `${this.task.name.slice(0, 25).trim()}...` : this.task.name;
			}

			return this.task.name;
		}
	}

	module.exports = { ActionMenu, BottomMenuEngine, TopMenuEngine };
});
