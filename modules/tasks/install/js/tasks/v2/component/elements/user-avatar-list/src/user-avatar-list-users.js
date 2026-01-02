import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';
import 'ui.tooltip';

import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { UserAvatar } from 'tasks.v2.component.elements.user-avatar';

import './user-avatar-list-users.css';

// @vue/component
export const UserAvatarListUsers = {
	components: {
		BIcon,
		HoverPill,
		UserAvatar,
	},
	props: {
		users: {
			type: Array,
			required: true,
		},
		withCross: {
			type: Boolean,
			required: true,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'onUserClick',
		'onUserCrossClick',
	],
	setup(): Object
	{
		return {
			Outline,
		};
	},
	methods: {
		getNode(userId: number): ?HTMLElement
		{
			return this.$refs[`user_${userId}`]?.[0];
		},
	},
	template: `
		<template v-for="(user, index) of users" :key="user.id">
			<div class="b24-user-avatar-list-user-container">
				<HoverPill
					class="b24-user-avatar-list-user"
					:class="'--' + user.type"
					:withClear="!readonly"
					@click.stop="$emit('onUserClick', user.id)"
					@clear="$emit('onUserCrossClick', user.id)"
				>
					<div
						class="b24-user-avatar-list-user-inner"
						:ref="'user_' + user.id"
						:bx-tooltip-user-id="user.id"
						bx-tooltip-context="b24"
					>
							<span class="b24-user-avatar-list-user-image">
								<UserAvatar :src="user.image" :type="user.type"/>
							</span>
						<span class="b24-user-avatar-list-user-title">{{ user.name }}</span>
					</div>
				</HoverPill>
			</div>
		</template>
	`,
};
