/**
 * @module ui-system/blocks/avatar/src/providers/redux
 */
jn.define('ui-system/blocks/avatar/src/providers/redux', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type-enum');

	const mapStateToProps = (state, props) => {
		const {
			id: userId,
			name: userName,
			uri: userUri,
			entityType,
		} = props;

		const {
			avatarSize100,
			isCollaber = false,
			isExtranet = false,
			fullName,
		} = usersSelector.selectById(state, Number(userId)) || {};

		let avatarEntityType = AvatarEntityType.USER;

		if (entityType)
		{
			avatarEntityType = AvatarEntityType.resolveType(entityType);
		}
		else
		{
			if (isExtranet)
			{
				avatarEntityType = AvatarEntityType.EXTRANET;
			}

			if (isCollaber)
			{
				avatarEntityType = AvatarEntityType.COLLAB;
			}
		}

		return {
			...props,
			entityType: avatarEntityType,
			uri: avatarSize100 || userUri,
			name: fullName || userName,
		};
	};

	module.exports = {
		reduxConnect: connect(mapStateToProps),
	};
});
