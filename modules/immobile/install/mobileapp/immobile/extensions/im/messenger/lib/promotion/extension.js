/**
 * @module im/messenger/lib/promotion
 */
jn.define('im/messenger/lib/promotion', (require, exports, module) => {

	const { Loc } = jn.require('loc');
	const { RestManager } = jn.require('im/messenger/lib/rest-manager');
	const { RestMethod } = jn.require('im/messenger/const');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { MessengerParams } = jn.require('im/messenger/lib/params');
	const { PromotionService } = jn.require('im/messenger/service');

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

			RestManager.once(RestMethod.imPromotionGet, { DEVICE_TYPE: 'mobile' }, this.handlePromotionGet.bind(this));
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
			PromotionService.read(id);
		}
	}

	module.exports = {
		Promotion: new Promotion(),
	};
});
