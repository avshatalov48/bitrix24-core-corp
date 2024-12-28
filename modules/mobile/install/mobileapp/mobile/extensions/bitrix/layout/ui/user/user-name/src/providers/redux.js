/**
 * @module layout/ui/user/user-name/src/providers/redux
 */
jn.define('layout/ui/user/user-name/src/providers/redux', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { UserType } = require('layout/ui/user/user-name/src/enums/type-enum');

	const mapStateToProps = (state, props) => {
		const {
			id: userId,
			text: userName,
			color: userColor,
		} = props;

		const {
			isCollaber = false,
			isExtranet = false,
			fullName,
		} = usersSelector.selectById(state, Number(userId)) || {};

		let userEntityColor = userColor || UserType.USER;

		if (isExtranet)
		{
			userEntityColor = UserType.EXTRANET.getColor();
		}

		if (isCollaber)
		{
			userEntityColor = UserType.COLLAB.getColor();
		}

		return {
			text: fullName || userName,
			color: userEntityColor,
		};
	};

	module.exports = {
		reduxConnect: connect(mapStateToProps),
	};
});
