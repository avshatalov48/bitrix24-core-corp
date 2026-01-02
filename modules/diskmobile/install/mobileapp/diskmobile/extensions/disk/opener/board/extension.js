/**
 * @module disk/opener/board
 */
jn.define('disk/opener/board', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class BoardOpener
	 */
	class BoardOpener
	{
		#props = {};
		#boardWidget = null;

		/**
		 * @typedef BoardOpenerProps
		 * @property props
		 * @property props.id {string|number}
		 * @property [props.title] {string}
		 * @property [props.parentWidget] {Object}
		 * @property [props.analytics] {Object}
		 * @property [props.canOpenInDefault] {boolean}
		 * @property [props.isAttached] {boolean}
		 *
		 * @param {BoardOpenerProps} props
		 */
		constructor(props)
		{
			this.#props = props || {};

			BX.addCustomEvent('onBoardUpdate', this.#onBoardUpdate);
		}

		get #boardId()
		{
			const { id, fileData } = this.#props;

			return id || fileData?.id;
		}

		get #boardName()
		{
			const { title, fileData } = this.#props;

			return title || fileData?.name;
		}

		/**
		 * @returns {Promise<LayoutWidget>}
		 */
		async open()
		{
			try
			{
				if (!this.#boardId)
				{
					return Promise.reject(new Error('boardId is required'));
				}

				const { canOpenInDefault } = this.#props;
				const boardWidgetOpener = canOpenInDefault
					? this.#openComponent
					: this.#openBoardWidget;

				this.#sendAnalytics();

				this.#boardWidget = await boardWidgetOpener();


				return this.#boardWidget;
			}
			catch (error)
			{
				console.error(error);

				return Promise.reject(error);
			}
		}

		/**
		 * @returns {Promise<LayoutWidget>}
		 */
		#openBoardWidget = async () => {
			const { parentWidget } = this.#props;
			const parentManager = parentWidget || PageManager;

			return parentManager.openWidget('web', this.#getBoardOpenSettings()).catch(console.error);
		};

		#openComponent = () => {
			return PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: true,
				rootWidget: {
					name: 'web',
					settings: this.#getBoardOpenSettings(),
				},
			});
		};

		#getBoardOpenSettings()
		{
			return {
				modal: true,
				url: this.getBoardUrl(),
				titleParams: {
					text: this.#prepareTitle(this.#boardName),
				},
			};
		}

		getBoardUrl()
		{
			const { attachedId, uniqueCode, versionId } = this.#props;
			const params = uniqueCode
				? {
					versionId,
					uniqueCode,
					attachedId,
				}
				: {
					boardId: this.#boardId,
					attached: this.#isAttached() ? 'Y' : 'N',
				};

			const webAdditionalParams = {
				variant: 'mobile',
				no_redirect: 'Y',
			};

			return this.#buildUrl('/mobile/disk/board.php', { ...params, ...webAdditionalParams });
		}

		#buildUrl(path, params)
		{
			const query = Object.entries(params)
				.map(([key, value]) => (key && value ? `${key}=${value}` : null))
				.filter(Boolean)
				.join('&');

			return `${path}?${query}`;
		}

		#sendAnalytics()
		{
			const { analytics } = this.#props;

			if (isEmpty(analytics))
			{
				return;
			}

			const analyticsEvent = new AnalyticsEvent()
				.setEvent('open')
				.setCategory('boards')
				.setTool('boards');

			if (analytics.moduleId)
			{
				analyticsEvent.setElement(`docs_attache_${analytics.moduleId}`);
			}

			analyticsEvent.send();
		}

		#onBoardUpdate = (data) => {
			if (!this.#boardWidget)
			{
				return;
			}

			if (data.boardName)
			{
				this.#boardWidget.setTitle?.({
					text: this.#prepareTitle(data.boardName),
				});
			}
		};

		#prepareTitle(title)
		{
			if (typeof title !== 'string')
			{
				return '';
			}

			return title.replace(/\.board$/, '');
		}

		#isAttached()
		{
			const { isAttached, attachedId } = this.#props;

			return isAttached || attachedId > 0;
		}
	}

	module.exports = {
		/**
		 * @param {BoardOpenerProps} props
		 */
		boardOpener: (props) => (new BoardOpener(props)).open(),
	};
});
