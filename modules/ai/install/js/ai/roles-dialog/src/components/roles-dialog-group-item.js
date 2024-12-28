import { RolesDialogGroupData } from '../roles-dialog';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions } from 'ui.icon-set.api.core';
import { EventEmitter } from 'main.core.events';

import '../css/roles-dialog-group-item.css';

import { RolesDialogLabelNew } from './roles-dialog-label-new';

export const RolesDialogGroupItem = {
	components: {
		BIcon,
		RolesDialogLabelNew,
	},
	props: ['groupData'],
	computed: {
		group(): RolesDialogGroupData {
			return this.groupData.groupData;
		},
		isNew(): boolean {
			return this.group.customData.isNew;
		},
		isSelected(): boolean {
			return this.group.selected;
		},
		handleClick(): ?Function
		{
			if (this.group.selected)
			{
				return undefined;
			}

			return this.groupData.handleClick;
		},
		groupItemClassname(): Object
		{
			return {
				'ai__roles-dialog_group-item': true,
				'--selected': this.isSelected,
			};
		},
		chevronRightIconName(): string
		{
			return Actions.CHEVRON_RIGHT;
		},
	},
	created() {
		if (this.groupData.groupData.id === 'recents')
		{
			EventEmitter.subscribe('update-complete', this.onUpdate);
		}
	},
	beforeDestroy() {
		if (this.groupData.groupData.id === 'recents')
		{
		EventEmitter.unsubscribe('update-complete', this.onUpdate);
		}
	},
	methods: {
		onUpdate() {
			this.groupData.handleClick();
		},
	},
	template: `
		<div @click="handleClick" class="ai__roles-dialog_group-item-wrapper">
			<div :class="groupItemClassname">
				<div class="ai__roles-dialog_group-item-inner">
				<div class="ai__roles-dialog_group-item-title-wrapper">
					<span class="ai__roles-dialog_group-item-title">
						{{ group.name }}
					</span>
					<div class="ai__roles-dialog_group-item-label-new">
						<RolesDialogLabelNew
							v-if="isNew"
							:inverted="isSelected"
						/>
					</div>
				</div>
					<b-icon :size="16" :name="chevronRightIconName"></b-icon>
				</div>
			</div>
		</div>
	`,
};
