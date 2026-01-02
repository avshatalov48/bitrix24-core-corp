import { Dom, Type } from 'main.core';
import { mapActions, mapGetters } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';
import { checkListService } from 'tasks.v2.provider.service.check-list-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { UserModel } from 'tasks.v2.model.users';

// @vue/component
export const CheckListItemMixin = {
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		id: {
			type: [Number, String],
			required: true,
		},
		isPreview: {
			type: Boolean,
			default: false,
		},
	},
	emits: [
		'update',
		'addItem',
		'removeItem',
		'focus',
		'blur',
		'emptyBlur',
		'show',
		'hide',
	],
	data(): Object
	{
		return {
			isHovered: false,
			scrollContainer: null,
		};
	},
	computed: {
		...mapGetters({
			currentUserId: `${Model.Interface}/currentUserId`,
		}),
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		checkLists(): CheckListModel[]
		{
			return this.$store.getters[`${Model.CheckList}/getByIds`](this.task.checklist);
		},
		item(): CheckListModel
		{
			return this.$store.getters[`${Model.CheckList}/getById`](this.id);
		},
		canSave(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return this.task.rights.checklistSave === true;
		},
		canAdd(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return this.task.rights.checklistAdd === true;
		},
		canEdit(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return (
				this.canSave
				&& (
					this.task.rights.checklistEdit === true
					|| this.item.creator?.id === this.currentUserId
				)
			);
		},
		canCheckListToggle(): boolean
		{
			if (!this.isEdit)
			{
				return true;
			}

			return this.task.rights.checklistToggle === true;
		},
		canModify(): boolean
		{
			if (this.canEdit === true)
			{
				return true;
			}

			return this.item.actions.modify === true;
		},
		canRemove(): boolean
		{
			if (this.canEdit === true)
			{
				return true;
			}

			return this.item.actions.remove === true;
		},
		canToggle(): boolean
		{
			if (this.canCheckListToggle === true)
			{
				return true;
			}

			return this.item.actions.toggle === true;
		},
		hasAttachments(): boolean
		{
			return this.hasUsers;
		},
		hasUsers(): boolean
		{
			return (
				this.hasAccomplices
				|| this.hasAuditors
			);
		},
		hasAccomplices(): boolean
		{
			return this.accomplices?.length > 0;
		},
		hasAuditors(): boolean
		{
			return this.auditors?.length > 0;
		},
		accomplices(): ?UserModel[]
		{
			return this.item.accomplices;
		},
		auditors(): ?UserModel[]
		{
			return this.item.auditors;
		},
		files(): ?[]
		{
			return this.item.attachments;
		},
		textColor(): string
		{
			return this.completed ? 'var(--ui-color-base-4)' : 'var(--ui-color-base-1)';
		},
		groupMode(): boolean
		{
			return this.item.groupMode?.active === true;
		},
		groupModeSelected(): boolean
		{
			return this.item.groupMode?.selected === true;
		},
		completed(): boolean
		{
			return this.item.localCompleteState ?? this.item.isComplete;
		},
	},
	methods: {
		...mapActions(Model.Interface, [
			'addCheckListCompletionCallback',
		]),
		handleFocus(): void
		{
			this.$emit('focus', this.id);
		},
		handleBlur(): void
		{
			this.$emit('blur', this.id);
		},
		handleEmptyBlur(): void
		{
			this.$emit('emptyBlur', this.id);
		},
		updateCheckList(id: number | string, fields: Partial<CheckListModel>): Promise<void>
		{
			this.$emit('update', this.id);

			return this.$store.dispatch(`${Model.CheckList}/update`, { id, fields });
		},
		upsertCheckLists(items: CheckListModel[]): Promise<void>
		{
			this.$emit('update', this.id);

			return this.$store.dispatch(`${Model.CheckList}/upsertMany`, items);
		},
		updateTitle(title: string = ''): void
		{
			this.$store.dispatch(`${Model.CheckList}/update`, {
				id: this.id,
				fields: { title },
			});

			this.$emit('update', this.id);
		},
		addItem(sort: ?number): void
		{
			this.$emit(
				'addItem',
				{
					id: this.id,
					sort: Type.isNumber(sort) ? sort : null,
				},
			);
		},
		removeItem(): void
		{
			this.$emit('removeItem', this.id);
		},
		async complete(isComplete: boolean): void
		{
			if (this.canToggle === false)
			{
				return;
			}

			await this.updateCheckList(this.id, { localCompleteState: isComplete });

			const listParents = new Map();
			this.checkListManager.syncParentCompletionState(
				this.id,
				async (id: string | number, fields: Partial<CheckListModel>) => {
					listParents.set(id, fields);
					await this.updateCheckList(id, { localCompleteState: fields.isComplete });
				},
			);

			this.$emit('update', this.id);

			const completionCallback = async () => {
				await this.updateCheckList(this.id, { isComplete });
				listParents.forEach(async (fields: Partial<CheckListModel>, id: string | number) => {
					await this.updateCheckList(id, fields);
					this.saveCompleteState(id, fields.isComplete);
				});
			};

			this.addCheckListCompletionCallback({
				id: this.id,
				callback: completionCallback,
			});

			this.saveCompleteState(this.id, isComplete);
		},
		saveCompleteState(itemId: string | number, isComplete: boolean): void
		{
			if (!this.isPreview || !this.isEdit)
			{
				return;
			}

			if (isComplete)
			{
				void checkListService.complete(this.taskId, itemId);
			}
			else
			{
				void checkListService.renew(this.taskId, itemId);
			}
		},
		focusToItem(): void
		{
			const item = this.$refs.item;
			const scrollContainer = this.$parent.$el?.closest('[data-list]');

			const itemRect = Dom.getPosition(item);
			const containerRect = Dom.getPosition(scrollContainer);

			const offsetTopInsideContainer = itemRect.top - containerRect.top + scrollContainer.scrollTop;

			scrollContainer.scrollTo({
				top: offsetTopInsideContainer - 200,
				behavior: 'smooth',
			});
		},
	},
};
