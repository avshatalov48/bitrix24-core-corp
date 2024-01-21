/**
 * @module im/messenger/lib/element/recent/item/call
 */
jn.define('im/messenger/lib/element/recent/item/call', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	/**
	 * @class CallItem
	 */
	class CallItem
	{
		constructor(callStatus, call)
		{
			let itemConfig;

			switch (callStatus)
			{
				case 'local':
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_OPEN'),
						color: AppTheme.colors.base2,
						background: AppTheme.colors.accentBrandGreen,
						canJoin: true,
					};
					break;

				case 'none':
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_JOIN'),
						color: AppTheme.colors.base2,
						background: AppTheme.colors.accentBrandGreen,
						canJoin: true,
					};
					break;

				default:
					itemConfig = {
						text: Loc.getMessage('IMMOBILE_ELEMENT_RECENT_CALL_STATUS_REMOTE'),
						color: AppTheme.colors.base1,
						background: AppTheme.colors.accentSoftGreen2,
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
			this.color = AppTheme.colors.accentSoftGreen2;
			this.useColor = true;
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
