/**
 * @module tasks/layout/task/view-new/ui/comments-button
 */
jn.define('tasks/layout/task/view-new/ui/comments-button', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { Icon } = require('assets/icons');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { PureComponent } = require('layout/pure-component');
	const { connect } = require('statemanager/redux/connect');
	const { selectByTaskIdOrGuid, selectIsMember } = require('tasks/statemanager/redux/slices/tasks');

	class CommentsButton extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.containerRef = null;

			this.state = {
				visible: true,
				totalComments: Number(props.totalComments || 0),
				unreadComments: Number(props.unreadComments || 0),
				isBadgeActive: Boolean(props.isBadgeActive),
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				...this.state,
				totalComments: Number(props.totalComments || 0),
				unreadComments: Number(props.unreadComments || 0),
				isBadgeActive: Boolean(props.isBadgeActive),
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

		/**
		 * @public
		 * @param {number} totalComments
		 * @param {number} unreadComments
		 * @param {boolean} isBadgeActive
		 */
		updateCounters(totalComments, unreadComments, isBadgeActive)
		{
			this.setState({
				totalComments: Number(totalComments),
				unreadComments: Number(unreadComments),
				isBadgeActive: Boolean(isBadgeActive),
			});
		}

		render()
		{
			const stringify = (value) => (value > 99 ? '99+' : String(value));

			const totalComments = (this.state.totalComments > 0 ? stringify(this.state.totalComments) : '');
			const unreadComments = (this.state.unreadComments > 0 ? stringify(this.state.unreadComments) : '');

			return View(
				{
					testId: `${this.props.testId}_Container`,
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
						testId: `${this.props.testId}_VisibilityLayer`,
						style: {
							display: this.state.visible ? 'flex' : 'none',
							flexDirection: 'row',
						},
						ref: (ref) => {
							this.containerRef = ref;
						},
					},
					Button({
						testId: `${this.props.testId}_ClickableArea`,
						text: (
							totalComments
								? Loc.getMessage(
									'M_TASK_DETAILS_COMMENTS_BUTTON_MSGVER_1',
									{ '#COMMENTS_COUNT#': totalComments },
								)
								: Loc.getMessage('M_TASK_DETAILS_COMMENTS_BUTTON_EMPTY')
						),
						size: ButtonSize.XL,
						backgroundColor: Color.accentMainPrimary,
						color: Color.baseWhiteFixed,
						onClick: this.props.onClick,
						leftIcon: Icon.CHATS,
						badge: this.renderBadge(unreadComments),
					}),
				),
			);
		}

		renderBadge(unreadComments)
		{
			if (unreadComments.length <= 0)
			{
				return null;
			}

			return BadgeCounter({
				testId: `${this.props.testId}_Badge`,
				value: `+${unreadComments.replace('+', '')}`,
				showRawValue: true,
				mode: this.state.isBadgeActive ? BadgeCounterDesign.SUCCESS : BadgeCounterDesign.GREY,
			});
		}
	}

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId) || {};

		return {
			totalComments: (task.commentsCount - task.serviceCommentsCount),
			unreadComments: task.newCommentsCount,
			isBadgeActive: selectIsMember(task) && !task.isMuted,
		};
	};

	module.exports = {
		CommentsButton: connect(mapStateToProps)(CommentsButton),
	};
});
