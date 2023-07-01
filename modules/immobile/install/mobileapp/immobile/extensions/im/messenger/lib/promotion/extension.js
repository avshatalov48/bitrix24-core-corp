/**
 * @module im/messenger/lib/promotion
 */
jn.define('im/messenger/lib/promotion', (require, exports, module) => {

	const { Loc } = require('loc');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { PromotionRest } = require('im/messenger/provider/rest');

	/**
	 * @class Promotion
	 */
	class Promotion
	{
		constructor()
		{
			this.promoCollection = {
				'im:video:01042020:mobile': {
					type: 'spotlight',
					options: {
						target: 'call_video',
						text: Loc.getMessage('IM_PROMO_VIDEO_01042020_MOBILE', { '#BR#': '\n' }),
					},
				},
			};

			this.activePromoList = [];

			restManager.once(RestMethod.imPromotionGet, { DEVICE_TYPE: 'mobile' }, this.handlePromotionGet.bind(this));
		}

		handlePromotionGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Promotion.handlePromotionGet', error);

				return;
			}

			Logger.info('Promotion.handlePromotionGet', response.data());

			this.activePromoList = response.data();
		}

		checkDialog(dialogId)
		{
			if (
				!dialogId.startsWith('chat')
				&& dialogId !== MessengerParams.getUserId().toString()
			)
			{
				this.show('im:video:01042020:mobile');
			}
		}

		showSpotlight(options)
		{
			const spotlight = dialogs.createSpotlight();

			spotlight.setTarget(options.target);
			spotlight.setHint({
				text: options.text
			});

			spotlight.show();
		}

		show(id)
		{
			if (!this.promoCollection[id] || !this.activePromoList.includes(id))
			{
				return false;
			}

			const promo = this.promoCollection[id];

			if (promo.type === 'spotlight')
			{
				this.showSpotlight(promo.options);
			}

			this.deleteActivePromo(id);
			this.read(id);

			Logger.info('Promotion.show', id);

			return true;
		}

		deleteActivePromo(id)
		{
			this.activePromoList = this.activePromoList.filter(activePromoListId => activePromoListId !== id);
		}

		read(id)
		{
			PromotionRest.read(id);
		}
	}

	module.exports = {
		Promotion: new Promotion(),
	};
});
