/**
 * @module im/messenger/lib/promotion
 */
jn.define('im/messenger/lib/promotion', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Promo, PromoType, EventType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { PromotionRest } = require('im/messenger/provider/rest');
	const { ReleaseView } = require('im/messenger/lib/promotion/release-view');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');

	/**
	 * @class Promotion
	 */
	class Promotion
	{
		constructor()
		{
			this.promoCollection = {
				[Promo.immobileRelease2023]: {
					type: PromoType.widget,
					options: {},
				},
				[Promo.immobileVideo2020]: {
					type: PromoType.spotlight,
					options: {
						target: 'call_video',
						text: Loc.getMessage('IM_PROMO_VIDEO_01042020_MOBILE', { '#BR#': '\n' }),
					},
				},
			};

			this.activePromoList = [];
			this.currentActivePromo = '';
			this.messagerInitService = serviceLocator.get('messenger-init-service');
			this.bindMethods();
			this.subscribeInitMessengerEvent();

			this.onCloseWidget = this.onCloseWidget.bind(this);
		}

		bindMethods()
		{
			this.handlePromotionGet = this.handlePromotionGet.bind(this);
		}

		subscribeInitMessengerEvent()
		{
			this.messagerInitService.onInit(this.handlePromotionGet);
		}

		handlePromotionGet(data)
		{
			if (data?.promotion)
			{
				this.activePromoList = data.promotion;
			}
		}

		checkDialog(dialogId)
		{
			if (
				!dialogId.startsWith('chat')
				&& dialogId !== MessengerParams.getUserId().toString()
			)
			{
				this.show(Promo.immobileVideo2020);
			}
		}

		showSpotlight(options)
		{
			const spotlight = dialogs.createSpotlight();

			spotlight.setTarget(options.target);
			spotlight.setHint({
				text: options.text,
			});

			spotlight.show();
		}

		showWidget()
		{
			const langId = (Application.getLang() === 'ru' ? 'ru' : 'en');
			const url = sharedBundle.getVideo(`chat/newdialog_${langId}.mp4`);
			if (!url)
			{
				return;
			}

			const videoHeight = 554;
			PageManager.openWidget(
				'layout',
				{
					backdrop:
						{
							hideNavigationBar: true,
							shouldResizeContent: false,
							mediumPositionPercent: 93,
						},
				},
			).then(
				(widget) => {
					this.widget = widget;
					this.widgetReady();
					this.widget.showComponent(new ReleaseView({ widget, videoHeight, url }));
				},
			).catch((error) => {
				Logger.error('Promotion.error widget', error);
			});
		}

		widgetReady()
		{
			this.subscribeWidgetEvents();
		}

		subscribeWidgetEvents()
		{
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onCloseWidget);
		}

		onCloseWidget()
		{
			this.onReadPromo();
		}

		show(id)
		{
			if (!this.promoCollection[id] || !this.activePromoList.includes(id))
			{
				return false;
			}

			const promo = this.promoCollection[id];

			if (promo.type === PromoType.spotlight)
			{
				this.showSpotlight(promo.options);
			}

			if (promo.type === PromoType.widget)
			{
				this.currentActivePromo = id;
				this.showWidget();
			}
			Logger.info('Promotion.show', id);

			setTimeout(() => {
				this.onReadPromo(id);
			}, 5000);

			return true;
		}

		onReadPromo()
		{
			const currentPromoId = this.currentActivePromo;
			if (Type.isStringFilled(currentPromoId))
			{
				this.deleteActivePromo(currentPromoId);
				this.read(currentPromoId);
				this.currentActivePromo = '';
			}
		}

		deleteActivePromo(id)
		{
			this.activePromoList = this.activePromoList.filter((activePromoListId) => activePromoListId !== id);
		}

		read(id)
		{
			PromotionRest.read(id);
			Logger.info('Promotion.read', id);
		}
	}

	module.exports = {
		Promotion,
	};
});
