import { ajax as Ajax, Extension, Type } from 'main.core';
import { Dialog } from 'ui.entity-selector';
import { FeaturePromotersRegistry } from 'ui.info-helper';

const DEFAULT_TAB_ID = 'location';

export const LocationSelector = {
	props: {
		locationId: {
			type: Number,
		},
		forceShowLocationSelectorDialog: {
			type: Boolean,
		},
	},

	emits: [
		'change',
		'close',
	],

	data(): Object
	{
		return {
			locations: null,
		};
	},

	async mounted()
	{
		this.locations = await this.fetchRoomsListData();

		if (this.forceShowLocationSelectorDialog)
		{
			this.showLocationSelectorDialog();
		}
	},

	methods: {
		showLocationSelectorDialog(): void
		{
			if (!this.isLocationFeatureEnabled())
			{
				FeaturePromotersRegistry.getPromoter({ featureId: 'calendar_location' }).show();

				return;
			}

			setTimeout(() => {
				this.getLocationSelectorDialog()?.show();
			}, 5);
		},
		isLocationFeatureEnabled(): boolean
		{
			return Extension.getSettings('crm.activity.todo-editor-v2').get('locationFeatureEnabled');
		},
		getLocationSelectorDialog(): ?Dialog
		{
			if (this.locations === null)
			{
				return null;
			}

			if (Type.isNil(this.locationSelectorDialog))
			{
				const tabs = [
					{
						id: DEFAULT_TAB_ID,
						title: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_ROOMS_ENTITY_TITLE'),
					},
				];

				const items = [];

				this.locations.rooms?.forEach((room) => {
					items.push({
						id: room.ID,
						title: room.NAME,
						subtitle: this.getCapacityTitle(room.CAPACITY ?? null),
						entityId: DEFAULT_TAB_ID,
						tabs: DEFAULT_TAB_ID,
						avatarOptions: {
							bgColor: room.COLOR,
							bgSize: '22px',
							bgImage: 'none',
						},
						customData: {
							locationId: room.LOCATION_ID,
						},
					});
				});

				this.locationSelectorDialog = new Dialog({
					id: 'todo-editor-calendar-room-selector-dialog',
					targetNode: this.$refs.locationSelector,
					context: 'CRM_ACTIVITY_TODO_CALENDAR_ROOM',
					multiple: false,
					dropdownMode: true,
					showAvatars: true,
					enableSearch: items.length > 8,
					width: 450,
					height: 300,
					zIndex: 2500,
					items,
					tabs,
					events: {
						'Item:onSelect': this.onSelectLocation,
						'Item:onDeselect': this.onDeselectLocation,
					},
				});
			}

			return this.locationSelectorDialog;
		},
		getCapacityTitle(value: ?number): string
		{
			if (Type.isNil(value) || value <= 0)
			{
				return '';
			}

			return this.$Bitrix.Loc.getMessage(
				'CRM_ACTIVITY_TODO_CALENDAR_BLOCK_ROOMS_CAPACITY',
				{ '#CAPACITY_VALUE#': value },
			);
		},
		async fetchRoomsListData(): Object
		{
			return new Promise((resolve) => {
				Ajax
					.runAction('calendar.api.locationajax.getRoomsList')
					.then((response) => {
						resolve(response.data);
					})
					.catch((errors) => {
						console.log(errors);
					})
				;
			});
		},
		onSelectLocation({ data }): void
		{
			this.emitChangeEvent('select', data.item);
		},
		onDeselectLocation({ data }): void
		{
			this.emitChangeEvent('deselect', data.item);
		},
		emitChangeEvent(action: string, item: ?Object = null): void
		{
			this.$emit('change', {
				action,
				id: Number(item?.id),
			});
		},
		getLocationById(id: number): ?Object
		{
			return (this.locations.rooms.find((location) => Number(location.ID) === id) ?? null);
		},
	},
	computed: {
		blockTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_ROOMS_TITLE');
		},
		locationsListTitle(): string
		{
			if (Type.isNil(this.locationId))
			{
				return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CALENDAR_BLOCK_ROOMS_LIST');
			}

			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_CLIENT_BLOCK_CHANGE_ACTION');
		},
		selectedLocationTitle(): string
		{
			const location = this.getLocationById(this.locationId);
			if (!location)
			{
				return '';
			}

			return location.NAME;
		},
		hasSelectedLocation(): boolean
		{
			return Type.isNumber(this.locationId);
		},
	},

	template: `
		<div v-if="locations" class="crm-activity__todo-editor-v2_block-header">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon --calendar-room"
			></span>
			<span>
				{{ blockTitle }}
			</span>
			<span 
				v-if="hasSelectedLocation" 
				class="crm-activity__todo-editor-v2_block-header-data"
			>
				{{ selectedLocationTitle }}
			</span>
			<span
				ref="locationSelector"
				@click="showLocationSelectorDialog"
				class="crm-activity__todo-editor-v2_block-header-action"
			>
				{{ locationsListTitle }}
			</span>
			<div
				@click="$emit('close')"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
		<div v-else class="crm-activity__todo-editor-v2_block-header --skeleton"></div>
	`,
};
