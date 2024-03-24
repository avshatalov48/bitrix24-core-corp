/**
 * @module tasks/layout/action-menu
 */
jn.define('tasks/layout/action-menu', (require, exports, module) => {
	const { Alert } = require('alert');
	const { downloadImages } = require('asset-manager');
	const { Feature } = require('feature');
	const { Loc } = require('loc');
	const { executeIfOnline } = require('tasks/layout/online');
	const { TaskCreate } = require('tasks/layout/task/create');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { WarnLogger } = require('utils/logger/warn-logger');
	const store = require('statemanager/redux/store');
	const { showToast, showRemoveToast } = require('toast');
	const { Haptics } = require('haptics');
	const { dispatch } = store;
	const {
		selectById,
		selectActions,
		startTimer,
		pauseTimer,
		start,
		pause,
		complete,
		renew,
		approve,
		disapprove,
		ping,
		unfollow,
		remove,
		pin,
		unpin,
		mute,
		unmute,
		read,
		markAsRemoved,
		unmarkAsRemoved,
	} = require('tasks/statemanager/redux/slices/tasks');

	class ActionMenu
	{
		static get action()
		{
			return {
				startTimer: 'startTimer',
				pauseTimer: 'pauseTimer',
				start: 'start',
				pause: 'pause',
				complete: 'complete',
				renew: 'renew',
				approve: 'approve',
				disapprove: 'disapprove',
				unfollow: 'unfollow',
				remove: 'remove',
				pin: 'pin',
				unpin: 'unpin',
				mute: 'mute',
				unmute: 'unmute',
				addTask: 'addTask',
				addSubTask: 'addSubTask',
				share: 'share',
				read: 'read',
				ping: 'ping',
			};
		}

		static get section()
		{
			return {
				taskProgress: 'taskProgress',
				personalActions: 'personalActions',
				commonActions: 'commonActions',
			};
		}

		static prefetchAssets()
		{
			const menuIcons = Object.values(actionIconMap).filter((icon) => icon !== null);
			const toastIcons = Object.values(toastIconMap).filter((icon) => icon !== null);

			void downloadImages([
				...menuIcons,
				...toastIcons,
			]);
		}

		constructor(options)
		{
			this.layoutWidget = options.layoutWidget;
			this.actions = options.actions.filter((action) => Object.values(ActionMenu.action).includes(action));
			this.diskFolderId = options.diskFolderId;
			this.deadlines = options.deadlines;
			this.task = selectById(store.getState(), options.taskId);
			this.analyticsLabel = options.analyticsLabel;
		}

		show()
		{
			if (this.actions.length === 0)
			{
				(new WarnLogger()).warn('There is no actions to show!');
			}

			const taskActions = selectActions(this.task);
			const actions = this.actions
				.filter((action) => taskActions[action])
				.map((action) => this.prepareAction(action));

			this.menu = new ContextMenu({
				params: {
					showCancelButton: true,
					isRawIcon: true,
					title: this.task.name,
				},
				testId: 'taskViewActionMenu',
				actions,
				analyticsLabel: this.analyticsLabel,
			});
			void this.menu.show();
		}

		checkOnline(callback)
		{
			return () => executeIfOnline(callback, this.layoutWidget);
		}

		prepareAction(action)
		{
			const actions = {
				[ActionMenu.action.startTimer]: {
					id: ActionMenu.action.startTimer,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_START_TIMER'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.startTimer],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							startTimer({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.pauseTimer]: {
					id: ActionMenu.action.pauseTimer,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PAUSE_TIMER'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.pauseTimer],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							pauseTimer({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.start]: {
					id: ActionMenu.action.start,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_START'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.start],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							start({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.pause]: {
					id: ActionMenu.action.pause,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PAUSE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.pause],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							pause({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.complete]: {
					id: ActionMenu.action.complete,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_COMPLETE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.complete],
					},
					onClickCallback: this.checkOnline(() => {
						if (this.task.isResultRequired && !this.task.isOpenResultExists)
						{
							Haptics.notifyWarning();
							showToast(
								{
									code: ActionMenu.action.complete,
									message: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_COMPLETE_RESULT_REQUIRED'),
								},
								this.layoutWidget,
							);
						}
						else
						{
							dispatch(
								complete({
									taskId: this.task.id,
								}),
							);
						}
					}),
				},
				[ActionMenu.action.renew]: {
					id: ActionMenu.action.renew,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_RENEW'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.renew],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							renew({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.approve]: {
					id: ActionMenu.action.approve,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_APPROVE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.approve],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							approve({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.disapprove]: {
					id: ActionMenu.action.disapprove,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_DISAPPROVE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.disapprove],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							disapprove({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.unfollow]: {
					id: ActionMenu.action.unfollow,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_UNFOLLOW'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.unfollow],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							unfollow({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.remove]: {
					id: ActionMenu.action.remove,
					sectionCode: ActionMenu.section.commonActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_REMOVE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.remove],
					},
					isDestructive: true,
					onClickCallback: this.checkOnline(() => {
						const closePromise = () => new Promise((resolve) => {
							this.menu.close(() => resolve());
						});

						this.removeTask(closePromise);

						return Promise.resolve({ closeMenu: false });
					}),
				},
				[ActionMenu.action.pin]: {
					id: ActionMenu.action.pin,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PIN'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.pin],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							pin({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.unpin]: {
					id: ActionMenu.action.unpin,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_UNPIN'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.unpin],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							unpin({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.mute]: {
					id: ActionMenu.action.mute,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_MUTE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.mute],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							mute({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.unmute]: {
					id: ActionMenu.action.unmute,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_UNMUTE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.unmute],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							unmute({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.addTask]: {
					id: ActionMenu.action.addTask,
					sectionCode: ActionMenu.section.commonActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_START_TIMER'),
					onClickCallback: this.checkOnline(() => {
						TaskCreate.open({
							layoutWidget: this.layoutWidget,
							currentUser: this.task.currentUser,
							diskFolderId: this.diskFolderId,
							deadlines: this.deadlines,
						});
					}),
				},
				[ActionMenu.action.addSubTask]: {
					id: ActionMenu.action.addSubTask,
					sectionCode: ActionMenu.section.commonActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_START_TIMER'),
					onClickCallback: this.checkOnline(() => {
						TaskCreate.open({
							layoutWidget: this.layoutWidget,
							currentUser: this.task.currentUser,
							diskFolderId: this.diskFolderId,
							deadlines: this.deadlines,
							initialTaskData: {
								parentId: this.task.id,
								parentTask: {
									id: this.task.id,
									title: this.task.name,
								},
							},
						});
					}),
				},
				[ActionMenu.action.share]: {
					id: ActionMenu.action.share,
					sectionCode: ActionMenu.section.commonActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_SHARE'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.share],
					},
					onClickCallback: () => {
						this.menu.close(() => {
							dialogs.showSharingDialog({
								message: `${currentDomain}/company/personal/user/${env.userId}/tasks/task/view/${this.task.id}/`,
							});
						});
					},
				},
				[ActionMenu.action.read]: {
					id: ActionMenu.action.read,
					sectionCode: ActionMenu.section.personalActions,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_READ'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.read],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							read({
								taskId: this.task.id,
							}),
						);
					}),
				},
				[ActionMenu.action.ping]: {
					id: ActionMenu.action.ping,
					sectionCode: ActionMenu.section.taskProgress,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PING'),
					data: {
						svgUri: actionIconMap[ActionMenu.action.ping],
					},
					onClickCallback: this.checkOnline(() => {
						dispatch(
							ping({
								taskId: this.task.id,
							}),
						);
						if (Feature.isToastSupported())
						{
							showToast(
								{
									code: ActionMenu.action.ping,
									message: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PING_NOTIFICATION'),
									svg: {
										url: toastIconMap[ActionMenu.action.ping],
									},
								},
								this.layoutWidget,
							);
						}
						else
						{
							// eslint-disable-next-line no-undef
							Notify.showMessage('', Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_PING_NOTIFICATION'));
						}
					}),
				},
			};

			return actions[action];
		}

		removeTask(closePromise)
		{
			const showToastNotification = () => {
				if (Feature.isToastSupported())
				{
					dispatch(
						markAsRemoved({
							taskId: this.task.id,
						}),
					);

					showRemoveToast(
						{
							message: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_REMOVE_MESSAGE'),
							onButtonTap: () => {
								dispatch(
									unmarkAsRemoved({
										taskId: this.task.id,
									}),
								);
							},
							onTimerOver: () => {
								dispatch(
									remove({
										taskId: this.task.id,
									}),
								);
							},
						},
						this.layoutWidget,
					);
				}
				else
				{
					dispatch(
						remove({
							taskId: this.task.id,
						}),
					);
				}
			};

			Alert.confirm(
				'',
				Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_REMOVE_CONFIRM'),
				[
					{ type: 'cancel' },
					{
						type: 'destructive',
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_ACTION_MENU_REMOVE_CONFIRM_YES'),
						onPress: () => {
							closePromise()
								.then(() => showToastNotification())
								.catch(console.error);
						},
					},
				],
			);
		}
	}

	const pathToIcons = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/action-menu/images';
	const iconPrefix = `${currentDomain}${pathToIcons}/tasksmobile-layout-action-menu-`;
	const actionIconMap = {
		[ActionMenu.action.startTimer]: `${iconPrefix}start.svg`,
		[ActionMenu.action.pauseTimer]: `${iconPrefix}pause.svg`,
		[ActionMenu.action.start]: `${iconPrefix}start.svg`,
		[ActionMenu.action.pause]: `${iconPrefix}pause.svg`,
		[ActionMenu.action.complete]: `${iconPrefix}complete.svg`,
		[ActionMenu.action.renew]: `${iconPrefix}renew.svg`,
		[ActionMenu.action.approve]: `${iconPrefix}approve.svg`,
		[ActionMenu.action.disapprove]: `${iconPrefix}disapprove.svg`,
		[ActionMenu.action.unfollow]: `${iconPrefix}unfollow.svg`,
		[ActionMenu.action.remove]: `${iconPrefix}remove.svg`,
		[ActionMenu.action.pin]: `${iconPrefix}pin.svg`,
		[ActionMenu.action.unpin]: `${iconPrefix}unpin.svg`,
		[ActionMenu.action.mute]: `${iconPrefix}mute.svg`,
		[ActionMenu.action.unmute]: `${iconPrefix}unmute.svg`,
		[ActionMenu.action.addTask]: null,
		[ActionMenu.action.addSubTask]: null,
		[ActionMenu.action.share]: `${iconPrefix}share.svg`,
		[ActionMenu.action.read]: `${iconPrefix}read.svg`,
		[ActionMenu.action.ping]: `${iconPrefix}ping.svg`,
	};

	const toastPrefix = `${currentDomain}${pathToIcons}/toast-`;
	const toastIconMap = {
		[ActionMenu.action.ping]: `${toastPrefix}ping.svg`,
	};

	// prefetch assets with timeout to not affect core queries
	setTimeout(() => ActionMenu.prefetchAssets(), 1000);

	module.exports = { ActionMenu };
});
