import { Type } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon, Animated, Outline } from 'ui.icon-set.api.vue';

import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CheckListModel } from 'tasks.v2.model.check-list';
import { checkListService } from 'tasks.v2.provider.service.check-list-service';

import { CheckListManager } from '../../lib/check-list-manager';
import { checkListMeta } from '../../lib/check-list-meta';

import './check-list-list.css';

// @vue/component
export const CheckListList = {
	name: 'TaskCheckListList',
	components: {
		BIcon,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		isEmpty: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['open', 'addFastCheckList'],
	setup(): Object
	{
		return {
			Animated,
			Outline,
			checkListMeta,
		};
	},
	data(): Object
	{
		return {
			isLoading: null,
		};
	},
	computed: {
		...mapGetters({
			deletingCheckListIds: `${Model.Interface}/deletingCheckListIds`,
			disableCheckListAnimations: `${Model.Interface}/disableCheckListAnimations`,
		}),
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		checkLists(): CheckListModel[]
		{
			return this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist);
		},
		isFilledEmpty(): boolean
		{
			return this.checkListLength === 0 && this.wasFilled;
		},
		checkListLength(): number
		{
			const deletingRootIds = Object.values(this.deletingCheckListIds);

			const deletingIds = new Set();

			deletingRootIds.forEach((rootId: string | number) => {
				deletingIds.add(rootId);

				this.checkListManager
					.getAllChildren(rootId)
					.forEach((child: CheckListModel) => {
						deletingIds.add(child.id);
					});
			});

			return this.checkLists.filter(({ id }) => !deletingIds.has(id)).length;
		},
		containsChecklist(): boolean
		{
			return this.task.containsChecklist;
		},
		wasFilled(): boolean
		{
			return this.$store.getters[`${Model.Tasks}/wasFieldFilled`](this.taskId, checkListMeta.id);
		},
		loading(): boolean
		{
			return this.isLoading === true && this.isEdit;
		},
		canCheckListAdd(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return this.task.rights.checklistAdd === true;
		},
	},
	async created(): void
	{
		this.checkListManager = new CheckListManager({
			computed: {
				checkLists: () => this.checkLists,
			},
		});

		if (this.containsChecklist && this.checkLists.length === 0)
		{
			this.isLoading = true;

			await this.loadData();

			this.isLoading = false;
		}
	},
	methods: {
		async loadData(): Promise<void>
		{
			await checkListService.load(this.taskId);
		},
	},
	template: `
		<div
			:data-task-field-id="checkListMeta.id"
			class="tasks-check-list-list"
			:class="{ '--default': loading || isFilledEmpty }"
		>
			<div
				class="tasks-check-list-list-content"
				:class="{ '--default': loading || isFilledEmpty }"
			>
				<Transition name="check-list-fade" mode="in-out" :css="!disableCheckListAnimations">
					<div
						v-if="loading"
						key="loading"
						class="tasks-check-list-list-transition-content"
					>
						<div class="tasks-check-list-list-content-row">
							<BIcon :name="Animated.LOADER_WAIT"/>
							<div class="tasks-check-list-list-content-text">
								{{ loc('TASKS_V2_CHECK_LIST_LOADING') }}
							</div>
						</div>
					</div>
				</Transition>
				<Transition name="check-list-fade" mode="in-out" :css="!disableCheckListAnimations">
					<div
						v-if="!loading && isFilledEmpty"
						key="empty"
						class="tasks-check-list-list-transition-content"
					>
						<div class="tasks-check-list-list-content-row --stub" @click="$emit('addFastCheckList')">
							<BIcon :name="Outline.CHECK_LIST"/>
							<div class="tasks-check-list-list-content-text">
								{{ loc('TASKS_V2_CHECK_LIST_CHIP_TITLE') }}
							</div>
							<BIcon class="tasks-check-list-list-content-row-plus" :name="Outline.PLUS_L"/>
						</div>
					</div>
				</Transition>
				<div
					v-if="!loading && !isFilledEmpty"
					key="content"
					class="tasks-check-list-list-transition-content"
				>
					<slot></slot>
					<div
						v-if="canCheckListAdd"
						class="tasks-check-list-list-content-row --footer"
					>
						<div
							class="tasks-check-list-list-content-btn"
							:class="{ '--empty': isEmpty }"
							@click="$emit('addFastCheckList')"
						>
							<BIcon :name="Outline.PLUS_L"/>
							<div class="tasks-check-list-list-content-btn-text">
								{{ loc('TASKS_V2_CHECK_LIST_ADD_LABEL') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
