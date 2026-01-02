import { ajax as Ajax, Text } from 'main.core';
import { BaseStep } from './base-step';
import { ConnectionSelectorField } from '../fields/type/connection-selector-field';
import { CustomField } from '../fields/type/custom-field';
import { TableSelectorField } from '../fields/type/table-selector-field';
import { StringField } from '../fields/type/string-field';
import { EventEmitter } from 'main.core.events';
import '../css/connection.css';

export const ConnectionStep = {
	extends: BaseStep,
	props: {
		connections: {
			type: Array,
			required: true,
		},
	},
	data(): Object
	{
		return {
			tableId: this.$store.state.config.table,
			connectionSelectorId: 'biconnector-external-connection',
			tableSelectorId: 'biconnector-external-table',
			unvalidatedFields: {},
		};
	},
	components: {
		ConnectionSelectorField,
		CustomField,
		TableSelectorField,
		StringField,
	},
	computed: {
		defaultTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_HEADER');
		},
		connectionSelectorOptions(): Object
		{
			return {
				selectorId: this.connectionSelectorId,
			};
		},
		tableSelectorOptions(): Object
		{
			return {
				selectorId: this.tableSelectorId,
			};
		},
		selectedConnectionType(): string
		{
			return this.$store.getters.connectionProperties?.connectionType;
		},
		selectedConnectionAvatar(): ?string
		{
			const id = this.selectedConnectionId;
			if (id > 0)
			{
				const connection = this.connections.find((item) => parseFloat(item.ID) === id);
				if (connection)
				{
					return connection.AVATAR;
				}
			}

			return `/bitrix/images/biconnector/database-connections/${this.selectedConnectionType}.svg`;
		},
		selectedConnectionId(): number
		{
			return this.$store.getters.connectionProperties?.connectionId ?? 0;
		},
		selectedConnectionName(): number
		{
			return this.$store.getters.connectionProperties?.connectionName;
		},
		selectedTableName(): string
		{
			return this.$store.getters.connectionProperties?.tableName;
		},
		isEditMode(): boolean
		{
			return this.$store.getters.isEditMode;
		},
	},
	methods: {
		onConnectionSelected(event)
		{
			const tag = event.data.tag;
			const dialogItems = event.target.getDialog().getItems();
			dialogItems.forEach((item) => {
				if (item.getId() === tag.getId())
				{
					tag.customData = item.getCustomData();
				}
			});

			const sourceId = parseInt(event.data.tag.getId(), 10);

			this.$store.commit('setConnectionProperties', {
				connectionType: event.data.tag.getCustomData().get('connectionType'),
				connectionId: sourceId,
				tableName: null,
			});

			Ajax.runAction('biconnector.externalsource.source.checkExistingConnection', {
				data: {
					sourceId,
				},
			})
				.then(() => {
					this.unvalidatedFields = {};
				})
				.catch((response) => {
					this.unvalidatedFields = {
						connection: {
							result: false,
							message: response.errors[0].message,
						},
					};
				})
			;
			this.$emit('validation', false);
		},
		onConnectionDeselected()
		{
			this.$store.commit('setConnectionProperties', {
				connectionType: null,
				connectionId: null,
				tableName: null,
			});
			this.$emit('validation', false);
			this.unvalidatedFields = {};
		},
		onTableSelected(event)
		{
			this.$store.commit('setConnectionProperties', {
				connectionType: this.selectedConnectionType,
				connectionId: this.selectedConnectionId,
				tableName: event.data.tag.getTitle(),
			});

			const tag = event.data.tag;
			const dialogItems = event.target.getDialog().getItems();
			dialogItems.forEach((item) => {
				if (item.getId() === tag.getId())
				{
					tag.customData = item.getCustomData();
				}
			});

			this.$store.commit('setDatasetProperties', {
				id: null,
				name: event.data.tag.getCustomData().get('datasetName'),
				code: event.data.tag.getId(),
				description: event.data.tag.getTitle(),
				externalCode: event.data.tag.getId(),
				externalName: event.data.tag.getTitle(),
			});

			this.$emit('tableSelected', event);
			this.$emit('validation', true);
			this.unvalidatedFields = {};
		},
		onTableDeselected(event)
		{
			this.$store.commit('setConnectionProperties', {
				connectionType: this.selectedConnectionType,
				connectionId: this.selectedConnectionId,
				tableName: null,
			});
			this.$store.commit('setDatasetProperties', {
				id: null,
				name: null,
				code: null,
				description: null,
				externalCode: null,
				externalName: null,
			});
			this.$emit('tableDeselected', event);
			this.$emit('validation', false);
			this.unvalidatedFields = {};
		},
		openConnectionSlider()
		{
			const link = `/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=${this.selectedConnectionId}&closeAfterCreate=Y`;
			BX.SidePanel.Instance.open(link, {
				width: 564,
				allowChangeHistory: false,
				cacheable: false,
			});
		},
		handleSliderMessage(event)
		{
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionSave')
			{
				const connectionProperties = this.$store.getters.connectionProperties;
				connectionProperties.connectionName = Text.decode(messageEvent.data.connection.name);
				this.$store.commit('setConnectionProperties', connectionProperties);
			}
		},
	},
	mounted()
	{
		EventEmitter.subscribe('SidePanel.Slider:onMessage', this.handleSliderMessage);
	},
	beforeUnmount()
	{
		EventEmitter.unsubscribe('SidePanel.Slider:onMessage', this.handleSliderMessage);
	},
	emits: [
		'tableSelected',
		'tableDeselected',
	],
	template: `
		<Step
			:title="displayedTitle"
			:hint="displayedHint"
			:is-open-initially="isOpenInitially"
			:disabled="disabled"
			ref="stepBlock"
		>
			<ConnectionSelectorField
				v-if="!this.isEditMode"
				:options="this.connectionSelectorOptions"
				name="connections"
				:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_FIELD_TITLE')"
				:items="this.connections"
				:is-disabled="disabled"
				:connection-id="this.selectedConnectionId"
				@value-change="onConnectionSelected"
				@value-clear="onConnectionDeselected"
				ref="connectionField"
				:is-valid="unvalidatedFields.connection?.result ?? true"
				:error-message="unvalidatedFields.connection?.message ?? ''"
			/>
			<TableSelectorField
				v-if="!this.isEditMode"
				:options="this.tableSelectorOptions"
				name="tables"
				:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_FIELD_TITLE')"
				:connection-id="this.selectedConnectionId"
				:is-disabled="disabled"
				:selected-connection-type="selectedConnectionType"
				@value-change="onTableSelected"
				@value-clear="onTableDeselected"
				ref="tableField"
			/>

			<div
				v-if="this.isEditMode"
			>
				<CustomField
					name="connections"
					:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_FIELD_TITLE')"
				>
					<template #field-content>
						<div class="connection-preview">
							<div
								class="connection-icon"
								:style="'background-image: url(\\'' + selectedConnectionAvatar + '\\');'"
							></div>
							<div class="connection-name" @click="openConnectionSlider">
								{{ this.selectedConnectionName }}
							</div>
						</div>
					</template>
				</CustomField>

				<StringField
					name="tables"
					:title="this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_FIELD_TITLE')"
					:is-disabled="true"
					:defaultValue="this.selectedTableName"
				/>
			</div>
		</Step>
	`,
};
