import {Tag, Type, Dom, Event} from 'main.core';
import {Address, Format, ControlMode, MapBase, GeocodingServiceBase, AddressStringConverter} from 'location.core';
import {EventEmitter} from 'main.core.events';
import AddressString from './addressstring';
import './css/mappopup.css';
import Popup from './popup';

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
	#addressFormat;
	#gallery;
	#locationRepository;
	#isMapRendered = false;
	#mapInnerContainer;
	#geocodingService;
	#contentWrapper;

	constructor(props)
	{
		super(props);
		this.setEventNamespace('BX.Location.Widget.MapPopup');

		if(!(props.map instanceof MapBase))
		{
			BX.debug('map must be instance of Map');
		}

		this.#map = props.map;

		if(props.geocodingService instanceof GeocodingServiceBase)
		{
			this.#geocodingService = props.geocodingService;
		}

		this.#map.onLocationChangedEventSubscribe( (event) => {

			let data = event.getData(),
				location = data.location,
				address = location.toAddress();

			this.#address = address;
			this.#addressString.address = address;

			if(this.#gallery)
			{
				this.#gallery.location = location;
			}

			this.emit(
				MapPopup.#onChangedEvent,
				{ address: address}
			);
		});

		if(!(props.popup instanceof Popup))
		{
			BX.debug('popup must be instance of Popup');
		}

		this.#popup = props.popup;

		if(!(props.addressFormat instanceof Format))
		{
			BX.debug('addressFormat must be instance of Format');
		}

		this.#addressFormat = props.addressFormat;

		this.#addressString = new AddressString({
			addressFormat: this.#addressFormat
		});

		if(props.gallery)
		{
			this.#gallery = props.gallery;
		}

		this.#locationRepository = props.locationRepository;
	}

	render(props: object): void
	{
		this.#address = props.address;
		this.#mode = props.mode;
		this.#isMapRendered = false;
		this.#mapInnerContainer = Tag.render`<div class="location-map-inner"></div>`;
		this.#renderPopup(props.bindElement, this.#mapInnerContainer);
	}

	#renderPopup(bindElement: Element, mapInnerContainer: Element): Popup
	{
		let gallery = '';

		if(this.#gallery)
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
		if(Type.isDomNode(bindElement))
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
		this.#addressString.address = address;

		this.#convertAddressToLocation(address)
			.then((location) => {
				this.#setLocationInternal(location);
			});
	}

	#convertAddressToLocation(address: ?Address): Promise<?Location>
	{
		return new Promise((resolve) => {

			let location = address ? address.toLocation() : null;

			if(location)
			{
				if(!location.latitude
					&& !location.longitude
					&& address.latitude
					&& address.longitude
				)
				{
					location.latitude = address.latitude;
					location.longitude = address.longitude;
				}

				if(location.latitude && location.longitude)
				{
					resolve(location);
					return;
				}
			}

			if(this.#geocodingService)
			{
				let addressStr = null;

				if (address)
				{
					addressStr = address.toString(
						this.#addressFormat,
						AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE,
						AddressStringConverter.CONTENT_TYPE_TEXT
					);
				}

				this.#geocodingService.geocode(addressStr)
					.then((locationsList: Array) =>
					{
						let location = locationsList.length === 1 ? locationsList[0] : null;
						resolve(location);
					});
			}
		});
	}

	#setLocationInternal(location: ?Location): void
	{
		this.#map.location = location;

		if(this.#gallery)
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

				if(!location)
				{
					return;
				}

				this.#popup.show();

				if(!this.#isMapRendered)
				{
					this.#renderMap({location})
						.then(() => {

							if(this.#gallery)
							{
								this.#gallery.location = location;
							}
							this.emit(MapPopup.#onShowedEvent);
						});

					this.#isMapRendered = true;
				}
				else
				{
					if(this.#gallery)
					{
						this.#gallery.location = location;
					}

					this.emit(MapPopup.#onShowedEvent);
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
		this.emit(MapPopup.#onClosedEvent)
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

		this.#popup.destroy();
		this.#popup = null;
		Dom.remove(this.#contentWrapper);
		this.#contentWrapper = null;
		Event.unbindAll(this);
	}
}
