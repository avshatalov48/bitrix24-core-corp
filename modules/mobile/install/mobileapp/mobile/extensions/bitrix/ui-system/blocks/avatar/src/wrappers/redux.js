/**
 * @module ui-system/blocks/avatar/src/wrappers/redux
 */
jn.define('ui-system/blocks/avatar/src/wrappers/redux', (require, exports, module) => {
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { connect } = require('statemanager/redux/connect');

	const mapStateToProps = (state, props) => {
		const {
			id: userId,
			name: userName,
			uri: userUri,
		} = props;
		const { name, lastName, avatarSize100 } = usersSelector.selectById(state, Number(userId)) || {};
		const fullName = [name, lastName].filter(Boolean).join(' ');

		return {
			...props,
			uri: avatarSize100 || userUri,
			name: fullName || userName,
		};
	};

	module.exports = {
		reduxConnect: connect(mapStateToProps),
	};
});
