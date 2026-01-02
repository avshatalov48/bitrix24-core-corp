/**
 * @module im/messenger/model/users/default-element
 */

jn.define('im/messenger/model/users/default-element', (require, exports, module) => {
	const { UserType, UserColor } = require('im/messenger/const');

	const userDefaultElement = Object.freeze({
		id: 0,
		name: '',
		firstName: '',
		lastName: '',
		avatar: '',
		color: UserColor.default,
		type: UserType.user,
		workPosition: '',
		gender: 'M',
		extranet: false,
		network: false,
		bot: false,
		botData: {},
		connector: false,
		externalAuthId: 'default',
		status: '',
		idle: false,
		lastActivityDate: false,
		mobileLastDate: false,
		isOnline: false,
		isMobileOnline: false,
		birthday: false,
		isBirthday: false,
		absent: false,
		isAbsent: false,
		departments: [],
		departmentName: '',
		phones: {
			workPhone: '',
			personalMobile: '',
			personalPhone: '',
			innerPhone: '',
		},
		isCompleteInfo: true,
	});

	module.exports = {
		userDefaultElement,
	};
});
