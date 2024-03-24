/**
 * @module crm/payment-system/creation/actions/oauth
 */
jn.define('crm/payment-system/creation/actions/oauth', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BackdropHeader, CreateBannerImage } = require('layout/ui/banners');
	const { OAuthSession } = require('native/oauth');
	const { AnalyticsLabel } = require('analytics-label');
	const { ContextMenu } = require('layout/ui/context-menu');

	const EXTENSION_IMAGE_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/payment-system/creation/actions/oauth/images/`;

	/**
	 * @class Oauth
	 */
	class Oauth
	{
		constructor(props = {})
		{
			this.layout = null;
			this.helpArticleId = null;

			/**
			 *
			 * @type {String|null}
			 */
			this.context = null;

			/**
			 * @type {ContextMenu|null}
			 */
			this.menu = null;

			this.showMenuCustomSection = BX.prop.getBoolean(props, 'showMenuCustomSection', true);
		}

		/**
		 * @param data
		 * @returns {Promise<void>}
		 */
		run(data)
		{
			if (!data || data.done === true)
			{
				return Promise.resolve();
			}

			AnalyticsLabel.send({
				event: 'payment-system-oauth-menu-showed',
				context: this.context,
			});

			return new Promise((resolve, reject) => {
				const actions = data.items.map((item) => {
					return {
						title: item.title,
						data: {
							svgIcon: SvgIcons[item.id] || null,
						},
						onClickCallback: () => new Promise((menuClickResolve) => {
							AnalyticsLabel.send({
								event: 'payment-system-oauth-menu-item-clicked',
								context: this.context,
								menuItemId: item.id,
							});

							if (item.type === ActionTypes.oauth)
							{
								(new OAuthSession(item.params.url)).start()
									.then(({ url }) => {
										data.done = true;
										resolve();
									})
									.catch(({ error }) => {
										reject({
											errors: [
												{
													customData: {
														public: true,
													},
													message: item.params.error,
												},
											],
										});
									});
							}
							else if (item.type === ActionTypes.externalLink)
							{
								reject();
								Application.openUrl(item.params.url);
							}
							else
							{
								reject();
							}

							menuClickResolve({ closeMenu: false });
							this.menu.close();
						}),
					};
				});

				const menuProps = {
					testId: 'PaymentSystemOauthMenu',
					actions,
					params: {
						title: data.title,
						showCancelButton: true,
						showActionLoader: false,
						helpUrl: this.helpArticleId ? helpdesk.getArticleUrl(this.helpArticleId) : null,
					},
				};

				if (this.showMenuCustomSection)
				{
					menuProps.customSection = {
						layout: BackdropHeader({
							description: data.text,
							image: Oauth.createBannerImage('payments'),
						}),
						height: 136,
					};
				}

				this.menu = new ContextMenu(menuProps);

				this.menu.show(this.layout);
			});
		}

		/**
		 * @param {Object} layout
		 * @returns {Oauth}
		 */
		setLayout(layout)
		{
			this.layout = layout;

			return this;
		}

		/**
		 * @param {String} context
		 * @return Oauth
		 */
		setContext(context)
		{
			this.context = context;

			return this;
		}

		/**
		 * @param {String} helpArticleId
		 * @return Oauth
		 */
		setHelpArticleId(helpArticleId)
		{
			this.helpArticleId = helpArticleId;

			return this;
		}

		/**
		 * @param {string} imageName
		 * @return {View}
		 */
		static createBannerImage(imageName)
		{
			return CreateBannerImage({
				image: {
					svg: {
						uri: `${EXTENSION_IMAGE_PATH}/${AppTheme.id}/${imageName}.svg`,
					},
				},
			});
		}
	}

	const SvgIcons = {
		authorize: '<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.6102 15.7826C15.0562 15.6205 15.3037 15.1517 15.2122 14.686L14.8963 13.0768C14.8963 12.4053 14.0019 11.6382 12.2409 11.1912C11.6442 11.0278 11.077 10.7745 10.5596 10.4403C10.4464 10.3768 10.4636 9.79036 10.4636 9.79036L9.89639 9.70562C9.89639 9.65802 9.84789 8.95475 9.84789 8.95475C10.5265 8.7309 10.4567 7.41046 10.4567 7.41046C10.8877 7.64511 11.1684 6.60016 11.1684 6.60016C11.6781 5.14839 10.9145 5.23617 10.9145 5.23617C11.0481 4.34989 11.0481 3.44915 10.9145 2.56287C10.575 -0.377147 5.46373 0.420993 6.06979 1.38119C4.57596 1.11109 4.91682 4.44748 4.91682 4.44748L5.24084 5.31146C4.79173 5.5974 4.87993 5.92555 4.97844 6.29212C5.01952 6.44494 5.06238 6.60443 5.06886 6.77033C5.10016 7.6029 5.6192 7.43038 5.6192 7.43038C5.65119 8.8045 6.34152 8.98345 6.34152 8.98345C6.4712 9.84641 6.39037 9.69954 6.39037 9.69954L5.77605 9.77247C5.78436 9.96872 5.76807 10.1652 5.72755 10.3576C5.37062 10.5137 5.1521 10.638 4.93575 10.761C4.71426 10.8869 4.49503 11.0116 4.1319 11.1679C2.74504 11.7644 1.23779 12.5403 0.969838 13.5849C0.891502 13.8903 0.81478 14.3009 0.745425 14.729C0.672747 15.1776 0.922174 15.6168 1.34907 15.7728C3.21183 16.4533 5.31409 16.8566 7.54415 16.9044H8.44204C10.6614 16.8568 12.7541 16.4572 14.6102 15.7826Z" fill="#525C69"/></svg>',
		register: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 20.0317C16.4356 20.0317 20.0315 16.4359 20.0315 12.0002C20.0315 7.56456 16.4356 3.96875 12 3.96875C7.56434 3.96875 3.96854 7.56456 3.96854 12.0002C3.96854 16.4359 7.56434 20.0317 12 20.0317ZM12 18.2787C15.4675 18.2787 18.2785 15.4677 18.2785 12.0002C18.2785 8.53271 15.4675 5.72175 12 5.72175C8.5325 5.72175 5.72153 8.53271 5.72153 12.0002C5.72153 15.4677 8.5325 18.2787 12 18.2787ZM11 11.0002V8.00021H13V11.0002H16V13.0002H13V16.0002H11V13.0002H7.99999V11.0002H11Z" fill="#525C69"/></svg>',
	};

	const ActionTypes = {
		oauth: 'oauth',
		externalLink: 'externalLink',
	};

	module.exports = { Oauth };
});
