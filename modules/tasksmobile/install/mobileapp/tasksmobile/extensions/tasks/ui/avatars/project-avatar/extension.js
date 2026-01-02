/**
 * @module tasks/ui/avatars/project-avatar
 */
jn.define('tasks/ui/avatars/project-avatar', (require, exports, module) => {
	const { AvatarClass, AvatarEntityType, AvatarShape, AvatarAccentGradient } = require(
		'ui-system/blocks/avatar',
	);
	const { reduxConnect } = require('tasks/ui/avatars/project-avatar/src/providers/redux');
	const { projectSelectorDataProvider } = require('tasks/ui/avatars/project-avatar/src/providers/selector');

	/**
	 * @class ProjectAvatar
	 */
	class ProjectAvatar extends AvatarClass
	{
		static resolveEntitySelectorParams(params)
		{
			const {
				onUriLoadFailure,
				onAvatarClick,
				...restParams
			} = projectSelectorDataProvider(params);

			return restParams;
		}

		getStateConnector()
		{
			return reduxConnect;
		}
	}

	module.exports = {
		AvatarShape,
		AvatarAccentGradient,
		AvatarEntityType,
		ProjectAvatarClass: ProjectAvatar,
		ProjectAvatar: (props) => new ProjectAvatar(props),
	};
});
