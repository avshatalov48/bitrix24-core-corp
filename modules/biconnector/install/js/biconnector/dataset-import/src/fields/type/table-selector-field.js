import { Dom } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { BaseField } from './base-field';
import { TagSelector } from 'ui.entity-selector';
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
	},
	mounted()
	{
		const node = this.$refs['entity-selector'];
		const selector = new TagSelector({
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
		selector.setLocked(!this.isDisabled);
		selector.renderTo(node);

		this.selector = selector;
	},
	computed: {
		set(): Set
		{
			return Set;
		},
		hintOptions(): Object
		{
			return {
				html: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_TABLES_HINT'),
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
	methods: {},
	components: {
		BIcon,
	},
	// language=Vue
	template: `
		<div>
			<div class="ui-ctl-title">
				<div class="ui-ctl-label-text table-title">
					<span>{{ this.title }}</span>
					<div class="table-hint" v-hint="hintOptions">
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
