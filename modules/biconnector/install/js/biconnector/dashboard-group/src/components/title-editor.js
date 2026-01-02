import type { BitrixVueComponentProps } from 'ui.vue3';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import { hint } from 'ui.vue3.directives.hint';

export const TitleEditor: BitrixVueComponentProps = {
	props: {
		canEdit: {
			type: Boolean,
			required: true,
		},
	},
	data(): Object
	{
		return {
			editMode: false,
		};
	},
	directives: {
		hint,
	},
	computed: {
		groupName: {
			get(): string
			{
				return this.$store.getters.groupName;
			},
			set(value): void
			{
				this.$store.commit('setGroupName', value);
			},
		},
		set(): Set
		{
			return Set;
		},
		nameHintOptions(): Object
		{
			return {
				text: this.$Bitrix.Loc.getMessage('BI_GROUP_SYSTEM_NAME_HINT'),
				popupOptions: {
					bindOptions: {
						position: 'bottom',
					},
					width: 220,
					offsetLeft: 40,
					angle: {
						position: 'top',
						offset: 0,
					},
				},
			};
		},
	},
	methods: {
		setEditMode()
		{
			this.editMode = true;
			this.$nextTick(() => {
				this.$refs.nameInput.focus();
			});
		},
		setViewMode()
		{
			this.editMode = false;
			if (!this.groupName)
			{
				this.$store.commit('setGroupName', this.$Bitrix.Loc.getMessage('BI_GROUP_NAME_NEW'));
			}
			this.$emit('onNameUpdate');
		},
	},
	emits: [
		'onNameUpdate',
	],
	components: {
		BIcon,
	},
	template: `
		<div class="group-header-title" :style="{ width: editMode ? '80%' : 'auto' }">
			<template v-if="canEdit">
				<input v-if="editMode" class="group-name-input" @blur="setViewMode" v-model="groupName" ref="nameInput">
				<template v-else>
					<div class="group-name">{{groupName}}</div>
					<BIcon
						:name="set.PENCIL_40"
						:size="20"
						color="#BDC1C6"
						:class="'group-name-edit-icon'"
						@click="setEditMode"
					></BIcon>
				</template>
			</template>
			<template v-else>
				<div class="group-name" v-hint="nameHintOptions">{{groupName}}</div>
			</template>
		</div>
	`,
};
