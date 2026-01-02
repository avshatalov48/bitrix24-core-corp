import { Backend } from '../classes/backend';
import { InitialParamDict, InitialParamService } from '../classes/initial-param-service';

import type { DepartmentType, UserData } from '../type';
import { Divider } from './divider/divider';
import { ErrorState } from './error-state/error-state';
import { ErrorStateDict } from './error-state/const';
import { LoaderTransition } from './loader-transition/loader-transition';
import { UserMiniProfileLoader } from './loader/loader';
import { StructureViewList } from './structure-view-list/structure-view-list';
import { CollapseTransition } from './transition-collapse/transition-collapse';
import type { UserMiniProfileDataType } from './type';
import { UserBaseInfo } from './user-base-info/user-base-info';
import { UserDetailedInfo } from './user-detailed-info/user-detailed-info';

// @vue/component
export const UserMiniProfileComponent = {
	name: 'UserMiniProfile',
	components: {
		UserMiniProfileLoader,
		UserBaseInfo,
		UserDetailedInfo,
		Divider,
		StructureViewList,
		CollapseTransition,
		ErrorState,
		LoaderTransition,
	},
	props: {
		popup: {
			/** @type Popup */
			type: Object,
			required: true,
		},
		userId: {
			type: Number,
			required: true,
		},
	},
	data(): UserMiniProfileDataType
	{
		return {
			isError: false,
			errorType: ErrorStateDict.Default,
			isLoaded: false,
			isLoading: false,
			isExpanded: true,
			isExpandBlocked: false,
			backendData: null,
		};
	},
	computed: {
		userDepartments(): Array<DepartmentType>
		{
			const userDepartmentIds = this.backendData.structure?.userDepartmentIds ?? [];
			const departmentDictionary = this.backendData.structure?.departmentDictionary ?? [];
			const userDepartments = [];
			userDepartmentIds.forEach((id: number) => {
				const department = departmentDictionary[id];
				if (!department)
				{
					return;
				}

				userDepartments.push(department);
			});

			return userDepartments;
		},
		departments(): Array<DepartmentType>
		{
			return Object.values(this.backendData.structure?.departmentDictionary ?? {});
		},
		heads(): Array<UserData>
		{
			const userDepartmentIds = this.backendData.structure?.userDepartmentIds ?? [];
			const departmentDictionary = this.backendData.structure?.departmentDictionary ?? [];

			const headDictionary = this.backendData.structure?.headDictionary ?? [];

			const headList = [];
			const headIds: Set<number> = new Set();

			userDepartmentIds.forEach((departmentId: number) => {
				/** @type DepartmentType | null */
				const department = departmentDictionary[departmentId];
				if (!department)
				{
					return;
				}

				const { headIds: departmentHeadIds } = department;
				if (departmentHeadIds.includes(this.userId))
				{
					return;
				}

				departmentHeadIds.forEach((headId) => {
					if (!headIds.has(headId) && headDictionary[headId])
					{
						headList.push(headDictionary[headId]);
						headIds.add(headId);
					}
				});
			});

			return headList.filter((head) => head.id !== this.userId);
		},
		canShowDepartments(): boolean
		{
			return this.userDepartments.length > 0;
		},
		isShouldBeExpandedByInitial(): boolean
		{
			return InitialParamService.getValue(InitialParamDict.RightSideExpand) === 'Y';
		},
		canChat(): boolean
		{
			return this.backendData?.access.canChat ?? false;
		},
		isShowStructure(): boolean
		{
			return this.canShowDepartments && this.isExpanded;
		},
	},
	created(): void
	{
		if (!this.isLoaded)
		{
			this.isLoading = true;
			void Backend.load(this.userId)
				.then((data) => {
					this.backendData = data;
					this.isLoaded = true;
				})
				.catch((errorCode) => {
					if (errorCode === 'ACCESS_DENIED')
					{
						this.errorType = ErrorStateDict.AccessDenied;
					}
					this.isError = true;
				})
				.finally(() => {
					this.isLoading = false;
					this.adjustPopup();
				})
			;
		}

		this.isExpanded = this.isShouldBeExpandedByInitial;
	},
	mounted(): void
	{
		this.adjustPopup();
	},
	methods: {
		onExpand(): void
		{
			if (this.isExpandBlocked)
			{
				return;
			}

			this.isExpanded = !this.isExpanded;
			InitialParamService.save(InitialParamDict.RightSideExpand, this.isExpanded ? 'Y' : 'N');
		},
		adjustPopup(): void
		{
			this.popup?.adjustPosition();
		},

		onCollapseStart(): void
		{
			this.isExpandBlocked = true;
			this.adjustPopup();
		},
		onCollapseEnd(): void
		{
			this.isExpandBlocked = false;
			this.adjustPopup();
		},
		getUserData(): UserData
		{
			const { avatar, name, workPosition, url } = this.backendData.baseInfo;

			return {
				id: this.userId,
				avatar,
				name,
				workPosition,
				url,
			};
		},
	},
	template: `
		<div class="intranet-user-mini-profile-wrapper">
			<template v-if="!isError">
				<LoaderTransition 
					:isLoading="isLoading" 
					:isShowContent="!!backendData"
					:isLoaderShort="!isShouldBeExpandedByInitial"
					@end="() => this.adjustPopup()"
				>
					<div class="intranet-user-mini-profile-wrapper__content">
						<div class="intranet-user-mini-profile-wrapper__column --left"
							 ref="leftColumn"
						>
							<UserBaseInfo
								:userId="userId"
								:info="backendData.baseInfo"
								:isShowExpand="canShowDepartments"
								:isExpanded="isExpanded"
								:canChat="canChat"
								@expand="onExpand"
							/>
							<template v-if="backendData.detailInfo">
								<Divider style="margin-top: 18px; margin-bottom: 14px"/>
								<UserDetailedInfo
								  :info="backendData.detailInfo"
								  :userDepartments="userDepartments"
								  :departments="departments"
								  :heads="heads"
								  :teams="backendData.structure.teams"
								/>
							</template>
						</div>
						<CollapseTransition
							@start="onCollapseStart"
							@end="onCollapseEnd"
							:initialHeight="$refs.leftColumn?.offsetHeight"
						>
							<div v-if="isShowStructure" 
								class="intranet-user-mini-profile-wrapper__content__right-wrapper"
								data-test-id="usermp_structure-wrapper"
							>
								<Divider isVertical style="margin: 0 18px"/>
								<div class="intranet-user-mini-profile-wrapper__column --right" data-test-id="usermp_structure-column">
									<StructureViewList
										:structure="backendData.structure"
										:user="getUserData()"
										data-test-id="usermp_structure-view-list"
									/>
								</div>
							</div>
						</CollapseTransition>
					</div>
				</LoaderTransition>
			</template>
			<ErrorState v-if="isError"
				:type="errorType"
			/>
		</div>
	`,
};
