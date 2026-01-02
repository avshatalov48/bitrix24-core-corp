import { Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { BaseField } from './base-field';
import { TagSelector, Dialog } from 'ui.entity-selector';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { hint } from 'ui.vue3.directives.hint';
import '../../css/entity-selector-field.css';

export const TableSelectorField = {
	extends: BaseField,
	directives: {
		hint,
	},
	props: {
		options: {
			type: Object,
			required: true,
		},
		connectionId: {
			type: Number,
			required: true,
		},
		selectedConnectionType: {
			type: String,
			required: false,
		},
	},
	data(): {}
	{
		return {
			hasErrors: false,
		};
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
				enableSearch: true,
				dropdownMode: true,
				showAvatars: false,
				compactView: true,
				multiple: false,
				dynamicLoad: true,
				width: 460,
				height: 420,
				tabs: [{
					id: 'tables',
					stub: true,
					stubOptions: {
						title: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_STUB_TITLE'),
						subtitle: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_STUB_SUBTITLE'),
					},
				}],
				entities: [{
					id: 'biconnector-external-table',
					dynamicLoad: false,
					dynamicSearch: true,
					options: {
						...(this.connectionId && { connectionId: this.connectionId }),
					},
				}],
				events: {
					'Entity:onError': (event: BaseEvent) => {
						this.onError(event);
					},
					onSearch: (event: BaseEvent) => {
						this.onSearch(event);
					},
				},
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
		selector.setLocked(!(this.connectionId > 0 && !this.isDisabled));
		selector.renderTo(node);

		this.selector = selector;

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionSave')
			{
				this.selector.removeTags();
			}
		});
	},
	computed: {
		set(): Set
		{
			return Set;
		},
		hintOptions(): Object
		{
			const hintCode: string =
				this.selectedConnectionType === 'rest'
					? 'DATASET_IMPORT_REST_TABLES_HINT'
					: 'DATASET_IMPORT_TABLES_HINT'
			;

			return {
				text: this.$Bitrix.Loc.getMessage(hintCode),
				popupOptions: {
					bindOptions: {
						position: 'top',
					},
					offsetTop: -10,
					angle: {
						position: 'top',
						offset: 34,
					},
				},
			};
		},
	},
	watch: {
		connectionId(newConnectionId)
		{
			const selector: TagSelector = this.selector;
			selector.removeTags();
			selector.getDialog().removeItems();

			if (!newConnectionId)
			{
				selector.setLocked(true);

				return;
			}

			selector.getDialog().getEntity('biconnector-external-table').options.connectionId = newConnectionId;
			selector.setLocked(this.isDisabled);
		},
	},
	methods: {
		onError(event: BaseEvent)
		{
			const errors = event.getData().errors;
			const dialog: Dialog = event.getTarget();
			const tableTab = dialog.getTab('tables');
			tableTab.getStub().hide();
			dialog.selectTab('tables');
			tableTab.setStub(
				true,
				{
					title: errors[0].message,
					icon: '/bitrix/js/biconnector/dataset-import/dist/images/table-error-state.svg',
					iconOpacity: 100,
				},
			);
			tableTab.getStub().show();

			this.hasErrors = true;
		},
		onSearch(event: BaseEvent)
		{
			if (!this.hasErrors)
			{
				return;
			}

			const dialog: Dialog = event.getTarget();
			const tableTab = dialog.getTab('tables');
			tableTab.getStub().hide();
			tableTab.setStub(true, {
				title: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_STUB_TITLE'),
				subtitle: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_STUB_SUBTITLE'),
			});

			this.hasErrors = false;
		},
	},
	components: {
		BIcon,
	},
	// language=Vue
	template: `
		<div>
			<div class="ui-ctl-title">
				<div class="ui-ctl-label-text table-title">
					<span>{{ this.title }}</span>
					<div v-if='selectedConnectionType' class="table-hint" v-hint="hintOptions">
						<BIcon
							:name="set.HELP"
							:size="20"
							color="#D5D7DB"
						></BIcon>
					</div>
				</div>
			</div>
			<div ref="entity-selector"></div>
		</div>
	`,
};
