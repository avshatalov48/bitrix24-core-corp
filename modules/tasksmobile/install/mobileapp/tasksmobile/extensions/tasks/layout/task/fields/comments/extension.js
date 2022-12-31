/**
 * @module tasks/layout/task/fields/comments
 */
jn.define('tasks/layout/task/fields/comments', (require, exports, module) => {
	const {Loc} = require('loc');

	class Comments extends LayoutComponent
	{
		static getHeight()
		{
			return 56;
		}

		constructor(props)
		{
			super(props);

			this.state = {
				commentsCount: props.commentsCount,
				newCommentsCounter: props.newCommentsCounter,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				commentsCount: props.commentsCount,
				newCommentsCounter: props.newCommentsCounter,
			};
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
			return new UI.BottomToolbar({
				style: {
					backgroundColor: '#fdfae1',
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
						color: '#333333',
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
						color: '#333333',
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
			let color = '#e2e5e9';
			let fontColor = '#828b95';

			if (counter)
			{
				value = counter.value;
				if (value > 0)
				{
					color = counter.color;
					fontColor = '#ffffff';
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
				svg: {
					content: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.16016 6.34368L12.6872 10.8707L13.8598 12.0001L12.6872 13.1301L8.16016 17.6572L9.75762 19.2547L17.0118 12.0005L9.75762 4.74634L8.16016 6.34368Z" fill="#D5D7DB"/></svg>',
				},
			});
		}
	}

	module.exports = {Comments};
});