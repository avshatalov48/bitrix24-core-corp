/**
 * @module sign/banner
 */
jn.define('sign/banner', (require, exports, module) => {
	const { StatusBlock, makeLibraryImagePath } = require('ui-system/blocks/status-block');

	/**
	 * @class Banner
	 */
	class Banner extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				imageName: props.imageName,
				title: props.title,
				description: props.description,
			};
		}

		render()
		{
			return View(
				{
					style: {
						paddingBottom: 100,
						height: '100%',
					},
				},
				StatusBlock({
					testId: 'flow-status-block',
					emptyScreen: false,
					image: Image({
						svg: {
							uri: makeLibraryImagePath(this.state.imageName, 'empty-states', 'sign'),
						},
						style: {
							width: 138,
							height: 138,
						},
					}),
					title: this.state.title,
					description: this.state.description,
				}),
			)
		}

		rerender(imageName, title, description)
		{
			this.setState({ imageName, title, description });
		}
	}

	module.exports = { Banner };
});
