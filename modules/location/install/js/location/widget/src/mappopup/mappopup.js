import {Tag, Type, Dom, Event} from 'main.core';
import {
	Address, Format, Location, ControlMode, MapBase, GeocodingServiceBase, AddressStringConverter
} from 'location.core';
import {EventEmitter} from 'main.core.events';
import AddressString from './addressstring';
import './css/mappopup.css';
import Popup from './popup';
import AddressRestorer from './addressrestorer';

export default class MapPopup extends EventEmitter
{
	static #onChangedEvent = 'onChanged';
	static #onMouseOverEvent = 'onMouseOver';
	static #onMouseOutEvent = 'onMouseOut';
	static #onShowedEvent = 'onShow';
	static #onClosedEvent = 'onClose';

	#map;
	#mode;
	#address;
	#popup;
	#addressString;
	#addressRestorer;
	#addressFormat;
	#gallery;
	#locationRepository;
	#isMapRendered = false;
	#mapInnerContainer;
	#geocodingService;
	#contentWrapper;
	#needRestore = false;
	#userLocation;

	constructor(props)
	{
		super(props);
		this.setEventNamespace('BX.Location.Widget.MapPopup');

		if (!(props.map instanceof MapBase))
		{
			BX.debug('map must be instance of Map');
		}

		this.#map = props.map;

		if (props.geocodingService instanceof GeocodingServiceBase)
		{
			this.#geocodingService = props.geocodingService;
		}

		this.#map.onLocationChangedEventSubscribe(this.#onLocationChanged.bind(this));

		if (!(props.popup instanceof Popup))
		{
			BX.debug('popup must be instance of Popup');
		}

		this.#popup = props.popup;

		if (!(props.addressFormat instanceof Format))
		{
			BX.debug('addressFormat must be instance of Format');
		}

		this.#addressFormat = props.addressFormat;

		this.#addressString = new AddressString({
			addressFormat: this.#addressFormat
		});

		this.#addressRestorer = new AddressRestorer({
			addressFormat: this.#addressFormat
		});

		this.#addressRestorer.onRestoreEventSubscribe(this.#onAddressRestore.bind(this));

		if (props.gallery)
		{
			this.#gallery = props.gallery;
		}

		this.#locationRepository = props.locationRepository;
		this.#userLocation = props.userLocation;
	}

	#onLocationChanged(event: Event)
	{
		const data = event.getData();
		const location = data.location;
		const address = location.toAddress();

		this.#address = address;
		this.#addressString.address = address;

		if (this.#needRestore)
		{
			if (this.#addressRestorer.isHidden())
			{
				this.#addressRestorer.show();
			}
		}

		if (this.#gallery)
		{
			this.#gallery.location = location;
		}

		this.emit(
			MapPopup.#onChangedEvent,
			{address: address}
		);
	}

	#onAddressRestore(event: Event)
	{
		const data = event.getData();
			const prevAddress = data.address;

		prevAddress.latitude = this.#address.latitude;
		prevAddress.longitude = this.#address.longitude;

		this.#address = prevAddress;
		this.#addressString.address = prevAddress;

		this.#addressRestorer.hide();

		this.emit(
			MapPopup.#onChangedEvent,
			{address: prevAddress}
		);
	}

	render(props: object): void
	{
		this.#address = props.address;
		this.#needRestore = true;
		this.#mode = props.mode;
		this.#isMapRendered = false;
		this.#mapInnerContainer = Tag.render`<div class="location-map-inner"></div>`;
		this.#renderPopup(props.bindElement, this.#mapInnerContainer);
	}

	#renderPopup(bindElement: Element, mapInnerContainer: Element): Popup
	{
		let gallery = '';

		if (this.#gallery)
		{
			gallery = this.#gallery.render();
		}

		this.#contentWrapper = Tag.render`
			<div class="location-map-wrapper">
				<div class="location-map-container">
					${mapInnerContainer}
					${gallery}
				</div>
				${this.#mode === ControlMode.edit ? this.#addressString.render({address: this.#address}) : ''}
				${this.#mode === ControlMode.edit ? this.#addressRestorer.render({address: this.#address}) : ''}
			</div>`;

		Event.bind(this.#contentWrapper, 'click', (e) => e.stopPropagation());
		Event.bind(this.#contentWrapper, 'mouseover', (e) => this.emit(MapPopup.#onMouseOverEvent, e));
		Event.bind(this.#contentWrapper, 'mouseout', (e) => this.emit(MapPopup.#onMouseOutEvent, e));
		this.bindElement = bindElement;
		this.#popup.setContent(this.#contentWrapper);
	}

	get bindElement()
	{
		return this.#popup.getBindElement();
	}

	set bindElement(bindElement: Element)
	{
		if (Type.isDomNode(bindElement))
		{
			this.#popup.setBindElement(bindElement);
		}
		else
		{
			BX.debug('bindElement must be type of dom node');
		}
	}

	set address(address: ?Address): void
	{
		this.#address = address;
		this.#needRestore = true;
		this.#addressString.address = address;
		this.#addressRestorer.address = address;

		this.#convertAddressToLocation(address)
			.then((location) => {
				this.#setLocationInternal(location);
			});
	}

	#convertAddressToLocation(address: ?Address): Promise<?Location>
	{
		return new Promise((resolve) => {
			if (address)
			{
				let lat;
				let lon;

				if (address.latitude && address.longitude)
				{
					lat = address.latitude;
					lon = address.longitude;
				}
				else if (address.location
					&& address.location.latitude
					&& address.location.longitude
				)
				{
					lat = address.location.latitude;
					lon = address.location.longitude;
				}

				if (lat && lat !== '0' && lon && lon !== '0')
				{
					resolve(new Location({
						latitude: lat,
						longitude: lon,
						type: address.getType()
					}));
					return;
				}

				// If we'll not find the address location - let's use the user's one
				let location = this.#userLocation && this.#mode !== ControlMode.view ? this.#userLocation : null;

				// Try to find via geocoding by string name
				if (this.#geocodingService)
				{
					const addressStr = address.toString(
						this.#addressFormat,
						AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE,
						AddressStringConverter.CONTENT_TYPE_TEXT
					);

					this.#geocodingService.geocode(addressStr)
						.then((locationsList: ?Array) =>
						{
							// If we have found just one location - we probably have found the right one.
							if (Array.isArray(locationsList) && locationsList.length === 1)
							{
								location = locationsList[0];
							}

							// geocoded or user's location
							resolve(location);
						});

					return;
				}
			}

			// If address is null, let's use the user's location in view mode.
			resolve(this.#userLocation && this.#mode !== ControlMode.view ? this.#userLocation : null);
		});
	}

	#setLocationInternal(location: ?Location): void
	{
		this.#map.location = location;

		if (this.#gallery)
		{
			this.#gallery.location = location;
		}
	}

	set mode(mode: string): void
	{
		this.#mode = mode;
		this.#map.mode = mode;
	}

	#renderMap({location})
	{
		return this.#map.render({
			mapContainer: this.#mapInnerContainer,
			location: location,
			mode: this.#mode
		});
	}

	show(): void
	{
		this.#convertAddressToLocation(this.#address)
			.then((location) => {
				if (!location)
				{
					return;
				}

				this.#popup.show();

				if (!this.#isMapRendered)
				{
					this.#renderMap({location})
						.then(() => {
							if (this.#gallery)
							{
								this.#gallery.location = location;
							}
							this.emit(MapPopup.#onShowedEvent);
							this.#map.onMapShow();
						});

					this.#isMapRendered = true;
				}
				else
				{
					this.#map.location = location;

					if (this.#gallery)
					{
						this.#gallery.location = location;
					}

					this.emit(MapPopup.#onShowedEvent);
					this.#map.onMapShow();
				}
			});
	}

	isShown(): boolean
	{
		return this.#popup.isShown();
	}

	close(): void
	{
		this.#popup.close();

		this.#needRestore = false;

		if (!this.#addressRestorer.isHidden())
		{
			this.#addressRestorer.hide();
		}

		this.emit(MapPopup.#onClosedEvent);
	}

	onChangedEventSubscribe(listener: Function): void
	{
		this.subscribe(MapPopup.#onChangedEvent, listener);
	}

	onMouseOverSubscribe(listener: Function): void
	{
		this.subscribe(MapPopup.#onMouseOverEvent, listener);
	}

	onMouseOutSubscribe(listener: Function): void
	{
		this.subscribe(MapPopup.#onMouseOutEvent, listener);
	}

	subscribeOnShowedEvent(listener: Function): void
	{
		this.subscribe(MapPopup.#onShowedEvent, listener);
	}

	subscribeOnClosedEvent(listener: Function): void
	{
		this.subscribe(MapPopup.#onClosedEvent, listener);
	}

	destroy()
	{
		this.#map = null;
		this.#gallery = null;
		this.#addressString = null;
		this.#addressRestorer = null;

		this.#popup.destroy();
		this.#popup = null;
		Dom.remove(this.#contentWrapper);
		this.#contentWrapper = null;
		Event.unbindAll(this);
	}
}
