/**
 * @module intranet/background
 */
jn.define('intranet/background', (require, exports, module) => {
	const { isModuleInstalled } = require('module');

	class IntranetBackground
	{
		static init()
		{
			const { UserMiniProfile } = require('intranet/user-mini-profile');
			UserMiniProfile.init();

			if (env.isAdmin && isModuleInstalled('bitrix24'))
			{
				const { Qualification } = require('intranet/qualification');
				void Qualification.init();
			}
		}
	}

	module.exports = {
		IntranetBackground,
	};
});
