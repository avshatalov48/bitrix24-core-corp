/**
 * @module lists/element-creation-guide/stub
 */
jn.define('lists/element-creation-guide/stub', (require, exports, module) => {
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { mergeImmutable } = require('utils/object');

	class Stub extends EmptyScreen
	{
		constructor(props)
		{
			const defaultStyle = {};
			// eslint-disable-next-line no-param-reassign
			props.styles = mergeImmutable(defaultStyle, props.styles);

			const defaultImage = {
				uri: EmptyScreen.makeLibraryImagePath('workflows.png', 'lists'),
				style: { width: 148, height: 149 },
			};
			// eslint-disable-next-line no-param-reassign
			props.image = mergeImmutable(defaultImage, props.image);

			super(props);
		}
	}

	module.exports = { Stub };
});
