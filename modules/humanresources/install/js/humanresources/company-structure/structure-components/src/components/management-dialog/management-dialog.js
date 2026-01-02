// eslint-disable-next-line no-unused-vars
import { Dialog, type DialogOptions, type TabOptions } from 'ui.entity-selector';
import { BaseManagementDialogHeader } from './core/header';
import { BaseManagementDialogFooter } from './core/footer';
// eslint-disable-next-line no-unused-vars
import type { ManagementDialogDataTestIds } from './types';
import { Dom } from 'main.core';
import './management-dialog.css';
import 'ui.icon-set.actions';

export const ManagementDialog = {
	name: 'ManagementDialog',
	emits: ['managementDialogAction', 'close'],
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: false,
		},
		description: {
			type: String,
			required: false,
		},
		entities: {
			type: Array,
			required: true,
		},
		isActive: {
			type: Boolean,
			required: false,
			default: false,
		},
		hiddenItemsIds: {
			type: Array,
			required: false,
			default: [],
		},
		confirmButtonText: {
			type: String,
			required: false,
		},
		/** @var { ManagementDialogDataTestIds } dataTestIds */
		dataTestIds: {
			type: Object,
			required: false,
			default: {},
		},
		/** @var TabOptions */
		recentTabOptions: {
			type: Object,
			required: false,
			default: {},
		},
	},

	data(): Object {
		return {
			headerContainer: HTMLElement | null,
			footerContainer: HTMLElement | null,
			selectedItemsCount: 0,
		};
	},

	created(): void
	{
		this.instance = this.getDialogInstance();
		this.instance.show();
	},

	beforeUnmount(): void
	{
		if (!this.instance || !this.instance.isOpen())
		{
			return;
		}

		this.instance.destroy();
	},

	methods: {
		getDialogInstance(): Dialog
		{
			if (this.instance)
			{
				return this.instance;
			}

			Dialog.getById(this.id)?.destroy();
			const config = this.getDialogConfig();
			this.instance = new Dialog(config);
			this.headerContainer = this.instance.getHeader().getContainer();
			this.footerContainer = this.instance.getFooter().getContainer();
			if (this.dataTestIds.containerDataTestId)
			{
				Dom.attr(this.instance.getContainer(), 'data-test-id', this.dataTestIds.containerDataTestId);
			}

			return this.instance;
		},
		getDialogConfig(): DialogOptions
		{
			return {
				id: this.id,
				width: 400,
				height: 511,
				multiple: true,
				cacheable: false,
				dropdownMode: true,
				compactView: false,
				enableSearch: true,
				showAvatars: true,
				autoHide: false,
				popupOptions: {
					overlay: { opacity: 40 },
				},
				header: BaseManagementDialogHeader,
				footer: BaseManagementDialogFooter,
				recentTabOptions: this.recentTabOptions,
				entities: this.entities,
				events: {
					'Item:onSelect': (): void => {
						this.selectedItemsCount++;
					},
					'Item:onDeselect': () => {
						this.selectedItemsCount--;
					},
					onLoad: (event) => {
						const dialog: Dialog = event.getTarget();
						this.toggleItems(dialog);

						const tabs = dialog.getTabs();
						for (const tab of tabs)
						{
							if (tab.id === 'recents')
							{
								tab.select();
							}

							if (!['recents', 'search'].includes(tab.id))
							{
								tab.deselect();
								tab.setVisible(false);
							}
						}
					},
					'SearchTab:onLoad': (event) => {
						const dialog: Dialog = event.getTarget();
						this.toggleItems(dialog);
					},
					onDestroy: () => {
						this.instance = null;
						this.$emit('close');
					},
					onHide: () => {
						this.$emit('close');
					},
				},
			};
		},
		onActionItemClick(): void
		{
			if (this.isActive || !this.selectedItemsCount)
			{
				return;
			}

			const selectedItems = this.instance.getSelectedItems() ?? [];
			this.$emit('managementDialogAction', selectedItems);
		},
		closeDialog(): void
		{
			this.$emit('close');
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		toggleItems(dialog: Dialog): void
		{
			if (this.hiddenItemsIds.length === 0)
			{
				return;
			}

			const items = dialog.getItems();
			items.forEach((item) => {
				const hidden = this.hiddenItemsIds.includes(item.id);
				item.setHidden(hidden);
			});
		},
	},

	template: `
		<div>
			<teleport :to="headerContainer">
				<div class="hr-management-dialog__header_container">
					<div class="hr-management-dialog__header_content-container">
						<div class="hr-management-dialog__header_title">{{title}}</div>
						<div v-if="description" class="hr-management-dialog__header_subtitle">{{description}}</div>
						<slot name="extra-subtitle" />
					</div>
					<div
						class="ui-icon-set --cross-40 hr-management-dialog__header_close-button"
						@click="closeDialog"
						:data-test-id="dataTestIds.closeButtonDataTestId"
					/>
				</div>
			</teleport>
			<teleport :to="footerContainer">
				<div class="hr-management-dialog__footer_container">
					<button
						class="ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round"
						:class="{ 'ui-btn-wait': isActive, 'ui-btn-disabled': !selectedItemsCount, }"
						@click="onActionItemClick"
						:data-test-id="dataTestIds.confirmButtonDataTestId"
					>
						{{ confirmButtonText ?? loc('HUMANRESOURCES_STRUCTURE_COMPONENTS_MANAGEMENT_DIALOG_CONFIRM_BUTTON') }}
					</button>
					<button
						class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round"
						@click="closeDialog"
						:data-test-id="dataTestIds.cancelButtonDataTestId"
					>
						{{ loc('HUMANRESOURCES_STRUCTURE_COMPONENTS_MANAGEMENT_DIALOG_CANCEL_BUTTON') }}
					</button>
				</div>
			</teleport>
		</div>
	`,
};
