import { BIcon } from 'ui.icon-set.api.vue';

import { OpenActionService } from '../../classes/open-action-service';
import { IconSetMixin } from '../../mixins/icon-set-mixin';
import { LocMixin } from '../../mixins/loc-mixin';
import { UserDetailedInfoContactItemValue } from './components/user-detailed-info-contact-item-value/user-detailed-info-contact-item-value';
import { UserDetailedInfoItem } from './components/user-detailed-info-item/user-detailed-info-item';
import { UserDetailedInfoEntityListValue } from './components/user-detailed-info-entity-list-value/user-detailed-info-entity-list-value';

import type { DepartmentType } from '../../type';

import './style.css';

// @vue/component
export const UserDetailedInfo = {
	name: 'UserDetailedInfo',
	components: {
		BIcon,
		UserDetailedInfoItem,
		UserDetailedInfoContactItemValue,
		UserDetailedInfoEntityListValue,
	},
	mixins: [LocMixin, IconSetMixin],
	props: {
		info: {
			/** @type UserMiniProfileData['detailInfo'] */
			type: Object,
			required: true,
		},
		heads: {
			/** @type Array<UserInfo> */
			type: Array,
			default: () => [],
		},
		userDepartments: {
			/** @type Array<DepartmentType> */
			type: Array,
			default: () => [],
		},
		departments: {
			/** @type Array<DepartmentType> */
			type: Array,
			default: () => [],
		},
		teams: {
			/** @type Array<TeamType> */
			type: Array,
			default: () => [],
		},
	},
	computed: {
		departmentItems(): Array<{ id: number, title: string }>
		{
			return this.userDepartments.map((department) => ({
				id: department.id,
				title: department.title,
				parentId: department.parentId,
				image: {
					bIconName: this.outlineSet.GROUP,
					iconClass: '--department',
				},
			}));
		},
		headItems(): Array<{ id: number, title: string }>
		{
			return this.heads.map((head) => ({
				id: head.id,
				title: head.name,
				image: {
					imageSrc: head.avatar,
				},
				href: head.url,
			}));
		},
		teamItems(): Array<{ id: number, title: string }>
		{
			return this.teams.map((team) => ({
				id: team.id,
				title: team.title,
				image: {
					bIconName: this.outlineSet.MY_PLAN,
					iconClass: '--team',
				},
			}));
		},
		headTitle(): string
		{
			return this.headItems.length < 2
				? this.loc('INTRANET_USER_MINI_DETAILED_INFO_HEAD')
				: this.loc('INTRANET_USER_MINI_DETAILED_INFO_HEAD_MULTIPLE')
			;
		},
		teamTitle(): string
		{
			return this.teamItems.length < 2
				? this.loc('INTRANET_USER_MINI_DETAILED_INFO_FC_SINGLE')
				: this.loc('INTRANET_USER_MINI_DETAILED_INFO_FC')
			;
		},
		departmentTitle(): string
		{
			return this.departmentItems.length < 2
				? this.loc('INTRANET_USER_MINI_DETAILED_INFO_DEPARTMENT')
				: this.loc('INTRANET_USER_MINI_DETAILED_INFO_DEPARTMENT_MULTIPLE')
			;
		},
	},
	methods: {
		onHeadClicked(id: number): void
		{
			const head = this.heads.find((item) => item.id === id);
			if (head?.url)
			{
				OpenActionService.openUserProfile(head.url);
			}
		},
		onStructureNodeClicked(id: number): void
		{
			OpenActionService.openStructureNodeId(id);
		},
		getParentDepartmentById(id: number): ?DepartmentType
		{
			return this.departments.find((item) => item.id === id);
		},
	},
	template: `
		<div class="intranet-user-mini-profile__detailed-info">
			<UserDetailedInfoItem v-if="info.personalMobile"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_PERSONAL_MOBILE')"
				type="personal-mobile"
			>
				<UserDetailedInfoContactItemValue :value="info.personalMobile" type="phone"/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="info.innerPhone"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_PHONE_INNER')"
				type="inner-phone"
			>
				{{ info.innerPhone }}
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="info.email"
				:title="loc('INTRANET_USER_MINI_DETAILED_INFO_EMAIL')"
				type="email"
			>
				<UserDetailedInfoContactItemValue :value="info.email" type="mail"/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="headItems.length"
				:title="headTitle"
				type="head"
			>
				<UserDetailedInfoEntityListValue
					entityType="user"
					:items="headItems"
					@click="(id) => onHeadClicked(id)"
				/>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="departmentItems.length"
				:title="departmentTitle"
				type="department"
			>
				<UserDetailedInfoEntityListValue 
					:items="departmentItems"
					entityType="department"
					@click="(id) => onStructureNodeClicked(id)"
					v-slot="{ item }"
				>
					<template v-if="getParentDepartmentById(item.parentId)">
						<div class="intranet-user-mini-profile__detailed-info-item__parent-department">
							<div class="intranet-user-mini-profile__detailed-info-item__parent-department-text"
								 :title="getParentDepartmentById(item.parentId)?.title"
								 data-test-id="usermp_parent-department"
							>
								{{ getParentDepartmentById(item.parentId)?.title }}
							</div>
							<div class="intranet-user-mini-profile__detailed-info-item__parent-department-arrow">
								<div class="intranet-user-mini-profile__detailed-info-item__parent-department-arrow-icon">
									<BIcon :name="outlineSet.CHEVRON_RIGHT_L" :size="16"/>
								</div>
							</div>
						</div>
					</template>
				</UserDetailedInfoEntityListValue>
			</UserDetailedInfoItem>
			<UserDetailedInfoItem v-if="teamItems.length"
				:title="teamTitle"
				type="team"
			>
				<UserDetailedInfoEntityListValue 
					:items="teamItems"
					entityType="team"
					@click="(id) => onStructureNodeClicked(id)"
				/>
			</UserDetailedInfoItem>
		</div>
	`,
};
