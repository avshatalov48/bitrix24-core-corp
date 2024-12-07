/**
 * @module im/messenger/controller/dialog/lib/attach-manager
 */
jn.define('im/messenger/controller/dialog/lib/attach-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { inAppUrl } = require('in-app-url');
	const {
		EventType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dialog--attach-manager');

	/**
	 * @class AttachManager
	 */
	class AttachManager
	{
		#serviceLocator;
		#dialogLocator;

		/**
		 * @param {IServiceLocator<MessengerLocatorServices>} serviceLocator
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 */
		constructor(serviceLocator, dialogLocator)
		{
			/**
			 * @private
			 * @type {IServiceLocator<MessengerLocatorServices>}
			 */
			this.#serviceLocator = serviceLocator;
			/**
			 * @private
			 * @type {IServiceLocator<DialogLocatorServices>}
			 */
			this.#dialogLocator = dialogLocator;

			this.#bindMethods();
			this.#subscribeEvents();
		}

		get #store()
		{
			return this.#serviceLocator.get('core').getStore();
		}

		/**
		 * @return {MessageService|null}
		 */
		get #messageService()
		{
			const messageService = this.#dialogLocator.get('message-service');
			if (messageService)
			{
				return messageService;
			}

			this.#logError('messageService is not initialized.');

			return null;
		}

		#log(...message)
		{
			logger.log(`${this.constructor.name}.`, ...message);
		}

		#logError(...message)
		{
			logger.error(`${this.constructor.name}.`, ...message);
		}

		/**
		 * @private
		 * @return {void}
		 */
		#bindMethods()
		{
			this.userTapHandler = this.userTapHandler.bind(this);
			this.urlTapHandler = this.urlTapHandler.bind(this);
			this.imageTapHandler = this.imageTapHandler.bind(this);
			this.fileTapHandler = this.fileTapHandler.bind(this);
			this.richPreviewTapHandler = this.richPreviewTapHandler.bind(this);
			this.richNameTapHandler = this.richNameTapHandler.bind(this);
			this.richCancelTapHandler = this.richCancelTapHandler.bind(this);
		}

		/**
		 * @private
		 * @return {void}
		 */
		#subscribeEvents()
		{
			this.#dialogLocator.get('view')
				.on(EventType.dialog.messageAttachUserTap, this.userTapHandler)
				.on(EventType.dialog.messageAttachUrlTap, this.urlTapHandler)
				.on(EventType.dialog.messageAttachImageTap, this.imageTapHandler)
				.on(EventType.dialog.messageAttachFileTap, this.fileTapHandler)
				.on(EventType.dialog.richNameTap, this.richNameTapHandler)
				.on(EventType.dialog.richPreviewTap, this.richPreviewTapHandler)
				.on(EventType.dialog.richCancelTap, this.richCancelTapHandler)
			;
		}

		/**
		 * @param {AttachUserItemConfig} user
		 */
		userTapHandler(user)
		{
			this.#log('userTapHandler', user);
			if (Type.isStringFilled(user.link))
			{
				inAppUrl.open(user.link);
			}
		}

		/**
		 * @param {AttachLinkItemConfig} url
		 */
		urlTapHandler(url)
		{
			this.#log('urlTapHandler', url);
			if (Type.isStringFilled(url.link))
			{
				inAppUrl.open(url.link);
			}
		}

		/**
		 * @param {AttachImageItemConfig} image
		 */
		imageTapHandler(image)
		{
			this.#log('imageTapHandler', image);
			if (Type.isStringFilled(image.link))
			{
				viewer.openImage(image.link, image.name ?? '');
			}
		}

		/**
		 * @param {AttachFileItemConfig} file
		 */
		fileTapHandler(file)
		{
			this.#log('fileTapHandler', file);
			if (Type.isStringFilled(file.link))
			{
				viewer.openDocument(file.link, file.name ?? '');
			}
		}

		/**
		 * @param {string} link
		 * @param {string} messageId
		 */
		richPreviewTapHandler(link, messageId)
		{
			this.#log('richPreviewTapHandler', link, messageId);
			inAppUrl.open(link);
		}

		/**
		 * @param {string} link
		 * @param {string} messageId
		 */
		richNameTapHandler(link, messageId)
		{
			this.#log('richNameTapHandler', link, messageId);
			inAppUrl.open(link);
		}

		/**
		 * @param {string} messageId
		 */
		richCancelTapHandler(messageId)
		{
			this.#log('richCancelTapHandler', messageId);
			const message = this.#store.getters['messagesModel/getById'](messageId);
			if (message.richLinkId)
			{
				this.#messageService.deleteRichLink(messageId, message.richLinkId);
			}
		}
	}

	module.exports = { AttachManager };
});
