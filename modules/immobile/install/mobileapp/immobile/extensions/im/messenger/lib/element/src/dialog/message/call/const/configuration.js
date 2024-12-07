/**
 * @module im/messenger/lib/element/dialog/message/call/const/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/call/const/configuration', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { CallMessageType } = require('im/messenger/lib/element/dialog/message/call/const/type');
	const { Theme } = require('im/lib/theme');
	const { MessageHelper } = require('im/messenger/lib/helper');

	/**
	 * @type {CallMetaData}
	 */
	const metaData = {
		[CallMessageType.START]: {
			iconName: Icon.PHONE_UP.getIconName(),
			iconColors: (modelMessage) => getIconColors(modelMessage, CallMessageType.START),
			iconFallbackUrl: currentDomain + Icon.PHONE_UP.getPath(),
		},
		[CallMessageType.FINISH]: {
			iconName: Icon.PHONE_UP.getIconName(),
			iconColors: (modelMessage) => getIconColors(modelMessage, CallMessageType.FINISH),
			iconFallbackUrl: currentDomain + Icon.PHONE_UP.getPath(),
		},
		[CallMessageType.BUSY]: {
			iconName: Icon.PHONE_UP.getIconName(),
			iconColors: (modelMessage) => getIconColors(modelMessage, CallMessageType.BUSY),
			iconFallbackUrl: currentDomain + Icon.PHONE_UP.getPath(),
		},
		[CallMessageType.DECLINED]: {
			iconName: Icon.PHONE_UP.getIconName(),
			iconColors: (modelMessage) => getIconColors(modelMessage, CallMessageType.DECLINED),
			iconFallbackUrl: currentDomain + Icon.PHONE_UP.getPath(),
		},
		[CallMessageType.MISSED]: {
			iconName: Icon.PHONE_UP.getIconName(),
			iconColors: (modelMessage) => getIconColors(modelMessage, CallMessageType.MISSED),
			iconFallbackUrl: currentDomain + Icon.PHONE_UP.getPath(),
		},
	};

	/**
	 * @return {CallMessageIconColor}
	 */
	function getIconColors(modelMessage, messageType)
	{
		const isYourMessage = MessageHelper.createById(modelMessage.id)?.isYour;
		let iconColor = '';
		let iconBorderColor = '';

		switch (messageType)
		{
			case CallMessageType.START:
				if (isYourMessage)
				{
					iconColor = Theme.colors.chatMyBase1_2;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				else
				{
					iconColor = Theme.colors.base4;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				break;
			case CallMessageType.FINISH:
				if (isYourMessage)
				{
					iconColor = Theme.colors.chatMyPrimary1;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				else
				{
					iconColor = Theme.colors.accentMainPrimary;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				break;
			case CallMessageType.BUSY:
			case CallMessageType.DECLINED:
			case CallMessageType.MISSED:
				if (isYourMessage)
				{
					iconColor = Theme.colors.chatMyChannelAlert;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				else
				{
					iconColor = Theme.colors.accentMainAlert;
					iconBorderColor = Theme.colors.chatMyBase0_4;
				}
				break;
		}

		return { iconColor, iconBorderColor };
	}

	module.exports = {
		metaData,
	};
});
