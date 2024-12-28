import '../css/entitycontrol.css';
import 'ui.design-tokens';
import { Event, Loc, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import { EntitySelectorValue } from '../store';

const ANY_VALUE = 'ANY';
const BLOCKED_VALUE = 'BLOCKED';
const INHERIT_VALUE = 'INHERIT';
export const EntityControl = {
	name: 'EntityControl',
	entitySelector: null,
	props: {
		values: {
			type: Array,
			required: true,
		},
		valuesMap: {
			type: Object,
			required: true,
		},
		permissionIdentifier: {
			type: Object,
			required: true,
		},
	},
	emits: ['onTransitionValuesChanged'],
	data(): Object
	{
		return {
			label: {
				any: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_ANY'),
				blocked: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_BLOCKED'),
				inherit: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_INHERIT'),
				stages: Loc.getMessage('CRM_PERMS_EDIT_ENTITIES_STAGES'),
			},
			isEditMode: false,
			valuesData: this.values,
		};
	},
	computed: {
		isStageControl(): boolean
		{
			return Boolean(this.permissionIdentifier.stageField);
		},
		isInherited(): void
		{
			return this.isStageControl && this.currentValues.some((el): boolean => el.id === INHERIT_VALUE);
		},
		currentValues: {
			get(): Array | any
			{
				if (!Type.isArray(this.valuesData))
				{
					return [];
				}

				return this.availableValues.filter((el) => {
					return this.valuesData.includes(el.id);
				});
			},
			set(values: Array): void
			{
				this.valuesData = values.map((list) => list.id);
				this.$emit(
					'onTransitionValuesChanged',
					{ identifier: this.permissionIdentifier, values: this.valuesData },
				);
			},
		},
		availableValues(): EntitySelectorValue[]
		{
			const availableValues = [];
			for (const [key: string, value: string] of Object.entries(this.valuesMap))
			{
				if (
					(key === INHERIT_VALUE
						&& Type.isUndefined(this.permissionIdentifier.stageField)
						&& Type.isUndefined(this.permissionIdentifier.stageCode))
					|| key === this.permissionIdentifier.stageCode
				)
				{
					continue;
				}

				availableValues.push({
					id: key,
					entityId: 'stages',
					tabs: 'stages_tab',
					title: value,
				});
			}

			return availableValues;
		},
	},
	methods: {
		toEditMode(): void
		{
			this.isEditMode = true;
		},
		onHideDialogEvent(): void
		{
			if (this.entitySelector.getTags().length === 0)
			{
				const tag = {
					id: ANY_VALUE,
					entityId: 'stages',
					tabs: 'stages_tab',
					title: this.label.any,
				};

				if (this.isStageControl)
				{
					tag.id = INHERIT_VALUE;
					tag.title = this.label.inherit;
				}

				this.entitySelector.addTag(tag);
			}
			this.currentValues = this.entitySelector.getTags();
			this.isEditMode = this.entitySelector.getDialog().isOpen();
		},
		readOnlyLabels(): string
		{
			return this.currentValues.map((list) => list.title).join(', ');
		},
		clickSomewhere(): void
		{
			if (this.isEditMode && !this.entitySelector.getDialog().isOpen())
			{
				this.isEditMode = false;
				this.onHideDialogEvent();
			}
		},
		getDialogOptions(): Object
		{
			return {
				multiple: true,
				items: this.availableValues,
				selectedItems: this.currentValues,
				dropdownMode: false,
				height: 300,
				showAvatars: false,
				tabs: [
					{
						id: 'stages_tab',
						title: this.label.stages,
					},
				],
				recentTabOptions: {
					visible: false,
				},
				events: {
					onHide: this.onHideDialogEvent,
				},
			};
		},
		selectorOnBeforeTagAdd(event: BaseEvent): void
		{
			const selector = event.getTarget();
			const { tag } = event.getData();
			const singleValues: Set<string> = new Set([ANY_VALUE, INHERIT_VALUE, BLOCKED_VALUE]);
			if (singleValues.has(tag.getId()))
			{
				selector.removeTags();
			}
			else
			{
				selector.getTags().forEach((item) => {
					if (singleValues.has(item.getId()))
					{
						selector.removeTag(item);
					}
				});
			}
		},
	},
	mounted(): void
	{
		this.entitySelector = new TagSelector({
			events: {
				onBeforeTagAdd: this.selectorOnBeforeTagAdd,
				onAfterTagRemove: this.onHideDialogEvent,
			},
			dialogOptions: this.getDialogOptions(),
		});
		this.entitySelector.renderTo(this.$refs.entitySelectorRef);
	},
	created(): void
	{
		Event.bind(window, 'click', this.clickSomewhere);
	},
	destroyed(): void
	{
		Event.unbind(window, 'click', this.clickSomewhere);
	},
	template: `
		<div class="bx-crm-perms-edit-entity_control">
			<div
				class="bx-crm-perms-edit-entity_control-text"
				:class="{'--inherited': isInherited}"
				data-role="crm-type-relation-parent-selected-values"
				@click.stop="toEditMode"
				v-if="!isEditMode"
			>{{ readOnlyLabels() }}
			</div>
			<div
				ref="entitySelectorRef"
				data-role="crm-type-relation-parent-entity-selector"
				v-show="isEditMode"
			></div>
		</div>
	`,
};
