/**
 * @module user-profile/common-tab/src/block/header/block
 */
jn.define('user-profile/common-tab/src/block/header/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { Header } = require('user-profile/common-tab/src/block/header/view');
	const { UserStatus } = require('user-profile/common-tab/enum/user-status');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { dispatch } = require('statemanager/redux/store');

	class HeaderBlock extends BaseBlock
	{
		prepareProps(commonTabData)
		{
			const { owner, ownerId, statusData = {}, isEditMode, onChange } = commonTabData ?? {};

			if (owner)
			{
				dispatch(usersUpserted([owner]));
			}

			return {
				ownerId,
				isEditMode,
				onChange,
				GMTString: statusData.GMTString,
				lastSeenDate: statusData.lastSeenDate,
				personalGender: owner?.personalGender,
				onVacationDateTo: statusData.onVacationDateTo,
				status: UserStatus.getEnum(statusData?.status),
			};
		}

		shouldUseBaseViewWrapper()
		{
			return false;
		}

		getContentClass()
		{
			return Header;
		}
	}

	module.exports = {
		HeaderBlock,
	};
});
