/**
 * @module bizproc/workflow/details
 */
jn.define('bizproc/workflow/details', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { EventEmitter } = require('event-emitter');
	const { Haptics } = require('haptics');
	const { PureComponent } = require('layout/pure-component');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { WorkflowComments } = require('bizproc/workflow/comments');
	const { WorkflowDetailsSkeleton } = require('bizproc/workflow/details/skeleton');
	const { WorkflowDetailsContent } = require('bizproc/workflow/details/content');

	class WorkflowDetails extends PureComponent
	{
		static open(props, layout = PageManager)
		{
			layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: props.title || Loc.getMessage('M_BP_WORKFLOW_DETAILS_WIDGET_TITLE'),
					textColor: AppTheme.colors.base1,
					type: 'dialog',
				},
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					mediumPositionPercent: 90,
					onlyMediumPosition: true,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
					hideNavigationBar: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new WorkflowDetails({
						parentLayout: layout,
						layout: readyLayout,
						workflowId: props.workflowId || null,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				workflow: null,
				editorConfig: null,
				taskCount: 0,
				commentCounter: null,
				canView: false,
				showTimeline: false,

				additionalContent: null,
				additionalContentIsLoaded: false,
				showRightError: true,
			};

			this.workflowId = props.workflowId;

			this.isClosing = false;
			this.isChanged = false;

			this.scrollViewRef = null;
			this.scrollY = 0;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.handleExit = this.handleExit.bind(this);
			this.handleScrollToInvalidField = this.handleScrollToInvalidField.bind(this);
			this.handleScrollToFocusedField = this.handleScrollToFocusedField.bind(this);
		}

		get layout()
		{
			return this.props.layout;
		}

		get parentLayout()
		{
			return this.props.parentLayout;
		}

		get workflow()
		{
			return this.state.workflow;
		}

		get isLoaded()
		{
			return this.workflow !== null && this.state.additionalContentIsLoaded === true;
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.customEventEmitter
				.on('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField)
				.on('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField)
			;

			this.layout.preventBottomSheetDismiss(true);
			this.layout.on('preventDismiss', this.handleExit);

			this.loadWorkflow();
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			this.customEventEmitter
				.off('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField)
				.off('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField)
			;

			this.layout.preventBottomSheetDismiss(false);
			this.layout.off('preventDismiss', this.handleExit);
		}

		handleExit()
		{
			if (this.isClosing)
			{
				return Promise.resolve();
			}

			let promise = Promise.resolve();
			if (this.isChanged)
			{
				const onDiscardHandler = (resolve) => () => resolve();

				const onAcceptHandler = (reject) => () => reject();

				promise = promise.then(() => new Promise((resolve, reject) => {
					this.showConfirmExit(onDiscardHandler(resolve), onAcceptHandler(reject));
				}));
			}

			return promise.then(() => {
				this.isClosing = true;

				this.layout.close();
			});
		}

		showConfirmExit(onDiscard, onContinue)
		{
			Haptics.impactLight();

			Alert.confirm(
				Loc.getMessage('M_BP_WORKFLOW_DETAILS_CONFIRM_EXIT_TITLE'),
				Loc.getMessage('M_BP_WORKFLOW_DETAILS_CONFIRM_EXIT_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('M_BP_WORKFLOW_DETAILS_CONFIRM_EXIT_EXIT'),
						type: 'destructive',
						onPress: onDiscard,
					},
					{
						text: Loc.getMessage('M_BP_WORKFLOW_DETAILS_CONFIRM_EXIT_CONTINUE'),
						type: 'cancel',
						onPress: onContinue,
					},
				],
			);
		}

		handleScrollToInvalidField(fieldView)
		{
			if (this.scrollViewRef && fieldView)
			{
				const position = this.scrollViewRef.getPosition(fieldView);
				position.y -= 50;
				this.scrollViewRef.scrollTo({ ...position, animated: true });
			}
		}

		handleScrollToFocusedField(fieldView)
		{
			if (this.scrollViewRef && fieldView)
			{
				const { y } = this.scrollViewRef.getPosition(fieldView);
				if (y > this.scrollY + device.screen.height * 0.4)
				{
					const positionY = y - 150;
					this.scrollViewRef.scrollTo({ y: positionY, animated: true });
				}
			}
		}

		loadWorkflow()
		{
			BX.ajax.runAction('bizprocmobile.Workflow.loadDetails', { data: { workflowId: this.workflowId } })
				.then((response) => {
					const isLiveFeedProcess = response.data.isLiveFeedProcess || false;
					const editorConfig = response.data.editor || null;
					const taskCount = response.data.taskCount || 0;
					const commentCounter = response.data.commentCounter;
					const canView = response.data.canViewWorkflow || false;

					if (isLiveFeedProcess)
					{
						// no need to rerender
						this.state.workflow = {};
						this.state.editorConfig = editorConfig;
						this.state.taskCount = taskCount;
						this.state.commentCounter = commentCounter;
						this.state.canView = canView;
						this.state.showTimeline = true;
						this.state.showRightError = false;

						this.loadListsDetails(response.data.documentId);
					}
					else
					{
						const workflow = response.data.workflow || {};

						this.layout.setTitle(
							{
								text: (workflow.title || Loc.getMessage('M_BP_WORKFLOW_DETAILS_WIDGET_TITLE')),
								type: 'dialog',
							},
						);

						this.setState({
							workflow,
							editorConfig,
							taskCount,
							commentCounter,
							canView,
							additionalContentIsLoaded: true,
							showTimeline: true,
						});
					}
				})
				.catch((response) => {
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}

					this.setState({
						workflow: {},
						additionalContentIsLoaded: true,
						canView: true,
					});
				})
			;
		}

		loadListsDetails(documentId)
		{
			void requireLazy('lists:element-details', false)
				.then(({ ElementDetails }) => {
					if (!ElementDetails)
					{
						this.setState({ additionalContentIsLoaded: true });

						return;
					}

					const content = new ElementDetails({
						uid: this.uid,
						layout: this.layout,
						isEmbedded: true,
						isNeedShowSkeleton: false,
						elementId: documentId,
						interceptExit: false,
					});

					this.setState({ additionalContent: content });

					this.customEventEmitter
						.once('Lists.ElementDetails:OnAfterLoadContent', () => {
							this.setState({ additionalContentIsLoaded: true });
						})
						.on('Lists.ElementDetails:onChange', (isChanged) => {
							this.isChanged = isChanged;
						})
					;

					content.loadDetails();
				})
			;
		}

		render()
		{
			return View(
				{
					style: { flex: 1, backgroundColor: AppTheme.colors.bgSecondary },
					resizableByKeyboard: true,
					safeArea: { bottom: true },
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
						{ onClick: () => FocusManager.blurFocusedFieldIfHas() },
						!this.isLoaded && new WorkflowDetailsSkeleton({}),
						this.isLoaded && View(
							{
								style: {
									flexDirection: 'column',
									backgroundColor: AppTheme.colors.bgContentPrimary,
									minHeight: device.screen.height * 0.85 - 250, // ?
									paddingHorizontal: 6,
									paddingVertical: 12,
								},
							},
							View(
								{
									style: {
										flexGrow: 1,
										flexDirection: 'column',
									},
								},
								this.state.additionalContent,
								new WorkflowDetailsContent({
									uid: this.uid,
									layout: this.layout,
									workflow: this.state.workflow,
									editorConfig: this.state.editorConfig,
									canView: this.state.canView,
									showRightError: this.state.showRightError,
								}),
							),
							this.state.showTimeline && this.renderTimelineButton(),
						),
						View({ style: { height: 100 } }),
					),
				),
				this.isLoaded && new WorkflowComments({
					workflowId: this.workflowId,
					commentCounter: this.state.commentCounter,
				}),
			);
		}

		renderTimelineButton()
		{
			return View(
				{
					style: {
						marginHorizontal: 10,
						height: 48,
						flexDirection: 'row',
						alignItems: 'flex-end',
					},
				},
				View(
					{
						style: {
							paddingHorizontal: 10,
							height: 36,
							borderRadius: 8,
							borderWidth: 1,
							borderColor: AppTheme.colors.base5,
							justifyContent: 'center',
						},
						onClick: () => {
							if (this.isChanged)
							{
								this.showConfirmExit(() => {
									this.openTimeline();
								});

								return;
							}

							this.openTimeline();
						},
					},
					Text({
						testId: 'WORKFLOW_DETAILS_TIMELINE',
						style: {
							fontWeight: '500',
							fontSize: 14,
							color: AppTheme.colors.base2,
						},
						text: Loc.getMessage('M_BP_WORKFLOW_DETAILS_TIMELINE_BTN_MSGVER_1'),
					}),
				),
				this.renderCounter(),
			);
		}

		renderCounter()
		{
			const counter = this.state.taskCount;

			return counter > 0 && Text({
				style: {
					position: 'absolute',
					top: 5,
					borderRadius: 9,
					backgroundColor: AppTheme.colors.accentMainAlert,
					textAlign: 'center',
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 12,
					fontWeight: '400',
					minWidth: 18,
					height: 18,
					paddingHorizontal: 2,
				},
				text: String(counter),
			});
		}

		openTimeline()
		{
			void requireLazy('bizproc:workflow/timeline').then(({ WorkflowTimeline }) => {
				void this.props.layout.close(() => {
					void WorkflowTimeline.open(
						this.props.parentLayout,
						{
							workflowId: this.workflowId,
						},
					);
				});
			});
		}
	}

	module.exports = { WorkflowDetails };
});
