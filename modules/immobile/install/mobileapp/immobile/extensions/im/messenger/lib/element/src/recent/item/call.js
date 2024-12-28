/**
 * @module im/messenger/lib/element/recent/item/call
 */
jn.define('im/messenger/lib/element/recent/item/call', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	const { Theme } = require('im/lib/theme');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class CallItem
	 */
	class CallItem
	{
		constructor(callStatus, call)
		{
			const dialogId = call.associatedEntity.id;
			const store = serviceLocator.get('core').getStore();
			const recentItem = store.getters['dialoguesModel/getById'](dialogId);

			let itemConfig;
			switch (callStatus)
			{
				case 'local':
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_OPEN'),
						color: Theme.colors.baseWhiteFixed,
						background: Theme.colors.accentMainPrimaryalt,
						canJoin: true,
					};
					break;

				case 'none':
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_JOIN'),
						color: Theme.colors.baseWhiteFixed,
						background: Theme.colors.accentMainPrimaryalt,
						canJoin: true,
					};
					break;

				default:
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_REMOTE'),
						color: Theme.colors.accentMainPrimaryalt,
						border: {
							color: Theme.colors.accentMainPrimaryalt,
							width: 1,
						},
						cornerRadius: 32,
						canJoin: false,
					};
					break;
			}

			this.id = `call${call.id}`;
			this.title = call.associatedEntity.name;
			this.subtitle = itemConfig.text;
			this.imageUrl = this.prepareAvatarUrl(call.associatedEntity.avatar);
			this.useLetterImage = true;
			this.unselectable = true;

			if (recentItem && Type.isStringFilled(recentItem.color))
			{
				this.color = recentItem.color;
			}
			else
			{
				this.color = Theme.colors.accentSoftGreen3;
			}
			this.useColor = true;

			this.backgroundColor = Theme.colors.accentSoftBlue2;
			this.useBackgroundColor = true;
			this.sectionCode = 'call';

			this.params = {
				call: {
					id: call.id,
					provider: call.provider,
					associatedEntity: call.associatedEntity,
				},
				isLocal: callStatus === 'local',
				canJoin: itemConfig.canJoin,
				type: 'call',
			};

			this.styles = {
				title: {
					image: {
						name: 'status_call',
						sizeMultiplier: 1.4,
					},
					font: {
						fontStyle: 'semibold',
					},
				},
				subtitle: {
					font: {
						size: '13',
						fontStyle: 'medium',
						color: itemConfig.color,
						useColor: true,
					},
					singleLine: true,
					cornerRadius: 12,
					backgroundColor: itemConfig.background,
					padding: {
						top: 3.5,
						right: 12,
						bottom: 3.5,
						left: 12,
					},
				},
			};

			if (Type.isObject(itemConfig.border))
			{
				this.styles.subtitle.border = itemConfig.border;
			}
		}

		prepareAvatarUrl(url)
		{
			if (!url || url.includes('/bitrix/js/im/images/blank.gif'))
			{
				return '';
			}

			// eslint-disable-next-line no-param-reassign
			url = url.indexOf('http') === 0 ? url : currentDomain + url;

			return encodeURI(url);
		}
	}

	module.exports = {
		CallItem,
	};
});
