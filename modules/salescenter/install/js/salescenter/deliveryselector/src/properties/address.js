import ClosableDirective from '../closabledirective';
import {Location} from 'location.core';
import {State, AutocompleteFeature} from 'location.widget'

import '../css/address.css';

export default {
	directives: {
		closable: ClosableDirective
	},
	props: {
		name: {type: String, required: true},
		initValue: {},
		settings: {},
		options: {required: false},
		editable: {type: Boolean, default: true},
	},
	data()
	{
		return {
			enterTookPlace: false,
			isEntering: false,
			isLoading: false,
			editMode: false,
			value: null,
			addressWidgetState: null,
		}
	},
	methods: {
		onInputClicked()
		{
			if (!this.editable)
			{
				return;
			}

			if (this.value)
			{
				this.showMap();
			}
			else
			{
				this.closeMap();
			}
		},
		onTextClicked()
		{
			if (!this.editable)
			{
				return;
			}

			this.editMode = true;
		},
		onClearClicked()
		{
			if (!this.editable)
			{
				return;
			}

			this.addressWidget.address = null;
			this.changeValue(null);
			this.closeMap();
		},
		onInputFocus()
		{
			this.enterTookPlace = true;
			this.isEntering = true;
		},
		onInputBlur()
		{
			this.isEntering = false;

			this.closeMap();
		},
		onInputEnterKeyDown()
		{
			this.isEntering = false;
		},
		changeValue(newValue)
		{
			this.value = newValue;

			this.$emit('change', this.value);

			if (this.onChangeCallback)
			{
				this.onChangeCallback();
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

			this.editMode = false;
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
			let showDangerIndicator = (
				!this.isEntering
				&& this.enterTookPlace
				&& !this.value
				&& this.addressWidgetState !== State.DATA_LOADING
			);

			return {
				'ui-ctl': true,
				'ui-ctl-textbox': true,
				'ui-ctl-danger': showDangerIndicator,
				'ui-ctl-w100': true,
				'ui-ctl-after-icon': true,
				'sale-address-control-top-margin-5': this.isEditMode
			}
		},
	},
	mounted()
	{
		if (this.initValue)
		{
			this.value = this.initValue;
		}

		let presetLocationList = this.options && this.options.hasOwnProperty('defaultItems')
			? this.options.defaultItems.map((item) => new Location(item))
			: [];

		this.addressWidget = (new BX.Location.Widget.Factory).createAddressWidget({
			address: this.initValue ? this.buildAddress(this.initValue) : null,
			mapBehavior: 'manual',
			popupBindOptions: {
				position: 'right'
			},
			mode: BX.Location.Core.ControlMode.edit,
			useFeatures: {
				fields: false,
				map: true,
				autocomplete: true
			},
			presetLocationList
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

			if (!address.latitude || !address.longitude)
			{
				this.changeValue(null);
				this.closeMap();
			}
			else
			{
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
				this.isLoading = (data.eventCode === AutocompleteFeature.searchStartedEvent);
			}
		});

		/**
		 * Render widget
		 */
		this.addressWidget.render({
			inputNode: this.$refs['input-node'],
			mapBindElement: this.$refs['input-node'],
			controlWrapper: this.$refs['control-wrapper'],
		});
	},
	template: `
		<div
			v-closable="{
				exclude: ['input-node'],
				handler: 'onInputBlur'
			}"
			class="ui-ctl-w100"
		>
			<div :class="wrapperClass" ref="control-wrapper">
				<div
					v-show="!isLoading && isEditMode"
					@click="onClearClicked"
					class="ui-ctl-after ui-ctl-icon-btn ui-ctl-icon-clear"
				></div>
				<div
					v-show="isLoading"
					class="ui-ctl-after ui-ctl-icon-loader"
				></div>
				<input
					v-show="isEditMode"
					@click="onInputClicked"
					@focus="onInputFocus"
					@keydown.enter="onInputEnterKeyDown"
					:disabled="!editable"
					ref="input-node"
					type="text"
					class="ui-ctl-element"
				/>
				<span
					v-show="!isEditMode"
					@click="onTextClicked"
					type="text"
					class="ui-ctl-element ui-ctl-textbox sale-address-control-path-input"
					contenteditable="false"
					v-html="addressFormatted"
				></span>
				<input v-model="value" :name="name" type="hidden" />
			</div>					
		</div>
	`
};
