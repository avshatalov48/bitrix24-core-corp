/**
 * @module im/messenger/controller/sidebar-v2/ui/empty-screen
 */
jn.define('im/messenger/controller/sidebar-v2/ui/empty-screen', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { PropTypes } = require('utils/validation');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { makeLibraryImagePath } = require('im/messenger/assets');
	const { Color } = require('tokens');

	/**
	 * @class EmptyScreen
	 */
	class EmptyScreen extends PureComponent
	{
		render()
		{
			const items = [{
				type: 'default',
				key: 'default',
			}];

			return View(
				{
					style: {
						flex: 1,
					},
				},
				ListView({
					onScroll: this.props.onScroll,
					onOverscrollTop: this.props.onOverscrollTop,
					onOverscrollBottom: this.props.onOverscrollBottom,
					style: {
						flexDirection: 'column',
						flex: 1,
					},
					data: [{ items }],
					renderItem: () => this.renderInnerContent(),
				}),
			);
		}

		renderInnerContent()
		{
			const { testId, topOffset = 0, workingAreaHeight = 0 } = this.props;

			const height = workingAreaHeight > 0
				? workingAreaHeight - topOffset - 50 // optical hack
				: undefined;

			return View(
				{},
				StatusBlock({
					style: {
						flexGrow: 0,
						height,
					},
					testId: `${testId}-empty-screen`,
					image: this.getImage(),
					title: this.getTitle(),
					description: this.getDescription(),
					titleColor: Color.base3,
					descriptionColor: Color.base4,
				}),
			);
		}

		getTitle()
		{
			const { title } = this.props;

			return title;
		}

		getDescription()
		{
			const { description } = this.props;

			return description;
		}

		getImage()
		{
			const { image } = this.props;

			if (!image)
			{
				return null;
			}

			return Image({
				resizeMode: 'contain',
				style: {
					width: 108,
					height: 108,
				},
				svg: {
					uri: makeLibraryImagePath(image, 'sidebar/empty-state'),
				},
			});
		}
	}

	EmptyScreen.propTypes = {
		title: PropTypes.string,
		image: PropTypes.string,
		description: PropTypes.string,
	};

	module.exports = {
		EmptyScreen,
	};
});
