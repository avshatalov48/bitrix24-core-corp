import {Type, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Address as AddressEntity, AddressType, ControlMode, Format,
		AddressStringConverter, LocationRepository, ErrorPublisher, Location} from 'location.core';
import State from '../state';
import BaseFeature from './features/basefeature';
import AutocompleteFeature from './features/autocompletefeature';
import {FeatureEvent} from './featurevent';

/**
 * Props for the address widget constructor
 */
export type AddressConstructorProps = {
	/** @see ControlMode */
	mode: string,
	addressFormat: Format,
	address?: AddressEntity,
	needWarmBackendAfterAddressChanged?: boolean,
	locationRepository?: LocationRepository,
	presetLocationList?: Array
};

/**
 * Props for the address widget render method
 */
export type AddressRenderProps = {
	/** Input control witch will be used by user to enter the address */
	inputNode: Element,
	/** Control wrapper witch could be used for mouseover event etc. */
	controlWrapper: Element,
	/** If map feature is used it could be used ti bind map popup */
	mapBindElement: ?Element
};

/**
 * Address widget
 */
export default class Address extends EventEmitter
{
	/* If address was changed by user */
	static onAddressChangedEvent = 'onAddressChanged';
	/* If state of the widget was changed */
	static onStateChangedEvent = 'onStateChanged';
	/* Any feature-related events */
	static onFeatureEvent = 'onFeatureEvent';

	#mode;
	#state;
	#address;
	#addressFormat;
	#languageId;

	#features = [];

	#inputNode;
	#controlWrapper;

	#destroyed = false;

	#isAddressChangedByFeature = false;
	#isInputNodeValueUpdated = false;

	#needWarmBackendAfterAddressChanged = true;
	#locationRepository;

	#presetLocationList = [];

