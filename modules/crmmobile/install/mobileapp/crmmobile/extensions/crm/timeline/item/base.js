/**
 * @module crm/timeline/item/base
 */
jn.define('crm/timeline/item/base', (require, exports, module) => {
	const { MarketBanner } = require('crm/timeline/item/ui/market-banner');
	const { TimelineItemHeader } = require('crm/timeline/item/ui/header');
	const { TimelineItemIcon } = require('crm/timeline/item/ui/icon');
	const { TimelineItemBody } = require('crm/timeline/item/ui/body');
	const { TimelineItemFooter } = require('crm/timeline/item/ui/footer');
	const { TimelineItemBackground } = require('crm/timeline/item/ui/styles');
	const { TimelineItemBackgroundLayer } = require('crm/timeline/item/ui/background');
	const { TimelineItemLoadingOverlay } = require('crm/timeline/item/ui/loading-overlay');

	const { get } = require('utils/object');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class TimelineItemBase
	 */
	class TimelineItemBase extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = Random.getString();

			/**
			 * @public
			 * @readonly
			 * @type {EventEmitter}
			 */
			this.itemScopeEventBus = EventEmitter.createWithUid(this.uid);

			/**
			 * @public
			 * @readonly
			 * @type {EventEmitter}
			 */
			this.timelineScopeEventBus = props.timelineScopeEventBus;

			/** @type {TimelineItemBackgroundLayer|null} */
			this.backgroundLayerRef = null;

			/** @type {TimelineItemLoadingOverlay|null} */
			this.loadingOverlayRef = null;

			this.containerRef = null;
		}

		/**
		 * @returns {TimelineItemModel}
		 */
		get model()
		{
			return this.props.model;
		}

		/**
		 * @returns {TimelineLayoutSchema|{}}
		 */
		get layoutSchema()
		{
			return this.model.layout;
		}

		get hasIcon()
		{
			const logo = get(this.layoutSchema, 'body.logo', null);

			return logo !== null;
		}

		get backgroundColor()
		{
			return TimelineItemBackground.getByModel(this.model);
		}

		get hasPlayer()
		{
			return false;
		}

		render()
		{
			return this.renderContainer(
				this.renderInnerContent(),
				this.model.needShowMarketBanner && MarketBanner({
					onClick: () => this.openMarketBannerInfo(),
					onClose: () => this.hideMarketBanner(),
				}),
			);
		}

		renderContainer(...children)
		{
			return View(
				{
					ref: (ref) => this.containerRef = ref,
					testId: `${this.model.type}_${this.model.id}`,
					style: {
						borderRadius: 12,
						padding: 0,
						marginBottom: 16,
						borderColor: '#dfe0e3',
						borderWidth: this.model.hasLowPriority ? 1 : 0,
					},
				},
				...children,
			);
		}

		renderInnerContent()
		{
			return View(
				{},
				new TimelineItemBackgroundLayer({
					ref: (ref) => this.backgroundLayerRef = ref,
					color: this.backgroundColor,
					opacity: this.model.hasLowPriority ? 0.4 : 1,
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					this.hasIcon && new TimelineItemIcon({
						logo: this.layoutSchema.body.logo,
						additionalIcon: this.layoutSchema.icon,
						counterType: this.layoutSchema.icon.counterType,
						onAction: this.onAction.bind(this),
						hasPlayer: this.hasPlayer,
						itemScopeEventBus: this.itemScopeEventBus,
					}),
					this.layoutSchema.header && new TimelineItemHeader({
						...this.layoutSchema.header,
						hasIcon: this.hasIcon,
						opacity: this.model.hasLowPriority ? 0.6 : 1,
						onAction: this.onAction.bind(this),
						useFriendlyDate: this.model.isScheduled || this.model.isPinned,
						isReadonly: this.model.isReadonly,
					}),
				),
				this.layoutSchema.body && new TimelineItemBody({
					...this.layoutSchema.body,
					onAction: this.onAction.bind(this),
					isReadonly: this.model.isReadonly,
					model: this.model,
					itemScopeEventBus: this.itemScopeEventBus,
					timelineScopeEventBus: this.timelineScopeEventBus,
					style: {
						paddingBottom: this.getBodyBottomGap(),
					},
				}),
				this.layoutSchema.footer && new TimelineItemFooter({
					...this.layoutSchema.footer,
					onAction: this.onAction.bind(this),
					isReadonly: this.model.isReadonly,
				}),
				new TimelineItemLoadingOverlay({
					ref: (ref) => this.loadingOverlayRef = ref,
				}),
			);
		}

		// @todo article will be added later
		openMarketBannerInfo()
		{
			// helpdesk.openHelpArticle('code');
		}

		hideMarketBanner()
		{
			if (this.props.onHideMarketBanner)
			{
				this.props.onHideMarketBanner();
			}
		}

		onAction(params = {})
		{
			if (this.props.onAction)
			{
				this.props.onAction({ ...params, source: this });
			}
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		blink()
		{
			return new Promise((resolve) => {
				if (this.isBlinkable())
				{
					this.backgroundLayerRef.blink().finally(resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		isBlinkable()
		{
			if (this.model.hasLowPriority)
			{
				return false;
			}
			return Boolean(this.backgroundLayerRef);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		fadeOut()
		{
			return new Promise((resolve) => {
				if (this.containerRef)
				{
					this.containerRef.animate({
						duration: 300,
						opacity: 0,
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		fadeIn()
		{
			return new Promise((resolve) => {
				if (this.containerRef)
				{
					this.containerRef.animate({
						duration: 300,
						opacity: 1,
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		/**
		 * @public
		 */
		showLoader()
		{
			if (this.loadingOverlayRef)
			{
				this.loadingOverlayRef.show();
			}
		}

		/**
		 * @public
		 */
		hideLoader()
		{
			if (this.loadingOverlayRef)
			{
				this.loadingOverlayRef.hide();
			}
		}

		/**
		 * @protected
		 * @return {number}
		 */
		getBodyBottomGap()
		{
			return 16;
		}
	}

	module.exports = { TimelineItemBase };
});
