/**
 * @module tasks/layout/task/fields/comments
 */
jn.define('tasks/layout/task/fields/comments', (require, exports, module) => {
	const { Loc } = require('loc');
	const { chevronRight } = require('assets/common');
	const AppTheme = require('apptheme');

	class Comments extends LayoutComponent
	{
		static getHeight()
		{
			return 56;
		}

		constructor(props)
		{
			super(props);

			this.containerRef = null;

			this.state = {
				visible: true,
				commentsCount: props.commentsCount,
				newCommentsCounter: props.newCommentsCounter,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				...this.state,
				commentsCount: props.commentsCount,
				newCommentsCounter: props.newCommentsCounter,
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
		}

		updateState(newState)
		{
			this.setState({
				commentsCount: newState.commentsCount,
				newCommentsCounter: newState.newCommentsCounter,
			});
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

			let newCommentsCount = 0;
			let newCommentsColor = AppTheme.colors.base6;

			if (this.state.newCommentsCounter)
			{
				const { value, color } = this.state.newCommentsCounter;

				newCommentsCount = value;
				if (newCommentsCount > 0)
				{
					newCommentsColor = color;
				}
			}

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
						onClick: this.props.onClick,
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
						text: `${Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_COMMENTS')} ${commentsCount}`,
						testId: 'commentsFieldCounterAll',
					}),
					newCommentsCount > 0 && View(
						{
							style: {
								marginLeft: 8,
								backgroundColor: newCommentsColor,
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

		renderBottomToolbar()
		{
			return new UI.BottomToolbar({
				style: {
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
				renderContent: () => View(
					{
						style: {
							flex: 1,
							height: Comments.getHeight(),
							justifyContent: 'center',
							paddingHorizontal: 8,
						},
						testId: 'commentsField',
						onClick: this.props.onClick,
					},
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'space-between',
							},
						},
						View(
							{
								style: {
									flexDirection: 'row',
								},
							},
							this.renderAllCommentsBlock(),
							this.renderNewCommentsBlock(),
						),
						this.renderRightArrow(),
					),
				),
			});
		}

		renderAllCommentsBlock()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						marginLeft: 8,
					},
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_COMMENTS'),
				}),
				this.renderCounter(),
			);
		}

		renderNewCommentsBlock()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginLeft: 16,
					},
				},
				Text({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						marginLeft: 8,
					},
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_NEW_COMMENTS'),
				}),
				this.renderCounter(this.state.newCommentsCounter),
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

		renderRightArrow()
		{
			return Image({
				style: {
					width: 24,
					height: 24,
				},
				tintColor: AppTheme.colors.base3,
				svg: {
					content: chevronRight(AppTheme.colors.base3),
				},
			});
		}
	}

	module.exports = { Comments };
});