	/**
	 * Constructor
	 * @param {AddressConstructorProps} props
	 */
	constructor(props: AddressConstructorProps)
	{
		super();

		this.setEventNamespace('BX.Location.Widget.Address');

		if(!(props.addressFormat instanceof Format))
		{
			BX.debug('addressFormat must be instance of Format');
		}

		this.#addressFormat = props.addressFormat;

		if(props.address && !(props.address instanceof AddressEntity))
		{
			BX.debug('address must be instance of Address');
		}

		this.#address = props.address || null;

		if(!(ControlMode.isValid(props.mode)))
		{
			BX.debug('mode must be valid ControlMode');
		}

		this.#mode = props.mode;

		if(!Type.isString(props.languageId))
		{
			throw new TypeError('props.languageId must be type of string');
		}

		this.#languageId = props.languageId;

		if(props.features)
		{
			if(!Type.isArray(props.features))
			{
				throw new TypeError('features must be an array');
			}

			props.features.forEach((feature: BaseFeature) => {
				this.#addFeature(feature);
			});
		}

		if(Type.isBoolean(props.needWarmBackendAfterAddressChanged))
		{
			this.#needWarmBackendAfterAddressChanged = props.needWarmBackendAfterAddressChanged;
		}

		if(props.locationRepository instanceof LocationRepository)
		{
			this.#locationRepository = props.locationRepository;
		}
		else if(this.#needWarmBackendAfterAddressChanged)
		{
			this.#locationRepository = new LocationRepository();
		}

		if(props.presetLocationList)
		{
			if(!Type.isArray(props.presetLocationList))
			{
				throw new TypeError('Preset location list must be an array');
			}

			for (let location of props.presetLocationList)
			{
				if(!(location instanceof Location))
				{
					BX.debug('location must be instance of Location');
				}

				this.#presetLocationList.push(location);
			}
		}

		this.#state = State.INITIAL;
	}

	/**
	 * @param {AddressEntity} address
	 * @param {BaseFeature} sourceFeature
	 * @internal
	 */
	setAddressByFeature(address: AddressEntity, sourceFeature: BaseFeature): void
	{
		const addressId = this.#address ? this.#address.id : 0;
		this.#address = address;

		if(addressId > 0)
		{
			this.#address.id = addressId;
		}

		this.#isAddressChangedByFeature = true;
		this.#setInputValue(address);

		this.#executeFeatureMethod('setAddress', [address], sourceFeature);

		if(this.#state !== State.DATA_INPUTTING)
		{
			this.#emitOnAddressChanged();
		}
	}

	emitFeatureEvent(featureEvent: FeatureEvent)
	{
		this.emit(
			Address.onFeatureEvent,
			featureEvent
		);
	}

	/**
	 * Add feature to the widget
	 * @param {BaseFeature} feature
	 */
	#addFeature(feature: BaseFeature)
	{
		if(!(feature instanceof BaseFeature))
		{
			BX.debug('feature must be instance of BaseFeature');
		}

		feature.setAddressWidget(this);
		this.#features.push(feature);
	}

	get features()
	{
		return this.#features;
	}

	#executeFeatureMethod(method, params = [], excludeFeature = null)
	{
		let result;

		for(let feature of this.#features)
		{
			if(feature !== excludeFeature)
			{
				result = feature[method].apply(feature, params);
			}
		}

		return result;
	}

	#emitOnAddressChanged()
	{
		this.emit(
			Address.onAddressChangedEvent,
			{address: this.#address}
		);

		if(this.#address && this.#needWarmBackendAfterAddressChanged)
		{
			this.#warmBackendAfterAddressChanged(this.#address);
		}
	}

	#warmBackendAfterAddressChanged(address: AddressEntity): void
	{
		if(address.location !== null && address.location.id <= 0)
		{
			this.#locationRepository.findParents(address.location);
		}
	}

	#onInputFocus(e: KeyboardEvent)
	{
		let value = this.#inputNode.value;

		if(value.length > 0)
		{
			BX.setCaretPosition(this.#inputNode, value.length - 1)
		}
	}

	#onInputClick(e: MouseEvent)
	{
		let value = this.#inputNode.value;

		if(value.length === 0 && this.#presetLocationList.length > 0)
		{
			this.#showPresetLocations();
		}
	}

	#showPresetLocations()
	{
		let autocompleteFeature = this.#getAutocompleteFeature();

		if (!autocompleteFeature.autocomplete || !autocompleteFeature.autocomplete.prompt)
		{
			return;
		}

		autocompleteFeature.autocomplete.prompt.show(this.#presetLocationList, '');
	}

	#convertAddressToString(address: ?Address): string
	{
		if(!address)
		{
			return '';
		}

		return address.toString(
			this.#addressFormat,
			AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE,
			AddressStringConverter.CONTENT_TYPE_TEXT
		);
	}

	#setInputValue(address: ?Address)
	{
		if(this.#inputNode)
		{
			let selectionStart = this.#inputNode.selectionStart;
			let selectionEnd = this.#inputNode.selectionEnd;

			const addressString = this.#convertAddressToString(address);
			this.#inputNode.value = addressString;
			this.#inputNode.title = addressString;

			this.#inputNode.setSelectionRange(selectionStart, selectionEnd);
		}
	}

	#onInputFocusOut(e: KeyboardEvent)
	{
		// Seems that we don't have any autocompleter feature
		if(this.#isInputNodeValueUpdated && !this.#isAddressChangedByFeature)
		{
			let value = this.#inputNode.value.trim();
			let address = new AddressEntity({languageId: this.#languageId});
			address.setFieldValue(this.#addressFormat.fieldForUnRecognized, value);
			this.address = address;
			this.#emitOnAddressChanged();
		}

		this.#isInputNodeValueUpdated = false;
		this.#isAddressChangedByFeature = false;
	}

	onInputKeyup(e: KeyboardEvent)
	{
		let value = this.#inputNode.value;

		switch (e.code)
		{
			case 'Tab':
			case 'Esc':
			case 'Enter':
			case 'NumpadEnter':
				this.resetView();
				break;
			default:
				this.#isInputNodeValueUpdated = true;
		}

		if(value.length === 0 && this.#presetLocationList.length > 0)
		{
			this.#showPresetLocations();
		}
	}

	resetView(): void
	{
		this.#executeFeatureMethod('resetView');
	}

	/**
	 * Render Widget
	 * @param {AddressRenderProps} props
	 */
	render(props: AddressRenderProps): void
	{
		if(!Type.isDomNode(props.controlWrapper))
		{
			BX.debug('props.controlWrapper  must be instance of Element');
		}

		this.#controlWrapper = props.controlWrapper;

		if(this.#mode === ControlMode.edit)
		{
			if(!Type.isDomNode(props.inputNode))
			{
				BX.debug('props.inputNode  must be instance of Element');
			}

			this.#inputNode = props.inputNode;
			this.#setInputValue(this.#address);
			Event.bind(this.#inputNode, 'focus', this.#onInputFocus.bind(this));
			Event.bind(this.#inputNode, 'focusout', this.#onInputFocusOut.bind(this));
			Event.bind(this.#inputNode, 'keyup', this.onInputKeyup.bind(this));
			Event.bind(this.#inputNode, 'click', this.#onInputClick.bind(this));
		}

		this.#executeFeatureMethod('render', [props]);
	}

	get controlWrapper()
	{
		return this.#controlWrapper;
	}

	get inputNode()
	{
		return this.#inputNode;
	}

	get address(): ?AddressEntity
	{
		return this.#address;
	}

	set address(address: ?AddressEntity): void
	{
		if(address && !(address instanceof AddressEntity))
		{
			BX.debug('address must be instance of Address');
		}

		this.#address = address;
		this.#executeFeatureMethod('setAddress', [address]);
		this.#isInputNodeValueUpdated = false;
		this.#isAddressChangedByFeature = false;
		this.#setInputValue(address);
	}

	get mode()
	{
		return this.#mode;
	}

	set mode(mode: string): void
	{
		if(!(ControlMode.isValid(mode)))
		{
			BX.debug('mode must be valid ControlMode');
		}

		this.#mode = mode;

		this.#executeFeatureMethod('setMode', [mode]);
	}

	get state(): string
	{
		return this.#state;
	}

	get addressFormat(): Format
	{
		return this.#addressFormat;
	}

	setStateByFeature(state: string)
	{
		this.#state = state;

		this.emit(
			Address.onStateChangedEvent,
			{state: state}
		);
	}

	#getAutocompleteFeature()
	{
		let result = null;

		for( let feature of this.#features)
		{
			if(feature instanceof AutocompleteFeature)
			{
				result = feature;
				break;
			}
		}

		return result;
	}

	subscribeOnStateChangedEvent(listener: Function): void
	{
		this.subscribe(Address.onStateChangedEvent, listener);
	}

	subscribeOnAddressChangedEvent(listener: Function): void
	{
		this.subscribe(Address.onAddressChangedEvent, listener);
	}

	subscribeOnFeatureEvent(listener: Function): void
	{
		this.subscribe(Address.onFeatureEvent, listener);
	}

	subscribeOnErrorEvent(listener: Function): void
	{
		ErrorPublisher.getInstance().subscribe(listener);
	}

	destroy()
	{
		if (this.#destroyed)
		{
			return;
		}

		Event.unbindAll(this);
		Event.unbind(this.#inputNode, 'focus', this.#onInputFocus);
		Event.unbind(this.#inputNode, 'focusout', this.#onInputFocusOut);
		Event.unbind(this.#inputNode, 'keyup', this.onInputKeyup);
		Event.unbind(this.#inputNode, 'click', this.onInputClick);

		this.#executeFeatureMethod('destroy');
		this.#destroyFeatures();
		this.#destroyed = true;
	}

	#destroyFeatures()
	{
		this.#features.splice(0, this.#features.length);
	}

	isDestroyed()
	{
		return this.#destroyed;
	}
}