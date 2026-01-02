/**
 * @module layout/ui/app-rating/src/strategies/support-chat-strategy
 */

jn.define('layout/ui/app-rating/src/strategies/support-chat-strategy', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { AppRatingAnalytics } = require('layout/ui/app-rating/src/analytics');
	const { Indent } = require('tokens');
	const { BaseAppRatingStrategy } = require('layout/ui/app-rating/src/strategies/strategy');

	class SupportChatStrategy extends BaseAppRatingStrategy
	{
		constructor(props)
		{
			super(props);

			this.supportChat = props?.botId ?? null;
			this.parentWidget = props?.parentWidget ?? null;
		}

		isApplicable(props)
		{
			return Boolean(props.botId);
		}

		execute(props)
		{
			this.supportChat = props.botId;
			this.parentWidget = props.parentWidget;

			return this;
		}

		getButtonText()
		{
			return Loc.getMessage('M_UI_APP_RATING_QUESTION_BUTTON_TEXT');
		}

		buttonHandler(triggerEvent, sendAnalytics)
		{
			if (this.supportChat && this.parentWidget)
			{
				this.parentWidget.close(() => {
					BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId: Number(this.supportChat) }], 'im.messenger');
				});
			}

			if (sendAnalytics)
			{
				AppRatingAnalytics.sendClickSupport({
					section: triggerEvent,
				});
			}
		}

		getDescription()
		{
			return Loc.getMessage('M_UI_APP_RATING_QUESTION_DESCRIPTION');
		}

		getSubtitle()
		{
			return Loc.getMessage('M_UI_APP_RATING_QUESTION_SUBTITLE');
		}

		/**
		 * @returns {boolean}
		 */
		isFilledButton()
		{
			return false;
		}

		renderContent()
		{
			return Image(
				{
					style: {
						width: 119,
						height: 119,
						alignSelf: 'center',
						marginTop: 38,
						marginBottom: Indent.XL2.toNumber(),
					},
					svg: {
						uri: makeLibraryImagePath('support.svg', 'graphic'),
					},
				},
			);
		}
	}

	module.exports = {
		SupportChatStrategy,
	};
});
