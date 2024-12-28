import { Engine, type Role, RoleIndustry } from 'ai.engine';
import { Type, Loc, BaseError, Runtime, Text } from 'main.core';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { UI } from 'ui.notification';
import { type EntityCatalog as EntityCatalogClass, type ItemData, type GroupData } from 'ui.entity-catalog';
import { RolesDialogLoaderPopup } from './roles-dialog-loader-popup';
import { showRolesDialogErrorPopup } from './roles-dialog-error-popup';
import { RolesDialogAnalytics } from './roles-dialog-analytics';

import { RolesDialogGroupListFooter, RolesDialogGroupListFooterEvents } from './components/roles-dialog-group-list-footer';
import { RolesDialogGroupListHeader } from './components/roles-dialog-group-list-header';
import { getRolesDialogContentHeader } from './components/roles-dialog-content-header';
import { getRolesDialogRoleItemWithStates } from './components/roles-dialog-role-item';
import { RolesDialogGroupItem } from './components/roles-dialog-group-item';
import { RolesDialogSearchStub, RolesDialogSearchStubEvents } from './components/roles-dialog-search-stub';
import { getRolesDialogEmptyGroupStubWithStates } from './components/roles-dialog-empty-group-stub';
import { RolesDialogRolesLibrary } from './components/roles-dialog-roles-library';

import './css/roles-dialog.css';

export type RolesDialogOptions = {
	moduleId: string;
	contextId: string;
	selectedRoleCode?: string;
	title?: string;
}

export const RolesDialogEvents = {
	HIDE: 'hide',
	SELECT_ROLE: 'select-role',
};

export type SelectRoleEventDataType = {
	role: Role;
}

export type RolesDialogItemData = ItemData | {
	customData: {
		selected: boolean;
		isInfoItem: boolean;
		avatar?: string;
		isNew: boolean;
		canBeFavourite?: boolean;
		isFavourite?: boolean;
		actions?: RolesDialogItemDataCustomActions;
	}
}

type RolesDialogItemDataCustomActions = {
	toggleFavourite?: (makeItFavourite: boolean) => void;
}

export type RolesDialogGroupData = GroupData | {
	customData: {
		isNew: boolean;
		emptyStubData: RolesDialogGroupDataEmptyStub,
	},
}

export type RolesDialogGroupDataEmptyStub = {
	title: string;
	description: string;
}

const RECOMMENDED_GROUP_CODE = 'recommended';
const RECENT_GROUP_CODE = 'recents';
const FAVOURITE_GROUP_CODE = 'favorites';
const CUSTOM_GROUP_CODE = 'customs';

export class RolesDialog extends EventEmitter
{
	#entityCatalog: EntityCatalogClass;
	#engine: Engine;
	#analytic: RolesDialogAnalytics;
	#roles: Role[];
	#recentRoles: Role[];
	#favouriteRoles: Role[];
	#customRoles: Role[];
	#defaultRoleCode: string;
	#industries: RoleIndustry[];
	#selectedDefaultRoleHandler: Function | null;
	#reloadDialogHandler: Function | null;
	#selectedRoleCode: ?string;
	#universalRole: Role;
	#title: ?string;
	#moduleId: string;
	#contextId: string;

