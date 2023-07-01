import { Events } from 'crm.activity.settings-popup';
import { TagSelector } from 'crm.entity-selector';

import 'ui.design-tokens';
import './ping.css'

export const Ping = {
	props: {
		params: {
			type: Object,
			default: {},
		},
	},

	data(): Object
	{
		const selectedItems = this.params.selectedItems || [];

		return {
			id: this.getId(),
			selectedItems,
		};
	},

	mounted(): void
	{
		const preselectedItems = [];
		
		this.selectedItems.forEach(item => preselectedItems.push(['timeline_ping', item]));

		this.pingSelector = new TagSelector({
			textBoxWidth: '100%',
			dialogOptions: {
				height: 330,
				dropdownMode: true,
				showAvatars: false,
				enableSearch: false,
				preselectedItems: preselectedItems,
				entities: [{
					id: 'timeline_ping'
				}],
				events: {
					'Item:onSelect': () => {
						this.onChangeSelectorData()
					},
					'Item:onDeselect': () => {
						this.onChangeSelectorData()
					}
				},
			}
		});

		this.pingSelector.renderTo(this.$refs.pingSel);

		this.emitSettingsChange();
	},

	unmounted(): void
	{
		this.emitSettingsChange(false);
	},

	watch: {
		selectedItems()
		{
			this.emitSettingsChange();
		},
	},

	methods: {
		getId(): string
		{
			return 'ping';
		},

		onChangeSelectorData()
		{
			if (this.pingSelector)
			{
				this.selectedItems = this.pingSelector.getDialog().getSelectedItems().map(item => item.id);
			}
		},

		emitSettingsChange(active: boolean = true): void
		{
			this.$Bitrix.eventEmitter.emit(Events.EVENT_SETTINGS_CHANGE, this.exportParams(active));
		},

		exportParams(active: boolean = true): Object
		{
			this.pingSelector.getDialog().getSelectedItems().map(item => item.id);
			
			return {
				id: this.id,
				selectedItems: this.selectedItems,
				active,
			}
		},

		updateSettings(data: Object | null): void
		{
		},
	},

	template: `
		<div class="ui-form">
			<div ref="pingSel" class="crm-activity__settings_popup__ping-selector-container"></div>
		</div>
	`
};
