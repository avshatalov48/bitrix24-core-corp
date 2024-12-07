import { ClientSelector } from 'crm.client-selector';
import { ajax as Ajax, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import { UI } from 'ui.notification';

export const TodoEditorBlocksClient = {
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
			selectedClients: new Set(),
			isFetchedConfig: false,
			clients: [],
		};

		return this.getPreparedData(data);
	},

	mounted()
	{
		this.fetchConfig();
		this.subscribeToReceiversChanges();
	},

	methods: {
		getId(): string
		{
			return 'client';
		},
		getPreparedData(data: Object): Object
		{
			const { filledValues } = this;

			if (Type.isObject(filledValues?.clients))
			{
				filledValues.clients.forEach(({ entityId, entityTypeId, isAvailable }) => {
					data.selectedClients.add({
						entityId: Number(entityId),
						entityTypeId: Number(entityTypeId),
						isAvailable,
					});
				});
			}

			if (Type.isObject(filledValues?.selectedClients))
			{
				// eslint-disable-next-line no-param-reassign
				data.selectedClients = filledValues.selectedClients;
			}

			return data;
		},
		subscribeToReceiversChanges(): void
		{
			EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', this.onReceiversChanged);
		},
		onReceiversChanged(event: BaseEvent): void
		{
			const { item } = event.getData();

			if (this.entityTypeId !== item?.entityTypeId || this.entityId !== item?.entityId)
			{
				return;
			}

			this.fetchConfig(true);
		},
		onShowAddClientPhoneSelector(): void
		{
			if (Type.isArrayFilled(this.clients))
			{
				this.onShowClientSelector();

				return;
			}

			const id = 'client-selector-dialog';
			const context = `CRM_TIMELINE_TODO-${this.entityTypeId}`;

			if (!this.userSelectorDialog)
			{
				this.userSelectorDialog = new Dialog({
					id,
					context,
					targetNode: this.$refs.addClientsSelector,
					multiple: false,
					dropdownMode: false,
					showAvatars: true,
					enableSearch: true,
					width: 450,
					zIndex: 2500,
					entities: this.getClientSelectorEntities(),
					events: {
						'Item:onSelect': this.onAddClient,
					},
				});
			}

			if (this.userSelectorDialog.isOpen())
			{
				this.userSelectorDialog.hide();
			}
			else
			{
				setTimeout(() => {
					this.userSelectorDialog.setTargetNode(this.$refs.addClientsSelector);
					this.userSelectorDialog.show();
				}, 5);
			}
		},
		async onAddClient(event: BaseEvent): void
		{
			if (this.isClientBindingInProgress)
			{
				event.preventDefault();

				return;
			}

			this.isClientBindingInProgress = true;

			const { item } = event.getData();

			const entityId = item.id;
			const entityTypeId = BX.CrmEntityType.resolveId(item.entityId);

			const isBound = await this.bindClient(entityId, entityTypeId);
			if (isBound)
			{
				BX.Crm.EntityEditor?.getDefault()?.reload();
			}
		},
		async bindClient(clientId: number, clientTypeId: number): Promise
		{
			const ajaxParams = {
				entityId: this.entityId,
				entityTypeId: this.entityTypeId,
				clientId,
				clientTypeId,
			};

			return new Promise((resolve) => {
				Ajax.runAction('crm.activity.todo.bindClient', { data: ajaxParams })
					.then(({ data }) => {
						this.isClientBindingInProgress = false;

						if (!data)
						{
							resolve(false);
						}

						if (Type.isArrayFilled(data.clients))
						{
							this.clients = data.clients;

							this.selectedClients.add({
								entityId: clientId,
								entityTypeId: clientTypeId,
								isAvailable: true,
							});
						}

						resolve(true);
					})
					.catch((data) => {
						this.isClientBindingInProgress = false;

						if (data.errors.length > 0)
						{
							this.showNotify(data.errors[0].message);

							return;
						}

						this.showNotify(this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_BIND_CLIENT_ERROR'));
					})
				;
			});
		},
		showNotify(content: string): void
		{
			UI.Notification.Center.notify({ content });
		},
		onShowClientSelector(): void
		{
			if (this.clientSelector && this.clientSelector.isOpen())
			{
				this.clientSelector.hide();
			}
			else
			{
				const targetNode = this.$refs.clientsContainer;

				this.clientSelector = ClientSelector.createFromItems({
					targetNode,
					multiple: true,
					items: this.clients,
					events: {
						onSelect: this.onSelectClient,
						onDeselect: this.onDeselectClient,
					},
				});

				setTimeout(() => {
					this.availableSelectedClients.forEach(({ entityId, entityTypeId }) => {
						this.clientSelector.setSelectedItemByEntityData(entityId, entityTypeId);
					});
					this.clientSelector.show();
				}, 5);
			}
		},
		getClientSelectorEntities(): []
		{
			const contact = {
				id: 'contact',
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					showTab: true,
					showPhones: true,
					showMails: true,
					hideReadMoreLink: true,
				},
			};

			const company = {
				id: 'company',
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					excludeMyCompany: true,
					showTab: true,
					showPhones: true,
					showMails: true,
					hideReadMoreLink: true,
				},
			};

			if (this.entityTypeId === BX.CrmEntityType.enumeration.contact)
			{
				return [company];
			}

			if (this.entityTypeId === BX.CrmEntityType.enumeration.company)
			{
				return [contact];
			}

			return [contact, company];
		},
		onSelectClient({ data: { item } }): void
		{
			this.selectedClients.add({
				entityId: item.customData.get('entityId'),
				entityTypeId: item.customData.get('entityTypeId'),
				isAvailable: true,
			});
		},
		onDeselectClient({ data: { item } }): void
		{
			this.selectedClients.forEach((client) => {
				if (
					client.entityId === item.customData.get('entityId')
					&& client.entityTypeId === item.customData.get('entityTypeId')
				)
				{
					this.selectedClients.delete(client);
				}
			});
		},
		fetchConfig(force: boolean = false): void
		{
			if (this.isFetchedConfig && !force)
			{
				return;
			}

			this.isFetchedConfig = false;

			if (!this.entityTypeId)
			{
				return;
			}

			const ajaxParameters = {
				entityTypeId: this.entityTypeId,
				entityId: this.entityId,
			};

			Ajax.runAction('crm.activity.todo.getClientConfig', { data: ajaxParameters })
				.then(({ data }) => {
					this.isFetchedConfig = true;

					if (Type.isArrayFilled(data.clients))
					{
						this.clients = data.clients;

						if (this.selectedClients.size === 0)
						{
							const { entityId, entityTypeId } = data.clients[0].customData;

							this.selectedClients.add({
								entityId,
								entityTypeId,
								isAvailable: true,
							});
						}
					}
				})
				.catch(() => {
					BX.UI.Notification.Center.notify({ content: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_ERROR') });
				})
			;
		},
		onShowClient({ entityId, entityTypeId }): void
		{
			let path = '';
			if (entityTypeId === BX.CrmEntityType.enumeration.company)
			{
				path = `/crm/company/details/${Number(entityId)}/`;
			}

			if (entityTypeId === BX.CrmEntityType.enumeration.contact)
			{
				path = `/crm/contact/details/${Number(entityId)}/`;
			}

			if (!Type.isStringFilled(path))
			{
				return;
			}

			if (BX.SidePanel)
			{
				BX.SidePanel.Instance.open(path);
			}
			else
			{
				window.top.location.href = path;
			}
		},
		getClientTitleFormatted(entityId: number, entityTypeId: number, showComma: boolean = false): string
		{
			const clientInfo = this.getClientInfo(entityId, entityTypeId);

			if (!clientInfo)
			{
				return '';
			}

			const comma = showComma ? ', ' : '';

			return `${clientInfo.title}${comma}`;
		},
		getClientInfo(entityId: number, entityTypeId: number): Object | undefined
		{
			return this.clients.find(
				({ customData }) => {
					return customData.entityId === entityId && customData.entityTypeId === entityTypeId;
				},
			);
		},
		getExecutedData(): Object
		{
			return {
				selectedClients: [...this.selectedClients],
			};
		},
		emitUpdateFilledValues(): void
		{
			let { filledValues } = this;
			const { selectedClients } = this;

			const newFilledValues = {
				selectedClients,
			};
			filledValues = { ...filledValues, ...newFilledValues };
			this.$emit('updateFilledValues', this.getId(), filledValues);
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
		changeTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CLIENT_BLOCK_CHANGE_ACTION');
		},
		addTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CLIENT_BLOCK_ADD_ACTION');
		},
		hasClients(): boolean
		{
			return Type.isArrayFilled(this.availableSelectedClients);
		},
		entityTypeId(): number
		{
			return this.settings.entityTypeId;
		},
		entityId(): number
		{
			return this.settings.entityId;
		},
		availableSelectedClients(): []
		{
			return [...this.selectedClients].filter((client) => client.isAvailable);
		},
	},

	created()
	{
		this.$watch(
			'selectedClients',
			this.emitUpdateFilledValues,
			{
				deep: true,
			},
		);
	},

	template: `
		<div v-if="isFetchedConfig" class="crm-activity__todo-editor-v2_block-header">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon"
				:style="iconStyles"
			></span>
			<span>{{ encodedTitle }}</span>
			<span 
				v-if="hasClients"
				class="crm-activity__todo-editor-v2_block-header-data"
			>
				<span
					v-for="(client, index) in availableSelectedClients"
					:key="client.entityTypeId + '-' + client.entityId"
					@click="onShowClient(client)"
				>
					<template v-if="Boolean(getClientInfo(client.entityId, client.entityTypeId))">
						{{ getClientTitleFormatted(client.entityId, client.entityTypeId, index !== availableSelectedClients.length - 1) }}
					</template>
				</span>
			</span>
			<span ref="clientsContainer">
				<span
					v-if="hasClients"
					ref="clientsSelector"
					@click="onShowClientSelector"
					class="crm-activity__todo-editor-v2_block-header-action"
				>
					{{ changeTitle }}
				</span>
				<span
					v-else
					ref="addClientsSelector"
					@click="onShowAddClientPhoneSelector"
					class="crm-activity__todo-editor-v2_block-header-action"
				>
					{{ addTitle }}
				</span>
			</span>
			<div
				@click="$emit('close', id)"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
		<div v-else class="crm-activity__todo-editor-v2_block-header --skeleton"></div>
	`,
};
