import {Event, Type} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {LocationRepository, AutocompleteServiceBase, Format, Address, Location, DistanceCalculator,
	AddressStringConverter, ErrorPublisher} from 'location.core';
import Prompt from './prompt';
import State from '../state';
import {Loc} from 'main.core';

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
	/** {String} */
	#addressString = '';
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
	/** {number} miliseconds promptDelay before the searching will start */
	#promptDelay;
	/** {number} */
	#maxPromptDelay;
	/** {number} */
	#timerId = null;
	/** {Element} */
	#inputNode;

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

	constructor(props)
	{
		super(props);

		this.setEventNamespace('BX.Location.Widget.Autocomplete');

		if(!(props.addressFormat instanceof Format))
		{
			throw new Error('props.addressFormat must be type of Format');
		}

		this.#addressFormat = props.addressFormat;

		if(!(props.autocompleteService instanceof AutocompleteServiceBase))
		{
			throw new Error('props.autocompleteService must be type of AutocompleteServiceBase');
		}

		this.#autocompleteService = props.autocompleteService;

		if(!props.languageId)
		{
			throw new Error('props.languageId must be defined');
		}

		this.#languageId = props.languageId;
		this.#sourceCode = props.sourceCode;
		this.#address = props.address;
		this.#presetLocationsProvider = props.presetLocationsProvider;
		this.#locationRepository = props.locationRepository || new LocationRepository();
		this.#userLocation = props.userLocation;
		this.#promptDelay = props.promptDelay || 500;
		this.#maxPromptDelay = props.maxPromptDelay || 1500;
		this.#minCharsCountToAutocomplete = props.minCharsCountToAutocomplete || 3;
		this.#setState(State.INITIAL);
		this.#avgKeyUpDelay = this.#promptDelay;
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

	render(props: {}): void
	{
		this.#inputNode = props.inputNode;
		this.#addressString = this.#inputNode.value;

		this.#address = props.address;

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

	#onInputClick(e: MouseEvent)
	{
		let value = this.#inputNode.value;

		if(value.length === 0)
		{
			this.#showPresetLocations();
		}
	}

	#showPresetLocations()
	{
		let presetLocationList = this.#presetLocationsProvider();

		this.#prompt.setMenuItems(presetLocationList, '');

		this.#prompt.addShowOnMapMenuItem(
			this.#getShowOnMapHandler(null),
			Loc.getMessage(
				(presetLocationList.length > 0)
					? 'LOCATION_WIDGET_PICK_ADDRESS_OR_SHOW_ON_MAP'
					: 'LOCATION_WIDGET_START_PRINTING_OR_SHOW_ON_MAP'
			)
		);

		this.#prompt.getMenu().show();
	}

	#onInputFocusOut(e: Event)
	{
		if(this.#isDestroyed)
		{
			return;
		}

		// If we have selected item from prompt, the focusOut event will be first.
		setTimeout(() => {
			if(this.#state === State.DATA_INPUTTING)
			{
				this.#setState(State.DATA_SUPPOSED);
				this.#setAddressFromInput();
			}
		}, 200);

		if(this.#prompt)
		{
			this.#prompt.close();
		}

		// Let's prevent other onInputFocusOut handlers.
		e.stopImmediatePropagation();
	}

	#onInputFocus()
	{
		if(this.#isDestroyed)
		{
			return;
		}

		if(this.#address
			&& (!this.#address.location || !this.#address.location.hasExternalRelation())
			&& this.#inputNode.value.length > 0
		)
		{
			this.showPrompt(this.#inputNode.value, this.#makeParams());
		}
	}

	#makeParams(): Object
	{
		return {
			userCoordinates: this.#userLocation ? [this.#userLocation.latitude, this.#userLocation.longitude] : null
		};
	}

	/**
	 * @param address
	 */
	set address(address: ?Address): void
	{
		this.#address = address;

		if(this.#inputNode)
		{
			this.#addressString = this.#inputNode.value;
		}
	}

	#getInputValue()
	{
		let result = '';

		if(this.#inputNode)
		{
			result = this.#inputNode.value;
		}

		return result;
	}

	/**
	 * @returns {Address}
	 */
	get address(): ?Address
	{
		return this.#address;
	}

	#setAddressFromInput()
	{
		this.#address = this.#convertStringToAddress(
			this.#getInputValue()
		);

		this.#onAddressChangedEventEmit();
	}

	/**
	 * Close menu on mouse click outside
	 * @param {MouseEvent} event
	 */
	#onDocumentClick(event: MouseEvent)
	{
		if(this.#isDestroyed)
		{
			return;
		}

		if (event.target === this.#inputNode)
		{
			return;
		}

		if(this.#prompt.isShown())
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
		if(Array.isArray(locationsList) && locationsList.length > 0)
		{
			this.#prompt.setMenuItems(locationsList, this.#searchPhrase.requested);

			this.#prompt.addShowOnMapMenuItem(
				this.#getShowOnMapHandler(locationsList[0]),
				Loc.getMessage('LOCATION_WIDGET_PICK_ADDRESS_OR_SHOW_ON_MAP')
			);

			this.#prompt.getMenu().show();
		}
		else
		{
			const split = Autocomplete.#splitPhrase(this.#searchPhrase.current);
			this.#searchPhrase.current = split[0];
			this.#searchPhrase.dropped = split[1] + ' ' + this.#searchPhrase.dropped;

			if(this.#searchPhrase.current.length > 0)
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
						onclick: (event, item) => {
							this.#prompt.close();
						}
					}
				);

				this.#prompt.addShowOnMapMenuItem(
					this.#getShowOnMapHandler(null),
					Loc.getMessage('LOCATION_WIDGET_CHECK_ADDRESS_OR_SHOW_ON_MAP')
				);

				this.#prompt.getMenu().show();
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
				let firstItemUserDistance = DistanceCalculator.getDistanceFromLatLonInKm(
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

			this.emit(Autocomplete.#onShowOnMapClickedEvent);
		};
	}

	static #splitPhrase(phrase: string): Object
	{
		phrase = phrase.trim();

		if(phrase.length <= 0)
		{
			return['', ''];
		}

		const tailPosition = phrase.lastIndexOf(' ');

		if(tailPosition <= 0)
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
		if(event.data.location)
		{
			this.#fulfillSelection(event.data.location)
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
		if(location)
		{
			if (location.hasExternalRelation() && this.#sourceCode === location.sourceCode)
			{
				result = this.#getLocationDetails(location)
					.then((location: ?Location) => {
							this.#onLocationSelect(location);
							return true;
						},
						response => ErrorPublisher.getInstance().notify(response.errors)
					);
			}
			else
			{
				result = new Promise((resolve) => {
					setTimeout(() => {
						this.#onLocationSelect(location);
						resolve();
					}, 0);
				});
			}
		}
		else
		{
			result = new Promise((resolve) => {
				setTimeout(() => {
					this.#onLocationSelect(null);
					resolve();
				}, 0);
			});
		}

		return result;
	}

	#onAddressChangedEventEmit(excludeSetAddressFeatures: Array = [])
	{
		this.#addressString = this.#address ? this.#convertAddressToString(this.#address) : '';
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
			.then((location: ?Location) => {
					this.#setState(State.DATA_LOADED);
					return location;
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
		this.#address = location ? location.toAddress() : null;

		if(this.#address && this.#searchPhrase.dropped.length > 0)
		{
			this.#address.setFieldValue(this.#addressFormat.fieldForUnRecognized, this.#searchPhrase.dropped);
		}

		this.#onAddressChangedEventEmit();
	}

	#onInputKeyUp(e: KeyboardEvent): void
	{
		if(this.#isDestroyed)
		{
			return;
		}

		const now = Date.now();

		if(this.#prevKeyUpTime)
		{
			const delta = now - this.#prevKeyUpTime;
			this.#avgKeyUpDelay = (this.#avgKeyUpDelay + delta) / 2;
		}

		this.#prevKeyUpTime = now;

		if(
			this.#state !== State.DATA_INPUTTING
			&& this.#addressString.trim() !== this.#getInputValue().trim()
		)
		{
			this.#setState(State.DATA_INPUTTING);
		}

		if(this.#prompt.isShown())
		{
			switch (e.code)
			{
				case 'NumpadEnter':
				case 'Enter':
					if(this.#prompt.isItemChosen())
					{
						this.#fulfillSelection(this.#prompt.getChosenItem())
							.then(() => {
									this.#prompt.close();
								},
								error => BX.debug(error)
							);
					}
					return;
				case 'Tab':
				case 'Escape':
					this.#setState(State.DATA_SUPPOSED);
					this.#setAddressFromInput();
					this.#prompt.close();
					return;

				case 'ArrowUp':
					this.#prompt.choosePrevItem();
					return;

				case 'ArrowDown':
					this.#prompt.chooseNextItem();
					return;
			}
		}

		if(this.#addressString.trim() !== this.#getInputValue().trim())
		{
			this.showPrompt(this.#inputNode.value, this.#makeParams());
		}

		if(this.#inputNode.value.length === 0)
		{
			this.#showPresetLocations();
		}
	}

	/**
	 * @param {string} searchPhrase
	 * @param {Object} params
	 */
	showPrompt(searchPhrase: string, params: Object): void
	{
		this.#searchPhrase.requested = searchPhrase;
		this.#searchPhrase.current = searchPhrase;
		this.#searchPhrase.dropped = '';
		this.#showPromptInner(searchPhrase, params, this.#computePromptDelay());
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
		if(this.#prompt)
		{
			this.#prompt.close();
		}
	}

	isPromptShown(): boolean
	{
		if(this.#prompt)
		{
			this.#prompt.isShown();
		}
	}

	#showPromptInner(searchPhrase: string, params: Object, promptDelay: number): void
	{
		if(searchPhrase.length > this.#minCharsCountToAutocomplete)
		{
			if(this.#timerId !== null)
			{
				clearTimeout(this.#timerId);
			}

			this.#timerId = this.#createTimer(searchPhrase, params, promptDelay);
		}
	}

	/**
	 * Wait for further user input for some time
	 * @param {string} searchPhrase
	 * @param {object} params
	 * @param {number} promptDelay
	 * @returns {number}
	 */
	#createTimer(searchPhrase: string, params: Object, promptDelay: number): number
	{
		return setTimeout(() => {

				// to avoid multiple parallel requests, if server responses are too slow.
				if(this.#isAutocompleteRequestStarted)
				{
					clearTimeout(this.#timerId);
					this.#timerId = this.#createTimer(searchPhrase, params, promptDelay);
					return;
				}

				this.emit(Autocomplete.#onSearchStartedEvent);
				this.#isAutocompleteRequestStarted = true;
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
		if(this.#isDestroyed)
		{
			return;
		}

		Event.unbindAll(this);

		if(this.#prompt)
		{
			this.#prompt.destroy();
			this.#prompt = null;
		}

		this.#timerId = null;

		if(this.#inputNode)
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