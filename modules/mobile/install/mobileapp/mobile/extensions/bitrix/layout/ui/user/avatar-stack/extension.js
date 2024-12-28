/**
 * @module layout/ui/user/avatar-stack
 * */
jn.define('layout/ui/user/avatar-stack', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { AvatarStack: AvatarStackAir, AvatarStackDirection } = require('ui-system/blocks/avatar-stack');

	/**
	 * @deprecated
	 * @see ui-system/blocks/avatar-stack
	 *
	 * @class AvatarStack
	 */
	class AvatarStack extends LayoutComponent
	{
		render()
		{
			const { avatars, size } = this.props;

			return AvatarStackAir({
				testId: 'AVATAR_STACK',
				entities: avatars,
				size,
				offset: Indent.XL4,
				withRedux: true,
				showRest: false,
				direction: AvatarStackDirection.LEFT,
			});
		}
	}

	module.exports = { AvatarStack };
});
