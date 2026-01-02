import { EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import type { UserSelectorOptions } from './type';
import { EntityType } from 'sign.type';

export const UserSelectorEvent = Object.freeze({
	onShow: 'onShow',
	onHide: 'onHide',
	onItemSelect: 'onItemSelect',
	onItemDeselect: 'onItemDeselect',
});

export class UserSelector extends EventEmitter
{
	#container: HTMLElement = null;
	#dialog: Dialog = null;
	#isRoleEnabled: boolean = false;
	#cacheable: boolean = true;
	#excludedEntityList: Array = [];
	#options: UserSelectorOptions;
	#preselectedEntityList: Array<Object> = [];

	constructor(options: UserSelectorOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.UserSelector');
		this.#options = options;
		this.#isRoleEnabled = options.roleEnabled ?? false;
		this.#excludedEntityList = options.excludedEntityList ?? [];
		this.#cacheable = options.cacheable ?? true;
		this.#dialog = this.#createDialog();
	}

	#createDialog(): Dialog
	{
		const preselectedEntityList = this.#preselectedEntityList.length > 0
			? this.#preselectedEntityList.map((entity: Object) => [entity.type, entity.id])
			: this.#options.preselectedIds?.map((id) => [EntityType.USER, id]);

		return new Dialog({
			cacheable: this.#cacheable,
			width: 425,
			height: 363,
			multiple: this.#options.multiple ?? true,
			targetNode: this.#container,
			context: this.#options.context ?? 'sign_b2e_user_selector',
			entities: this.#getEntities(),
			dropdownMode: false,
			enableSearch: true,
			preselectedItems: preselectedEntityList,
			hideOnDeselect: true,
			events: {
				onHide: (event) => this.emit(UserSelectorEvent.onHide, {
					items: this.#dialog.getSelectedItems(),
				}),
				'Item:onSelect': (event) => this.emit(UserSelectorEvent.onItemSelect, {
					items: this.#dialog.getSelectedItems(),
				}),
				'Item:onDeselect': (event) => this.emit(UserSelectorEvent.onItemSelect, {
					items: this.#dialog.getSelectedItems(),
				}),
			},
		});
	}

	setExcludedEntityList(excludedEntityList: Array): void
	{
		this.#excludedEntityList = excludedEntityList;
	}

	setPreselectedEntityList(preselectedEntityList: Array<Object>): void
	{
		this.#preselectedEntityList = preselectedEntityList;
	}

	#getEntities(): Array<Object>
	{
		const entities = [
			{
				id: EntityType.USER,
				options: {
					intranetUsersOnly: true,
					'!userId': this.#getExcludedIdListByEntityType(EntityType.USER),
				},
				dynamicLoad: true,
			},
		];

		if (this.#isRoleEnabled)
		{
			entities.push(
				{
					id: EntityType.STRUCTURE_NODE_ROLE,
					options: {
						excludedRoleIdList: this.#getExcludedIdListByEntityType(EntityType.STRUCTURE_NODE_ROLE),
					},
					dynamicLoad: true,
					dynamicSearch: true,
				},
			);
		}

		return entities;
	}

	#getExcludedIdListByEntityType(entityType: string): number[]
	{
		const excludedEntityList = this.#excludedEntityList.filter(
			(entity: Object): boolean => entity.entityType === entityType,
		);

		return excludedEntityList.map((entity: Object): number => entity.entityId);
	}

	toggle(): void
	{
		this.getDialog().show();
	}

	getDialog(): Dialog
	{
		if (this.#cacheable === false)
		{
			this.#dialog = this.#createDialog();
		}

		return this.#dialog;
	}
}
