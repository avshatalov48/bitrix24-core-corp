/**
 * @module layout/ui/fields/project/theme/air/src/entity
 */
jn.define('layout/ui/fields/project/theme/air/src/entity', (require, exports, module) => {
	const { AirThemeEntity } = require('layout/ui/fields/entity-selector/theme/air');
	const { ProjectAvatarClass, ProjectAvatar } = require('tasks/ui/avatars/project-avatar');

	/**
	 * @class ProjectAirThemeEntity
	 */
	class ProjectAirThemeEntity extends AirThemeEntity
	{
		renderAvatar()
		{
			const { entityId, customData } = this.props;

			let avatarParams = this.getAvatarParams();

			if (customData || entityId)
			{
				avatarParams = {
					...avatarParams,
					...ProjectAvatarClass.resolveEntitySelectorParams(this.getSelectorEntityParams()),
				};
			}

			return ProjectAvatar(avatarParams);
		}
	}

	module.exports = {
		ProjectAirThemeEntity,
	};
});
