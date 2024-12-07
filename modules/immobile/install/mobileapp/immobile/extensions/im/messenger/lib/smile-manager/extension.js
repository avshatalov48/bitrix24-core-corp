/**
 * @module im/messenger/lib/smile-manager
 */
jn.define('im/messenger/lib/smile-manager', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { backgroundCache } = require('im/messenger/lib/background-cache');
	const { MessengerParams } = require('im/messenger/lib/params');

	const CACHE_VERSION = 1;
	const LAST_UPDATE_OPTION_NAME = 'SMILE_LAST_UPDATE_DATE';

	let instance = null;

	/**
	 * @class SmileManager
	 */
	class SmileManager
	{
		/**
		 * @return {SmileManager}
		 */
		static getInstance()
		{
			instance ??= new SmileManager();

			return instance;
		}

		static async init()
		{
			const smileManager = SmileManager.getInstance();

			if (smileManager.inited)
			{
				return;
			}

			try
			{
				await smileManager.initSmileList();
				await smileManager.loadAssets();

				smileManager.initComplete();
			}
			catch (error)
			{
				smileManager.initReject(error);
			}
		}

		constructor()
		{
			this.smileCollection = {};
			this.smilesUrl = new Set();
			this.pattern = '';
			this.inited = false;
			this.rejected = false;
			/**
			 * @private
			 * @type {Promise<SmileManager>}
			 */
			this.initPromise = new Promise((resolve, reject) => {
				this.initPromiseResolver = resolve;
				this.initPromiseRejected = reject;
			});

			const lastUpdate = MessengerParams.get(LAST_UPDATE_OPTION_NAME, 0);
			this.lastUpdateDate = Date.parse(lastUpdate) + CACHE_VERSION;

			this.repository = serviceLocator.get('core').getRepository();
		}

		getSmiles()
		{
			return this.smileCollection;
		}

		getPattern()
		{
			return this.pattern;
		}

		/**
		 * @private
		 * @return {Promise<void>}
		 */
		async initSmileList()
		{
			/**
			 * @type {Array<SmileRow>}
			 */
			let smileList = await this.fetchSmilesFromStorage();

			const shouldRequestFromServer = await this.shouldRequestFromServer(smileList);

			if (shouldRequestFromServer)
			{
				smileList = await this.fetchSmilesFromServer();

				void this.fillStorage(smileList);
			}

			this.smileCollection = this.prepareSmiles(smileList);
			this.pattern = this.preparePattern();
		}

		/**
		 *
		 * @param {Array<SmileRow>} smileList
		 * @return {Promise<boolean>}
		 */
		async shouldRequestFromServer(smileList)
		{
			if (smileList.length === 0)
			{
				return true;
			}

			const localLastUpdate = await this.repository.option.get(LAST_UPDATE_OPTION_NAME, 0);

			return Number(localLastUpdate) !== this.lastUpdateDate;
		}

		/**
		 * @private
		 * @return {Promise<Array<SmileRow>>}
		 */
		async fetchSmilesFromServer()
		{
			const result = await BX.rest.callMethod('smile.get', { FULL_TYPINGS: 'Y' });

			/**
			 * @type {SmileServerResult}
			 */
			const smileList = result.data().smiles;

			return smileList.map((smile) => {
				return {
					id: smile.id,
					setId: smile.setId,
					width: smile.width,
					height: smile.height,
					imageUrl: smile.image.replace(currentDomain, ''),
					typing: smile.typing,
					name: smile.name,
				};
			});
		}

		/**
		 * @private
		 * @return {Promise<Array<SmileRow>>}
		 */
		async fetchSmilesFromStorage()
		{
			return this.repository.smile.getSmiles();
		}

		/**
		 * @private
		 * @param {Array<SmileRow>} smileList
		 */
		async fillStorage(smileList)
		{
			await this.repository.smile.clear();
			await this.repository.option.set(LAST_UPDATE_OPTION_NAME, this.lastUpdateDate);

			return this.repository.smile.save(smileList);
		}

		/**
		 * @private
		 * @param {Array<SmileRow>} smileList
		 */
		prepareSmiles(smileList)
		{
			const result = {};
			for (const smile of smileList)
			{
				this.smilesUrl.add(smile.imageUrl);

				smile.typing
					.split(' ')
					.forEach((code) => {
						result[code] = {
							...smile,
							typing: code,
						};
					})
				;
			}


			return result;
		}

		preparePattern(smileCollection)
		{
			const sortedSmiles = Object.values(this.smileCollection).sort((a, b) => {
				return b.typing.localeCompare(a.typing);
			});
			const pattern = sortedSmiles.map((smile) => {
				return smile.typing.replaceAll(/[$()*+./?[\\\]^{|}-]/g, '\\$&');
			}).join('|');

			return pattern;
		}

		/**
		 * @return {Promise}
		 */
		async loadAssets()
		{
			if (!backgroundCache.isSupported)
			{
				return;
			}

			const smileUrls = [...this.smilesUrl.values()].map(url => currentDomain + url);
			// eslint-disable-next-line consistent-return
			await backgroundCache.downloadImages(smileUrls);
		}

		/**
		 * @private
		 */
		initComplete()
		{
			Logger.log('SmileManager init complete');

			this.inited = true;
			this.rejected = false;

			this.initPromiseResolver(this);
		}

		/**
		 * @private
		 */
		initReject(error)
		{
			Logger.error('SmileManager init error:', error);

			void this.repository.option.delete(LAST_UPDATE_OPTION_NAME);

			this.rejected = true;
			this.initPromiseRejected();
		}

		/**
		 * @return {Promise<SmileManager>}
		 */
		async ready()
		{
			if (this.inited)
			{
				return Promise.resolve(this);
			}

			if (this.rejected)
			{
				return Promise.reject();
			}

			return this.initPromise;
		}
	}

	module.exports = { SmileManager };
});
