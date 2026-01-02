BX.namespace('BX.ImMobileNotify');

BX.ImMobileNotify = {
	renderAvatar: ({ userType, url, containerId }) => {
		const parent = document.getElementById(containerId);
		const defaultIcon = '/bitrix/js/im/images/blank.gif';
		const avatarUrl = url === defaultIcon ? null : url;

		switch (userType)
		{
			case 'collaber': {
				return BX.ImMobileNotify.renderCollaberAvatar(avatarUrl, parent);
			}

			case 'extranet': {
				return BX.ImMobileNotify.renderExtranetAvatar(avatarUrl, parent);
			}

			default: {
				return BX.ImMobileNotify.renderUserAvatar(avatarUrl, parent);
			}
		}
	},

	renderUserAvatar: (url, parent) => {
		const avatar = new BX.UI.AvatarRound({
			size: 38,
		});

		if (url)
		{
			avatar.setUserPic(url);
		}
		avatar.renderTo(parent);
	},

	renderCollaberAvatar: (url, parent) => {
		const avatar = new BX.UI.AvatarRoundGuest({
			size: 38,
		});

		if (url)
		{
			avatar.setUserPic(url);
		}
		avatar.renderTo(parent);
	},

	renderExtranetAvatar: (url, parent) => {
		const avatar = new BX.UI.AvatarRoundExtranet({
			size: 38,
		});

		if (url)
		{
			avatar.setUserPic(url);
		}
		avatar.renderTo(parent);
	},
};
