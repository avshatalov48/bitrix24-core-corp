/**
 * @module tasks/ui/avatars/project-avatar/src/providers/selector
 */
jn.define('tasks/ui/avatars/project-avatar/src/providers/selector', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { SelectorDataProviderClass, AvatarShape } = require('ui-system/blocks/avatar');
	const { AvatarEntityType } = require('ui-system/blocks/avatar');

	class ProjectSelectorDataProvider extends SelectorDataProviderClass
	{
		getReduxData({ id, withRedux })
		{
			if (!withRedux)
			{
				return {};
			}

			return selectGroupById(store.getState(), Number(id)) || {};
		}

		getAvatarEntityType()
		{
			if (this.isCollab())
			{
				return AvatarEntityType.COLLAB;
			}

			if (this.isExtranet())
			{
				return AvatarEntityType.EXTRANET;
			}

			return AvatarEntityType.GROUP;
		}

		isExtranet()
		{
			const { isExtranet } = this.getCustomData();

			return Boolean(isExtranet) || super.isExtranet();
		}

		isCollab()
		{
			const { isCollab } = this.data;
			const { isCollab: selectorIsCollab } = this.getCustomData();

			return Boolean(selectorIsCollab) || Boolean(isCollab);
		}

		getShape()
		{
			if (this.isCollab())
			{
				return AvatarShape.HEXAGON;
			}

			return null;
		}
	}

	module.exports = {
		projectSelectorDataProvider: (data) => (new ProjectSelectorDataProvider(data)).getParams(),
	};
});
