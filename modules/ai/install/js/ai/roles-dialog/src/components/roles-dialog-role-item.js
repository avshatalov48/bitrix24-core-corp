import { Type, Text } from 'main.core';
import { Main, Animated } from 'ui.icon-set.api.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { mapWritableState } from 'ui.vue3.pinia';
import { type States as StatesType } from 'ui.entity-catalog';
import { RolesDialogRoleItemAvatar } from './roles-dialog-role-item-avatar';
import { RolesDialogLabelNew } from './roles-dialog-label-new';

import 'ui.icon-set.animated';
import '../css/roles-dialog-role-item.css';

import type { RolesDialogItemData } from '../roles-dialog';

// eslint-disable-next-line max-lines-per-function
export function getRolesDialogRoleItemWithStates(States: StatesType): Object
{
	return {
		name: 'RolesDialogRoleItem',
		components: {
			BIcon,
			RolesDialogRoleItemAvatar,
			RolesDialogLabelNew,
		},
		data(): { isFavourite: boolean, isProcessingRoleFavourite: boolean } {
			return {
				isFavourite: this.itemData.itemData.customData.isFavourite,
				isProcessingRoleFavourite: false,
			};
		},
		props: ['itemData'],
		computed: {
			...mapWritableState(States.useGlobalState, {
				searching: 'searchApplied',
				searchQuery: 'searchQuery',
			}),
			item(): RolesDialogItemData {
				return this.itemData.itemData;
			},
			subtitle(): string {
				const subtitle = Text.encode(this.item.subtitle);

				if (this.searching && this.searchQuery !== '')
				{
					return subtitle.replaceAll(new RegExp(this.searchQuery, 'gi'), (match) => `<mark>${match}</mark>`);
				}

				return subtitle;
			},
			title(): string {
				const title = Text.encode(this.item.title);

				if (this.searching && this.searchQuery !== '')
				{
					return title.replaceAll(new RegExp(this.searchQuery, 'gi'), (match) => `<mark>${match}</mark>`);
				}

				return title;
			},
			isSelected(): boolean {
				return Boolean(this.item.customData?.selected);
			},
			isNew(): boolean {
				return Boolean(this.item.customData?.isNew);
			},
			isInfoItem(): boolean {
				return Boolean(this.item.customData?.isInfoItem);
			},
			className(): Object {
				return {
					'ai__roles-dialog_role-item': true,
					'--selected': this.isSelected,
				};
			},
			isRoleCanBeFavourite(): boolean {
				return this.item.customData.canBeFavourite === true;
			},
			favouriteLabelIconData(): { name: string, color: string, size: number } {
				const iconName = this.isProcessingRoleFavourite
					? Animated.LOADER_WAIT
					: Main.BOOKMARK_1
				;

				return {
					name: iconName,
					size: 24,
				};
			},
			favouriteLabelTitle(): string {
				return this.isFavourite
					? this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_REMOVE_FROM_FAVOURITE')
					: this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_ADD_TO_FAVOURITE')
				;
			},
			favouriteLabelClassname(): Object {
				return {
					'ai__roles-dialog_role-item-favourite-label': true,
					'--active': this.isFavourite,
					'--loading': this.isProcessingRoleFavourite,
				};
			},
			infoIcon(): string {
				return Main.INFO;
			},
		},
		methods: {
			selectRole(): void {
				if (Type.isFunction(this.item.button.action))
				{
					this.item.button.action();
				}
			},
			toggleFavourite(): void {
				if (this.isProcessingRoleFavourite)
				{
					return;
				}

				let isRequestFinished = false;

				setTimeout(() => {
					if (isRequestFinished === false)
					{
						this.isProcessingRoleFavourite = true;
					}
				}, 300);

				// eslint-disable-next-line promise/catch-or-return
				this.item.customData.actions.toggleFavourite(!this.isFavourite)
					.then(() => {
						this.isFavourite = !this.isFavourite;
					})
					.finally(() => {
						this.isProcessingRoleFavourite = false;
						isRequestFinished = true;
					});
			},
		},
		template: `
			<article @click="selectRole" :class="className">
				<RolesDialogRoleItemAvatar
					:avatar="item.customData.avatar"
					:avatar-alt="item.title"
					:icon="isInfoItem ? infoIcon : null"
				/>
				<div class="ai__roles-dialog_role-item-info">
					<div class="ai__roles-dialog_role-item-title-wrapper">
						<div class="ai__roles-dialog_role-item-title" v-html="title"></div>
						<div class="ai__roles-dialog_role-item-label">
							<RolesDialogLabelNew v-if="isNew" />
						</div>
					</div>
					<p class="ai__roles-dialog_role-item-description" v-html="subtitle"></p>
				</div>
				<button
					v-if="isRoleCanBeFavourite"
					:class="favouriteLabelClassname"
					:title="favouriteLabelTitle"
					@click.stop.prevent="toggleFavourite"
					@mousedown.stop
				>
					<BIcon
						:name="favouriteLabelIconData.name"
						:size="favouriteLabelIconData.size"
					/>
				</button>
			</article>
		`,
	};
}
