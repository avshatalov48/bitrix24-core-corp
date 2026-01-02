import { Dom, Event, Text, UI } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Dialog, Item } from 'ui.entity-selector';
import type { BitrixVueComponentProps } from 'ui.vue3';
import { BIcon, Set as IconSet } from 'ui.icon-set.api.vue';
import { hint } from 'ui.vue3.directives.hint';
import type { Scope } from '../type';
import { Store } from '../store';

export const GroupScopeSelector: BitrixVueComponentProps = {
	props: {
		canEdit: {
			type: Boolean,
			required: true,
		},
	},
	directives: {
		hint,
	},
	data(): Object {
		return {
			scopeNameLengthLimit: 30,
			initialScopedIds: [],
		};
	},
	computed: {
		scopes(): Scope[]
		{
			return this.$store.getters.groupScopes;
		},
		hintOptions(): Object
		{
			return {
				html: this.$Bitrix.Loc.getMessage('BI_GROUP_SCOPE_HINT')
					.replace('[link]', '<a class="scope-hint-link" onclick="top.BX.Helper.show(`redirect=detail&code=25556500`)">')
					.replace('[/link]', '</a>'),
				interactivity: true,
				popupOptions: {
					bindOptions: {
						position: 'bottom',
					},
					autoHide: false,
					width: 244,
					offsetTop: -4,
					angle: {
						position: 'top',
						offset: 34,
					},
				},
			};
		},
		scopeText(): string
		{
			const element: string = `
				<span
					class="scope-name ${this.canEdit ? '' : 'scope-list-system'}"
					title="${this.scopes[0].name.length > this.scopeNameLengthLimit ? Text.encode(this.scopes[0].name) : ''}"
				>
					${Text.encode(this.formatScopeName(this.scopes[0].name))}
				</span>
			`;

			if (this.scopes.length <= 1)
			{
				return this.$Bitrix.Loc.getMessage('BI_GROUP_SCOPES_GROUP')
					.replace('[title]', '<span class="group-scope-title">')
					.replace('[/title]', '</span>')
					.replace('#SCOPE#', element)
				;
			}

			return this.$Bitrix.Loc.getMessage('BI_GROUP_SCOPES_GROUP_MANY')
				.replace('[title]', '<span class="group-scope-title">')
				.replace('[/title]', '</span>')
				.replace('#FIRST_SCOPES#', element)
				.replace('[hint]', '<span class="scope-list scope-list-more group-scope-hint">')
				.replace('[/hint]', '</span>')
				.replace('#NUMBER#', this.scopes.length - 1)
			;
		},
		emptyScopesText(): string
		{
			return this.$Bitrix.Loc.getMessage('BI_GROUP_SCOPES_GROUP_EMPTY')
				.replace('[title]', '<span class="group-scope-title">')
				.replace('[/title]', '</span>')
			;
		},
		set(): IconSet
		{
			return IconSet;
		},
	},
	methods: {
		openScopeSelector(): void
		{
			if (!this.canEdit)
			{
				return;
			}

			if (!this.dialog)
			{
				this.initDialog();
			}

			this.initialScopedIds = this.scopes.map((scope: Scope) => scope.code);

			this.dialog.show();
		},
		initDialog(): void
		{
			const preselectedItems = [];
			for (const scope: Scope of this.scopes)
			{
				preselectedItems.push(['biconnector-superset-scope', scope.code]);
			}
			this.dialog = new Dialog({
				id: 'group-scope-selector',
				targetNode: this.$refs.groupScopes,
				width: 350,
				height: 300,
				dropdownMode: true,
				compactView: true,
				showAvatars: false,
				enableSearch: true,
				preload: true,
				preselectedItems,
				entities: [
					{
						id: 'biconnector-superset-scope',
						dynamicLoad: true,
						dynamicSearch: true,
					},
				],
				events: {
					'Item:onSelect': this.onGroupScopeAdd,
					'Item:onDeselect': this.onGroupScopeRemove,
					onHide: this.onDialogHide,
				},
			});
			this.dialog.getPopup().setOffset({ offsetLeft: -320 });
			this.dialog.adjustPosition();
			Dom.addClass(this.dialog.getContainer(), 'biconnector-scope-selector');
		},
		onGroupScopeAdd(event: BaseEvent): void
		{
			const item: Item = event.getData().item;
			const scope: Scope = { code: item.getId(), name: item.getTitle() };
			this.$emit('onGroupScopeAdd', scope);
		},
		onGroupScopeRemove(event: BaseEvent): void
		{
			const item: Item = event.getData().item;
			const scope: Scope = { code: item.getId(), name: item.getTitle() };
			this.$emit('onGroupScopeRemove', scope);
		},
		onDialogHide(event: BaseEvent): void
		{
			const wasChanged: boolean = !Store.areSetsEqual(
				new Set(this.initialScopedIds),
				new Set(this.scopes.map((scope: Scope) => scope.code)),
			);

			EventEmitter.emit(
				'BIConnector.GroupPopup.ScopeSelector:onDialogHide',
				{
					isScopeListEdited: wasChanged,
				},
			);
		},
		showMoreScopesHint(): void
		{
			const hintNode: HTMLElement = this.$el.querySelector('.group-scope-hint');
			this.hintManager.show(hintNode, this.scopes.slice(1).map((scope: Scope) => scope.name).join(', '), false);
		},
		hideMoreScopesHint(): void
		{
			this.hintManager.hide();
			this.hintManager.popup = null;
		},
		showSystemScopesHint(): void
		{
			const hintNode: HTMLElement = this.$el.querySelector('.scope-list-system');
			this.systemScopeHintManager.show(hintNode, this.$Bitrix.Loc.getMessage('BI_GROUP_SYSTEM_SCOPES_HINT'), false);
		},
		hideSystemScopesHint(): void
		{
			this.systemScopeHintManager.hide();
			this.systemScopeHintManager.popup = null;
		},
		formatScopeName(scopeName: string): string
		{
			if (scopeName.length < this.scopeNameLengthLimit)
			{
				return scopeName;
			}

			return `${scopeName.slice(0, this.scopeNameLengthLimit)}...`;
		},
		attachHintHandlers(): void
		{
			const node: ?HTMLElement = this.$el.querySelector('.group-scope-hint');
			if (node)
			{
				Event.unbindAll(node);
				Event.bind(node, 'mouseenter', this.showMoreScopesHint);
				Event.bind(node, 'mouseleave', this.hideMoreScopesHint);
			}

			if (!this.canEdit)
			{
				const scopeName: ?HTMLElement = this.$el.querySelector('.scope-list-system');
				if (scopeName)
				{
					Event.unbindAll(scopeName);
					Event.bind(scopeName, 'mouseenter', this.showSystemScopesHint);
					Event.bind(scopeName, 'mouseleave', this.hideSystemScopesHint);
				}
			}
		},
	},
	mounted(): void
	{
		this.hintManager = UI.Hint.createInstance({
			id: 'group-scope-hint',
			popupParameters: {
				bindOptions: {
					position: 'bottom',
				},
				width: 190,
				offsetLeft: -80,
				angle: {
					position: 'top',
					offset: 130,
				},
				cacheable: false,
			},
		});
		this.systemScopeHintManager = UI.Hint.createInstance({
			id: 'system-scope-hint',
			popupParameters: {
				bindOptions: {
					position: 'bottom',
				},
				width: 244,
				offsetLeft: -140,
				angle: {
					position: 'top',
					offset: 180,
				},
				cacheable: false,
			},
		});
		this.attachHintHandlers();
	},
	updated(): void
	{
		this.attachHintHandlers();
	},
	beforeUnmount(): void
	{
		if (this.dialog)
		{
			this.dialog.destroy();
		}
		this.dialog = null;

		const node: ?HTMLElement = this.$el.querySelector('.group-scope-hint');
		if (node)
		{
			Event.unbindAll(node);
		}

		const scopeName: ?HTMLElement = this.$el.querySelector('.scope-list-system');
		if (scopeName)
		{
			Event.unbindAll(scopeName);
		}
	},
	emits: [
		'onGroupScopeAdd',
		'onGroupScopeRemove',
	],
	components: {
		BIcon,
	},
	template: `
		<div class="group-scope-selector">
			<span
				v-if="scopes.length > 0"
				@click="openScopeSelector"
				class="scope-list scope-list-group"
				v-html="scopeText"
			></span>
			<span
				v-else
				@click="openScopeSelector"
				class="scope-list scope-list-group"
				v-html="emptyScopesText"
			></span>
			<div class="group-scope-list-hint" ref="groupScopes" v-hint="hintOptions">
				<BIcon
					:name="set.HELP"
					:size="20"
					color="#D5D7DB"
				></BIcon>
			</div>
		</div>
	`,
};
