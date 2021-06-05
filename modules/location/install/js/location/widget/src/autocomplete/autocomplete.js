import {Event, Loc, Tag} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {LocationRepository, AutocompleteServiceBase, Format, Address, Location,
	DistanceCalculator,	ErrorPublisher, LocationType,
	AutocompleteServiceFilter,
} from 'location.core';
import type {AutocompleteServiceParams} from 'location.core';
import Prompt from './prompt';
import State from '../state';
import AddressString from './addressstring';

/**
 * @mixes EventEmitter
 */
export default class Autocomplete extends EventEmitter
{
	static #onAddressChangedEvent = 'onAddressChanged';
	static #onStateChangedEvent = 'onStateChanged';
	static #onSearchStartedEvent = 'onSearchStarted';
	static #onSearchCompletedEvent = 'onSearchCompleted';
	static #onShowOnMapClickedEvent = 'onShowOnMapClicked';

	/** {Address} */
	#address;
	/** {AddressString|null} */
	#addressString = null;
	/** {String} */
	#languageId;
	/** {Format} */
	#addressFormat;
	/** {String} */
	#sourceCode;
	/** {LocationRepository} */
	#locationRepository;
	/** {Location} */
	#userLocation;
	/** {Function} */
	#presetLocationsProvider;
	/** {Prompt} */
	#prompt;
	/** {AutocompleteServiceBase} */
	#autocompleteService;
	/** @type {number} */
	#minCharsCountToAutocomplete;
	/** {number} milliseconds promptDelay before the searching will start */
	#promptDelay;
	/** {number} */
	#maxPromptDelay;
	/** {number} */
	#timerId = null;
	/** {Element} */
	#inputNode;

	/** {Location} */
	#lastSelectedLocation = null;

