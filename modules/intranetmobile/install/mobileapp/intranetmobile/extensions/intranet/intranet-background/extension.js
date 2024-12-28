/**
 * @module intranet/background
 */
jn.define('intranet/background', (require, exports, module) => {
	class IntranetBackground
	{
		static init()
		{
			const { UserMiniProfile } = require('intranet/user-mini-profile');
			UserMiniProfile.init();
		}
	}

	module.exports = {
		IntranetBackground,
	};
});
