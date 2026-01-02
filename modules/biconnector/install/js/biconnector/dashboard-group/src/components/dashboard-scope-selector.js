import type { BitrixVueComponentProps } from 'ui.vue3';
import { Dom, Event, UI, Text } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Dialog, Item } from 'ui.entity-selector';
import { Store } from '../store';
// eslint-disable-next-line no-unused-vars
import type { Scope, Dashboard } from '../type';

export const DashboardScopeSelector: BitrixVueComponentProps = {
	props: {
		dashboard: {
			/** @type Dashboard */
			type: Object,
			required: true,
		},
	},
	data(): Object {
		return {
			scopeNameLengthLimit: 30,
			initialScopedIds: [],
		};
	},
	beforeUnmount(): void
	{
		if (this.dialog)
		{
			this.dialog.destroy();
		}
		this.dialog = null;

		const node: ?HTMLElement = this.$el.querySelector('.scope-hint');
		if (node)
		{
			Event.unbindAll(node);
		}
	},
	computed: {
		visibleScopes(): Scope[]
		{
			return this.scopes.slice(0, 3);
		},
		scopes(): Scope[]
		{
			return [...this.dashboard.scopes];
		},
		isGroupScope(): (scopeCode: string) => boolean
		{
			return (scopeCode: string) => this.$store.getters.isGroupScope(this.dashboard.id, scopeCode);
		},
		scopeText(): string
		{
			const formattedScopes = [];
			for (const [index: number, scope: Scope] of this.visibleScopes.entries())
			{
				const name = this.formatScopeName(scope.name);
				const suffix: string = index < this.visibleScopes.length - 1 ? ', ' : '';

				const element: string = `
					<span 
						class="scope-name"
						title="${scope.name.length > this.scopeNameLengthLimit ? Text.encode(scope.name) : ''}"
					>
						${Text.encode(name + suffix)}
					</span>
				`;

				formattedScopes.push(element);
			}

			if (this.scopes.length <= 3)
			{
				return formattedScopes.join('');
			}

			return this.$Bitrix.Loc.getMessage('BI_GROUP_SCOPES_DASHBOARD')
				.replace('#FIRST_SCOPES#', formattedScopes.join(''))
				.replace('[hint]', '<span class="scope-list scope-list-more scope-hint">')
				.replace('[/hint]', '</span>')
				.replace('#NUMBER#', this.dashboard.scopes.length - 3)
			;
		},
	},
	emits: [
		'onScopeChange',
	],
	methods: {
		formatScopeName(scopeName: string): string
		{
			if (scopeName.length < this.scopeNameLengthLimit)
			{
				return scopeName;
			}

			return `${scopeName.slice(0, this.scopeNameLengthLimit)}...`;
		},
		openSelector(): void
		{
			if (!this.dialog)
			{
				this.initDialog();
			}

			this.initialScopedIds = this.dashboard.scopes.map((scope: Scope) => scope.code);

			this.dialog.show();
		},
		initDialog(): void
		{
			const preselectedItems = [];
			const undeselectedItems = [];
			for (const scope: Scope of this.dashboard.scopes)
			{
				preselectedItems.push(['biconnector-superset-scope', scope.code]);
				if (this.isGroupScope(scope.code))
				{
					undeselectedItems.push(['biconnector-superset-scope', scope.code]);
				}
			}
			this.dialog = new Dialog({
				id: 'dashboard-scope-selector',
				targetNode: this.$refs.dashboardScopes,
				width: 350,
				height: 300,
				dropdownMode: true,
				compactView: true,
				showAvatars: false,
				enableSearch: true,
				preload: true,
				preselectedItems,
				undeselectedItems,
				entities: [
					{
						id: 'biconnector-superset-scope',
						dynamicLoad: true,
						dynamicSearch: true,
					},
				],
				events: {
					'Item:onSelect': this.handleScopeAdd,
					'Item:onDeselect': this.handleScopeRemove,
					onHide: this.handleDialogHide,
				},
			});
			this.dialog.getPopup().setOffset({ offsetLeft: -320 });
			this.dialog.adjustPosition();
			Dom.addClass(this.dialog.getContainer(), 'biconnector-scope-selector');
		},
		handleScopeAdd(event: BaseEvent): void
		{
			const addedItem: Item = event.getData().item;
			const scopes: Scope[] = this.scopes;
			scopes.push({
				code: addedItem.getId(),
				name: addedItem.getTitle(),
			});
			this.$store.commit('setDashboardScopes', {
				dashboardId: this.dashboard.id,
				scopes,
			});
			this.$emit('onScopeChange');
		},
		handleScopeRemove(event: BaseEvent): void
		{
			const removedItem: Item = event.getData().item;
			const scopes = this.scopes.filter((item: Scope) => item.code !== removedItem.id);
			this.$store.commit('setDashboardScopes', {
				dashboardId: this.dashboard.id,
				scopes,
			});
			this.$emit('onScopeChange');
		},
		handleDialogHide(event: BaseEvent): void
		{
			const wasChanged: boolean = !Store.areSetsEqual(
				new Set(this.initialScopedIds),
				new Set(this.dashboard.scopes.map((scope: Scope) => scope.code)),
			);

			EventEmitter.emit(
				'BIConnector.GroupPopup.DashboardScopeSelector:onDialogHide',
				{
					isScopeListEdited: wasChanged,
				},
			);
		},
		showMoreScopesHint(): void
		{
			const hintNode = this.$el.querySelector('.scope-hint');
			this.hintManager.show(hintNode, this.scopes.slice(3).map((scope: Scope) => scope.name).join(', '), false);
		},
		hideMoreScopesHint(): void
		{
			this.hintManager.hide();
			this.hintManager.popup = null;
		},
		onGroupScopeChanged(): void
		{
			this.dialog?.destroy();
			this.dialog = null;
		},
		attachHintHandlers(): void
		{
			const node: ?HTMLElement = this.$el.querySelector('.scope-hint');
			if (node)
			{
				Event.unbindAll(node);
				Event.bind(node, 'mouseenter', this.showMoreScopesHint);
				Event.bind(node, 'mouseleave', this.hideMoreScopesHint);
			}
		},
	},
	mounted(): void
	{
		this.hintManager = UI.Hint.createInstance({
			id: `dashboard-scope-hint-${Date.now()}`,
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
		this.attachHintHandlers();
	},
	updated(): void
	{
		this.attachHintHandlers();
	},
	template: `
		<div class="group-dashboard-scopes-container">
			<span class="scope-list scope-list-dashboard" @click="openSelector">
				<span
					v-if="scopes.length > 0"
					v-html="scopeText"
				></span>
				<span v-else class="scope-list-empty">
					{{$Bitrix.Loc.getMessage('BI_GROUP_DASHBOARD_SCOPES_EMPTY')}}
				</span>
			</span>
			<span ref="dashboardScopes"></span>
		</div>
	`,
};
