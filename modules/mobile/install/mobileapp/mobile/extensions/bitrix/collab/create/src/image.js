/**
 * @module collab/create/src/image
 */
jn.define('collab/create/src/image', (require, exports, module) => {
	const { Color } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Avatar, AvatarShape, AvatarAccentGradient } = require('ui-system/blocks/avatar');

	const CollabImage = ({ onClick, ref, url }) => {
		return Avatar({
			testId: 'COLLAB_IMAGE',
			size: 60,
			accent: true,
			onClick,
			forwardRef: ref,
			uri: url,
			accentGradient: AvatarAccentGradient.GREEN,
			shape: AvatarShape.HEXAGON,
			backgroundColor: Color.collabBgContent1,
			icon: IconView({
				testId: 'COLLAB_IMAGE_CAMERA',
				size: 40,
				color: Color.collabAccentPrimary,
				icon: Icon.CAMERA,
			}),
		});
	};

	module.exports = { CollabImage };
});
