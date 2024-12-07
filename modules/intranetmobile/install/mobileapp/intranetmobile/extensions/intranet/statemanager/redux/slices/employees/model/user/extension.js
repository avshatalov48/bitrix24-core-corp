/**
 * @module intranet/statemanager/redux/slices/employees/model/user
 */
jn.define('intranet/statemanager/redux/slices/employees/model/user', (require, exports, module) => {
	const { Type } = require('type');
	const { RequestStatus } = require('intranet/enum');

	class IntranetUserModel
	{
		/**
		 * Method maps fields from API responses of "intranetmobile" module to redux store.
		 *
		 * @public
		 * @param {object} sourceServerUser
		 * @returns {IntranetUserReduxModel}
		 */
		static prepareReduxUserFromServerUser(sourceServerUser)
		{
			const preparedUser = { ...sourceServerUser };

			if (Type.isUndefined(preparedUser.id))
			{
				throw new TypeError(`id for user ${JSON.stringify(preparedUser)} is not defined`);
			}

			preparedUser.id = Number(preparedUser.id);

			if (!Type.isObject(preparedUser.installedApps) || Type.isNil(preparedUser.installedApps))
			{
				preparedUser.installedApps = {};
			}

			preparedUser.isWindowsAppInstalled = Boolean(preparedUser.installedApps.windows);
			preparedUser.isLinuxAppInstalled = Boolean(preparedUser.installedApps.linux);
			preparedUser.isMacAppInstalled = Boolean(preparedUser.installedApps.mac);
			preparedUser.isIosAppInstalled = Boolean(preparedUser.installedApps.ios);
			preparedUser.isAndroidAppInstalled = Boolean(preparedUser.installedApps.android);

			preparedUser.requestStatus = RequestStatus.IDLE.getValue();

			preparedUser.dateRegister = Number(preparedUser.dateRegister);

			return preparedUser;
		}
	}

	module.exports = { IntranetUserModel };
});
