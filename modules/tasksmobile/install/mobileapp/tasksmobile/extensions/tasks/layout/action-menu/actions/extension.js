/**
 * @module tasks/layout/action-menu/actions
 */
jn.define('tasks/layout/action-menu/actions', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const { Icon } = require('ui-system/blocks/icon');
	const { downloadImages } = require('asset-manager');
	const { Haptics } = require('haptics');
	const { Feature } = require('feature');
	const { Loc } = require('tasks/loc');
	const { Dod } = require('tasks/layout/dod');

	const { showSafeToast } = require('toast');
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');
	const { ActionMenuError } = require('tasks/layout/action-menu/actions/src/error');
	const { ExtraSettings } = require('tasks/layout/task/view-new/ui/extra-settings');
	const { FeatureId } = require('tasks/enum');
	const { Alert } = require('alert');
	const { AnalyticsEvent } = require('analytics');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { usersSelector } = require('statemanager/redux/slices/users');

	const { executeIfOnline } = require('tasks/layout/online');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { getFeatureRestriction } = require('tariff-plan-restriction');
	const { truncate } = require('utils/string');
	const { throttle } = require('utils/function');
	const throttledShowSafeToast = throttle(showSafeToast, 4000);

	const {
		startTimer,
		pauseTimer,
		start,
		take,
		pause,
		complete,
		renew,
		defer,
		delegate,
		approve,
		disapprove,
		ping,
		follow,
		unfollow,
		pin,
		unpin,
		mute,
		unmute,
		read,
		selectByTaskIdOrGuid,
		selectIsCreator,
		tasksUpserted,
	} = require('tasks/statemanager/redux/slices/tasks');

	const ActionId = {
		READ: 'read',
		REMOVE: 'remove',
		START_TIMER: 'startTimer',
		PAUSE_TIMER: 'pauseTimer',
		START: 'start',
		TAKE: 'take',
		PAUSE: 'pause',
		COMPLETE: 'complete',
		RENEW: 'renew',
		APPROVE: 'approve',
		DISAPPROVE: 'disapprove',
		DEFER: 'defer',
		DELEGATE: 'delegate',
		FOLLOW: 'follow',
		UNFOLLOW: 'unfollow',
		PIN: 'pin',
		UNPIN: 'unpin',
		MUTE: 'mute',
		UNMUTE: 'unmute',
		PING: 'ping',
		SHARE: 'share',
		COPY: 'copy',
		COPY_ID: 'copyId',
		EXTRA_SETTINGS: 'extraSettings',
	};

	const Section = {
		TASK_PROGRESS: 'taskProgress',
		PERSONAL_ACTIONS: 'personalActions',
		COMMON_ACTIONS: 'commonActions',
		ADDITIONAL_ACTIONS: 'additionalActions',
		REMOVE_ACTIONS: 'removeActions',
	};

	const actionsBySectionsMap = {
		[Section.TASK_PROGRESS]: [
			ActionId.START_TIMER,
			ActionId.PAUSE_TIMER,
			ActionId.START,
			ActionId.PAUSE,
			ActionId.COMPLETE,
			ActionId.RENEW,
			ActionId.DEFER,
			ActionId.APPROVE,
			ActionId.DISAPPROVE,
			ActionId.DELEGATE,
			ActionId.PING,
		],
		[Section.PERSONAL_ACTIONS]: [
			ActionId.FOLLOW,
			ActionId.UNFOLLOW,
			ActionId.PIN,
			ActionId.UNPIN,
			ActionId.MUTE,
			ActionId.UNMUTE,
			ActionId.READ,
		],
		[Section.COMMON_ACTIONS]: [
			ActionId.COPY_ID,
			ActionId.COPY,
			ActionId.SHARE,
		],
		[Section.REMOVE_ACTIONS]: [
			ActionId.REMOVE,
		],
		[Section.ADDITIONAL_ACTIONS]: [
			ActionId.EXTRA_SETTINGS,
		],
	};

	const ActionMeta = {
		[ActionId.COPY_ID]: {
			id: ActionId.COPY_ID,
			title: (task) => Loc.getMessage('M_TASKS_ACTIONS_MENU_COPY_ID_MSGVER_1', {
				'#TASK_ID#': task.id,
			}),
			successToastPhrase: (task) => {
				if (Feature.hasCopyToClipboardAutoNotification())
				{
					return null;
				}

				return Loc.getMessage('M_TASKS_ACTIONS_MENU_COPY_ID_SUCCESS_MSGVER_1', {
					'#TASK_ID#': task.id,
				});
			},
			handleAction: (task) => {
				Application.copyToClipboard(String(task.id));
			},
		},
		[ActionId.START_TIMER]: {
			id: ActionId.START_TIMER,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_START_TIMER'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_START_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						startTimer({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.PAUSE_TIMER]: {
			id: ActionId.PAUSE_TIMER,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PAUSE_TIMER'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PAUSE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						pauseTimer({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.START]: {
			id: ActionId.START,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_START'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_START_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						start({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.TAKE]: {
			id: ActionId.TAKE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_TAKE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_TAKE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						take({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.PAUSE]: {
			id: ActionId.PAUSE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PAUSE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PAUSE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						pause({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.COMPLETE]: {
			id: ActionId.COMPLETE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_COMPLETE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_COMPLETE_SUCCESS'),
			useSuccessHaptic: true,
			handleAction: (task, layoutWidget, options, analyticsLabel = {}) => executeIfOnline(
				() => {
					const completeAction = () => {
						if (
							task.isResultRequired
							&& !task.isOpenResultExists
							&& !selectIsCreator(task)
							&& !env.isAdmin
						)
						{
							// todo show button to attach instant result
							showSafeToast(
								{
									code: ActionId.COMPLETE,
									message: Loc.getMessage('M_TASKS_ACTIONS_MENU_COMPLETE_RESULT_REQUIRED'),
								},
								layoutWidget,
							);

							throw new ActionMenuError('Result required');
						}

						dispatch(
							complete({
								taskId: task.id,
								analyticsLabel: {
									tool: 'tasks',
									category: 'task_operations',
									event: 'task_complete',
									type: 'task',
									...analyticsLabel,
								},
							}),
						)
							.then((response) => {
								const {
									data,
									status,
								} = response.payload;

								if (
									status === 'success'
									&& data?.isSuccess
									&& data.areAllSubtasksCompleted
									&& data.parentTask
								)
								{
									tasksUpserted([data.parentTask]);
									showCompleteParentTaskAlert(data.task, data.parentTask);
								}
							})
							.catch(console.error);

						return Promise.resolve({});
					};

					if (task.isDodNecessary)
					{
						return new Promise((resolve) => {
							Dod.show({
								userId: Number(env.userId),
								taskId: task.id,
								groupId: task.groupId,
								parentWidget: layoutWidget,
								dodTypes: task.dodTypes,
								activeTypeId: task.activeDodTypeId,
								onComplete: async () => {
									await completeAction();

									resolve();
								},
							});
						});
					}

					return completeAction();
				},
				layoutWidget,
			),
		},
		[ActionId.RENEW]: {
			id: ActionId.RENEW,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_RENEW'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_RENEW_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						renew({
							taskId: task.id,
						}),
					)
						.then((response) => {
							const {
								data,
								status,
							} = response.payload;
							if (
								status === 'success'
								&& data?.isSuccess
								&& data.allSubtasksWereCompleted
								&& data.parentTask
							)
							{
								tasksUpserted([data.parentTask]);
								showRenewParentTaskAlert(data.task, data.parentTask);
							}
						})
						.catch(console.error);
				},
				layoutWidget,
			),
		},
		[ActionId.APPROVE]: {
			id: ActionId.APPROVE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_APPROVE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_APPROVE_SUCCESS'),
			useSuccessHaptic: true,
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						approve({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.DISAPPROVE]: {
			id: ActionId.DISAPPROVE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DISAPPROVE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DISAPPROVE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						disapprove({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.DEFER]: {
			id: ActionId.DEFER,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DEFER'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DEFER_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						defer({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.DELEGATE]: {
			id: ActionId.DELEGATE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DELEGATE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_DELEGATE_SUCCESS'),
			isRestricted: () => getFeatureRestriction(FeatureId.DELEGATING).isRestricted(),
			showRestriction: (parentWidget, analyticsData) => {
				return getFeatureRestriction(FeatureId.DELEGATING).showRestriction({ parentWidget, analyticsData });
			},
			useHaptic: false,
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => new Promise((resolve) => {
					let layout = null;

					SocialNetworkUserSelector.make({
						initSelectedIds: [task.responsible],
						createOptions: {
							enableCreation: true,
							analytics: new AnalyticsEvent().setSection('tasks'),
							getParentLayout: () => layout,
						},
						selectOptions: {
							getNonSelectableErrorText: (item) => {
								const isCollaber = item.params.entityType === 'collaber';
								const isCollab = selectGroupById(store.getState(), task.groupId)?.isCollab;

								if (isCollaber && !isCollab)
								{
									return Loc.getMessage('M_TASKS_DENIED_SELECT_COLLABER_WITHOUT_COLLAB');
								}

								return Loc.getMessage('M_TASKS_ACTIONS_MENU_DELEGATE_NON_SELECTABLE');
							},
						},
						provider: {
							context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
							options: {
								recentItemsLimit: 10,
								maxUsersInRecentTab: 10,
								searchLimit: 20,
							},
							filters: [
								{
									id: 'tasks.userDataFilter',
									options: {
										role: 'R',
										groupId: task.groupId,
									},
								},
							],
						},
						events: {
							onClose: (selectedUsers) => {
								if (!selectedUsers || selectedUsers.length === 0)
								{
									return;
								}

								const userId = selectedUsers[0].id;
								if (userId === task.responsible)
								{
									return;
								}

								Haptics.impactLight();

								dispatch(
									delegate({
										taskId: task.id,
										userId,
									}),
								);
								resolve();
							},
						},
						widgetParams: {
							title: Loc.getMessage('M_TASKS_ACTIONS_MENU_DELEGATE'),
							backdrop: {
								mediumPositionPercent: 70,
								horizontalSwipeAllowed: false,
							},
						},
					}).show({}, layoutWidget)
						.then((widget) => {
							layout = widget;
						})
						.catch(console.error);
				}),
				layoutWidget,
			),
		},
		[ActionId.FOLLOW]: {
			id: ActionId.FOLLOW,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_FOLLOW'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_FOLLOW_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						follow({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.UNFOLLOW]: {
			id: ActionId.UNFOLLOW,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNFOLLOW_MSGVER_1'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNFOLLOW_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						unfollow({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.REMOVE]: {
			id: ActionId.REMOVE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_REMOVE_MSGVER_1'),
			isDestructive: true,
			useHaptic: false,
			handleAction: (task, layoutWidget, { shouldBackOnRemove = true }) => executeIfOnline(
				() => {
					const confirmRemove = () => {
						Haptics.impactLight();
						BX.postComponentEvent('taskbackground::removeTask', [task.id]);
						if (shouldBackOnRemove)
						{
							layoutWidget.back();
						}
					};

					confirmDestructiveAction({
						title: '',
						description: Loc.getMessage('M_TASKS_ACTIONS_MENU_REMOVE_CONFIRM'),
						onDestruct: confirmRemove,
					});
				},
				layoutWidget,
			),
		},
		[ActionId.PIN]: {
			id: ActionId.PIN,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PIN'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PIN_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						pin({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.UNPIN]: {
			id: ActionId.UNPIN,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNPIN'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNPIN_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						unpin({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.MUTE]: {
			id: ActionId.MUTE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_MUTE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_MUTE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						mute({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.UNMUTE]: {
			id: ActionId.UNMUTE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNMUTE'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_UNMUTE_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						unmute({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.SHARE]: {
			id: ActionId.SHARE,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_SHARE'),
			useHaptic: false,
			handleAction: (task) => {
				dialogs.showSharingDialog({
					message: `${currentDomain}/company/personal/user/${env.userId}/tasks/task/view/${task.id}/`,
				});
			},
		},
		[ActionId.READ]: {
			id: ActionId.READ,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_READ'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						read({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.PING]: {
			id: ActionId.PING,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PING'),
			successToastPhrase: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_PING_SUCCESS'),
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					dispatch(
						ping({
							taskId: task.id,
						}),
					);
				},
				layoutWidget,
			),
		},
		[ActionId.COPY]: {
			id: ActionId.COPY,
			title: () => Loc.getMessage('M_TASKS_ACTIONS_MENU_COPY_TASK_MSGVER_1'),
			useHaptic: false,
			handleAction: (task, layoutWidget) => executeIfOnline(
				() => {
					const mapUser = (user) => {
						return user ? {
							id: user.id,
							name: user.fullName,
							image: user.avatarSize100,
							link: user.link,
							workPosition: user.workPosition,
						} : undefined;
					};

					const state = store.getState();
					const accomplices = task.accomplices.map((userId) => {
						return mapUser(usersSelector.selectById(state, userId));
					});
					const auditors = task.auditors.map((userId) => {
						return mapUser(usersSelector.selectById(state, userId));
					});

					const taskCreateParameters = {
						initialTaskData: {
							accomplices,
							auditors,
							title: task.name,
							description: task.description,
							deadline: task.deadline ? new Date(task.deadline * 1000) : null,
							groupId: task.groupId,
							group: selectGroupById(state, task.groupId),
							responsible: mapUser(usersSelector.selectById(state, task.responsible)),
							priority: String(task.priority),
							files: task.files,
							tags: task.tags,
							crm: task.crm,
							flowId: task.flowId,
						},
						copyId: task.id,
						layoutWidget,
					};

					openTaskCreateForm(taskCreateParameters);
				},
				layoutWidget,
			),
		},
		[ActionId.EXTRA_SETTINGS]: {
			id: ActionId.EXTRA_SETTINGS,
			title: () => Loc.getMessage('M_TASKS_EXTRA_SETTINGS'),
			useHaptic: false,
			handleAction: (task, layoutWidget) => {
				ExtraSettings.open({ layoutWidget, taskId: task.id });
			},
		},
	};

	const pathToIcons = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/action-menu/images';
	const iconPrefix = `${currentDomain}${pathToIcons}/tasksmobile-layout-action-menu-`;
	const actionIconMap = {
		[ActionId.START_TIMER]: `${iconPrefix}start.svg`,
		[ActionId.PAUSE_TIMER]: `${iconPrefix}pause.svg`,
		[ActionId.START]: `${iconPrefix}start.svg`,
		[ActionId.PAUSE]: `${iconPrefix}pause.svg`,
		[ActionId.COMPLETE]: `${iconPrefix}complete.svg`,
		[ActionId.RENEW]: `${iconPrefix}renew.svg`,
		[ActionId.APPROVE]: `${iconPrefix}approve.svg`,
		[ActionId.DISAPPROVE]: `${iconPrefix}disapprove.svg`,
		[ActionId.UNFOLLOW]: `${iconPrefix}unfollow.svg`,
		[ActionId.REMOVE]: `${iconPrefix}remove.svg`,
		[ActionId.PIN]: `${iconPrefix}pin.svg`,
		[ActionId.UNPIN]: `${iconPrefix}unpin.svg`,
		[ActionId.MUTE]: `${iconPrefix}mute.svg`,
		[ActionId.UNMUTE]: `${iconPrefix}unmute.svg`,
		[ActionId.SHARE]: `${iconPrefix}share.svg`,
		[ActionId.READ]: `${iconPrefix}read.svg`,
		[ActionId.PING]: `${iconPrefix}ping.svg`,
	};

	const pathToOutlineIcons = `${currentDomain}${pathToIcons}/outline/`;
	const outlineIconMap = {
		[ActionId.COPY_ID]: `${pathToOutlineIcons}copy.svg`,
		[ActionId.START_TIMER]: `${pathToOutlineIcons}play.svg`,
		[ActionId.PAUSE_TIMER]: `${pathToOutlineIcons}pause.svg`,
		[ActionId.START]: `${pathToOutlineIcons}play.svg`,
		[ActionId.PAUSE]: `${pathToOutlineIcons}pause.svg`,
		[ActionId.FOLLOW]: `${pathToOutlineIcons}follow.svg`,
		[ActionId.UNFOLLOW]: `${pathToOutlineIcons}unfollow.svg`,
		[ActionId.DEFER]: `${pathToOutlineIcons}delay.svg`,
		[ActionId.RENEW]: `${pathToOutlineIcons}renew.svg`,
		[ActionId.DELEGATE]: `${pathToOutlineIcons}delegate.svg`,
		[ActionId.REMOVE]: `${pathToOutlineIcons}remove.svg`,
		[ActionId.PIN]: `${pathToOutlineIcons}pin.svg`,
		[ActionId.UNPIN]: `${pathToOutlineIcons}unpin.svg`,
		[ActionId.MUTE]: `${pathToOutlineIcons}mute.svg`,
		[ActionId.UNMUTE]: `${pathToOutlineIcons}unmute.svg`,
		[ActionId.COPY]: `${pathToOutlineIcons}duplicate.svg`,
		[ActionId.SHARE]: `${pathToOutlineIcons}share.svg`,
		[ActionId.COMPLETE]: `${pathToOutlineIcons}complete.svg`,
		[ActionId.APPROVE]: `${pathToOutlineIcons}approve.svg`,
		[ActionId.DISAPPROVE]: `${pathToOutlineIcons}disapprove.svg`,
		[ActionId.PING]: `${pathToOutlineIcons}ping.svg`,
		[ActionId.READ]: `${pathToOutlineIcons}chatsWithCheck.svg`,
		[ActionId.EXTRA_SETTINGS]: `${pathToOutlineIcons}settings.svg`,
	};
	const outlineLockIcon = `${pathToOutlineIcons}lock.svg`;

	const outlineInlineIconMap = {
		[ActionId.START_TIMER]: Icon.PLAY,
		[ActionId.PAUSE_TIMER]: Icon.PAUSE,
		[ActionId.START]: Icon.PLAY,
		[ActionId.TAKE]: Icon.PLAY,
		[ActionId.PAUSE]: Icon.PAUSE,
		[ActionId.DEFER]: Icon.DELAY,
		[ActionId.RENEW]: Icon.REFRESH,
		[ActionId.DELEGATE]: Icon.DELEGATE,
		[ActionId.COMPLETE]: Icon.FLAG,
		[ActionId.APPROVE]: Icon.CHECK,
		[ActionId.DISAPPROVE]: Icon.CROSS,
		[ActionId.PING]: Icon.PING,
		[ActionId.READ]: Icon.CHATS_WITH_CHECK,
		[ActionId.EXTRA_SETTINGS]: Icon.SETTINGS,
	};

	Object.keys(ActionMeta).forEach((actionId) => {
		const sectionCode = Object.keys(actionsBySectionsMap).find((section) => {
			return actionsBySectionsMap[section].includes(actionId);
		});
		const currentAction = ActionMeta[actionId];
		const { useSuccessHaptic, useHaptic = true, successToastPhrase, isRestricted, showRestriction } = currentAction;

		ActionMeta[actionId] = {
			...currentAction,
			sectionCode,
			getData: () => ({
				svgUri: actionIconMap[actionId],
				outlineIconUri: (isRestricted?.() ? outlineLockIcon : outlineIconMap[actionId]),
				outlineIconContent: outlineInlineIconMap[actionId],
			}),
			/**
			 * @param {object} [task]
			 * @param {string|number} [taskId]
			 * @param {object} [layout]
			 * @param {object} [options]
			 * @param {object} [analyticsLabel]
			 * @return {Promise<void>}
			 */
			handleAction: async ({ task, taskId, layout, options, analyticsLabel }) => {
				try
				{
					if (isRestricted?.())
					{
						showRestriction?.(layout, analyticsLabel);

						return;
					}

					const taskRedux = task ?? selectByTaskIdOrGuid(store.getState(), taskId);

					await currentAction.handleAction(taskRedux, layout, options, analyticsLabel);

					if (useSuccessHaptic)
					{
						Haptics.notifySuccess();
					}
					else if (useHaptic)
					{
						Haptics.impactLight();
					}

					if (successToastPhrase)
					{
						showSuccessToast({
							actionId,
							message: successToastPhrase(taskRedux),
							iconUrl: outlineIconMap[actionId],
							layout,
						});
					}
				}
				catch (error)
				{
					if (error instanceof ActionMenuError)
					{
						Haptics.notifyFailure();
					}
					else
					{
						console.error(error);
					}
				}
			},
		};
	});

	const showSuccessToast = ({ actionId, message, iconUrl, layout }) => {
		if (!message || message.length === 0)
		{
			return;
		}

		const params = {
			message,
			code: actionId,
		};

		if (iconUrl)
		{
			params.svg = {
				url: iconUrl,
			};
		}

		throttledShowSafeToast(params, layout);
	};

	const showCompleteParentTaskAlert = (subTask, parentTask) => {
		Alert.confirm(
			Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_COMPLETE_TITLE'),
			Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_COMPLETE_DESCRIPTION', {
				'#TASK_NAME#': truncate(parentTask.name, 40),
			}),
			[
				{
					text: Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_NOT_COMPLETE_BUTTON'),
				},
				{
					text: Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_COMPLETE_BUTTON'),
					onPress: () => {
						ActionMeta[ActionId.COMPLETE]?.handleAction({ taskId: parentTask.id });
					},
				},
			],
		);
	};

	const showRenewParentTaskAlert = (subTask, parentTask) => {
		Alert.confirm(
			Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_RENEW_TITLE'),
			Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_RENEW_DESCRIPTION', {
				'#TASK_NAME#': truncate(parentTask.name, 40),
			}),
			[
				{
					text: Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_NOT_RENEW_BUTTON'),
				},
				{
					text: Loc.getMessage('M_TASKS_ACTIONS_MENU_PARENT_SCRUM_TASK_RENEW_BUTTON'),
					onPress: () => {
						ActionMeta[ActionId.RENEW]?.handleAction({ taskId: parentTask.id });
					},
				},
			],
		);
	};

	// prefetch assets with timeout to not affect core queries
	setTimeout(() => {
		const menuIcons = Object.values(actionIconMap).filter((icon) => icon !== null);
		const outlineIcons = Object.values({ ...outlineIconMap, outlineLockIcon }).filter(Boolean);

		void downloadImages([
			...menuIcons,
			...outlineIcons,
		]);
	}, 1000);

	module.exports = { ActionId, ActionMeta, ActionMenuError };
});
