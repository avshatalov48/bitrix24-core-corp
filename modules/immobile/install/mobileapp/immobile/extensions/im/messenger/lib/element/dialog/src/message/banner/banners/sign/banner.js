/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/sign/banner
 */

jn.define('im/messenger/lib/element/dialog/message/banner/banners/sign/banner', (require, exports, module) => {
	const { Type } = require('type');
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { BannerMessageConfiguration } = require('im/messenger/lib/element/dialog/message/banner/configuration');
	const { MessageParams } = require('im/messenger/const');
	const { Theme } = require('im/lib/theme');
	const { transparent } = require('utils/color');

	class SignMessage extends BannerMessage
	{
		prepareTextMessage()
		{
			const description = this.replacePhrase(this.metaData.description);

			this.message[0].text = `[color=${Theme.colors.base3}]${description}[/color]`;
		}

		getMessageComponentParams()
		{
			return super.getComponentParams();
		}

		setBannerProp()
		{
			const { title, buttons, imageName } = this.metaData;

			this.banner = {
				title,
				imageName,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 0.2),
				shouldDisplayTime: true,
				buttons,
			};
		}

		/**
		 * @param {string} phrase
		 * @return {string}
		 */
		replacePhrase(phrase)
		{
			let text = phrase ?? '';
			const {
				user,
				helpArticle,
				initiator,
				document,
			} = this.getMessageComponentParams();

			const helpdeskUrl = `/immobile/in-app/helpdesk=${helpArticle}`;
			const startBlackText = `[/color][color=${Theme.colors.base1}]`;
			const endBlackText = `[/color][color=${Theme.colors.base3}]`;
			const startMention = (id) => `[/color][USER=${id}]`;
			const endMention = `[/USER][color=${Theme.colors.base3}]`;
			const startLink = (url) => `[/color][b][url=${url}]`;
			const endLink = `[/url][/b][color=${Theme.colors.base3}]`;

			const phrases = {
				'#DOCUMENT_NAME#': `${startBlackText}${document?.name ?? ''}${endBlackText}`,
				'#USER_LINK#': user ? `${startMention(user.id)}${user.name}${endMention}` : '',
				'#INITIATOR_LINK#': initiator ? `${startMention(initiator.id)}${initiator.name}${endMention}` : '',
				'[helpdesklink]': startLink(helpdeskUrl),
				'[/helpdesklink]': endLink,
			};

			Object.keys(phrases).forEach((code) => {
				text = text.replaceAll(code, phrases[code]);
			});

			return text;
		}

		static getComponentId()
		{
			return MessageParams.ComponentId.SignMessage;
		}

		get metaData()
		{
			const configuration = new BannerMessageConfiguration(this.id);
			const { stageId } = this.getMessageComponentParams();
			const data = configuration.getMetaData(SignMessage.getComponentId());

			return data[stageId].banner;
		}
	}

	module.exports = { SignMessage };
});
