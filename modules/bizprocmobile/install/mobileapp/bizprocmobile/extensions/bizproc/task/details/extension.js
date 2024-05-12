/**
 * @module bizproc/task/details
 */
jn.define('bizproc/task/details', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert, confirmClosing } = require('alert');
	const { PureComponent } = require('layout/pure-component');
	const { openNativeViewer } = require('utils/file');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');
	const { TaskButtons } = require('bizproc/task/buttons');
	const { TaskFields } = require('bizproc/task/fields');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { Skeleton } = require('bizproc/task/details/skeleton');
	const { NotifyManager } = require('notify-manager');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { Feature } = require('feature');
	const { Haptics } = require('haptics');
	const { EventEmitter } = require('event-emitter');
	const { inAppUrl } = require('in-app-url');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { WorkflowComments } = require('bizproc/workflow/comments');

	class TaskDetails extends PureComponent
	{
		static open(layout = PageManager, props = {})
		{
			layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: props.title || Loc.getMessage('BPMOBILE_TASK_DETAILS_TITLE'),
				},
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new TaskDetails({
						parentLayout: layout,
						layout: readyLayout,
						taskId: props.taskId,
						targetUserId: props.targetUserId || null,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				taskInfo: null,
				allCount: 0,
				editor: null,
				taskResponsibleMessage: '',
				rights: {
					delegate: false,
				},
			};

			this.fieldList = null;
			this.scrollViewRef = null;
			this.scrollY = 0;
			this.isClosing = false;
			this.isChanged = false;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.showContextMenu = this.showContextMenu.bind(this);
			this.handleExitFromTask = this.handleExitFromTask.bind(this);
			this.handleChangeFields = this.handleChangeFields.bind(this);
		}

		componentDidMount()
		{
			if (Feature.isPreventBottomSheetDismissSupported())
			{
				this.props.layout.preventBottomSheetDismiss(true);
				this.props.layout.on('preventDismiss', this.handleExitFromTask);
			}

			this.customEventEmitter.on('TaskFields:onChangeFieldValue', this.handleChangeFields);

			this.loadTask();
		}

		componentWillUnmount()
		{
			if (Feature.isPreventBottomSheetDismissSupported())
			{
				this.props.layout.preventBottomSheetDismiss(false);
				this.props.layout.off('preventDismiss', this.handleExitFromTask);
			}

			this.customEventEmitter.off('TaskFields:onChangeFieldValue', this.handleChangeFields);
		}

		handleExitFromTask()
		{
			if (this.isClosing)
			{
				return Promise.resolve();
			}

			let promise = Promise.resolve();

			if (this.isChanged)
			{
				const onDiscardHandler = (resolve, reject) => () => resolve();

				promise = promise.then(() => new Promise((resolve, reject) => {
					this.showConfirmExitEntity(
						onDiscardHandler(resolve, reject),
					);
				}));
			}

			return promise.then(() => this.close());
		}

		showConfirmExitEntity(onDiscard)
		{
			Haptics.impactLight();

			confirmClosing({
				hasSaveAndClose: false,
				description: Loc.getMessage('BPMOBILE_TASK_DETAILS_ALERT_TEXT'),
				onClose: onDiscard,
			});
		}

		close()
		{
			this.isClosing = true;

			if (this.props.layout)
			{
				this.props.layout.back();
				this.props.layout.close();
			}
		}

		handleChangeFields()
		{
			this.isChanged = true;
		}

		get task()
		{
			return (this.state.taskInfo && this.state.taskInfo.task) || {};
		}

		get isTaskCompleted()
		{
			return (this.task.status > 0 || this.task.userStatus > 0);
		}

		get isMyTask()
		{
			return this.props.targetUserId ? (Number(this.props.targetUserId) === Number(env.userId)) : true;
		}

		get files()
		{
			return (this.state.taskInfo && this.state.taskInfo.files) || {};
		}

		render()
		{
			if (!this.task.id)
			{
				return new Skeleton({});
			}

			// todo: add RPA tasks?
			if (this.task.activity === 'RpaRequestActivity')
			{
				// eslint-disable-next-line no-undef
				return new QRCodeAuthComponent({
					redirectUrl: this.task.documentUrl,
					showHint: true,
					hintText: Loc.getMessage('BPMOBILE_TASK_DETAILS_RPA'),
					parentWidget: this.props.layout,
				});
			}

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgSecondary,
					},
					resizableByKeyboard: true,
				},
				ScrollView(
					{
						style: { flex: 1 },
						ref: (ref) => {
							this.scrollViewRef = ref;
						},
						onScroll: (params) => {
							this.scrollY = params.contentOffset.y;
						},
					},
					View(
						{
							onClick: () => {
								FocusManager.blurFocusedFieldIfHas();
							},
						},
						View(
							{
								style: {
									flexDirection: 'column',
									padding: 5,
									backgroundColor: AppTheme.colors.bgContentPrimary,
									minHeight: device.screen.height * 0.85 - 250,
								},
							},
							!this.isMyTask && this.renderTaskResponsibleBlock(),
							View(
								{
									style: {
										flexGrow: 1,
									},
								},
								Text(
									{
										testId: 'TASK_DETAILS_NAME',
										style: styles.taskName,
										text: jnComponent.convertHtmlEntities(this.task.name),
									},
								),
								this.renderDescription(),
								View(
									{ style: { marginVertical: 8 } },
									this.renderTaskFields(),
								),
							),
							this.renderTaskButtons(),
						),
						this.renderComments(),
					),
				),
			);
		}

		renderDescription()
		{
			const description = this.task.description && new CollapsibleText({
				bbCodeMode: true,
				value: jnComponent.convertHtmlEntities(this.task.description),
				style: styles.taskDescription,
				containerStyle: {
					flexGrow: 0,
				},
				onLinkClick: ({ url }) => {
					if (this.files.hasOwnProperty(url))
					{
						const file = this.files[url];
						const openViewer = throttle(openNativeViewer, 500);
						openViewer({
							fileType: UI.File.getType(UI.File.getFileMimeType(file.type, file.name)),
							url: file.url,
							name: file.name,
						});

						return;
					}

					inAppUrl.open(url);
				},
			});

			if (description && !this.canRenderTaskFields())
			{
				description.toggleExpand();
			}

			return description;
		}

		loadTask()
		{
			BX.ajax.runAction(
				'bizprocmobile.Task.loadDetails',
				{ data: { taskId: this.props.taskId, targetUserId: this.props.targetUserId } },
			)
				.then(({ data }) => {
					this.setState({
						taskInfo: data.task.data,
						allCount: data.allCount,
						editor: data.editor,
						taskResponsibleMessage: data.taskResponsibleMessage,
						rights: Object.assign(this.state.rights, (data.rights || {})),
					});

					this.props.layout.setRightButtons([
						{
							type: 'more',
							callback: this.showContextMenu,
						},
					]);
				})
				.catch(({ errors }) => {
					if (Array.isArray(errors) && errors.length > 0)
					{
						Alert.alert(errors[0].message, '', () => {
							if (this.props.layout)
							{
								this.props.layout.close();
							}
						});
					}
				})
			;
		}

		renderTaskFields()
		{
			if (this.canRenderTaskFields())
			{
				return new TaskFields({
					uid: this.uid,
					editor: this.state.editor,
					layout: this.props.layout,
					onScrollToInvalidField: (fieldView) => {
						if (this.scrollViewRef && fieldView)
						{
							const position = this.scrollViewRef.getPosition(fieldView);
							position.y -= 50;
							this.scrollViewRef.scrollTo({ ...position, animated: true });
						}
					},
					onScrollToFocusedField: (fieldView) => {
						if (this.scrollViewRef && fieldView)
						{
							const { y } = this.scrollViewRef.getPosition(fieldView);
							if (y > this.scrollY + device.screen.height * 0.4)
							{
								const positionY = y - 150;
								this.scrollViewRef.scrollTo({ y: positionY, animated: true });
							}
						}
					},
					ref: (ref) => {
						this.fieldList = ref;
					},
				});
			}

			return null;
		}

		canRenderTaskFields()
		{
			return Boolean(this.isMyTask && !this.isTaskCompleted && this.state.editor);
		}

		renderTaskResponsibleBlock()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						marginTop: 12,
					},
				},
				Text({
					style: {
						marginHorizontal: 24,
						marginVertical: 16,
						color: AppTheme.colors.base5,
						fontSize: 14,
						fontWeight: '400',
						textAlign: 'center',
					},
					text: this.state.taskResponsibleMessage,
				}),
			);
		}

		renderTaskButtons()
		{
			const task = this.task;

			const showTaskButtons = this.isMyTask && !this.isTaskCompleted && task.buttons && task.buttons.length > 0;
			const showDelegateButton = !this.isMyTask && !this.isTaskCompleted && this.state.rights.delegate;
			const showTimelineButton = this.isMyTask;

			return ScrollView(
				{
					style: {
						height: 64,
					},
					horizontal: true,
				},
				View(
					{
						style: {
							paddingTop: 16,
							paddingBottom: 12,
							flexDirection: 'row',
							alignContent: 'center',
							alignItems: 'center',
							paddingHorizontal: 11,
						},
					},
					showTaskButtons && View(
						{
							style: {
								maxWidth: (device.screen.width) * 0.69,
							},
						},
						new TaskButtons({
							testId: 'TASK_DETAILS_BUTTONS',
							task,
							onBeforeAction: this.handleBeforeButtonAction.bind(this),
							onComplete: () => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
								// eslint-disable-next-line no-undef
								Notify.showIndicatorSuccess({ hideAfter: 300 });
								if (this.props.layout)
								{
									setTimeout(() => this.props.layout.close(), 250);
								}
							},
							onFail: (errors) => {
								NotifyManager.hideLoadingIndicator(false);

								const firstError = errors[0];
								if (firstError.code === 'TASK_NOT_FOUND_ERROR')
								{
									// eslint-disable-next-line no-undef
									InAppNotifier.showNotification({
										backgroundColor: AppTheme.colors.baseBlackFixed,
										message: firstError.message,
										code: 'bp-workflow-details-buttons-task-not-found',
										time: 3,
									});

									if (this.props.layout)
									{
										this.props.layout.close();
									}

									return;
								}

								Alert.alert(firstError.message);
							},
						}),
					),
					showDelegateButton && this.renderDelegateButton(),
					(showTaskButtons || showDelegateButton) && showTimelineButton && View(
						{
							style: {
								marginLeft: 12,
								width: 1,
								height: 19,
								backgroundColor: AppTheme.colors.base6,
							},
						},
					),
					showTimelineButton && this.renderTimelineButton(),
				),
			);
		}

		async handleBeforeButtonAction(task, button)
		{
			await NotifyManager.showLoadingIndicator();

			if (!this.fieldList)
			{
				return Promise.resolve(null);
			}

			// cancel === 4
			const isValid = button.TARGET_USER_STATUS === 4 ? true : this.fieldList.isValid();

			if (isValid)
			{
				let errors = null;
				const data = await this.fieldList.getData().catch((err) => {
					console.error(err);
					errors = err;
				});
				if (errors === null)
				{
					return Promise.resolve(data);
				}
			}

			NotifyManager.hideLoadingIndicator(false);

			return Promise.reject();
		}

		renderTimelineButton()
		{
			return View(
				{
					style: {
						paddingLeft: 12,
						height: 64,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							height: 36,
							borderRadius: 8,
							borderWidth: 1,
							borderColor: AppTheme.colors.base5,
							padding: 8,
							paddingHorizontal: 10,
							marginRight: 12,
							maxWidth: 157,
						},
						// testId: `${this.testId}_BUTTON_${type.toUpperCase()}`,
						onClick: () => {
							this.openTimeline();
						},
					},
					Text({
						style: {
							fontWeight: '500',
							fontSize: 14,
							color: AppTheme.colors.base2,
						},
						text: Loc.getMessage('BPMOBILE_TASK_DETAILS_TIMELINE'),
					}),
				),
				this.renderTimelineCounter(),
			);
		}

		renderTimelineCounter()
		{
			let value = this.state.allCount;

			if (!this.isTaskCompleted)
			{
				value -= 1;
			}

			if (value <= 0)
			{
				return null;
			}

			return Text(
				{
					style: {
						position: 'absolute',
						top: 10,
						left: 7,
						width: 18,
						height: 18,
						borderRadius: 9,
						backgroundColor: AppTheme.colors.accentMainAlert,
						textAlign: 'center',
						color: AppTheme.colors.baseWhiteFixed,
						fontSize: 12,
						fontWeight: '500',
					},
					text: String(value),
				},
			);
		}

		renderDelegateButton()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						height: 36,
						borderRadius: 8,
						borderWidth: 1,
						borderColor: AppTheme.colors.base5,
						paddingVertical: 8,
						paddingHorizontal: 10,
					},
					onClick: () => {
						this.openDelegationSelector();
					},
				},
				Text({
					style: {
						fontWeight: '500',
						fontSize: 14,
						color: AppTheme.colors.base2,
					},
					text: Loc.getMessage('BPMOBILE_TASK_DETAILS_ACTION_DELEGATE'),
				}),
			);
		}

		showContextMenu()
		{
			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: true,
					isRawIcon: true,
					// title: this.task.name,
				},
				actions: [
					{
						id: 'timeline',
						title: Loc.getMessage('BPMOBILE_TASK_DETAILS_TIMELINE'),
						onClickCallback: (action, itemId, { parentWidget, parent }) => {
							parentWidget.close(() => this.openTimeline());
						},
						data: {
							svgIcon: icons.timeline,
						},
					},
					{
						id: 'delegate',
						title: Loc.getMessage('BPMOBILE_TASK_DETAILS_ACTION_DELEGATE'),
						onClickCallback: (action, itemId, { parentWidget, parent }) => {
							parentWidget.close(() => this.openDelegationSelector());
						},
						data: {
							svgIcon: icons.delegate,
						},
					},
				],
				testId: 'taskDetailsContextMenu',
			});

			void contextMenu.show(this.props.layout);
		}

		openTimeline()
		{
			const openTimeline = () => {
				void requireLazy('bizproc:workflow/timeline').then(({ WorkflowTimeline }) => {
					void this.props.layout.close(() => {
						void WorkflowTimeline.open(
							this.props.parentLayout,
							{
								workflowId: this.task.workflowId,
								taskId: this.task.id,
							},
						);
					});
				});
			};

			if (this.isChanged)
			{
				this.showConfirmExitEntity(() => {
					openTimeline();
				});

				return;
			}

			openTimeline();
		}

		openDelegationSelector()
		{
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (selectedUsers) => {
						if (!selectedUsers || selectedUsers.length === 0)
						{
							return;
						}

						BX.ajax.runAction('bizproc.task.delegate', {
							data: {
								taskIds: [this.task.id],
								toUserId: selectedUsers.pop().id,
								fromUserId: this.props.targetUserId ?? env.userId,
							},
						})
							.then(({ data }) => {
								if (data && data.message)
								{
									// eslint-disable-next-line no-undef
									InAppNotifier.showNotification({
										message: data.message,
										code: 'bp-task-delegate',
										time: 3,
									});
								}
								this.props.layout.close();
							})
							.catch(({ errors }) => Alert.alert(errors.pop().message))
						;
					},
				},
				widgetParams: {
					title: Loc.getMessage('BPMOBILE_WORKFLOW_LIST_ITEM_MENU_DELEGATE'),
					// backgroundColor: '#eef2f4',
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
						// navigationBarColor: '#eef2f4',
					},
				},
			});

			return selector.show({}, this.props.layout);
		}

		renderComments()
		{
			return new WorkflowComments({
				workflowId: this.task.workflowId,
			});
		}
	}

	const styles = {
		taskName: {
			fontWeight: '600',
			fontSize: 18,
			lineHeightMultiple: 1.1,
			color: AppTheme.colors.base1,
			marginHorizontal: 11,
			marginTop: 7,
			marginBottom: 12,
			// paddingVertical: 12,
		},
		taskDescription: {
			marginHorizontal: 11,
			marginBottom: 16,
			fontSize: 14,
			fontWeight: '400',
			lineHeightMultiple: 1.15,
			color: AppTheme.colors.base2,
		},
	};

	const icons = {
		timeline: (() => {
			const fill = AppTheme.colors.base2;

			return `
				<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M6.67773 7.06348C6.67773 5.95891 7.57316 5.06348 8.67773 5.06348H17.3813H21.2988C22.4034 5.06348 23.2988 5.95891 23.2988 7.06348L23.2988 22.873C23.2988 23.9776 22.4034 24.873 21.2988 24.873H8.67773C7.57316 24.873 6.67773 23.9776 6.67773 22.873V7.06348ZM8.95627 7.70357C8.95627 7.53788 9.09058 7.40356 9.25627 7.40356H20.6571C20.8227 7.40356 20.9571 7.53788 20.9571 7.70356V22.1925C20.9571 22.3582 20.8227 22.4925 20.6571 22.4925H9.25627C9.09058 22.4925 8.95627 22.3582 8.95627 22.1925V7.70357ZM11.2642 15.6624H17.6564C17.824 15.6624 17.9602 15.5028 17.9602 15.3059V14.0232C17.9602 13.8275 17.824 13.6667 17.6564 13.6667H11.2642C11.0976 13.6667 10.9615 13.8275 10.9615 14.0232V15.3059C10.9615 15.5028 11.0976 15.6624 11.2642 15.6624ZM15.8038 17.658L11.326 17.658C11.1249 17.658 10.9615 17.85 10.9615 18.0868V19.2262C10.9615 19.463 11.1249 19.6537 11.326 19.6537L15.8038 19.6537C16.0049 19.6537 16.1672 19.4629 16.1672 19.2261V18.0868C16.1672 17.8499 16.0049 17.658 15.8038 17.658ZM11.2642 11.671H17.6564C17.824 11.671 17.9602 11.5127 17.9602 11.3157V10.0318C17.9602 9.83611 17.824 9.67531 17.6564 9.67531H11.2642C11.0976 9.67531 10.9615 9.83611 10.9615 10.0318V11.3157C10.9615 11.5127 11.0976 11.671 11.2642 11.671Z" fill="${fill}"/>
				</svg>
			`;
		})(),
		delegate: (() => {
			const fill = AppTheme.colors.base2;

			return `
				<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" fill="none">
				  <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 7.92953L18.3593 13.5883H6.26172V16.4126H18.3593L12.7005 22.0714L14.6973 24.0683L23.765 15.0006L14.6973 5.93286L12.7005 7.92953Z" fill="${fill}"/>
				</svg>
			`;
		})(),
	};

	module.exports = { TaskDetails };
});
