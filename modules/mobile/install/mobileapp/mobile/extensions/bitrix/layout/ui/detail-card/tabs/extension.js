/**
 * @module layout/ui/detail-card/tabs
 */
jn.define('layout/ui/detail-card/tabs', (require, exports, module) => {

	const { animate } = require('animation/effects/skeleton');
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

			this.layout = props.layout || layout;
			this.active = false;

			this.loadingAnimation = null;

			this.scrollTop = throttle(this.scrollTop, 500, this);
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
			if (this.inDoneStatus())
			{
				const { result } = this.state;

				if (this.props.onContentLoaded)
				{
					this.props.onContentLoaded(result);
				}

				clearInterval(this.loadingAnimation);
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

		inInitialStatus()
		{
			return this.state.status === Status.INITIAL;
		}

		inFetchingStatus()
		{
			return this.state.status === Status.FETCHING;
		}

		inDoneStatus()
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

		fetch(extraPayload = {})
		{
			if (this.state.status !== Status.INITIAL)
			{
				return;
			}

			if (this.props.endpoint)
			{
				this.customEventEmitter.emit('DetailCard::onSaveLock', [true]);

				this.setState({ status: Status.FETCHING }, () => {
					if (this.loaderRef)
					{
						clearInterval(this.loadingAnimation);
						this.loadingAnimation = animate(this.loaderRef);
					}

					BX.ajax.runAction(this.props.endpoint, {
						json: {
							...this.props.payload,
							...extraPayload,
						},
					})
						.then((response) => this.setResult(response.data))
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
		{
		}

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
			else if (this.state.status === Status.DONE)
			{
				return [
					this.renderCustomLoader(),
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
						backgroundColor: '#eef2f4',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
			);
		}

		renderLoader()
		{
			const customLoader = this.renderCustomLoader();
			if (customLoader)
			{
				return customLoader;
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				new LoadingScreenComponent({ backgroundColor: '#eef2f4' }),
			);
		}

		/**
		 * @returns {View|null}
		 */
		renderCustomLoader()
		{
			const loaderContent = TabLoaderFactory.createLoader(this.getType(), this.getLoaderProps());

			if (loaderContent)
			{
				return View(
					{
						style: {
							position: 'absolute',
							top: 0,
							left: 0,
							right: 0,
							display: this.inDoneStatus() ? 'none' : 'flex',
						},
					},
					loaderContent,
				);
			}

			return null;
		}

		getLoaderProps()
		{
			return {
				onRef: (ref) => this.loaderRef = ref,
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
	}

	module.exports = { Tab };
});
