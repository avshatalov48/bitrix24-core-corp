/**
 * @module bizproc/task/details
 */
jn.define('bizproc/task/details', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
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
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.state.taskInfo = null;
			this.state.allCount = 0;
			this.state.editor = null;
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

			Alert.confirm(
				BX.message('BPMOBILE_TASK_DETAILS_ALERT_TITLE'),
				BX.message('BPMOBILE_TASK_DETAILS_ALERT_TEXT'),
				[
					{
						text: BX.message('BPMOBILE_TASK_DETAILS_ALERT_DISCARD'),
						type: 'destructive',
						onPress: onDiscard,
					},
					{
						text: BX.message('BPMOBILE_TASK_DETAILS_ALERT_CANCEL'),
						type: 'cancel',
					},
				],
			);
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
										text: this.task.name,
									},
								),
								this.task.description && BBCodeText(
									{
										value: this.task.description,
										style: styles.taskDescription,
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
									},
								),
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

		loadTask()
		{
			BX.ajax.runAction(
				'bizprocmobile.Task.loadDetails',
				{ data: { taskId: this.props.taskId } },
			)
				.then((response) => {
					this.setState({
						taskInfo: response.data.task.data,
						allCount: response.data.allCount,
						editor: response.data.editor,
					});

					this.props.layout.setRightButtons([
						{
							type: 'more',
							callback: this.showContextMenu,
						},
					]);
				})
				.catch((response) => {})
			;
		}

		renderTaskFields()
		{
			if (this.task.status > 0)
			{
				return null;
			}

			if (this.state.editor)
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

		renderTaskButtons()
		{
			const task = this.task;

			if (task.status > 0)
			{
				return null;
			}

			if (task.buttons && task.buttons.length > 0)
			{
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
						View(
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
									NotifyManager.hideLoadingIndicator(true);
									if (this.props.layout)
									{
										this.props.layout.close();
									}
								},
								onFail: () => {
									NotifyManager.hideLoadingIndicator(false);
								},
							}),
						),
						View(
							{
								style: {
									marginLeft: 12,
									width: 1,
									height: 19,
									backgroundColor: AppTheme.colors.base6,
								},
							},
						),
						this.renderTimelineButton(),
					),
				);
			}

			return null;
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
			const value = this.state.allCount - 1;

			if (!value)
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
								fromUserId: env.userId,
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
			return View(
				{
					style: {
						paddingHorizontal: 18,
						paddingTop: 12,
						paddingBottom: 55,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					Text({
						style: {
							fontSize: 12,
							fontWeight: '400',
							color: AppTheme.colors.base4,
						},
						text: Loc.getMessage('BPMOBILE_TASK_DETAILS_COMMENTS_TITLE'),
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								borderWidth: 1,
								borderColor: AppTheme.colors.base4,
								borderRadius: 55,
								paddingHorizontal: 5,
								paddingRight: 9,
								height: 24,
							},
						},
						Image({
							style: {
								width: 28,
								height: 28,
								alignSelf: 'center',
							},
							svg: {
								content: `
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path opacity="0.7" fill-rule="evenodd" clip-rule="evenodd" d="M17.4799 9.90391L14.472 9.9045C14.3855 9.9045 14.3018 9.85442 14.2694 9.7749C14.1339 9.43617 14.1392 9.05444 14.2877 8.71807C14.5368 7.99291 14.5651 7.20942 14.3695 6.46776C14.1651 5.95761 14.1109 5.02567 13.1678 4.98267C12.8727 5.02626 12.6152 5.20357 12.4668 5.46218C12.4403 5.50813 12.4291 5.56233 12.4291 5.61476C12.4291 5.61476 12.4724 6.51967 12.4291 7.14874C12.3858 7.77782 11.182 9.38021 10.4168 10.3976C10.3685 10.4624 10.3007 10.5048 10.2206 10.5171C9.93371 10.5601 9.38648 10.6329 9.18923 10.6589C9.14306 10.665 9.11606 10.723 9.11606 10.7502C9.11606 12.022 9.11606 13.9244 9.11606 16.4574C9.11606 16.4784 9.14139 16.5241 9.1864 16.5316C9.34093 16.5571 9.72745 16.6278 10.1175 16.7532C10.6076 16.9105 11.0159 17.2828 11.8329 17.5585C11.8759 17.5732 11.9237 17.5809 11.969 17.5809H15.8057C16.3012 17.4925 16.6699 17.069 16.6953 16.5606C16.7023 16.2773 16.6458 15.9968 16.5297 15.7394C16.5132 15.7023 16.535 15.6634 16.5751 15.6558C17.067 15.5662 17.682 14.6325 16.9244 13.8803C16.9044 13.8608 16.9079 13.8343 16.935 13.8272C17.3527 13.7206 17.6726 13.3872 17.7704 12.9713C17.8081 12.8122 17.7945 12.6479 17.7474 12.4918C17.692 12.3056 17.6036 12.1312 17.4864 11.9757C17.457 11.9369 17.4735 11.8862 17.5206 11.8709C17.9365 11.7301 18.2228 11.3324 18.2198 10.88C18.2675 10.4364 17.9111 9.9045 17.4799 9.90391ZM7.93847 10.1443H5.76415C5.65105 10.1443 5.56504 10.2444 5.58448 10.3534L6.84748 17.4489C6.86692 17.5579 6.96294 17.6375 7.07546 17.6375H7.87957C8.00386 17.6375 8.1046 17.5391 8.1046 17.4177L8.12227 10.3245C8.12227 10.225 8.04039 10.1443 7.93847 10.1443Z" fill="${AppTheme.colors.base4}"/>
									</svg>
								`,
							},
						}),
						Text({
							style: {
								fontSize: 12,
								fontWeight: '400',
								color: AppTheme.colors.base4,
							},
							text: Loc.getMessage('BPMOBILE_TASK_DETAILS_COMMENTS_LIKE'),
						}),
					),
				),
				Image({
					style: {
						marginTop: 35,
						width: 61,
						height: 60,
						alignSelf: 'center',
					},
					svg: {
						content: `
							<svg width="61" height="60" viewBox="0 0 61 60" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g opacity="0.3">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M30.5 47C39.8888 47 47.5 39.3888 47.5 30C47.5 20.6112 39.8888 13 30.5 13C21.1112 13 13.5 20.6112 13.5 30C13.5 39.3888 21.1112 47 30.5 47ZM50.5 30C50.5 41.0457 41.5457 50 30.5 50C19.4543 50 10.5 41.0457 10.5 30C10.5 18.9543 19.4543 10 30.5 10C41.5457 10 50.5 18.9543 50.5 30Z" fill="${AppTheme.colors.base4}"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M30.2873 18.5163C31.1157 18.5163 31.7873 19.1878 31.7873 20.0163V28.7748L38.9198 28.7748C39.7482 28.7748 40.4198 29.4463 40.4198 30.2748C40.4198 31.1032 39.7482 31.7748 38.9198 31.7748L30.2873 31.7748C29.8895 31.7748 29.5079 31.6168 29.2266 31.3355C28.9453 31.0542 28.7873 30.6727 28.7873 30.2748V20.0163C28.7873 19.1878 29.4589 18.5163 30.2873 18.5163Z" fill="${AppTheme.colors.base4}"/>
							</g>
							</svg>
						`,
					},
				}),
				Text({
					style: {
						fontSize: 14,
						fontWeight: '400',
						color: AppTheme.colors.base4,
						alignSelf: 'center',
						marginTop: 4,
					},
					text: Loc.getMessage('BPMOBILE_TASK_DETAILS_COMMENTS_DEVELOPING_1'),
				}),
			);
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
