import { Text } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Dialog, ItemOptions } from 'ui.entity-selector';

import { EntitySelectorEntity, Model } from 'booking.const';
import { ResourceTypeModel } from 'booking.model.resource-types';
import { resourceTypeService } from 'booking.provider.service.resources-type-service';

import { ErrorMessage } from '../error-message/error-message';
import './base-fields.css';

export const BaseFields = {
	name: 'ResourceSettingsCardBaseFields',
	emits: [
		'nameUpdate',
		'typeUpdate',
	],
	props: {
		initialResourceName: {
			type: String,
			default: '',
		},
		initialResourceType: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {
			entityId: EntitySelectorEntity.ResourceType,
			typeSelectorId: `booking-resource-creation-types${Text.getRandom()}`,
			typeName: this.initialResourceType.typeName,
		};
	},
	computed: {
		resourceName: {
			get(): string
			{
				return this.initialResourceName;
			},
			set(name: string | null = '')
			{
				this.$emit('nameUpdate', name);
			},
		},
		invalidResourceName(): boolean
		{
			return this.$store.state[Model.ResourceCreationWizard].invalidResourceName;
		},
		invalidResourceType(): boolean
		{
			return this.$store.state[Model.ResourceCreationWizard].invalidResourceType;
		},
		errorMessage(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_REQUIRED_FIELD');
		},
	},
	methods: {
		showTypeSelector(): void
		{
			const dialog = this.getTypeSelectorDialog(this.$refs.typeSelectorAngle);

			dialog.show();
		},
		getTypeSelectorDialog(bindElement: HTMLElement): Dialog
		{
			const typeSelectorDialog = Dialog.getById(this.typeSelectorId);

			if (typeSelectorDialog)
			{
				typeSelectorDialog.setTargetNode(bindElement);

				return typeSelectorDialog;
			}

			return new Dialog({
				id: this.typeSelectorId,
				targetNode: bindElement,
				width: 300,
				height: 400,
				enableSearch: true,
				dropdownMode: true,
				context: 'bookingResourceCreationType',
				multiple: false,
				cacheable: true,
				entities: [
					{
						id: this.entityId,
						dynamicLoad: true,
						dynamicSearch: true,
					},
				],
				popupOptions: {
					targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
				},
				searchOptions: {
					allowCreateItem: true,
					footerOptions: {
						label: this.loc('BRCW_SETTINGS_CARD_TYPE_SELECTOR_CREATE_BTN'),
					},
				},
				events: {
					'Search:onItemCreateAsync': (baseEvent: BaseEvent) => {
						return new Promise((resolve) => {
							const { searchQuery } = baseEvent.getData();
							const dialog: Dialog = baseEvent.getTarget();

							this.createType(searchQuery.getQuery())
								.then((resourceType: ResourceTypeModel) => {
									this.updateResourceType(resourceType.id, resourceType.name);
									dialog.addItem(this.prepareTypeToDialog(resourceType));
									dialog.hide();
									resolve();
								})
								.catch(() => {
									resolve();
								})
							;
						});
					},
					'Item:onSelect': (baseEvent: BaseEvent) => {
						const selectedItem = baseEvent.getData().item;
						this.updateResourceType(selectedItem.getId(), selectedItem.getTitle());
					},
				},
			});
		},
		async createType(typeName: string): Promise<ResourceTypeModel>
		{
			return resourceTypeService.add({
				moduleId: 'booking',
				name: typeName,
			});
		},
		prepareTypeToDialog(type: ResourceTypeModel): ItemOptions
		{
			return {
				id: type.id,
				entityId: this.entityId,
				title: type.name,
				sort: 1,
				selected: true,
				tabs: 'recents',
				supertitle: this.loc('BRCW_SETTINGS_CARD_TYPE_SELECTOR_SUPER_TITLE'),
				avatar: '/bitrix/js/booking/images/entity-selector/resource-type.svg',
			};
		},
		updateResourceType(typeId: number, typeName: string): void
		{
			this.typeName = typeName;

			this.$emit('typeUpdate', typeId);
		},
		scrollToBaseFieldsForm(): void
		{
			this.$refs.baseFieldsForm?.scrollIntoView(true, {
				behavior: 'smooth',
				block: 'center',
			});
		},
	},
	watch: {
		invalidResourceName(invalid: boolean): void
		{
			if (invalid)
			{
				this.scrollToBaseFieldsForm();
			}
		},
		invalidResourceType(invalid: boolean): void
		{
			if (invalid)
			{
				this.scrollToBaseFieldsForm();
			}
		},
	},
	components: {
		ErrorMessage,
	},
	template: `
		<div ref="baseFieldsForm" class="ui-form resource-creation-wizard__form-settings --base">
			<div class="ui-form-row-inline booking--rcw--form-row-align">
				<div class="ui-form-row">
					<div class="ui-form-label">
						<label class="ui-ctl-label-text" for="brcw-settings-resource-name">
							{{ loc('BRCW_SETTINGS_CARD_NAME_LABEL') }}
						</label>
					</div>
					<div class="ui-form-content booking--rcw--field-with-validation">
						<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
							<input
								v-model.trim="resourceName"
								id="brcw-settings-resource-name"
								data-id="brcw-settings-resource-name-input"
								type="text"
								class="ui-ctl-element"
								:class="{ '--error': invalidResourceName }"
								:placeholder="this.loc('BRCW_SETTINGS_CARD_NAME_PLACEHOLDER')"
							/>
						</div>
						<ErrorMessage
							v-if="invalidResourceName"
							:message="errorMessage"
						/>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">
							{{ loc('BRCW_SETTINGS_CARD_TYPE_LABEL') }}
						</div>
					</div>
					<div class="ui-form-content booking--rcw--field-with-validation">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
							<div
								ref="typeSelectorAngle"
								class="ui-ctl-after ui-ctl-icon-angle"
							></div>
							<div
								ref="typeSelectorElement"
								data-id="brcw-settings-resource-type-selector"
								class="ui-ctl-element resource-creation-wizard__form-settings-element"
								:class="{
									'--placeholder': !typeName,
									'--error': invalidResourceType,
								}"
								@click="showTypeSelector"
							>
								<template v-if="typeName">
									{{ typeName }}
								</template>
								<template v-else>
									{{ loc('BRCW_SETTINGS_CARD_TYPE_PLACEHOLDER') }}
								</template>
							</div>
						</div>
						<ErrorMessage
							v-if="invalidResourceType"
							:message="errorMessage"
						/>
					</div>
				</div>
			</div>
		</div>
	`,
};
