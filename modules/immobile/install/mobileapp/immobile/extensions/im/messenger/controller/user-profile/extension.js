/**
 * @module im/messenger/controller/user-profile
 */
jn.define('im/messenger/controller/user-profile', (require, exports, module) => {

	class UserProfile
	{
		static show(userId, options) {
			if (Application.getApiVersion() >= 27) {
				let url = "/mobile/mobile_component/user.profile/?version=1";

				if (availableComponents && availableComponents["user.profile"]) {
					url = availableComponents["user.profile"]["publicUrl"];
				}

				let backdropOptions = {};
				let isBackdrop = false;
				if (options.backdrop) {
					if (typeof options.backdrop === 'object' && options.backdrop) {
						backdropOptions = {backdrop: options.backdrop};
						isBackdrop = true;
					} else if (typeof options.backdrop === 'boolean' && options.backdrop) {
						backdropOptions = {backdrop: {}};
						isBackdrop = true;
					}
				}

				PageManager.openComponent("JSStackComponent",
					{
						scriptPath: url,
						params: {userId, isBackdrop},
						canOpenInDefault: true,
						rootWidget: {
							name: "list",
							groupStyle: true,
							settings: Object.assign({
								objectName: "form",
								groupStyle: true,
							}, backdropOptions)
						}
					});
			} else {
				PageManager.openPage({url: "/mobile/users/?user_id=" + userId});
			}

		}
	}

	module.exports = { UserProfile };
});