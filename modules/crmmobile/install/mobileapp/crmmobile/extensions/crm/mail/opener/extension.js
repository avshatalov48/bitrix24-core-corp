/**
 * @module crm/mail/opener
 */
jn.define('crm/mail/opener', (require, exports, module) => {
	const { showEmailBanner } = require('communication/email-menu');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { mergeImmutable } = require('utils/object');
	const { fetchCanUseMail, getContactsPromise } = require('crm/mail/message/tools/connector');

	const CACHE_TTL = 60 * 60 * 4; // 4 hours
	const NOT_ACTIVE_ERROR = 'mail_not_active';

	let storage;
	let imMemoryIsActive = null;
	let inMemoryTtl = null;

	/**
	 * @class MailOpener
	 */
	class MailOpener
	{
		/**
		 * @public
		 * @param {Object} componentParams
		 * @param {?Object} componentParams.owner
		 * @param {?Number} componentParams.owner.ownerId
		 * @param {?String} componentParams.owner.ownerType
		 * @param {Object} widgetParams
		 * @param {Object} widgetParams.titleParams
		 * @param {String} widgetParams.titleParams.text
		 * @param {String} widgetParams.title
		 * @param parentWidget
		 */
		static openSend(
			componentParams,
			widgetParams = {},
			parentWidget = null,
		)
		{
			this.checkIfMailIsActive()
				.then(() => this.preloadInfo(componentParams))
				.then((preloadedParams = {}) => {
					componentParams = mergeImmutable(componentParams, preloadedParams);

					widgetParams = mergeImmutable(this.getModalWidgetParams(), widgetParams);
					widgetParams.titleParams = this.prepareTitleParams(componentParams, widgetParams.titleParams);

					ComponentHelper.openLayout(
						{
							name: 'crm:mail.messagesend',
							componentParams,
							widgetParams,
						},
						parentWidget,
					);
				})
				.catch((error) => {
					if (error === NOT_ACTIVE_ERROR)
					{
						showEmailBanner(parentWidget);
					}
					else
					{
						console.error(error);
					}
				})
			;
		}

		static checkIfMailIsActive()
		{
			let promise = Promise.resolve();

			if (this.cacheExpired())
			{
				promise = promise.then(() => this.loadIsActiveMail());
			}

			return promise.then(() => new Promise((resolve, reject) => {
				if (this.isActiveMail())
				{
					resolve();
				}
				else
				{
					reject(NOT_ACTIVE_ERROR);
				}
			}));
		}

		/**
		 * @internal
		 */
		static init()
		{
			if (this.cacheExpired())
			{
				// fake timeout to avoid affecting core queries
				setTimeout(() => this.loadIsActiveMail(), 100);
			}
		}

		static loadIsActiveMail()
		{
			return new Promise((resolve, reject) => {
				fetchCanUseMail()
					.then(({ data }) => {
						this.updateStorage(data);
						resolve();
					})
					.catch(reject)
				;
			});
		}

		/**
		 * @private
		 * @internal
		 *
		 * @return {KeyValueStorage}
		 */
		static getStorage()
		{
			if (!storage)
			{
				storage = Application.storageById(`crm/mail/opener/${env.languageId}`);
			}

			return storage;
		}

		/**
		 * @private
		 * @internal
		 */
		static updateStorage(isActive)
		{
			this.setIsActiveMail(isActive);
			this.setTtlValue(this.getCurrentTimeInSeconds());
		}

		/**
		 * @public
		 * @internal
		 */
		static isActiveMail()
		{
			if (imMemoryIsActive === null)
			{
				imMemoryIsActive = this.getStorage().getBoolean('isActive');
			}

			return imMemoryIsActive;
		}

		/**
		 * @private
		 * @internal
		 */
		static setIsActiveMail(isActive)
		{
			imMemoryIsActive = Boolean(isActive);

			return this.getStorage().setBoolean('isActive', imMemoryIsActive);
		}

		/**
		 * @private
		 * @internal
		 */
		static getTtlValue()
		{
			if (inMemoryTtl === null)
			{
				inMemoryTtl = this.getStorage().getNumber('ttl', 0);
			}

			return inMemoryTtl;
		}

		/**
		 * @private
		 * @internal
		 */
		static setTtlValue(ttl)
		{
			inMemoryTtl = ttl;

			return this.getStorage().setNumber('ttl', ttl);
		}

		/**
		 * @private
		 * @internal
		 */
		static cacheExpired(ttl = CACHE_TTL)
		{
			const cacheTime = this.getTtlValue();
			const currentTime = this.getCurrentTimeInSeconds();

			return currentTime > cacheTime + ttl;
		}

		/**
		 * @private
		 * @internal
		 */
		static getCurrentTimeInSeconds()
		{
			return Math.floor(Date.now() / 1000);
		}

		/**
		 * @private
		 * @param {Object} componentParams
		 * @return {Promise|*}
		 */
		static preloadInfo(componentParams)
		{
			const {
				owner: {
					ownerId,
					ownerType,
				} = {},
				uploadClients = true,
				uploadSenders = true,
			} = componentParams;

			if (ownerId && ownerType)
			{
				NotifyManager.showLoadingIndicator();

				return getContactsPromise(ownerId, ownerType, uploadClients, uploadSenders)
					.then(({ data }) => {
						const preloadInfo = {};

						if (Array.isArray(data.clients))
						{
							preloadInfo.clients = data.clients;
						}

						if (Array.isArray(data.senders))
						{
							preloadInfo.senders = data.senders;
						}

						return preloadInfo;
					})
					.catch(console.error)
					.finally(() => NotifyManager.hideLoadingIndicatorWithoutFallback());
			}

			return Promise.resolve();
		}

		/**
		 * @private
		 * @internal
		 */
		static getModalWidgetParams()
		{
			return {
				modal: true,
				leftButtons: [{
					// type: 'cross',
					svg: {
						content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
					},
					isCloseButton: true,
				}],
			};
		}

		/**
		 * @private
		 * @internal
		 */
		static prepareTitleParams(componentParams, titleParams = {})
		{
			const defaultTitleParams = {
				useLargeTitleMode: false,
				detailTextColor: '#a8adb4',
				text: Loc.getMessage('MCRM_MAIL_OPENER_TITLE_NEW'),
			};

			return mergeImmutable(defaultTitleParams, titleParams);
		}
	}

	MailOpener.init();

	module.exports = { MailOpener };
});
