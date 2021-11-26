import {Vue} from 'ui.vue';
import ClosableDirective from '../closabledirective';
import {Location} from 'location.core';
import {State, AutocompleteFeature} from 'location.widget'
import 'ui.notification';
import '../css/address.css';

export default
{
	directives: {
		closable: ClosableDirective
	},
	props: {
		name: {type: String, required: true},
		initValue: {},
		settings: {},
		options: {required: false},
		isStartMarker: {type: Boolean, required: true},
	},
	data()
	{
		return {
			value: null,
			enterTookPlace: false,
			rightIcon: null,
			isEntering: false,
			isLoading: false,
			editMode: false,
			addressWidgetState: null,
			enteredAddresses: [],
		}
	},
	methods: {
		switchToEditMode()
		{
			this.showMap();
			this.editMode = true;
		},
		clarifyAddress()
		{
			setTimeout(() => {
				this.$refs['input-node'].focus();
				this.$refs['input-node'].click();
				this.$refs['input-node'].click();
			}, 0);
		},
		clearAddress()
		{
			this.addressWidget.address = null;
			this.changeValue(null);
			this.clarifyAddress();
		},
		onControlClicked()
		{
			this.closeMap();
		},
		onControlFocus()
		{
			this.enterTookPlace = true;
			this.isEntering = true;
		},
		onControlBlur()
		{
			setTimeout(() => {
				this.isEntering = false;
			}, 200)

			this.editMode = false;

			this.closeMap();
		},
		changeValue(newValue)
		{
			this.value = newValue;

			this.syncRightIcon();

			this.$emit('change', this.value);

			if (this.onChangeCallback)
			{
				this.onChangeCallback();
			}
		},
		syncRightIcon()
		{
			if (this.$refs['input-node'].value.length === 0)
			{
				this.rightIcon = 'search';
			}
			else
			{
				this.rightIcon = 'clear';
			}
		},
		buildAddress(value)
		{
			try
			{
				return new BX.Location.Core.Address(JSON.parse(value));
			}
			catch(e)
			{
				return null;
			}
		},
		isValueValid(value)
		{
			return (
				value
				&& value.latitude
				&& value.longitude
				&& !(value.latitude === '0' && value.longitude === '0')
			);
		},
		getPresetLocationsProvider()
		{
			return () => {
				let result = this.options && this.options.hasOwnProperty('defaultItems')
					? this.options.defaultItems.map((item) => new Location(item))
					: [];

				for (let enteredAddress of this.enteredAddresses)
				{
					let location = enteredAddress.toLocation();
					if (!location)
					{
						continue;
					}

					location.name = BX.Location.Core.AddressStringConverter.convertAddressToString(
						enteredAddress,
						this.addressWidget.addressFormat,
						BX.Location.Core.AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE,
						BX.Location.Core.AddressStringConverter.CONTENT_TYPE_TEXT
					);

					result.push(location);
				}

				return result.filter((location, index, self) =>
					index === self.findIndex((l) => (
						l.name === location.name
					))
				);
			};
		},
		/**
		 * Map Feature Methods
		 */
		getMap()
		{
			if (!this.addressWidget)
			{
				return null;
			}

			for( let feature of this.addressWidget.features)
			{
				if(feature instanceof BX.Location.Widget.MapFeature)
				{
					return feature;
				}
			}

			return null;
		},
		showMap()
		{
			let map = this.getMap();

			if (map)
			{
				map.showMap();
			}
		},
		closeMap()
		{
			let map = this.getMap();

			if (map)
			{
				map.closeMap();
			}
		},
	},
	computed: {
		addressFormatted()
		{
			if(!this.value || !this.addressWidget)
			{
				return '';
			}

			let address = this.buildAddress(this.value);
			if (!address)
			{
				return '';
			}

			return address.toString(
				this.addressWidget.addressFormat,
				BX.Location.Core.AddressStringConverter.STRATEGY_TYPE_FIELD_SORT
			);
		},
		isEditMode()
		{
			return this.editMode || !this.value;
		},
		wrapperClass()
		{
			return {
				'ui-ctl': true,
				'ui-ctl-textbox': true,
				'ui-ctl-danger': this.needsClarification,
				'ui-ctl-w100': true,
				'ui-ctl-after-icon': true,
				'sale-address-control-top-margin-5 sale-address-control-top-margin-width-820': this.isEditMode
			}
		},
		mapMarkerClass()
		{
			return {
				'salescenter-delivery-path-icon': true,
				'salescenter-delivery-path-icon--green': !this.isStartMarker
			};
		},
		rightIconClass()
		{
			return {
				'ui-ctl-after': true,
				'ui-ctl-icon-btn': true,
				'ui-ctl-icon-search': (this.rightIcon === 'search'),
				'ui-ctl-icon-clear': (this.rightIcon === 'clear'),
				'sale-address-control-path-input-clear': true,
			};
		},
		needsClarification()
		{
			return (
				!this.isEntering
				&& this.enterTookPlace
				&& !this.value
				&& this.addressWidgetState !== State.DATA_LOADING
			);
		},
		localize()
		{
			return Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
		},
	},
	mounted()
	{
		if (this.initValue)
		{
			let initValue = null;
			let address = JSON.parse(this.initValue);

			if (this.isValueValid(address))
			{
				initValue = this.initValue;
			}
			else
			{
				/**
				 * Simulate invalid input
				 */
				this.isEntering = false;
				this.enterTookPlace = true;
			}

			this.changeValue(initValue);
		}

		this.addressWidget = (new BX.Location.Widget.Factory).createAddressWidget({
			address: this.initValue ? this.buildAddress(this.initValue) : null,
			mode: BX.Location.Core.ControlMode.edit,
			mapBehavior: 'manual',
			useFeatures: {
				fields: false,
				map: true,
				autocomplete: true
			},
			popupOptions: {
				offsetLeft: 14,
			},
			popupBindOptions: {
				forceBindPosition: true,
			},
			presetLocationsProvider: this.getPresetLocationsProvider()
		});

		/**
		 * Redefine native onInputKeyup
		 */
		const nativeOnInputKeyup = this.addressWidget.onInputKeyup;
		this.addressWidget.onInputKeyup = (e: KeyboardEvent) =>
		{
			switch (e.code)
			{
				case 'Enter':
				case 'NumpadEnter':
					return;
				default:
					break;
			}

			nativeOnInputKeyup.call(this.addressWidget, e);
		};

		/**
		 * Subscribe to widget events
		 */
		this.addressWidget.subscribeOnAddressChangedEvent((event) => {
			let data = event.getData();

			this.editMode = true;

			let address = data.address;

			if (!this.isValueValid(address))
			{
				this.changeValue(null);
			}
			else
			{
				this.enteredAddresses.push(address);
				this.changeValue(address.toJson());
				this.showMap();
			}
		});

		this.addressWidget.subscribeOnStateChangedEvent((event) => {
			let data = event.getData();

			this.addressWidgetState = data.state;

			if (data.state === State.DATA_INPUTTING)
			{
				this.changeValue(null);
				this.closeMap();
			}
			else if (data.state === State.DATA_LOADING)
			{
				this.isLoading = true;
			}
			else if (data.state === State.DATA_LOADED)
			{
				this.isLoading = false;
			}
		});

		this.addressWidget.subscribeOnFeatureEvent((event) => {
			let data = event.getData();

			if (data.feature instanceof AutocompleteFeature)
			{
				if (data.eventCode === AutocompleteFeature.searchStartedEvent)
				{
					this.isLoading = true;
				}
				else if (data.eventCode === AutocompleteFeature.searchCompletedEvent)
				{
					this.isLoading = false;
				}
			}
		});

		this.addressWidget.subscribeOnErrorEvent((event) => {
			let data = event.getData();
			let errors = data.errors;
			let errorMessage = errors
				.map((error) => error.message + (error.code.length ? `${error.code}` : ''))
				.join(', ');

			this.isLoading = false;

			BX.UI.Notification.Center.notify({
				content: errorMessage,
			});
		});

		/**
		 * Render widget
		 */
		this.addressWidget.render({
			inputNode: this.$refs['input-node'],
			autocompleteMenuElement: this.$refs['autocomplete-menu'],
			mapBindElement: this.$refs['map-marker'],
			controlWrapper: this.$refs['autocomplete-menu'],
		});

		this.syncRightIcon();
	},
	template: `
		<div class="salescenter-delivery-path-control">
			<div ref="map-marker" :class="mapMarkerClass"></div>
				<div
					v-closable="{
						exclude: ['input-node'],
						handler: 'onControlBlur'
					}"
					class="ui-ctl-w100"
				>
					<div :class="wrapperClass">
						<div
							v-show="isLoading"
							class="ui-ctl-after ui-ctl-icon-loader"
						></div>
						<div
							v-show="isEditMode" 
							ref="autocomplete-menu"
							class="sale-address-control-path-input-wrapper"
						>
							<input
								@click="onControlClicked"
								@focus="onControlFocus"
								ref="input-node"
								type="text"
								class="ui-ctl-element"
							/>
							<div
								v-show="!isLoading && isEditMode"
								@click="clearAddress"
								@mouseover.stop.prevent=""
								:class="rightIconClass"
							></div>
							<span
								v-show="needsClarification"
								@mouseover.stop.prevent=""
								@click="clarifyAddress"
								class="sale-address-control-path-input--alert"
							>
								{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLARIFY_ADDRESS}}
							</span>
						</div>
						<div v-show="!isEditMode"class="sale-address-control-path-input-wrapper">
							<span
								@click="switchToEditMode"
								type="text"
								class="ui-ctl-element ui-ctl-textbox sale-address-control-path-input"
								contenteditable="false"
								v-html="addressFormatted"
							></span>
						</div>
						<input v-model="value" :name="name" type="hidden" />
					</div>
				</div>
			</div>
		</div>
	`
};
