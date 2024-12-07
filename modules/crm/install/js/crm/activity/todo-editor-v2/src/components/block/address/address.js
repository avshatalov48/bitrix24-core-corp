import { Address, Format } from 'location.core';
import { Loc, Text, Type } from 'main.core';
import { InputPopup } from './input-popup';

export const TodoEditorBlocksAddress = {
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		settings: {
			type: Object,
			required: true,
		},
		filledValues: {
			type: Object,
		},
		context: {
			type: Object,
			required: true,
		},
		isFocused: {
			type: Boolean,
		},
	},

	emits: [
		'close',
		'updateFilledValues',
	],

	data(): Object
	{
		const data = {
			address: null,
			addressFormatted: null,
			addressJson: null,
		};

		return this.getPreparedData(data);
	},

	mounted(): void
	{
		if (this.isFocused)
		{
			void this.$nextTick(this.onShowAddressPopup);
		}
	},

	beforeUnmount(): void
	{
		this.inputPopup?.destroy();
	},

	methods: {
		getId(): string
		{
			return 'address';
		},
		getPreparedData(data: Object): Object
		{
			this.format = new Format(JSON.parse(Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_FORMAT')));

			const { filledValues, format } = this;
			let addressInstance = null;

			if (Type.isStringFilled(filledValues?.addressFormatted))
			{
				addressInstance = new Address({
					languageId: format.languageId,
				});

				addressInstance.setFieldValue(format.fieldForUnRecognized, filledValues.addressFormatted);

				// eslint-disable-next-line no-param-reassign
				data.address = addressInstance;
				data.addressFormatted = addressInstance?.toString(format).replaceAll('<br />', ', ');;
			}

			if (Type.isStringFilled(filledValues?.addressJson))
			{
				// eslint-disable-next-line no-param-reassign
				data.addressJson = filledValues.addressJson;
			}
			else
			{
				// eslint-disable-next-line no-param-reassign
				data.addressJson = addressInstance?.toJson();
			}

			return data;
		},
		getExecutedData(): Object
		{
			const { addressFormatted, addressJson } = this;

			return {
				addressFormatted,
				address: addressJson,
			};
		},
		emitUpdateFilledValues(): void
		{
			let { filledValues } = this;
			const { addressFormatted, addressJson } = this;

			const newFilledValues = {
				addressFormatted,
				addressJson,
			};
			filledValues = { ...filledValues, ...newFilledValues };
			this.$emit('updateFilledValues', this.getId(), filledValues);
		},
		onShowAddressPopup(): void
		{
			if (Type.isNil(this.inputPopup))
			{
				this.inputPopup = new InputPopup({
					bindElement: this.$refs.address,
					title: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_BLOCK_POPUP_TITLE'),
					addressFormatted: this.addressFormatted ?? '',
					addressJson: this.addressJson ?? '',
					format: this.format,
					onSubmit: (value: string) => {
						this.setAddress(value);
					},
				});
			}

			this.inputPopup.show();
		},
		setAddress(address: Address): void
		{
			this.address = address;

			if (Type.isObject(address))
			{
				this.addressJson = address.toJson();
				this.addressFormatted = address.toString(this.format).replaceAll('<br />', ', ');
			}
			else
			{
				this.addressJson = '';
				this.addressFormatted = '';
			}
		},
	},

	computed: {
		encodedTitle(): string
		{
			return Text.encode(this.title);
		},
		iconStyles(): Object
		{
			if (!this.icon)
			{
				return {};
			}

			const path = `/bitrix/js/crm/activity/todo-editor-v2/images/${this.icon}`;

			return {
				background: `url('${encodeURI(Text.encode(path))}') center center`,
			};
		},
		actionTitle(): string
		{
			return this.hasAddress ? this.changeTitle : this.addTitle;
		},
		changeTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_BLOCK_CHANGE_ACTION');
		},
		addTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_BLOCK_ADD_ACTION');
		},
		hasAddress(): boolean
		{
			return Type.isStringFilled(this.addressFormatted);
		},
		preparedAddress(): string
		{
			return Type.isStringFilled(this.addressFormatted) ? this.addressFormatted : '';
		},
	},

	created()
	{
		this.$watch(
			'address',
			this.emitUpdateFilledValues,
			{
				deep: true,
			},
		);

		this.$watch('addressFormatted', this.emitUpdateFilledValues);
	},

	template: `
		<div class="crm-activity__todo-editor-v2_block-header --address">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon"
				:style="iconStyles"
			></span>
			<span>{{ encodedTitle }}</span>
			<span 
				class="crm-activity__todo-editor-v2_block-header-data"
				v-html="preparedAddress"
			>
			</span>
			<span
				@click="onShowAddressPopup"
				ref="address"
				class="crm-activity__todo-editor-v2_block-header-action"
			>
				{{ actionTitle }}
			</span>
			<div
				@click="$emit('close', id)"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
	`,
};
