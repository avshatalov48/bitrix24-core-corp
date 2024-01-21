/**
 * @module layout/ui/detail-card/tabs
 */
jn.define('layout/ui/detail-card/tabs', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { TabLoaderFactory } = require('layout/ui/detail-card/tabs/loader-factory');
	const { PureComponent } = require('layout/pure-component');
	const { NotifyManager } = require('notify-manager');
	const { throttle } = require('utils/function');
	const { mergeImmutable } = require('utils/object');

	const Status = {
		INITIAL: 'INITIAL',
		FETCHING: 'FETCHING',
		DONE: 'DONE',
	};

	/**
	 * @abstract
	 * @class Tab
	 */
	class Tab extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.id = props.id;

			this.uid = props.uid || Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			const { result } = props;
			this.initializeState(result);

			this.active = false;

			this.scrollTop = throttle(this.scrollTop, 500, this);
			this.handleFloatingMenuAction = this.handleFloatingMenuAction.bind(this);

			this.customEventEmitter.on('DetailCard.FloatingMenu.Item::onAction', this.handleFloatingMenuAction);
		}

		componentWillReceiveProps(props)
		{
			const { result } = props;

			if (result !== undefined)
			{
				this.initializeState(result);
			}
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (this.isDoneStatus())
			{
				const { result } = this.state;

				if (this.props.onContentLoaded)
				{
					this.props.onContentLoaded(result);
				}
			}
		}

		initializeState(result)
		{
			if (result)
			{
				if (this.props.externalData)
				{
					result = mergeImmutable(result, this.props.externalData);
				}

				this.state = {
					status: Status.DONE,
					result,
				};
			}
			else
			{
				this.state = {
					status: Status.INITIAL,
					result: null,
				};
			}
		}

		get editor()
		{
			return this.props.editor;
		}

		getId()
		{
			return this.props.id;
		}

		/**
		 * @abstract
		 * @returns {String}
		 */
		getType()
		{
			return null;
		}

		/**
		 * @public
		 * @returns {*}
		 */
		getPayload()
		{
			return this.props.payload;
		}

		isInitialStatus()
		{
			return this.state.status === Status.INITIAL;
		}

		isFetchingStatus()
		{
			return this.state.status === Status.FETCHING;
		}

		isDoneStatus()
		{
			return this.state.status === Status.DONE;
		}

		/**
		 * @param {Boolean} active
		 * @returns {Tab}
		 */
		setActive(active)
		{
			this.active = active;

			return this;
		}

		isActive()
		{
			return this.active;
		}

		fetchIfNeeded(extraPayload = {})
		{
			if (this.state.status !== Status.INITIAL)
			{
				return;
			}

			this.fetch(extraPayload);
		}

		fetch(extraPayload = {})
		{
			if (this.props.endpoint)
			{
				this.customEventEmitter.emit('DetailCard::onSaveLock', [true]);

				this.setState({ status: Status.FETCHING }, () => {
					BX.ajax.runAction(
						this.props.endpoint,
						{
							json: {
								...this.props.payload,
								...extraPayload,
							},
						},
					)
						.then((response) => {
							const result = response.data;
							if (this.props.onFetchHandler)
							{
								this.props.onFetchHandler(result);
							}

							this.setResult(result);
						})
						.catch((response) => {
							if (this.props.onErrorHandler)
							{
								return this.props.onErrorHandler(response);
							}

							NotifyManager.showDefaultError();
							console.error(response);
						})
						.finally(() => {
							this.customEventEmitter.emit('DetailCard::onSaveLock', [false]);
						})
					;
				});
			}
			else
			{
				NotifyManager.showDefaultError();
				console.warn('Endpoint not found');
			}
		}

		/**
		 * @returns {Promise.<Object>}
		 */
		getData()
		{
			return Promise.resolve({});
		}

		/**
		 * @returns {Promise.<boolean|Array>}
		 */
		validate()
		{
			return Promise.resolve(true);
		}

		scrollTop(animate = true)
		{}

		refreshResult()
		{
			return new Promise((resolve) => {
				if (this.state.status !== Status.DONE)
				{
					resolve();

					return;
				}

				this.setState({}, resolve);
			});
		}

		setResult(result)
		{
			if (this.props.externalData)
			{
				result = mergeImmutable(result, this.props.externalData);
			}

			return new Promise((resolve) => {
				const state = {
					status: Status.DONE,
					result,
				};

				this.setState(state, resolve);
			});
		}

		render()
		{
			return View(
				{},
				...this.renderContent(),
			);
		}

		renderContent()
		{
			if (this.state.status === Status.INITIAL || this.state.status === Status.FETCHING)
			{
				return [this.renderLoader()];
			}

			if (this.state.status === Status.DONE)
			{
				return [
					this.renderCustomShimmer(),
					this.renderResult(),
				];
			}

			return [];
		}

		renderInitial()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgPrimary,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
			);
		}

		renderLoader()
		{
			const customShimmer = this.renderCustomShimmer();
			if (customShimmer)
			{
				return customShimmer;
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				new LoadingScreenComponent({ backgroundColor: AppTheme.colors.bgPrimary }),
			);
		}

		/**
		 * @returns {LayoutComponent|null}
		 */
		renderCustomShimmer()
		{
			return TabLoaderFactory.createLoader(this.getType(), this.getLoaderProps());
		}

		getLoaderProps()
		{
			return {
				animating: this.isFetchingStatus(),
			};
		}

		/**
		 * @abstract
		 * @returns {View}
		 */
		renderResult()
		{
			return null;
		}

		/**
		 * @return {FloatingMenuItem[]}
		 */
		getFloatingMenuItems()
		{
			return [];
		}

		/**
		 * @param {string} actionId
		 * @param {string} tabId
		 * @return void
		 */
		handleFloatingMenuAction({ actionId, tabId })
		{}
	}

	module.exports = { Tab };
});