	#searchPhrase = {
		requested: '',
		current: '',
		dropped: ''
	};

	#state;
	#isDestroyed = false;

	#prevKeyUpTime;
	#avgKeyUpDelay;

	#isAutocompleteRequestStarted = false;

	#maxFirstItemUserDistanceKm = 100;
	#onLocationSelectTimerId = null;

	/** {AutocompleteServiceFilter} */
	#filter;

	constructor(props)
	{
		super(props);

		this.setEventNamespace('BX.Location.Widget.Autocomplete');

		if (!(props.addressFormat instanceof Format))
		{
			throw new Error('props.addressFormat must be type of Format');
		}

		this.#addressFormat = props.addressFormat;

		if (!(props.autocompleteService instanceof AutocompleteServiceBase))
		{
			throw new Error('props.autocompleteService must be type of AutocompleteServiceBase');
		}

		this.#autocompleteService = props.autocompleteService;

		if (!props.languageId)
		{
			throw new Error('props.languageId must be defined');
		}

		this.#languageId = props.languageId;
		this.#sourceCode = props.sourceCode;
		this.#address = props.address;
		this.#presetLocationsProvider = props.presetLocationsProvider;
		this.#locationRepository = props.locationRepository || new LocationRepository();
		this.#userLocation = props.userLocation;
		this.#promptDelay = props.promptDelay || 300;
		this.#maxPromptDelay = props.maxPromptDelay || 500;
		this.#minCharsCountToAutocomplete = props.minCharsCountToAutocomplete || 3;
		this.#setState(State.INITIAL);
		this.#avgKeyUpDelay = this.#promptDelay;
		this.#filter = new AutocompleteServiceFilter();
	}

	render(props: {}): void
	{
		this.#inputNode = props.inputNode;
		this.#address = props.address;
		this.#addressString = new AddressString(this.#inputNode, this.#addressFormat, this.#address);
		this.#inputNode.addEventListener('keyup', this.#onInputKeyUp.bind(this));
		this.#inputNode.addEventListener('focus', this.#onInputFocus.bind(this));
		this.#inputNode.addEventListener('focusout', this.#onInputFocusOut.bind(this));
		this.#inputNode.addEventListener('click', this.#onInputClick.bind(this));

		this.#prompt = new Prompt({
			inputNode: props.inputNode,
			menuNode: props.menuNode,
		});

		this.#prompt.subscribe(Prompt.onItemSelectedEvent, this.#onPromptItemSelected.bind(this));
		document.addEventListener('click', this.#onDocumentClick.bind(this));
	}

	// eslint-disable-next-line no-unused-vars
	#onInputClick(e: MouseEvent)
	{
		const value = this.#addressString.value;

		if (value.length === 0)
		{
			this.#showPresetLocations();
		}
	}

	#showPresetLocations()
	{
		const presetLocationList = this.#presetLocationsProvider();

		this.#prompt.setMenuItems(presetLocationList, '');

		let leftBottomMenuMessage;

		if (presetLocationList.length > 0)
		{
			leftBottomMenuMessage = Loc.getMessage('LOCATION_WIDGET_PICK_ADDRESS_OR_SHOW_ON_MAP');
		}
		else
		{
			leftBottomMenuMessage = Loc.getMessage('LOCATION_WIDGET_START_PRINTING_OR_SHOW_ON_MAP');
		}

		this.#showMenu(leftBottomMenuMessage, null);
	}

	#createRightBottomMenuNode(location: ?Location): Element
	{
		const element = Tag.render`
				<span class="location-map-popup-item--show-on-map">
					${Loc.getMessage('LOCATION_WIDGET_SHOW_ON_MAP')}
				</span>
		`;

		element.addEventListener('click', this.#getShowOnMapHandler(location));

		return element;
	}

	#createLeftBottomMenuNode(text: string): Element
	{
		return Tag.render`
				<span>				
					<span class="menu-popup-item-icon"></span>
					<span class="menu-popup-item-text">${text}</span>
				</span>		
		`;
	}

	#showMenu(leftBottomText: string, location: ?Location): void
	{
		/* Menu destroys popup after the closing, so we need to refresh it every time, we show it */
		this.#prompt.getMenu().setBottomRightItemNode(
			this.#createRightBottomMenuNode(location)
		);
		this.#prompt.getMenu().setBottomLeftItemNode(
			this.#createLeftBottomMenuNode(leftBottomText)
		);
		this.#prompt.getMenu().show();
	}

	#onInputFocusOut(e: Event)
	{
		if (this.#isDestroyed)
		{
			return;
		}

		// If we have selected item from prompt, the focusOut event will be first.
		setTimeout(() => {

			if (this.#state === State.DATA_INPUTTING)
			{
				this.#setState(State.DATA_SUPPOSED);

				if (this.#addressString)
				{
					if (!this.#address || !this.#addressString.hasPureAddressString())
					{
						this.#address = this.#convertStringToAddress(
							this.#addressString.value
						);
					}
					// this.#addressString === null until autocompete'll be rendered
					else if (this.#addressString.customTail !== '')
					{
						this.#address.setFieldValue(
							this.#addressFormat.fieldForUnRecognized,
							this.#addressString.customTail
						);
					}
				}

				this.#onAddressChangedEventEmit();
			}
		}, 1);

		if (this.#prompt)
		{
			this.#prompt.close();
		}

		// Let's prevent other onInputFocusOut handlers.
		e.stopImmediatePropagation();
	}

	#onInputFocus()
	{
		if (this.#isDestroyed)
		{
			return;
		}

		if (
			this.#address
			&& (!this.#address.location || !this.#address.location.hasExternalRelation())
			&& this.#addressString.value.length > 0
		)
		{
			this.showPrompt(this.#addressString.value);
		}
	}

	#makeAutocompleteFilter(locationForBias: ?Location): AutocompleteServiceFilter
	{
		const result = new AutocompleteServiceFilter();

		if (!locationForBias)
		{
			return result;
		}

		let filterType = null;

		if (locationForBias.type === LocationType.COUNTRY)
		{
			filterType = LocationType.LOCALITY;
		}
		else if (locationForBias.type === LocationType.LOCALITY)
		{
			filterType = LocationType.STREET;
		}
		else if (locationForBias.type === LocationType.STREET)
		{
			filterType = LocationType.BUILDING;
		}

		if (filterType)
		{
			result.types = [filterType];
		}

		return result;
	}

	#makeAutocompleteServiceParams(): AutocompleteServiceParams
	{
		let locationForBias = null;
		const result: AutocompleteServiceParams = {};

		if (this.#lastSelectedLocation)
		{
			locationForBias = this.#lastSelectedLocation;
		}
		else if (this.#userLocation)
		{
			locationForBias = this.#userLocation;
		}

		result.filter = this.#filter;
		result.locationForBias = locationForBias;

		return result;
	}

	/**
	 * @param address
	 */
	set address(address: ?Address): void
	{
		this.#address = address;

		if (this.#addressString) // already rendered
		{
			this.#addressString.setValueFromAddress(this.#address);
		}

		if (!address)
		{
			this.#filter.reset();
		}
	}

	/**
	 * @returns {Address}
	 */
	get address(): ?Address
	{
		return this.#address;
	}

	/**
	 * Close menu on mouse click outside
	 * @param {MouseEvent} event
	 */
	#onDocumentClick(event: MouseEvent)
	{
		if (this.#isDestroyed)
		{
			return;
		}

		if (event.target === this.#inputNode)
		{
			return;
		}

		if (this.#prompt.isShown())
		{
			this.#prompt.close();
		}
	}

	/**
	 * Subscribe on changed event
	 * @param {Function} listener
	 */
	onAddressChangedEventSubscribe(listener: Function): void
	{
		this.subscribe(Autocomplete.#onAddressChangedEvent, listener);
	}

	/**
	 * Subscribe on loading event
	 * @param {Function} listener
	 */
	onStateChangedEventSubscribe(listener: Function): void
	{
		this.subscribe(Autocomplete.#onStateChangedEvent, listener);
	}

	/**
	 * @param {Function} listener
	 */
	onSearchStartedEventSubscribe(listener: Function): void
	{
		this.subscribe(Autocomplete.#onSearchStartedEvent, listener);
	}

	/**
	 * @param {Function} listener
	 */
	onSearchCompletedEventSubscribe(listener: Function): void
	{
		this.subscribe(Autocomplete.#onSearchCompletedEvent, listener);
	}

	/**
	 * @param {Function} listener
	 */
	onShowOnMapClickedEventSubscribe(listener: Function): void
	{
		this.subscribe(Autocomplete.#onShowOnMapClickedEvent, listener);
	}

	/**
	 * Is called when autocompleteService returned location list
	 * @param {array} locationsList
	 * @param {object} params
	 */
	#onPromptsReceived(locationsList: array<Location>, params: Object): void
	{
		if (Array.isArray(locationsList) && locationsList.length > 0)
		{
			if (
				locationsList.length === 1
				&& this.#address
				&& this.#address.location
				&& this.#address.location.externalId
				&& this.#address.location.externalId === locationsList[0].externalId
			)
			{
				this.closePrompt();
				return;
			}

			this.#prompt.setMenuItems(locationsList, this.#searchPhrase.requested, this.address);
			this.#showMenu(Loc.getMessage('LOCATION_WIDGET_PICK_ADDRESS_OR_SHOW_ON_MAP'), locationsList[0]);
		}
		else
		{
			const split = Autocomplete.#splitPhrase(this.#searchPhrase.current);
			this.#searchPhrase.current = split[0];
			this.#searchPhrase.dropped = `${split[1]} ${this.#searchPhrase.dropped}`;

			if (this.#searchPhrase.current.length > 0)
			{
				this.#showPromptInner(this.#searchPhrase.current, params, 1);
			}
			else
			{
				this.#prompt.getMenu().clearItems();

				this.#prompt.getMenu().addMenuItem(
					{
						id: 'notFound',
						html: `<span>${Loc.getMessage('LOCATION_WIDGET_PROMPT_ADDRESS_NOT_FOUND')}</span>`,
						// eslint-disable-next-line no-unused-vars
						onclick: (event, item) => {
							this.#prompt.close();
						}
					}
				);

				this.#showMenu(Loc.getMessage('LOCATION_WIDGET_CHECK_ADDRESS_OR_SHOW_ON_MAP'), null);
			}
		}
	}

	#getShowOnMapHandler(location: ?Location)
	{
		return () => {
			if (location && this.#userLocation
				&& location.latitude && location.longitude
				&& this.#userLocation.latitude
				&& this.#userLocation.longitude
			)
			{
				const firstItemUserDistance = DistanceCalculator.getDistanceFromLatLonInKm(
					location.latitude,
					location.longitude,
					this.#userLocation.latitude,
					this.#userLocation.longitude
				);

				if (firstItemUserDistance <= this.#maxFirstItemUserDistanceKm)
				{
					this.#fulfillSelection(location);

					return;
				}
			}

			setTimeout(() => {
					this.emit(Autocomplete.#onShowOnMapClickedEvent);
				},
				1 // Otherwise this click will close just opened map popup.
			);
		};
	}

	static #splitPhrase(phrase: string): Object
	{
		// eslint-disable-next-line no-param-reassign
		phrase = phrase.trim();

		if (phrase.length <= 0)
		{
			return ['', ''];
		}

		const tailPosition = phrase.lastIndexOf(' ');

		if (tailPosition <= 0)
		{
			return ['', ''];
		}

		return [phrase.slice(0, tailPosition), phrase.slice(tailPosition + 1)];
	}

	/**
	 * Is called when location from menu have chosen
	 * @param event
	 */
	#onPromptItemSelected(event: BaseEvent): void
	{
		if (event.data.location)
		{
			this.#fulfillSelection(event.data.location);
		}
	}

	get state(): string
	{
		return this.#state;
	}

	#setState(state: string)
	{
		this.#state = state;
		this.emit(Autocomplete.#onStateChangedEvent, {state: this.#state});
	}

	/**
	 * Fulfill selected location
	 * @param {Location} location
	 * @returns {Promise}
	 */
	#fulfillSelection(location: ?Location): void
	{
		let result;
		this.#setState(State.DATA_SELECTED);
		if (location)
		{
			if (location.hasExternalRelation() && this.#sourceCode === location.sourceCode)
			{
				result = this.#getLocationDetails(location)
					.then((location: ?Location) => {
							this.#createOnLocationSelectTimer(location, 0);
							return true;
						},
						(response) => ErrorPublisher.getInstance().notify(response.errors)
					);
			}
			else
			{
				result = new Promise((resolve) => {
					setTimeout(() => {
						this.#createOnLocationSelectTimer(location, 0);
						resolve();
					}, 0);
				});
			}
		}
		else
		{
			result = new Promise((resolve) => {
				setTimeout(() => {
					this.#createOnLocationSelectTimer(null, 0);
					resolve();
				}, 0);
			});
		}

		return result;
	}

	#onAddressChangedEventEmit(excludeSetAddressFeatures: Array = [])
	{
		this.emit(
			Autocomplete.#onAddressChangedEvent,
			{
				address: this.#address,
				excludeSetAddressFeatures
			}
		);
	}

	/**
	 * obtain location details
	 * @param {Location} location
	 * @returns {*}
	 */
	#getLocationDetails(location: Location): Promise
	{
		this.#setState(State.DATA_LOADING);

		return this.#locationRepository.findByExternalId(
			location.externalId,
			location.sourceCode,
			location.languageId
		)
			.then((detailedLocation: ?Location) => {
					this.#setState(State.DATA_LOADED);

					let result;
					/*
					 * Nominatim could return a bit different location without the coordinates.
					 * For example N752206814
					 */
					if (
						detailedLocation.latitude !== '0'
						&& detailedLocation.longitude !== '0'
						&& detailedLocation !== ''
					)
					{
						result = detailedLocation;
						result.name = location.name;
					}
					else
					{
						result = location;
					}

					return result;
				},
				(response) => {
					ErrorPublisher.getInstance().notify(response.errors);
				}
			);
	}

	#convertStringToAddress(addressString: string)
	{
		const result = new Address({
			languageId: this.#languageId
		});

		result.setFieldValue(this.#addressFormat.fieldForUnRecognized, addressString);
		return result;
	}

	/**
	 * Is called when location was selected and the location details were obtained
	 * @param {Location} location
	 */
	#onLocationSelect(location: ?Location): void
	{
		this.#lastSelectedLocation = location;
		this.#filter = this.#makeAutocompleteFilter(this.#lastSelectedLocation);
		this.#address = location ? location.toAddress() : null;
		this.#addressString.setValueFromAddress(this.#address);
		this.#onAddressChangedEventEmit();
	}

	#onInputKeyUp(e: KeyboardEvent): void
	{
		if (this.#isDestroyed)
		{
			return;
		}

		const now = Date.now();

		if (this.#prevKeyUpTime)
		{
			const delta = now - this.#prevKeyUpTime;
			this.#avgKeyUpDelay = (this.#avgKeyUpDelay + delta) / 2;
		}

		this.#prevKeyUpTime = now;

		if (
			this.#state !== State.DATA_INPUTTING
			&& this.#addressString.isChanged()
		)
		{
			this.#setState(State.DATA_INPUTTING);
		}

		if (this.#prompt.isShown())
		{
			let location;
			const onLocationSelectTimeout = 700;

			switch (e.code)
			{
				case 'NumpadEnter':
				case 'Enter':
					if (this.#prompt.isItemChosen())
					{
						this.#fulfillSelection(this.#prompt.getChosenItem())
							.then(() => {
									this.#prompt.close();
								},
								(error) => BX.debug(error)
							);
					}
					return;

				case 'Tab':
				case 'Escape':
					this.#setState(State.DATA_SUPPOSED);
					this.#onAddressChangedEventEmit();
					this.#prompt.close();
					return;

				case 'ArrowUp':
					location = this.#prompt.choosePrevItem();

					if (location && location.address)
					{
						this.#createOnLocationSelectTimer(location, onLocationSelectTimeout);
					}

					return;

				case 'ArrowDown':
					location = this.#prompt.chooseNextItem();

					if (location && location.address)
					{
						this.#createOnLocationSelectTimer(location, onLocationSelectTimeout);
					}

					return;

				case 'Backspace':
				case 'Delete':
					this.#filter.reset();
					break;
			}
		}

		if (this.#addressString.isChanged())
		{
			this.#addressString.actualize();
			this.showPrompt(this.#addressString.value);
		}

		if (this.#addressString.value.length === 0)
		{
			this.#showPresetLocations();
		}
	}

	#createOnLocationSelectTimer(location: Location, timeout: Number): void
	{
		if (this.#onLocationSelectTimerId !== null)
		{
			clearTimeout(this.#onLocationSelectTimerId);
		}

		this.#onLocationSelectTimerId = setTimeout(() => {
				this.#onLocationSelect(location);
			},
			timeout
		);
	}

	/**
	 * @param {string} searchPhrase
	 */
	showPrompt(searchPhrase: string): void
	{
		this.#searchPhrase.requested = searchPhrase;
		this.#searchPhrase.current = searchPhrase;
		this.#searchPhrase.dropped = '';
		this.#showPromptInner(searchPhrase, this.#computePromptDelay());
	}

	/**
	 * @returns {number}
	 */
	#computePromptDelay(): number
	{
		const delay = this.#promptDelay > this.#avgKeyUpDelay ? this.#promptDelay : this.#avgKeyUpDelay * 1.5;
		return delay > this.#maxPromptDelay ? this.#maxPromptDelay : delay;
	}

	closePrompt(): void
	{
		if (this.#prompt)
		{
			this.#prompt.close();
		}
	}

	isPromptShown(): boolean
	{
		if (this.#prompt)
		{
			this.#prompt.isShown();
		}
	}

	#showPromptInner(searchPhrase: string, promptDelay: number): void
	{
		if (searchPhrase.length <= this.#minCharsCountToAutocomplete)
		{
			promptDelay *= 2;
		}

		if (this.#timerId !== null)
		{
			clearTimeout(this.#timerId);
		}

		this.#timerId = this.#createTimer(searchPhrase, promptDelay);
	}

	/**
	 * Wait for further user input for some time
	 * @param {string} searchPhrase
	 * @param {number} promptDelay
	 * @returns {number}
	 */
	#createTimer(searchPhrase: string, promptDelay: number): number
	{
		return setTimeout(() => {
			// to avoid multiple parallel requests, server responses are too slow.
			if (this.#isAutocompleteRequestStarted)
			{
				clearTimeout(this.#timerId);
				this.#timerId = this.#createTimer(searchPhrase, promptDelay);
				return;
			}

			this.emit(Autocomplete.#onSearchStartedEvent);
			this.#isAutocompleteRequestStarted = true;
			const params = this.#makeAutocompleteServiceParams();

			this.#autocompleteService.autocomplete(searchPhrase, params)
				.then(
					(locationsList: Array<Location>) => {
						this.#timerId = null;
						this.#onPromptsReceived(locationsList, params);
						this.emit(Autocomplete.#onSearchCompletedEvent);
						this.#isAutocompleteRequestStarted = false;
					},
					(error) => {
						this.emit(Autocomplete.#onSearchCompletedEvent);
						this.#isAutocompleteRequestStarted = false;
						BX.debug(error);
					}
				);
		},
		promptDelay
		);
	}

	destroy(): void
	{
		if (this.#isDestroyed)
		{
			return;
		}

		Event.unbindAll(this);

		if (this.#prompt)
		{
			this.#prompt.destroy();
			this.#prompt = null;
		}

		this.#timerId = null;

		if (this.#inputNode)
		{
			this.#inputNode.removeEventListener('keyup', this.#onInputKeyUp);
			this.#inputNode.removeEventListener('focus', this.#onInputFocus);
			this.#inputNode.removeEventListener('focusout', this.#onInputFocusOut);
			this.#inputNode.removeEventListener('click', this.#onInputClick);
		}

		document.removeEventListener('click', this.#onDocumentClick);
		this.#isDestroyed = true;
	}
}