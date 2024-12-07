import { bind, Text } from 'main.core';
import { Popup } from 'main.popup';
import { BaseEvent } from 'main.core.events';
import { Dialog, Item } from 'ui.entity-selector';

import '../css/prompt-master-user-selector.css';

import { clickableHint } from '../directives/prompt-master-hover-hint';

type PromptMasterUserSelectorSelectedItemWithData = {
	entityId: string;
	id: string | number;
	title?: string;
	avatar?: string;
}

type PromptMasterUserSelectorSelectedItem = [string, string | number];

export const PromptMasterUserSelector = {
	directives: {
		clickableHint,
	},
	props: {
		selectedItems: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
		maxCirclesInInput: {
			type: Number,
			required: false,
			default: 8,
		},
		undeselectedItems: {
			type: Array,
			required: false,
			default: () => {
				return [];
			},
		},
	},
	data(): { etcItemHint: Popup } {
		return {
			etcItemHint: null,
			cursorOnEtcItem: false,
			selectedItemsWithData: [],
			dataIsLoaded: false,
		};
	},
	computed: {
		preselectedItems(): Array<string, string | number> {
			return this.typedSelectedItems.map((item) => {
				return item;
			});
		},
		typedSelectedItems(): PromptMasterUserSelectorSelectedItem[] {
			return this.selectedItems;
		},
		etcItemHintContent(): Object {
			const titles = this.selectedItemsWithData
				.slice(this.maxCirclesInInput)
				.map((item) => this.getEncodedString(item.title));

			const titlesText = titles.join('<br>');

			return `<div>${titlesText}</div>`;
		},
		etcSelectedItemsCount(): number {
			return this.selectedItems.slice(this.maxCirclesInInput).length;
		},
		etcSelectedItemsCircleNumber(): number {
			return this.etcSelectedItemsCount < 100 ? this.etcSelectedItemsCount : 99;
		},
	},
	methods: {
		updateSelectedItemsWithData(): void {
			const selectedItems = this.getUserSelectorDialog().getSelectedItems();

			if (selectedItems.length === this.selectedItemsWithData.length)
			{
				return;
			}

			this.selectedItemsWithData = selectedItems.map((item) => {
				return this.getSelectedItemsWithDataFromDialogItem(item);
			});
		},
		getSelectedItemsWithDataFromDialogItem(item: Item): PromptMasterUserSelectorSelectedItemWithData {
			return {
				id: item.id,
				avatar: item.avatar,
				entityId: item.entityId,
				title: item.title.text,
			};
		},
		getUserSelectorDialog(): Dialog {
			const existingDialog = Dialog.getById('ai-prompt-master-user-selector');

			if (existingDialog)
			{
				existingDialog.setTargetNode(this.$refs.userSelector);

				return existingDialog;
			}

			return new Dialog({
				id: 'ai-prompt-master-user-selector',
				targetNode: this.$refs.userSelector,
				width: 400,
				height: 300,
				dropdownMode: false,
				showAvatars: true,
				compactView: true,
				multiple: true,
				preload: true,
				enableSearch: true,
				entities: [
					{
						id: 'user',
						options: {
							inviteEmployeeLink: false,
						},
					},
					{
						id: 'department',
						options: { selectMode: 'usersAndDepartments' },
					},
					{
						id: 'meta-user',
						options: { 'all-users': true },
					},
					{
						id: 'project',
					},
				],
				preselectedItems: this.preselectedItems,
				undeselectedItems: this.undeselectedItems,
				events: {
					'Item:onSelect': (event: BaseEvent) => {
						this.selectItem(event.getData().item);
					},
					'Item:onDeselect': (event: BaseEvent) => {
						this.deselectItem(event.getData().item);
					},
					onLoad: (): void => {
						this.dataIsLoaded = true;
						this.updateSelectedItemsWithData();
					},
				},
			});
		},
		showUserSelector(): void {
			const dialog = this.getUserSelectorDialog();

			dialog.show();
		},
		selectItem(item: Item): void {
			this.$emit('select-item', {
				id: item.id,
				entityId: item.entityId,
			});
		},
		deselectItem(item): void {
			this.$emit('deselect-item', {
				id: item.id,
				entityId: item.entityId,
			});
		},
		getSelectedItemStyle(item: Object, index: number): Object {
			const backgroundImage = `url('${this.getAvatarFromItem(item)}')`;

			return {
				backgroundImage,
				left: `${24 * index - 8}px`,
			};
		},
		getAvatarFromItem(item: PromptMasterUserSelectorSelectedItemWithData): string {
			if (item.avatar)
			{
				return item.avatar;
			}

			if (item.entityId === 'user')
			{
				return '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
			}

			if (item.entityId === 'meta-user')
			{
				return '/bitrix/js/socialnetwork/entity-selector/src/images/meta-user-all.svg';
			}

			return '';
		},
		getDepartmentFirstLetter(title: string): string {
			return title.split(' ')[0][0].toUpperCase();
		},
		showEtcItemsHint(): void {
			this.cursorOnEtcItem = true;
			if (this.etcItemHint)
			{
				return;
			}

			this.etcItemHint = new Popup({
				bindElement: this.$refs.etcItem,
				darkMode: true,
				content: this.etcItemHintContent,
				autoHide: true,
				maxHeight: 300,
				bindOptions: {
					position: 'top',
				},
				animation: 'fading-slide',
				angle: true,
			});

			this.etcItemHint.setOffset({
				offsetTop: -10,
				offsetLeft: 16,
			});

			this.etcItemHint.show();
		},
		closeEtcItemsHint(): void {
			this.cursorOnEtcItem = false;
			setTimeout(() => {
				const hoveredItems = document.querySelectorAll(':hover');
				const lastHoveredItem = hoveredItems[hoveredItems.length - 1];

				const popupContainer = this.etcItemHint.getPopupContainer();
				const isHintPopupUnderCursor = popupContainer.contains(lastHoveredItem);

				if (isHintPopupUnderCursor === false)
				{
					this.destroyEtcItemsHint();

					return;
				}

				bind(popupContainer, 'mouseleave', () => {
					setTimeout(() => {
						if (this.cursorOnEtcItem === false)
						{
							this.destroyEtcItemsHint();
						}
					}, 100);
				});
			}, 100);
		},
		destroyEtcItemsHint(): void {
			this.etcItemHint?.destroy();
			this.etcItemHint = null;
		},
		getEncodedString(str: string): string {
			return Text.encode(str);
		},
	},
	watch: {
		'selectedItems.length': function() {
			this.updateSelectedItemsWithData();
		},
	},
	mounted() {
		this.updateSelectedItemsWithData();
	},
	unmounted() {
		this.getUserSelectorDialog().destroy();
	},
	template: `
		<div class="ai__prompt-master_user-selector">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				<div
					type="text"
					class="ui-ctl-element"
				>
					<div ref="userSelector" class="ai__prompt-master_user-selector_inner">
						<ul class="ai__prompt-master-user-selector_users">
							<li
								v-for="(item, index) in selectedItemsWithData.slice(0, maxCirclesInInput)"
								:style="getSelectedItemStyle(item, index)"
								v-clickable-hint="getEncodedString(item.title)"
								class="ai__prompt-master-user-selector_user"
							>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
							</li>
							<li
								v-if="etcSelectedItemsCount > 0"
								ref="etcItem"
								class="ai__prompt-master-user-selector_etc-item"
								@mouseenter="showEtcItemsHint"
								@mouseleave="closeEtcItemsHint"
								:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
							>
								<span class="ai__prompt-master-user-selector_etc-item-plus">+</span>
								<span>{{ etcSelectedItemsCircleNumber }}</span>
							</li>
						</ul>
						<button @click="showUserSelector" class="ai__prompt-master-user-selector_add">
							<span class="ai__prompt-master-user-selector_add-text">
								{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_USER_SELECTOR_ADD_BTN') }}
							</span>
						</button>
					</div>
				</div>
			</div>
			<div v-if="getUserSelectorDialog().getItems().length === 0" class="ai__prompt-master_user-selector-loader">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<div
						type="text"
						class="ui-ctl-element"
					>
						<div class="ai__prompt-master_user-selector_inner">
							<ul class="ai__prompt-master-user-selector_users">
								<li
									v-for="(item, index) in selectedItems.slice(0, maxCirclesInInput)"
									:style="getSelectedItemStyle(item, index)"
									class="ai__prompt-master-user-selector_user"
								>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
								</li>
								<li
									v-if="etcSelectedItemsCount > 0"
									class="ai__prompt-master-user-selector_etc-item"
									:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
								>
									<span class="ai__prompt-master-user-selector_etc-item-plus">+</span>
									<span>{{ etcSelectedItemsCircleNumber }}</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
