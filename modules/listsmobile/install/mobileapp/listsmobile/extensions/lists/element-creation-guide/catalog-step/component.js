/**
 * @module lists/element-creation-guide/catalog-step/component
 */
jn.define('lists/element-creation-guide/catalog-step/component', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { CatalogStepView } = require('lists/element-creation-guide/catalog-step/view');
	const { EventEmitter } = require('event-emitter');
	const { Duration } = require('utils/date/duration');
	const { NotifyManager } = require('notify-manager');
	const { StorageCache } = require('storage-cache');

	class CatalogStepComponent extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {String} props.uid
		 * @param {Object} props.selectedItem
		 * @param {Object} props.layout
		 */
		constructor(props)
		{
			super(props);

			this.state = { items: null, cachedItems: null };
			this.isLoading = false;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.selectedItem = this.props.selectedItem || null;

			this.cache = new StorageCache('lists.element-create-guide.catalog-step', `catalog-${env.userId}`);
			this.loadFromCache();

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter.on('CatalogStepView:OnSelectItem', (item) => {
				this.selectedItem = item;
				this.customEventEmitter.emit('CatalogStepComponent:OnSelectItem', [item]);
			});
		}

		loadFromCache()
		{
			const items = this.cache.get();
			if (this.state.items !== null || Object.keys(items).length === 0)
			{
				return;
			}

			this.state.cachedItems = Object.values(items);
		}

		get isLoaded()
		{
			return (this.state.items !== null);
		}

		loadItems()
		{
			if (this.isLoaded || this.isLoading)
			{
				return;
			}

			this.isLoading = true;
			let items = [];

			BX.ajax.runAction('listsmobile.ElementCreationGuide.loadCatalogStep', {})
				.then((response) => {
					items = response.data.items || [];
				})
				.catch((response) => {
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
				})
				.finally(() => {
					this.isLoading = false;

					const preparedItems = items.map((item) => {
						const formattedTime = this.getFormattedTime(item);

						return { ...item, formattedTime };
					});

					this.cache.set(preparedItems);
					this.setState({
						items: preparedItems,
						cachedItems: preparedItems,
					});
				})
			;
		}

		getFormattedTime(item)
		{
			if (item.time === null || item.time === undefined || item.time === '')
			{
				return null;
			}

			const duration = Duration.createFromSeconds(item.time);
			const time = {
				s: duration.getUnitPropertyModByFormat('s'),
				i: duration.getUnitPropertyModByFormat('i'),
				H: duration.getUnitPropertyModByFormat('H'),
				d: duration.getUnitPropertyModByFormat('d'),
				m: duration.getUnitPropertyModByFormat('m'),
				Y: duration.getUnitPropertyModByFormat('Y'),
			};

			const roundedTime = this.roundTime(time);

			if (roundedTime.Y !== 0)
			{
				return (new Duration(roundedTime.Y * Duration.getLengthFormat().YEAR)).format('Y');
			}

			if (roundedTime.m !== 0)
			{
				return (new Duration(roundedTime.m * Duration.getLengthFormat().MONTH)).format('m');
			}

			if (roundedTime.d !== 0)
			{
				return (new Duration(roundedTime.d * Duration.getLengthFormat().DAY)).format('d');
			}

			if (roundedTime.H !== 0)
			{
				return (new Duration(roundedTime.H * Duration.getLengthFormat().HOUR)).format('H');
			}

			if (roundedTime.i !== 0)
			{
				return (new Duration(roundedTime.i * Duration.getLengthFormat().MINUTE)).format('i');
			}

			return duration.format('s');
		}

		/**
		 * @param {{s: number, i: number, H: number, d: number, m: number, Y: number}} time
		 * @return {{s: number, i: number, H: number, d: number, m: number, Y: number}}
		 */
		roundTime(time)
		{
			const seconds = time.s;
			const minutes = (time.i !== 0 && seconds >= 30) ? time.i + 1 : time.i;
			const hours = (time.H !== 0 && minutes >= 30) ? time.H + 1 : time.H;
			const days = (time.d !== 0 && hours >= 12) ? time.d + 1 : time.d;
			const months = (time.m !== 0 && days >= 15) ? time.m + 1 : time.m;
			const years = (time.Y !== 0 && months >= 6) ? time.Y + 1 : time.Y;

			return { s: seconds, i: minutes, H: hours, d: days, m: months, Y: years };
		}

		render()
		{
			this.loadItems();

			return new CatalogStepView({
				uid: this.uid,
				items: this.isLoaded ? this.state.items : this.state.cachedItems,
				isLoaded: this.isLoaded || this.state.cachedItems !== null,
				selectedItem: this.selectedItem,
				layout: this.props.layout,
			});
		}
	}

	module.exports = { CatalogStepComponent };
});
