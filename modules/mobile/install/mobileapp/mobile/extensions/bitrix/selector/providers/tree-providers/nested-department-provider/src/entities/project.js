/**
* @module selector/providers/tree-providers/nested-department-provider/src/entities/project
*/
jn.define('selector/providers/tree-providers/nested-department-provider/src/entities/project', (require, exports, module) => {
	const { BaseEntity } = require('selector/providers/tree-providers/nested-department-provider/src/entities/base-entity');

	let ProjectAvatarClass = null;

	try
	{
		ProjectAvatarClass = require('tasks/ui/avatars/project-avatar').ProjectAvatarClass;
	}
	catch (e)
	{
		console.warn(e);
	}

	/**
	 * @class ProjectEntity
	 */
	class ProjectEntity extends BaseEntity
	{
		static getId()
		{
			return 'project';
		}

		prepareItemForDrawing(item, initialEntity)
		{
			const params = {
				...item,
				customData: initialEntity.customData,
				avatar: initialEntity.avatar,
			};

			const avatarParams = ProjectAvatarClass.resolveEntitySelectorParams({ ...params, withRedux: true });
			const avatar = ProjectAvatarClass.getAvatar(avatarParams)?.getAvatarNativeProps();

			return { ...item, avatar };
		}
	}

	module.exports = { ProjectEntity };
});
