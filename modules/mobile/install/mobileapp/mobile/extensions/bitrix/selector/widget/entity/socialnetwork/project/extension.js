/**
 * @module selector/widget/entity/socialnetwork/project
 */
jn.define('selector/widget/entity/socialnetwork/project', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseSelectorEntity } = require('selector/widget/entity');

	let ProjectAvatarClass = null;

	try
	{
		ProjectAvatarClass = require('tasks/ui/avatars/project-avatar').ProjectAvatarClass;
	}
	catch (e)
	{
		console.warn(e);
	}

	class ProjectSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'project';
		}

		static getContext()
		{
			return 'mobile-project';
		}

		static prepareItemForDrawing(item)
		{
			if (!item.id || !ProjectAvatarClass?.isNativeSupported())
			{
				return item;
			}

			const avatarParams = ProjectAvatarClass.resolveEntitySelectorParams({ ...item, withRedux: true });
			const avatar = ProjectAvatarClass.getAvatar(avatarParams)?.getAvatarNativeProps();

			return { ...item, avatar };
		}

		static getStartTypingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PROJECT');
		}

		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PICK_PROJECT');
		}
	}

	module.exports = { ProjectSelector };
});