	constructor(options: RolesDialogOptions)
	{
		super(options);
		this.setEventNamespace('AI.RolesDialog');

		this.#validateOptions(options);

		this.#title = options.title || '';
		if (options.engine)
		{
			this.#engine = options.engine;
		}
		else
		{
			this.#engine = new Engine({});
		}

		this.#moduleId = options.moduleId;
		this.#contextId = options.contextId;

		this.#engine.setModuleId(options.moduleId);
		this.#engine.setContextId(options.contextId);

		this.#selectedRoleCode = Type.isString(options.selectedRoleCode) ? options.selectedRoleCode : '';

		this.#analytic = new RolesDialogAnalytics({
			cSection: `${this.#moduleId}_${this.#contextId}`,
		});
	}

	#validateOptions(options: RolesDialogOptions)
	{
		if (Type.isStringFilled(options.moduleId) === false)
		{
			throw new BaseError('AI.RolesDialog: moduleId is required option and must be string');
		}

		if (Type.isStringFilled(options.contextId) === false)
		{
			throw new BaseError('AI.RolesDialog: contextId is required option and must be string');
		}

		if (options.selectedRoleCode !== undefined && Type.isString(options.selectedRoleCode) === false)
		{
			throw new BaseError('AI.RolesDialog: selectedRoleCode must be string');
		}

		if (options.title && Type.isString(options.title) === false)
		{
			throw new BaseError('AI.RolesDialog: title must be string');
		}

		if (options.engine && !(options.engine instanceof Engine))
		{
			throw new BaseError('AI.RolesDialog: engine option must be instance of Engine');
		}
	}

	setSelectedRoleCode(code: string): void
	{
		this.#selectedRoleCode = code;

		if (this.#entityCatalog)
		{
			this.#entityCatalog.setGroups(this.#getItemGroupsFromIndustries());
			this.#entityCatalog.setItems(this.#getItemsData());
		}
	}

	async show(): Promise<void>
	{
		if (this.#entityCatalog)
		{
			this.#entityCatalog.show();

			this.#analytic.sendOpenLabel(true, this.#selectedRoleCode);

			return;
		}

		await this.#showAfterInit();
	}

	hide(): void
	{
		this.#entityCatalog?.close();
	}

	async #showAfterInit(): Promise<void>
	{
		const loader = new RolesDialogLoaderPopup();
		let isShowLoader = true;

		setTimeout(() => {
			if (isShowLoader)
			{
				loader.show();
			}
		}, 300);

		try
		{
			await this.#init();

			this.#analytic.sendOpenLabel(true, this.#selectedRoleCode);

			this.#entityCatalog.show();
		}
		catch (e)
		{
			showRolesDialogErrorPopup();
			this.#analytic.sendOpenLabel(false, this.#selectedRoleCode);
			console.error(e);
		}
		finally
		{
			isShowLoader = false;
			loader.hide();
		}
	}

	#subscribeEvents(): void
	{
		this.#selectedDefaultRoleHandler = this.#selectDefaultRole.bind(this);

		EventEmitter.subscribe(
			document,
			RolesDialogGroupListFooterEvents.CHOOSE_STANDARD_ROLE,
			this.#selectedDefaultRoleHandler,
		);

		EventEmitter.subscribe(
			document,
			RolesDialogSearchStubEvents.CHOOSE_STANDARD_ROLE,
			this.#selectedDefaultRoleHandler,
		);

		this.#reloadDialogHandler = this.#reloadDialog.bind(this);

		EventEmitter.subscribe(
			'update',
			this.#reloadDialogHandler,
		);
	}

	async #reloadDialog() {
		const loader = new RolesDialogLoaderPopup();
		let isShowLoader = true;

		setTimeout(() => {
			if (isShowLoader)
			{
				loader.show();
			}
		}, 300);
		try
		{
			this.#entityCatalog.setItems([]);
			this.#entityCatalog.setGroups([]);
			await this.#loadData();
		}
		catch (e)
		{
			showRolesDialogErrorPopup();
			console.error(e);
		}
		finally
		{
			isShowLoader = false;
			loader.hide();
			this.#entityCatalog.setItems(this.#getItemsData());
			this.#entityCatalog.setGroups(this.#getItemGroupsFromIndustries());
			EventEmitter.emit('update-complete');
		}
	}

	#unsubscribeEvents(): void
	{
		EventEmitter.unsubscribe(
			document,
			RolesDialogGroupListFooterEvents.CHOOSE_STANDARD_ROLE,
			this.#selectedDefaultRoleHandler,
		);

		EventEmitter.unsubscribe(
			document,
			RolesDialogSearchStubEvents.CHOOSE_STANDARD_ROLE,
			this.#selectedDefaultRoleHandler,
		);

		EventEmitter.unsubscribe(
			'update',
			this.#reloadDialogHandler,
		);
	}

	#selectRole(role: Role): void
	{
		const event: BaseEvent<SelectRoleEventDataType> = new BaseEvent({
			data: {
				role,
			},
		});

		this.setSelectedRoleCode(role.code);
		this.#analytic.sendSelectLabel(this.#selectedRoleCode);

		this.emit(RolesDialogEvents.SELECT_ROLE, event);
	}

	#selectDefaultRole(): void
	{
		this.setSelectedRoleCode(this.#defaultRoleCode);

		const event: BaseEvent<SelectRoleEventDataType> = new BaseEvent({
			data: {
				role: this.#universalRole,
			},
		});

		this.emit(RolesDialogEvents.SELECT_ROLE, event);

		this.#entityCatalog.close();
	}

	async #init(): Promise<void>
	{
		await this.#loadData();

		await this.#initEntityCatalog();
	}

	async #initEntityCatalog(): void
	{
		const { EntityCatalog, States } = await Runtime.loadExtension('ui.entity-catalog');

		this.#entityCatalog = new EntityCatalog({
			title: this.#title,
			showSearch: true,
			showEmptyGroups: true,
			customComponents: {
				RolesDialogContentHeader: getRolesDialogContentHeader(States, this.#analytic),
				RolesDialogRoleItem: getRolesDialogRoleItemWithStates(States),
				RolesDialogGroupListHeader,
				RolesDialogGroupItem,
				RolesDialogGroupListFooter,
				RolesDialogSearchStub,
				RolesDialogEmptyGroupStub: getRolesDialogEmptyGroupStubWithStates(States),
				RolesDialogRolesLibrary,
			},
			popupOptions: {
				className: 'ai_roles-dialog_popup ui-entity-catalog__scope',
				resizable: false,
				width: 852,
				height: 510,
				animation: true,
				events: {
					onPopupShow: () => {
						this.#subscribeEvents();
					},
					onPopupClose: () => {
						this.emit(RolesDialogEvents.HIDE);
						this.#unsubscribeEvents();
						this.#analytic.sendCloseLabel(this.#selectedRoleCode);
					},
				},
			},
			slots: this.#getSlots(EntityCatalog),
			groups: this.#getItemGroupsFromIndustries(),
			items: this.#getItemsData(),
		});
	}

	#getSlots(EntityCatalog: EntityCatalogClass): Object {
		const slots = {
			[EntityCatalog.SLOT_MAIN_CONTENT_HEADER]: '<RolesDialogContentHeader />',
			[EntityCatalog.SLOT_MAIN_CONTENT_ITEM]: '<RolesDialogRoleItem :itemData="itemSlotProps" />',
			[EntityCatalog.SLOT_GROUP]: '<RolesDialogGroupItem :groupData="groupSlotProps" />',
			[EntityCatalog.SLOT_GROUP_LIST_HEADER]: '<RolesDialogGroupListHeader />',
			[EntityCatalog.SLOT_MAIN_CONTENT_EMPTY_GROUP_STUB]: '<RolesDialogEmptyGroupStub />',
			[EntityCatalog.SLOT_GROUP_LIST_FOOTER]: '<RolesDialogRolesLibrary />',
		};

		if (EntityCatalog.SLOT_MAIN_CONTENT_SEARCH_STUB)
		{
			slots[EntityCatalog.SLOT_MAIN_CONTENT_NO_SELECTED_GROUP_STUB] = '<RolesDialogSearchStub />';
			slots[EntityCatalog.SLOT_MAIN_CONTENT_SEARCH_STUB] = '<RolesDialogSearchStub />';
		}

		return slots;
	}

	#getInfoItemData(): RolesDialogItemData
	{
		return {
			id: 'info-item-data',
			title: Loc.getMessage('AI_COPILOT_ROLES_HELP_ITEM_TITLE'),
			subtitle: Loc.getMessage('AI_COPILOT_ROLES_HELP_ITEM_DESCRIPTION'),
			groupIds: this.#getAllIndustryCodesWithExcludes([FAVOURITE_GROUP_CODE, CUSTOM_GROUP_CODE]),
			customData: {
				isInfoItem: true,
			},
			button: {
				action: async () => {
					await Runtime.loadExtension('ui.feedback.form');
					const id = Math.round(Math.random() * 1000);
					BX.UI.Feedback.Form.open({

						id: `ai.roles-dialog.feedback-form_${id}`,
						presets: {
							sender_page: `${this.#moduleId}_${this.#contextId}`,
						},
						forms: [{
							zones: ['es'],
							id: 738,
							lang: 'es',
							sec: '77ui4p',
						}, {
							zones: ['en'],
							id: 740,
							lang: 'en',
							sec: 'obza3e',
						}, {
							zones: ['de'],
							id: 742,
							lang: 'de',
							sec: 'vqqxgr',
						}, {
							zones: ['com.br'],
							id: 744,
							lang: 'com.br',
							sec: 'nz3zig',
						}, {
							zones: ['ru', 'by', 'kz'],
							id: 746,
							lang: 'ru',
							sec: 'we50kv',
						}],
					});

					this.#analytic.sendFeedBackLabel();
				},
			},
		};
	}

	#getAllIndustryCodesWithExcludes(excludesCodes: string[]): string[]
	{
		const excludes = new Set(excludesCodes);

		return this.#industries
			.map((industry) => {
				return industry.code;
			})
			.filter((industryCode: string) => {
				return excludes.has(industryCode) === false;
			});
	}

	async #loadData(): void
	{
		const result = await this.#engine.getRolesDialogData();

		this.#universalRole = result.data.universalRole;
		this.#defaultRoleCode = this.#universalRole.code;

		this.#industries = result.data.items.map((roleIndustry) => {
			const { code, name, isNew } = roleIndustry;

			return {
				code,
				name,
				isNew,
			};
		});

		this.#industries.unshift(
			this.#getRecentRoleIndustry(),
			this.#getFavouriteRoleIndustry(),
			this.#getCustomRoleIndustry(),
			this.#getRecommendedRoleIndustry(),
		);

		this.#roles = result.data.items.reduce((roles: Role[], roleIndustry) => {
			const industryRoles = roleIndustry.roles;

			return [...roles, ...industryRoles];
		}, []);

		this.#recentRoles = result.data.recents;
		this.#favouriteRoles = result.data.favorites;
		this.#customRoles = result.data.customs;

		this.#roles = [...this.#roles, ...this.#customRoles];

		this.#selectedRoleCode = this.#selectedRoleCode || null;
	}

	#getRecommendedRoleIndustry(): RoleIndustry
	{
		return {
			code: RECOMMENDED_GROUP_CODE,
			name: Loc.getMessage('AI_COPILOT_ROLES_RECOMMENDED_GROUP'),
		};
	}

	#getRecentRoleIndustry(): RoleIndustry
	{
		return {
			code: RECENT_GROUP_CODE,
			name: Loc.getMessage('AI_COPILOT_ROLES_RECENT_GROUP'),
		};
	}

	#getFavouriteRoleIndustry(): RoleIndustry
	{
		return {
			code: FAVOURITE_GROUP_CODE,
			name: Loc.getMessage('AI_COPILOT_ROLES_FAVOURITE_GROUP'),
		};
	}

	#getCustomRoleIndustry(): RoleIndustry
	{
		return {
			code: CUSTOM_GROUP_CODE,
			name: Loc.getMessage('AI_COPILOT_ROLES_CUSTOM_GROUP'),
		};
	}

	#getItemsData(): RolesDialogItemData[]
	{
		let selectedRole = null;

		const items: RolesDialogItemData[] = this.#roles.map((role): RolesDialogItemData => {
			const groupIds = [role.industryCode];

			if (role.isRecommended)
			{
				groupIds.push(RECOMMENDED_GROUP_CODE);
			}

			if (this.#recentRoles.findIndex((recentRole) => recentRole.code === role.code) > -1)
			{
				groupIds.push(RECENT_GROUP_CODE);
			}

			if (this.#favouriteRoles.findIndex((favouriteRole) => favouriteRole.code === role.code) > -1)
			{
				groupIds.push(FAVOURITE_GROUP_CODE);
			}

			if (this.#customRoles.findIndex((customRole) => customRole.code === role.code) > -1)
			{
				groupIds.push(CUSTOM_GROUP_CODE);
			}

			if (role.code === this.#selectedRoleCode)
			{
				selectedRole = this.#getItemDataFromRole(role, groupIds);

				return null;
			}

			return this.#getItemDataFromRole(role, groupIds);
		}).filter((role) => role);

		const itemsSortedByNewness = items.sort((role) => {
			return role.customData.isNew ? -1 : 1;
		});

		const universalRoleItem = this.#getUniversalRoleItemData();

		return [
			universalRoleItem,
			selectedRole,
			...itemsSortedByNewness,
			this.#getInfoItemData(),
		].filter((role) => role);
	}

	#getUniversalRoleItemData(): ItemData
	{
		const role = this.#universalRole;
		const groupIds = [...this.#getAllIndustryCodesWithExcludes([FAVOURITE_GROUP_CODE, CUSTOM_GROUP_CODE])];

		return this.#getItemDataFromRole(role, groupIds);
	}

	#getItemGroupsFromIndustries(): [[RolesDialogGroupData[]]]
	{
		const selectedGroupIndex = this.#getSelectedGroupIndex();

		const groups = this.#industries.map((industry, index): RolesDialogGroupData => {
			const isSelectedRole = index === selectedGroupIndex;

			if (industry.code === RECENT_GROUP_CODE)
			{
				return this.#getRecentItemGroupData(isSelectedRole);
			}

			if (industry.code === FAVOURITE_GROUP_CODE)
			{
				return this.#getFavouriteItemGroupData(isSelectedRole);
			}

			if (industry.code === CUSTOM_GROUP_CODE)
			{
				return this.#getCustomItemGroupData(isSelectedRole);
			}

			return this.#getItemGroupDataFromIndustry(industry, isSelectedRole);
		});

		return [
			[
				...groups,
			],
		];
	}

	#getItemGroupDataFromIndustry(industry: RoleIndustry, isSelected: boolean = false): RolesDialogGroupData
	{
		return {
			id: industry.code,
			name: industry.name,
			selected: isSelected,
			customData: {
				isNew: industry.isNew,
			},
		};
	}

	#getRecentItemGroupData(isSelected: boolean = false): RolesDialogGroupData
	{
		return {
			...this.#getItemGroupDataFromIndustry(this.#getRecentRoleIndustry(), isSelected),
			compare: (item1, item2) => {
				return this.#compareRecentItems(item1, item2);
			},
		};
	}

	#compareRecentItems(item1: RolesDialogItemData, item2: RolesDialogItemData): number
	{
		const item1Index = this.#recentRoles.findIndex((rr) => item1.id === rr.code) + 1;
		const item2Index = this.#recentRoles.findIndex((rr) => item2.id === rr.code) + 1;

		if (item1.id === this.#getInfoItemData().id)
		{
			return 1;
		}

		return item1Index - item2Index;
	}

	#getFavouriteItemGroupData(isSelected: boolean = false): RolesDialogGroupData
	{
		return {
			...this.#getItemGroupDataFromIndustry(this.#getFavouriteRoleIndustry(), isSelected),
			compare: (item1, item2) => {
				return this.#compareFavouriteItems(item1, item2);
			},
			customData: {
				emptyStubData: {
					title: Loc.getMessage('AI_COPILOT_ROLES_EMPTY_FAVOURITE_GROUP_TITLE'),
					description: Loc.getMessage('AI_COPILOT_ROLES_EMPTY_FAVOURITE_GROUP'),
				},
			},
		};
	}

	#getCustomItemGroupData(isSelected: boolean = false): RolesDialogGroupData
	{
		return {
			...this.#getItemGroupDataFromIndustry(this.#getCustomRoleIndustry(), isSelected),

			customData: {
				emptyStubData: {
					title: Loc.getMessage('AI_COPILOT_ROLES_EMPTY_CUSTOM_GROUP_TITLE'),
				},
			},
		};
	}

	#compareFavouriteItems(item1: RolesDialogItemData, item2: RolesDialogItemData): number
	{
		const item1Index = this.#favouriteRoles.findIndex((rr) => item1.id === rr.code) + 1;
		const item2Index = this.#favouriteRoles.findIndex((rr) => item2.id === rr.code) + 1;

		return item1Index - item2Index;
	}

	#getItemDataFromRole(role: Role, groupIds: string[] = []): RolesDialogItemData
	{
		const isRoleInFavouriteList = this.#isRoleInFavouriteList(role.code);

		return {
			groupIds,
			id: role.code,
			name: role.name,
			title: role.name,
			subtitle: role.description,
			description: role.description,
			button: {
				text: Loc.getMessage('AI_COPILOT_ROLES_USE_ROLE_BTN'),
				action: () => {
					this.#selectRole(role);
					this.#entityCatalog.close();
				},
			},
			customData: {
				selected: role.code === this.#selectedRoleCode,
				avatar: role.avatar.medium,
				isNew: role.isNew,
				isFavourite: isRoleInFavouriteList,
				canBeFavourite: role.code !== this.#universalRole.code,
				actions: {
					toggleFavourite: (makeItFavourite: boolean) => {
						const roleCode = role.code;

						return this.#toggleRoleFavourite(roleCode, makeItFavourite);
					},
				},
			},
		};
	}

	#getSelectedGroupIndex(): number
	{
		const selectedGroupIndex = this.#industries.findIndex((industry) => {
			return this.#isSelectedIndustry(industry);
		});

		return selectedGroupIndex > -1 ? selectedGroupIndex : 0;
	}

	#isSelectedIndustry(industry: RoleIndustry): boolean
	{
		const items = this.#getItemsData();

		const selectedItem: RolesDialogItemData | undefined = items.find((item) => {
			return item.id === this.#selectedRoleCode;
		});

		return selectedItem?.groupIds.includes(industry.code) || false;
	}

	#isRoleInFavouriteList(roleCode: string): boolean
	{
		return this.#favouriteRoles.some((role) => {
			return role.code === roleCode;
		});
	}

	async #toggleRoleFavourite(roleCode: string, makeFavourite: boolean): Promise<any>
	{
		const role = roleCode === this.#universalRole.code
			? this.#universalRole
			: this.#roles.find((currentRole: Role) => currentRole.code === roleCode)
		;

		if (!role && roleCode !== this.#universalRole.code)
		{
			const failedMessage = makeFavourite
				? Loc.getMessage('AI_COPILOT_ROLES_ADD_TO_FAVOURITE_ACTION_FAILED')
				: Loc.getMessage('AI_COPILOT_ROLES_REMOVE_FROM_FAVOURITE_ACTION_FAILED')
			;

			UI.Notification.Center.notify({
				content: failedMessage,
			});

			return Promise.reject();
		}

		if (makeFavourite)
		{
			return this.#addRoleToFavouriteList(role.code, role.name);
		}

		return this.#removeRoleFromFavouriteList(role.code, role.name);
	}

	async #addRoleToFavouriteList(roleCode: string, roleName: string): Promise
	{
		return this.#engine.addRoleToFavouriteList(roleCode)
			.then((res) => {
				this.#favouriteRoles = res.data.items;

				this.#entityCatalog.setItems(this.#getItemsData());
				this.#entityCatalog.setGroups(this.#getItemGroupsFromIndustries());

				UI.Notification.Center.notify({
					content: Loc.getMessage('AI_COPILOT_ROLES_ADD_TO_FAVOURITE_NOTIFICATION_SUCCESS', {
						'#ROLE#': Text.encode(roleName),
					}),
				});
			})
			.catch((err) => {
				console.error(err);

				UI.Notification.Center.notify({
					content: Loc.getMessage('AI_COPILOT_ROLES_ADD_TO_FAVOURITE_ACTION_FAILED'),
				});
			});
	}

	async #removeRoleFromFavouriteList(roleCode: string, roleName: string): Promise
	{
		return this.#engine.removeRoleFromFavouriteList(roleCode)
			.then((res) => {
				this.#favouriteRoles = res.data.items;
				this.#entityCatalog.setItems(this.#getItemsData());
				this.#entityCatalog.setGroups(this.#getItemGroupsFromIndustries());

				UI.Notification.Center.notify({
					content: Loc.getMessage('AI_COPILOT_ROLES_REMOVE_FROM_FAVOURITE_NOTIFICATION_SUCCESS', {
						'#ROLE#': Text.encode(roleName),
					}),
				});
			})
			.catch((err) => {
				console.error(err);

				UI.Notification.Center.notify({
					content: Loc.getMessage('AI_COPILOT_ROLES_REMOVE_FROM_FAVOURITE_ACTION_FAILED'),
				});
			});
	}
}
