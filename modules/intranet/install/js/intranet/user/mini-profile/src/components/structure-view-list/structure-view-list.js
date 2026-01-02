import { BIcon } from 'ui.icon-set.api.vue';

import { ButtonMixin } from '../../mixins/button-mixin';
import { IconSetMixin } from '../../mixins/icon-set-mixin';
import { StructureView } from '../structure-view/structure-view';
import { LockedDepartment } from '../structure-view/const';

import type { LockedDepartmentType } from '../structure-view/type';
import type { StructureViewListDataType } from './type';
import type { DepartmentType } from '../../type';

import './style.css';

const StructureViewListAnimation = Object.freeze({
	next: 'intranet-user-mini-profile-structure-view-carousel-next',
	prev: 'intranet-user-mini-profile-structure-view-carousel-prev',
});

const maxElementsInBranch = 3;

// @vue/component
export const StructureViewList = {
	name: 'StructureViewList',
	components: {
		StructureView,
		BIcon,
	},
	mixins: [IconSetMixin, ButtonMixin],
	props: {
		structure: {
			/** @type StructureType */
			type: Object,
			required: true,
		},
		user: {
			/** @type UserData */
			type: Object,
			required: true,
		},
	},
	data(): StructureViewListDataType
	{
		return {
			index: 0,
			animationName: '',
			isTransitionInProgress: false,
		};
	},
	computed: {
		isPrevDisabled(): boolean
		{
			return this.isTransitionInProgress || this.index === 0;
		},
		isNextDisabled(): boolean
		{
			return this.isTransitionInProgress || this.index >= this.structure.userDepartmentIds.length - 1;
		},
		hasManyUserDepartments(): boolean
		{
			return this.structure.userDepartmentIds.length > 1;
		},
		maxBranchHeight(): number
		{
			let result = 0;

			for (const departmentId of this.structure.userDepartmentIds)
			{
				const departmentBranchHeight = this.makeDepartmentBranch(departmentId).length;
				if (departmentBranchHeight === maxElementsInBranch)
				{
					return maxElementsInBranch;
				}

				result = Math.max(result, departmentBranchHeight);
			}

			return result;
		},
		missingMaxDepartmentCount(): number
		{
			return maxElementsInBranch - this.maxBranchHeight;
		},
	},
	methods: {
		makeDepartmentBranch(departmentId: number): Array<DepartmentType | LockedDepartmentType>
		{
			const { departmentDictionary } = this.structure;
			const department = departmentDictionary[departmentId];
			if (!department)
			{
				return [];
			}

			const branch = [];

			let node = department;
			while (node && branch.length < maxElementsInBranch)
			{
				branch.push(node);

				if (node.parentId === null)
				{
					break;
				}

				node = departmentDictionary[node.parentId];
			}

			if (branch.length < maxElementsInBranch && branch[branch.length - 1].parentId !== 0)
			{
				branch.push(LockedDepartment);
			}

			return branch.reverse();
		},
		next(): void
		{
			if (this.isNextDisabled)
			{
				return;
			}

			this.animationName = StructureViewListAnimation.next;

			this.index += 1;
		},
		prev(): void
		{
			if (this.isPrevDisabled)
			{
				return;
			}

			this.animationName = StructureViewListAnimation.prev;

			this.index -= 1;
		},
	},
	template: `
		<div class="intranet-user-mini-profile__structure-view-list" data-test-id="usermp_structure-view-list">
			<div
				class="intranet-user-mini-profile__structure-view-list__preview-zone"
				:class="{ '--one-branch': !hasManyUserDepartments }"
				:style="{ '--missing-max-department-count': missingMaxDepartmentCount }"
				data-test-id="usermp_structure-preview-zone"
			>
				<TransitionGroup 
					type="transition" 
					:name="animationName"
					@beforeEnter="isTransitionInProgress = true"
					@afterLeave="isTransitionInProgress = false"
				>
					<template v-for="(departmentId, index) in structure.userDepartmentIds">
						<StructureView
							style="height: 100%;"
							v-if="index === this.index"
							:title="structure.title"
							:branch="makeDepartmentBranch(departmentId)"
							:key="departmentId"
							:userDepartmentId="departmentId"
							:headDictionary="structure.headDictionary"
							:user="user"
							data-test-id="usermp_structure-view"
						/>
					</template>
				</TransitionGroup>
			</div>
			<div v-if="hasManyUserDepartments"
				class="intranet-user-mini-profile__structure-view-control"
				data-test-id="usermp_structure-view-control"
			>
				<Button 
					:leftIcon="buttonIcon.CHEVRON_LEFT_S"
					:style="buttonStyle.PLAIN_NO_ACCENT"
					:size="buttonSize.EXTRA_SMALL"
					:removeRightCorners="true"
					:disabled="isPrevDisabled"
					@click="prev"
				/>
				<Button 
					:leftIcon="buttonIcon.CHEVRON_RIGHT_S"
					:style="buttonStyle.PLAIN_NO_ACCENT"
					:size="buttonSize.EXTRA_SMALL"
					:removeLeftCorners="true"
					:disabled="isNextDisabled"
					@click="next"
				/>
			</div>
		</div>
	`,
};
