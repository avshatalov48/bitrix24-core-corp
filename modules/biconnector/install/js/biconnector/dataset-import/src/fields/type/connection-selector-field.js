import { Dom, Event, Tag, Type, Text } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { BaseField } from './base-field';
import { Dialog, Item, TagSelector } from 'ui.entity-selector';
import '../../css/entity-selector-field.css';

export const ConnectionSelectorField = {
	extends: BaseField,
	props: {
		options: {
			type: Object,
			required: true,
		},
		items: {
			type: Array,
			required: true,
		},
		connectionId: {
			type: Number,
			required: false,
		},
	},
	mounted()
	{
		const node = this.$refs['entity-selector'];
		const selector: TagSelector = new TagSelector({
			id: this.options.selectorId,
			multiple: false,
			addButtonCaption: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_SELECT'),
			addButtonCaptionMore: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_CHANGE'),
			dialogOptions: {
				id: this.options.selectorId,
				items: this.preparedItems,
				enableSearch: true,
				dropdownMode: true,
				showAvatars: true,
				compactView: false,
				multiple: false,
				width: 460,
				height: 420,
				tabs: [{
					id: 'connections',
					stubOptions: {
						title: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_NO_CONNECTIONS_TITLE'),
						subtitle: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_NO_CONNECTIONS_SUBTITLE'),
						arrow: true,
						icon: '/bitrix/images/biconnector/database-connections/connections-empty-state.png',
						iconOpacity: 100,
					},
				}],
				entities: [{
					id: 'biconnector-external-connection',
				}],
			},
			events: {
				onTagAdd: (event: BaseEvent) => {
					this.$emit('valueChange', event);
				},
				onTagRemove: (event: BaseEvent) => {
					this.$emit('valueClear', event);
				},
			},
		});
		Dom.addClass(selector.getDialog().getContainer(), 'biconnector-dataset-entity-selector');
		selector.renderTo(node);

		const footer = Tag.render`
			<span class="ui-selector-footer-link ui-selector-footer-link-add">
				${this.$Bitrix.Loc.getMessage('DATASET_IMPORT_NO_CONNECTIONS_FOOTER')}
			</span>
		`;
		Event.bind(footer, 'click', () => {
			const link = '/bitrix/components/bitrix/biconnector.externalconnection/slider.php?closeAfterCreate=Y';
			BX.SidePanel.Instance.open(link, {
				width: 564,
				allowChangeHistory: false,
				cacheable: false,
			});
		});
		selector.getDialog().getTab(this.name).setFooter(footer);

		this.selector = selector;

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionSave')
			{
				this.onConnectionSave(messageEvent);
			}
		});
	},
	computed: {
		preparedItems(): Array
		{
			const selectorItems = [];
			this.items.forEach((item) => {
				const itemOptions = {
					id: item.ID,
					title: item.TITLE,
					entityId: this.options.selectorId,
					tabs: this.name,
					link: `/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=${item.ID}&closeAfterCreate=Y`,
					linkTitle: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_ABOUT'),
					customData: {
						connectionType: item.TYPE,
					},
				};

				if (item.AVATAR)
				{
					itemOptions.avatar = item.AVATAR;
				}

				if (this.connectionId)
				{
					itemOptions.selected = item.ID === this.connectionId.toString();
				}

				selectorItems.push(itemOptions);
			});

			return selectorItems;
		},
	},
	methods: {
		onConnectionSave(event)
		{
			const itemOptions: {id: number, name: string, type: string, avatar: ?string} = event.getData().connection;
			if (!Type.isStringFilled(itemOptions?.avatar))
			{
				itemOptions.avatar = `/bitrix/images/biconnector/database-connections/${itemOptions.type}.svg`;
			}

			const selector: TagSelector = this.selector;
			const dialog: Dialog = selector.getDialog();
			let item: ?Item = dialog.getItem({
				id: itemOptions.id,
				entityId: this.options.selectorId,
			});

			if (item)
			{
				item.setTitle({ text: Text.decode(itemOptions.name), type: 'text' });
				if (item.isSelected())
				{
					selector.removeTags();
					item.deselect();
					item.select();
				}
			}
			else
			{
				item = dialog.addItem({
					id: itemOptions.id,
					title: itemOptions.name,
					entityId: this.options.selectorId,
					tabs: this.name,
					link: `/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=${itemOptions.id}&closeAfterCreate=Y`,
					linkTitle: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONNECTIONS_ABOUT'),
					avatar: `/bitrix/images/biconnector/database-connections/${itemOptions.type}.svg`,
					customData: {
						connectionType: itemOptions.type,
					},
				});
				item.select();
			}

			dialog.hide();
		},
	},
	// language=Vue
	template: `
		<div>
			<div class="ui-ctl-title">
				<div class="ui-ctl-label-text">{{ this.title }}</div>
			</div>
			<div ref="entity-selector"></div>
			<div 
				v-if="!isValid"
				class="connection-error"
			>
				{{ this.errorMessage }}
			</div>
		</div>
	`,
};
