/**
 * @module tasks/layout/task/view/newCommentsHint
 */
jn.define('tasks/layout/task/view/newCommentsHint', (require, exports, module) => {
	const {Loc} = require('loc');

	class NewCommentsHint extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				newCommentsCount: props.newCommentsCount,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				newCommentsCount: props.newCommentsCount,
			};
		}

		updateState(newState)
		{
			this.setState({
				newCommentsCount: newState.newCommentsCount,
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
							height: (this.state.newCommentsCount > 0 ? 56 : 0),
							justifyContent: 'center',
							paddingHorizontal: 8,
						},
						testId: 'newCommentsHint',
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
							Image({
								style: {
									width: 27,
									height: 24,
								},
								uri: this.getImageUrl(`${this.props.pathToImages}/tasksmobile-layout-task-new-comments.png`),
							}),
							Text({
								style: {
									fontSize: 16,
									fontWeight: '400',
									color: '#333333',
									marginLeft: 8,
								},
								text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_NEW_COMMENTS_HINT_TEXT') + ` - ${this.state.newCommentsCount}`,
							}),
						),
						this.renderRightArrow(),
					),
				),
			});
		}

		renderRightArrow()
		{
			return Image({
				style: {
					width: 24,
					height: 24,
					alignSelf: 'center',
				},
				svg: {
					content: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.16016 6.34368L12.6872 10.8707L13.8598 12.0001L12.6872 13.1301L8.16016 17.6572L9.75762 19.2547L17.0118 12.0005L9.75762 4.74634L8.16016 6.34368Z" fill="#D5D7DB"/></svg>',
				},
			});
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = encodeURI(imageUrl);
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return imageUrl;
		}
	}

	module.exports = {NewCommentsHint};
});