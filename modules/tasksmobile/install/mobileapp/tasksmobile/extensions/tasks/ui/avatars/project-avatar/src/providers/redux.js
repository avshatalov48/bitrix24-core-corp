/**
 * @module tasks/ui/avatars/project-avatar/src/providers/redux
 */
jn.define('tasks/ui/avatars/project-avatar/src/providers/redux', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { AvatarEntityType, AvatarShape } = require('ui-system/blocks/avatar');

	const mapStateToProps = (state, props) => {
		const {
			id: projectId,
			name: projectName,
			uri: projectUri,
		} = props;

		let { entityType, shape } = props;

		const {
			avatarSize100,
			isCollab = false,
			isExtranet = false,
			name,
		} = selectGroupById(state, Number(projectId)) || {};

		if (entityType)
		{
			entityType = AvatarEntityType.resolveType(entityType);
		}
		else
		{
			if (isExtranet)
			{
				entityType = AvatarEntityType.EXTRANET;
			}

			if (isCollab)
			{
				entityType = AvatarEntityType.COLLAB;
				shape = AvatarShape.HEXAGON;
			}
		}

		return {
			...props,
			shape,
			entityType,
			uri: avatarSize100 || projectUri,
			name: name || projectName,
		};
	};

	module.exports = {
		reduxConnect: connect(mapStateToProps),
	};
});
