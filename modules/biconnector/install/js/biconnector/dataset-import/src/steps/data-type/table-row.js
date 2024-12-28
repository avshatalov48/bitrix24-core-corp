import '../../css/format-table.css';
import { ErrorPopup } from '../../layout/error-popup';
import { DataTypeDescriptions } from '../../types/data-types';
import { DataTypeSelector } from './data-type-selector';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { hint } from 'ui.vue3.directives.hint';

export const TableRow = {
	directives: {
		hint,
	},
	props: {
		enabled: {
			type: Boolean,
			required: false,
			default: true,
		},
		index: {
			type: Number,
			required: true,
		},
		fieldSettings: {
			type: Object,
			required: false,
			default: null,
		},
		invalidFields: {
			type: Object,
			required: false,
			default: {},
		},
		isEditMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data()
	{
		return {
			displayedValidationErrors: {
				name: true,
			},
			errorPopup: {
				name: null,
			},
			errorPopupTimeout: null,
		};
	},
	computed: {
		isNameValid(): boolean
		{
			return !('name' in this.invalidFields);
		},
		displayedErrorForName(): string
		{
			return this.invalidFields.name.message;
		},
		set(): Set
		{
			return Set;
		},
		originalsHintText(): string
		{
			const originalType = this.fieldSettings.originalType;
			const originalName = this.fieldSettings.originalName;

			let typeText = '';
			if (originalType)
			{
				typeText = this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_ORIG_TYPE', {
					'#CLASS#': '<span class="format-table__orig_info_title">',
					'#/CLASS#': '</span>',
					'#TYPENAME#': DataTypeDescriptions[originalType].title,
				});
			}

			const nameText = this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_ORIG_NAME', {
				'#CLASS#': '<span class="format-table__orig_info_title">',
				'#/CLASS#': '</span>',
				'#FIELDNAME#': originalName,
			});

			if (!originalType)
			{
				return nameText;
			}

			return `${typeText}<br>${nameText}`;
		},
		isNeedShowOriginalNameHint(): boolean
		{
			return (this.$store.state.config.fileProperties?.firstLineHeader ?? true)
				&& this.$store.state.config.connectionProperties?.connectionType
			;
		},
		hintOptions(): Object
		{
			return {
				html: this.originalsHintText,
				popupOptions: {
					angle: {
						position: 'left',
					},
					offsetLeft: 30,
					offsetTop: -46,
					autoHide: false,
				},
			};
		},
	},
	emits: [
		'checkboxClick',
		'fieldChange',
	],
	methods: {
		onCheckboxClick(event)
		{
			event.preventDefault();
			this.$emit('checkboxClick', {
				index: this.index,
			});
		},
		onTypeSelected(type)
		{
			this.$emit('fieldChange', {
				fieldName: 'type',
				value: type,
				index: this.index,
			});
		},
		onFieldInput(event)
		{
			this.displayedValidationErrors[event.target.name] = false;
			this.$emit('fieldChange', {
				fieldName: event.target.name,
				value: event.target.value,
				index: this.index,
			});
		},
		onFieldBlur(event)
		{
			this.displayedValidationErrors[event.target.name] = true;

			if ('name' in this.invalidFields)
			{
				this.showErrorHint();
				this.errorPopupTimeout = setTimeout(() => {
					this.hideErrorHint();
					this.errorPopupTimeout = null;
				}, 3000);
			}
		},
		showValidationErrors()
		{
			Object.keys(this.displayedValidationErrors).forEach((field) => {
				this.displayedValidationErrors[field] = true;
			});
		},
		showErrorHint()
		{
			if (this.errorPopupTimeout)
			{
				clearTimeout(this.errorPopupTimeout);
				this.errorPopupTimeout = null;
			}
			else
			{
				this.errorPopup.name = this.createErrorPopup();
				this.errorPopup.name.show();
			}
		},
		hideErrorHint()
		{
			if (this.errorPopup.name)
			{
				this.errorPopup.name.close();
			}
		},
		createErrorPopup()
		{
			return ErrorPopup.create(this.displayedErrorForName, this.$refs.errorIconWrapper);
		},
	},
	watch: {
		isNeedShowOriginalNameHint()
		{
			if (this.$refs.originalsHint)
			{
				this.$refs.originalsHint.remove();
			}
		},
	},
	components: {
		DataTypeButton: DataTypeSelector,
		BIcon,
	},
	// language=Vue
	template: `
		<tr class="format-table__row">
			<td class="format-table__checkbox-cell">
				<input class="format-table__checkbox" ref="visibilityCheckbox" type="checkbox" @change="onCheckboxClick" :checked="enabled">
			</td>
			<td class="format-table__cell">
				<DataTypeButton :selected-type="fieldSettings.type" @value-change="onTypeSelected" :is-edit-mode="isEditMode" />
			</td>
			<td class="format-table__cell">
				<div
					class="ui-ctl ui-ctl-textbox ui-ctl-w100 format-table__name-control"
					:class="{
						'format-table__text-input--invalid': displayedValidationErrors.name && !isNameValid,
						'format-table__text-input--disabled': isEditMode,
						'ui-ctl-after-icon': !isEditMode && !isNameValid,
					}"
				>
					<input
						class="ui-ctl-element format-table__text-input format-table__name-input"
						:disabled="isEditMode"
						type="text"
						:placeholder="$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_PLACEHOLDER')"
						name="name"
						@input="onFieldInput"
						@blur="onFieldBlur"
						:value="fieldSettings.name"
					>
					<div class="ui-ctl-after" ref="errorIconWrapper">
						<div
							class="ui-icon-set --warning format-table__error-icon"
							@mouseenter="showErrorHint"
							@mouseleave="hideErrorHint"
							v-if="displayedValidationErrors.name && !isNameValid"
						></div>
					</div>
				</div>
			</td>
			<td class="format-table__cell" v-if="isNeedShowOriginalNameHint">
				<div class="format-table__orig-type-hint-wrapper" v-hint="hintOptions" ref="originalsHint">
					<div class="format-table__orig-type-hint">
						<BIcon
							:name="set.INFO_1"
							:size="20"
							color="#B5BABE"
						></BIcon>
					</div>
				</div>
			</td>
		</tr>
	`,
};
