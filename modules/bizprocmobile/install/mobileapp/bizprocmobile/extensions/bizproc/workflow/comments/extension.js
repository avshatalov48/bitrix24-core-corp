/**
 * @module bizproc/workflow/comments
*/
jn.define('bizproc/workflow/comments', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');

	class WorkflowComments extends PureComponent
	{
		static getHeight()
		{
			return 56;
		}

		constructor(props)
		{
			super(props);

			this.containerRef = null;
			this.handleClick = this.onCommentsClick.bind(this);
			this.handlePullEvent = this.processPullEvent.bind(this);

			this.state = {
				visible: true,
				commentsCount: props.commentCounter?.all,
				newCommentsCount: props.commentCounter?.new,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				...this.state,
				commentsCount: props.commentCounter?.all,
				newCommentsCount: props.commentCounter?.new,
			};
		}

		componentDidMount()
		{
			Keyboard.on(Keyboard.Event.WillShow, () => {
				if (this.state.visible === true && this.containerRef)
				{
					this.containerRef.animate({ opacity: 0, duration: 300 }, () => {
						this.setState({ visible: false });
					});
				}
			});

			Keyboard.on(Keyboard.Event.WillHide, () => {
				if (this.state.visible === false && this.containerRef)
				{
					this.containerRef.animate({ opacity: 1, duration: 100 }, () => {
						this.setState({ visible: true });
					});
				}
			});

			this.unsubscribePushCallback = BX.PULL.subscribe({
				moduleId: 'bizproc',
				callback: this.handlePullEvent,
			});
		}

		componentWillUnmount()
		{
			if (this.unsubscribePushCallback)
			{
				this.unsubscribePushCallback();
			}
		}

		processPullEvent(data = {})
		{
			const { command, params } = data;

			if (command === 'comment' && params?.workflowId === this.props.workflowId)
			{
				this.setState({
					commentsCount: params.counter?.all,
					newCommentsCount: params.counter?.new,
				});
			}
		}

		render()
		{
			return this.renderFloatingCommentButton();
		}

		renderFloatingCommentButton()
		{
			let commentsCount = (this.state.commentsCount > 0 ? this.state.commentsCount : '');
			if (commentsCount > 99)
			{
				commentsCount = '99+';
			}

			const newCommentsCount = this.state.newCommentsCount;

			return View(
				{
					style: {
						flex: 1,
						alignSelf: 'center',
						position: 'absolute',
						bottom: 30,
					},
					safeArea: {
						bottom: true,
					},
				},
				View(
					{
						style: {
							display: this.state.visible ? 'flex' : 'none',
							flexDirection: 'row',
							backgroundColor: AppTheme.colors.accentExtraDarkblue,
							paddingHorizontal: 24,
							paddingVertical: 12,
							borderRadius: 88,
						},
						testId: 'commentsField',
						onClick: this.handleClick,
						ref: (ref) => {
							this.containerRef = ref;
						},
					},
					Text({
						style: {
							fontSize: 18,
							fontWeight: '400',
							color: AppTheme.colors.baseWhiteFixed,
						},
						text: `${Loc.getMessage('MBP_WORKFLOW_COMMENTS_TITLE')} ${commentsCount}`,
						testId: 'commentsFieldCounterAll',
					}),
					newCommentsCount > 0 && View(
						{
							style: {
								marginLeft: 8,
								backgroundColor: AppTheme.colors.accentMainSuccess,
								paddingHorizontal: 7,
								paddingVertical: 2,
								borderRadius: 55,
							},
							testId: 'commentsFieldCounterNew',
						},
						Text({
							style: {
								fontSize: 15,
								fontWeight: '500',
								color: AppTheme.colors.baseWhiteFixed,
							},
							text: `+${newCommentsCount > 99 ? 99 : newCommentsCount}`,
						}),
					),
				),
			);
		}

		renderCounter(counter = null)
		{
			let value = this.state.commentsCount;
			let color = AppTheme.colors.base6;
			let fontColor = AppTheme.colors.base3;

			if (counter)
			{
				value = counter.value;
				if (value > 0)
				{
					color = counter.color;
					fontColor = AppTheme.colors.base8;
				}
			}

			return View(
				{
					style: {
						height: 22,
						justifyContent: 'center',
						alignSelf: 'center',
						marginLeft: 8,
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							alignSelf: 'center',
							paddingHorizontal: 6,
							paddingVertical: 2,
							backgroundColor: color,
							borderRadius: 10,
						},
						testId: 'commentsFieldCounterNew',
					},
					Text({
						style: {
							fontSize: 12,
							fontWeight: '500',
							color: fontColor,
						},
						text: (value < 100 ? value.toString() : '99+'),
					}),
				),
			);
		}

		onCommentsClick()
		{
			PageManager.openPage({
				backgroundColor: AppTheme.colors.bgSecondary,
				url: `${env.siteDir}mobile/bp/comments.php?workflowId=${this.props.workflowId}`,
				backdrop: {
					mediumPositionPercent: 84,
					onlyMediumPosition: true,
					forceDismissOnSwipeDown: true,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
					enableNavigationBarBorder: false,
				},
				titleParams: {
					text: Loc.getMessage('MBP_WORKFLOW_COMMENTS_TITLE'),
				},
				enableNavigationBarBorder: false,
				loading: {
					type: 'comments',
				},
				modal: true,
				cache: true,
			});
		}
	}

	module.exports = { WorkflowComments };
});
