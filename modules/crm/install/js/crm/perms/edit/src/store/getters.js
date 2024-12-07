import { Type } from 'main.core';
import {
	AssignAttributeParam,
	AssignTransitionValues,
	MAX_SORT_ORDER_ON_THE_DESK,
	PermissionEntityIdentifier,
	Permissions,
} from '../store';
import { entityHash } from '../utils';

export default {
	permissionEntities(state) {
		return state.desk.permissionEntities;
	},

	permissionEntitiesExpanded(state) {
		return state.desk.permissionEntities.filter((entity) => {
			return !(entity.stageField && !state.ui.expandedStageEntities[entity.entityCode]);
		});
	},

	getMainPermissionEntityByCode: (state) => (entityCode) => {
		return state.desk.permissionEntities.find((item) => {
			return item.entityCode === entityCode && !item.stageField;
		});
	},

	getEntitiesGroupedByPermission: (state, getters) => (entityCode) => {
		const rootEntity = getters.getMainPermissionEntityByCode(entityCode);

		const entities = state.desk.permissionEntities
			.filter((entity) => entity.entityCode === rootEntity.entityCode);

		let permissions = [];

		for (const entity of entities)
		{
			for (const permCode of Object.keys(entity.permissions))
			{
				if (!Object.hasOwn(entity.permissions, permCode))
				{
					continue;
				}
				const values = entity.permissions[permCode];

				const permissionConfig: Permissions = state.desk.availablePermissions.find((perm: Permissions) => {
					return perm.code === permCode;
				});

				if (permissionConfig.sortOrder <= MAX_SORT_ORDER_ON_THE_DESK)
				{
					continue;
				}

				permissions.push({
					code: permCode,
					name: permissionConfig.name,
					values,
					isEntityStageSupport: entity.hasStages,
					isPermissionStageSupport: permissionConfig.canAssignPermissionToStages,
					stageField: entity.stageField,
					stageCode: entity.stageCode,
					stateName: entity.name,
					sortOrder: parseInt(permissionConfig.sortOrder, 10),
				});
			}
		}

		permissions = permissions.sort((a, b) => {
			if (a.sortOrder === b.sortOrder)
			{
				return 0;
			}

			return a.sortOrder < b.sortOrder ? -1 : 1;
		});

		return permissions;
	},

	availablePermissions(state) {
		return state.desk.availablePermissions;
	},

	getAssignedAttribute: (state) => (
		{ permissionCode, entityCode, stageField, stageCode }: PermissionEntityIdentifier,
	) => {
		if (stageField)
		{
			let value = state.roleAssignedPermissions?.[entityCode]?.[permissionCode]?.[stageField]?.[stageCode];

			// Not assigned value of stage attribute means it is inherited val from root permission entity
			if (Type.isUndefined(value))
			{
				value = '-';
			}

			return value;
		}

		return state.roleAssignedPermissions?.[entityCode]?.[permissionCode]?.['-'] || null;
	},

	isStageEntitiesVisible: (state) => (entity) => {
		const hash = entityHash(entity);

		return Boolean(state.ui.expandedStageEntities[hash]);
	},

	permissionsOnMainView(state) {
		return state.desk.availablePermissions.filter((perm) => {
			return perm.sortOrder <= MAX_SORT_ORDER_ON_THE_DESK;
		});
	},

	hasTariffPermission(state): boolean {
		return state.restriction.hasPermission;
	},

	getRestrictionScript(state): ?string {
		return state.restriction.restrictionScript;
	},

	getAvailablePermissionsOrders(state) {
		return state.desk.availablePermissionsOrders;
	},

	getSaveData(state) {
		const toRemove = [];
		const toChange = [];

		for (const hash of Object.keys(state.touched.touchedAttributes))
		{
			const touched: AssignAttributeParam = state.touched.touchedAttributes[hash];
			if (touched.identifier.stageField)
			{
				if (touched.value === '-')
				{
					toRemove.push({ ...touched.identifier });
				}
				else
				{
					toChange.push({ ...touched.identifier, value: touched.value });
				}

				continue;
			}

			if (touched.value === '')
			{
				toRemove.push({ ...touched.identifier });
			}
			else
			{
				toChange.push({ ...touched.identifier, value: touched.value });
			}
		}

		for (const hash of Object.keys(state.touched.touchedTransitions))
		{
			const touched: AssignTransitionValues = state.touched.touchedTransitions[hash];

			if (touched.identifier.stageField)
			{
				if (touched.values === '-')
				{
					toRemove.push({ ...touched.identifier });
				}
				else
				{
					toChange.push({ ...touched.identifier, settings: touched.values });
				}

				continue;
			}

			if (touched.values.length === 0 || (touched.values.length === 1 && touched.values[0] === 'INHERIT'))
			{
				toRemove.push({ ...touched.identifier });
			}
			else
			{
				toChange.push({ ...touched.identifier, settings: touched.values });
			}
		}

		if (state.role.id === 0) // is new role
		{
			appendDefaultAttributesPermissionsToChange(state, toChange);
			appendDefaultTransitionPermissionsToChange(state, toChange);
		}

		return {
			id: state.role.id,
			name: state.role.name,
			permissions: {
				toRemove,
				toChange,
			},
		};
	},

	getRoleName(state) {
		return state.role.name;
	},

	getRoleId(state): number {
		return state.role.id;
	},

	isSaveInProgress(state): boolean {
		return state.ui.isSaveInProgress;
	},

	getLastErrorMessage(state): string {
		return state.ui.lastErrorMessage;
	},

	setSaveInProgress(state): boolean {
		return state.ui.isSaveInProgress;
	},

	getTransitionSettings: (state) => (
		{ permissionCode, entityCode, stageField, stageCode }: PermissionEntityIdentifier,
	) => {
		if (stageField)
		{
			let value = state.transitions?.[entityCode]?.[stageField]?.[stageCode];

			// Not assigned value of stage attribute means it is inherited val from root permission entity
			if (Type.isUndefined(value))
			{
				value = ['INHERIT'];
			}

			return value;
		}

		return state.transitions?.[entityCode]?.['-'] || ['BLOCKED'];
	},
};

function appendDefaultAttributesPermissionsToChange(state, toChange): void
{
	Object.keys(state.roleAssignedPermissions).forEach((entityCode) => {
		const permissions = state.roleAssignedPermissions[entityCode] ?? {};
		Object.keys(permissions).forEach((permissionCode) => {
			if (permissionCode === 'TRANSITION')
			{
				return;
			}

			const permission = permissions[permissionCode];
			const value = permission?.['-'];
			if (!Type.isString(value))
			{
				return;
			}

			const hasSelectedPermission = toChange.find(
				(item) => item.entityCode === entityCode
					&& item.permissionCode === permissionCode
					&& Type.isUndefined(item.stageCode),
			);

			if (hasSelectedPermission)
			{
				return;
			}

			toChange.push({
				entityCode,
				permissionCode,
				value,
			});
		});
	});
}

function appendDefaultTransitionPermissionsToChange(state, toChange): void
{
	const transitionPermissionCode = 'TRANSITION';
	for (const [entityCode, permission] of Object.entries(state.transitions))
	{
		if (!Object.hasOwn(permission, ['-']))
		{
			return;
		}

		const hasSelectedPermission = toChange.find(
			(item) => item.entityCode === entityCode
				&& item.permissionCode === transitionPermissionCode
				&& Type.isUndefined(item.stageCode),
		);

		if (hasSelectedPermission)
		{
			return;
		}

		toChange.push({
			entityCode,
			permissionCode: transitionPermissionCode,
			settings: permission['-'],
		});
	}
}
