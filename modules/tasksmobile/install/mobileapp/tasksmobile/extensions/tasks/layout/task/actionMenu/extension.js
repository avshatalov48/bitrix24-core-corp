/**
 * @module tasks/layout/task/actionMenu
 */
jn.define('tasks/layout/task/actionMenu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { TaskCreate } = require('tasks/layout/task/create');
	const { CreateNew } = require('tasks/layout/task/create-new');
	const { confirmDestructiveAction } = require('alert');
	const { ActionMenuButton } = require('tasks/layout/task/actionMenu/src/button');
	const { Notify } = require('notify');
	const { Feature } = require('feature');

	const TaskCreationComponent = Feature.isAirStyleSupported() ? CreateNew : TaskCreate;

	class ActionMenu
	{
		static get action()
		{
			return {
				addTask: 'addTask',
				addSubTask: 'addSubTask',
				addToFavorite: 'favorite.add',
				removeFromFavorite: 'favorite.delete',
				startTimer: 'startTimer',
				pauseTimer: 'pauseTimer',
				start: 'start',
				pause: 'pause',
				complete: 'complete',
				renew: 'renew',
				approve: 'approve',
				disapprove: 'disapprove',
				delegate: 'delegate',
				share: 'share',
				remove: 'remove',
				pin: 'pin',
				unpin: 'unpin',
			};
		}

		constructor(options)
		{
			this.layoutWidget = options.layoutWidget;
			this.possibleActions = (options.possibleActions || null);
			this.diskFolderId = options.diskFolderId;
			this.deadlines = options.deadlines;
			this.isTaskLimitExceeded = options.isTaskLimitExceeded;
			this.task = options.task;

			this.eventEmitter = EventEmitter.createWithUid(this.task.id);
		}

		show()
		{
			const preparedActions = this.actions
				.filter((action) => this.task.actions[action.id])
				.filter((action) => !this.possibleActions || this.possibleActions.includes(action.id))
				// eslint-disable-next-line max-len
				.filter((action) => action.id !== ActionMenu.action.complete || !this.task.actions[ActionMenu.action.approve])
				.map((action) => ({
					...action,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => {
							action.onClickCallback();
							resolve({ closeMenu: false });
						});
					}),
				}));

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: true,
					isRawIcon: true,
					title: this.task.name,
				},
				actions: preparedActions,
				testId: 'taskViewActionMenu',
			});

			void contextMenu.show();
		}

		get actions()
		{
			const pathToImages = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/task/actionMenu/images';
			const imagePrefix = `${currentDomain}${pathToImages}/tasksmobile-layout-task-actionMenu-`;

			return [
				{
					id: ActionMenu.action.addTask,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_ADD_TASK'),
					data: {
						imgUri: `${imagePrefix}addTask.png`,
					},
					onClickCallback: () => {
						TaskCreationComponent.open({
							layoutWidget: this.layoutWidget,
							currentUser: this.task.currentUser,
							diskFolderId: this.diskFolderId,
							deadlines: this.deadlines,
							closeAfterSave: true,
						});
					},
				},
				{
					id: ActionMenu.action.addSubTask,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_ADD_SUB_TASK'),
					data: {
						imgUri: `${imagePrefix}addTask.png`,
					},
					onClickCallback: () => {
						TaskCreationComponent.open({
							layoutWidget: this.layoutWidget,
							currentUser: this.task.currentUser,
							diskFolderId: this.diskFolderId,
							deadlines: this.deadlines,
							closeAfterSave: true,
							initialTaskData: {
								parentId: this.task.id,
								parentTask: {
									id: this.task.id,
									title: this.task.title,
								},
							},
						});
					},
				},
				{
					id: ActionMenu.action.addToFavorite,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_ADD_TO_FAVORITE'),
					data: {
						imgUri: `${imagePrefix}favoriteAdd.png`,
					},
					onClickCallback: () => {
						void this.task.addToFavorite();
					},
				},
				{
					id: ActionMenu.action.removeFromFavorite,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_DELETE_FROM_FAVORITE'),
					data: {
						imgUri: `${imagePrefix}favoriteDelete.png`,
					},
					onClickCallback: () => {
						void this.task.removeFromFavorite();
					},
				},
				{
					id: ActionMenu.action.startTimer,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_START'),
					data: {
						svgUri: `${imagePrefix}start.svg`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canStartTimer: false,
							canPauseTimer: true,
							canStart: false,
							canPause: false,
							canRenew: false,
						});
						void this.task.startTimer();
						this.eventEmitter.emit('tasks.task.actionMenu:startTimer');
					},
				},
				{
					id: ActionMenu.action.pauseTimer,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_PAUSE'),
					data: {
						imgUri: `${imagePrefix}pause.png`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canStartTimer: true,
							canPauseTimer: false,
							canStart: false,
							canPause: false,
							canRenew: false,
						});
						void this.task.pauseTimer();
						this.eventEmitter.emit('tasks.task.actionMenu:pauseTimer');
					},
				},
				{
					id: ActionMenu.action.start,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_START'),
					data: {
						svgUri: `${imagePrefix}start.svg`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canStartTimer: false,
							canPauseTimer: false,
							canStart: false,
							canPause: true,
							canRenew: false,
						});
						void this.task.start();
						this.eventEmitter.emit('tasks.task.actionMenu:start');
					},
				},
				{
					id: ActionMenu.action.pause,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_PAUSE'),
					data: {
						imgUri: `${imagePrefix}pause.png`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canStartTimer: false,
							canPauseTimer: false,
							canStart: true,
							canPause: false,
							canRenew: false,
						});
						void this.task.pause();
						this.eventEmitter.emit('tasks.task.actionMenu:pause');
					},
				},
				{
					id: ActionMenu.action.complete,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_COMPLETE'),
					data: {
						svgUri: `${imagePrefix}complete.svg`,
					},
					onClickCallback: () => {
						void this.task.complete();
						if (!this.task.isResultRequired || this.task.isOpenResultExists)
						{
							this.eventEmitter.emit('tasks.task.actionMenu:complete');
						}
					},
				},
				{
					id: ActionMenu.action.renew,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_RENEW'),
					data: {
						imgUri: `${imagePrefix}renew.png`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canStart: true,
							canPause: false,
							canRenew: false,
						});
						void this.task.renew();
						this.eventEmitter.emit('tasks.task.actionMenu:renew');
					},
				},
				{
					id: ActionMenu.action.approve,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_APPROVE_MSGVER_1'),
					data: {
						imgUri: `${imagePrefix}approve.png`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canApprove: false,
							canDisapprove: false,
							canRenew: true,
							canComplete: false,
						});
						void this.task.approve();
						this.eventEmitter.emit('tasks.task.actionMenu:approve');
					},
				},
				{
					id: ActionMenu.action.disapprove,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_DISAPPROVE_MSGVER_2'),
					data: {
						imgUri: `${imagePrefix}disapprove.png`,
					},
					onClickCallback: () => {
						this.task.updateActions({
							canApprove: false,
							canDisapprove: false,
							canRenew: false,
							canComplete: false,
							canStart: true,
						});
						void this.task.disapprove();
						this.eventEmitter.emit('tasks.task.actionMenu:disapprove');
					},
				},
				{
					id: ActionMenu.action.delegate,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_DELEGATE'),
					data: {
						imgUri: `${imagePrefix}delegate.png`,
					},
					isDisabled: this.isTaskLimitExceeded,
					onClickCallback: () => {
						const selector = EntitySelectorFactory.createByType('user', {
							provider: {
								context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
							},
							initSelectedIds: [this.task.responsible.id],
							allowMultipleSelection: false,
							events: {
								onClose: (users) => {
									const newResponsible = users[0];
									const oldResponsible = this.task.responsible;
									if (Number(newResponsible.id) !== Number(oldResponsible.id))
									{
										this.task.updateData({
											responsible: {
												id: newResponsible.id,
												name: newResponsible.title,
												icon: (newResponsible.defaultImage ? '' : newResponsible.imageUrl),
											},
										});
										this.eventEmitter.emit('tasks.task.actionMenu:delegate');
										this.task.delegate()
											.then(
												() => {},
												() => {
													this.task.updateData({ responsible: oldResponsible });
													this.eventEmitter.emit('tasks.task.actionMenu:delegate');
												},
											)
											.catch(() => {
												this.task.updateData({ responsible: oldResponsible });
												this.eventEmitter.emit('tasks.task.actionMenu:delegate');
											})
										;
									}
								},
							},
							widgetParams: {
								title: Loc.getMessage(
									'TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_DELEGATE_MENU_TITLE_MSGVER_1',
								),
								backdrop: {
									mediumPositionPercent: 70,
								},
							},
						});
						void selector.show({}, this.layoutWidget);
					},
				},
				{
					id: ActionMenu.action.pin,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_PIN'),
					data: {
						svgUri: `${imagePrefix}pin.svg`,
					},
					onClickCallback: () => {
						void this.task.pin();
					},
				},
				{
					id: ActionMenu.action.unpin,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_UNPIN'),
					data: {
						svgUri: `${imagePrefix}unpin.svg`,
					},
					onClickCallback: () => {
						void this.task.unpin();
					},
				},
				{
					id: ActionMenu.action.share,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_SHARE'),
					data: {
						svgUri: `${imagePrefix}share.svg`,
					},
					onClickCallback: () => {
						dialogs.showSharingDialog({
							message: `${currentDomain}/company/personal/user/${this.task.currentUser.id}/tasks/task/view/${this.task.id}/`,
						});
					},
				},
				{
					id: ActionMenu.action.remove,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_REMOVE'),
					data: {
						svgUri: `${imagePrefix}remove.svg`,
					},
					isDestructive: true,
					onClickCallback: () => {
						confirmDestructiveAction({
							title: '',
							description: Loc.getMessage(
								'TASKSMOBILE_LAYOUT_TASK_ACTION_MENU_ACTION_REMOVE_CONFIRM_TITLE_MSGVER_1',
							),
							onDestruct: () => new Promise((resolve) => {
								setTimeout(() => Notify.showIndicatorLoading(), 500);
								resolve({ closeMenu: false });

								this.task.remove()
									.then(
										(response) => {
											if (response.result.task === true)
											{
												this.eventEmitter.emit('tasks.task.actionMenu:remove');
											}
										},
										() => Notify.hideCurrentIndicator(),
									)
									.catch(() => Notify.hideCurrentIndicator())
								;
							}),
						});
					},
				},
			];
		}
	}

	module.exports = { ActionMenu, ActionMenuButton };
});
