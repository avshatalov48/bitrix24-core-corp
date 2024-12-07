/* eslint no-param-reassign: off */
import { AppData } from '../app';
import { AssignAttributeParam, AssignTransitionValues, PermissionEntityIdentifier } from '../store';
import { entityHash, hashIdentifier } from '../utils';
import { Type } from 'main.core';

export default {
	setInitData(state, data: AppData) {
		state.roleAssignedPermissions = data.roleAssignedPermissions;
		state.desk.permissionEntities = data.permissionEntities;
		state.restriction.hasPermission = data.restriction.hasPermission;
		state.restriction.restrictionScript = data.restriction.restrictionScript;
		state.transitions = data.roleAssignedSettings;

		const availablePermissionsOrders = {};
		const availablePermissions = data.availablePermissions.map(item => {
			const sortOrder = parseInt(item.sortOrder, 10);
			availablePermissionsOrders[item.code] = sortOrder;

			return {
				...item,
				sortOrder,
			};
		});

		state.desk.availablePermissionsOrders = availablePermissionsOrders;
		state.desk.availablePermissions = availablePermissions;

		state.role.id = parseInt(data.role.id, 10);
		state.role.name = data.role.name;

		state.touched.originalAttributes = structuredClone(data.roleAssignedPermissions);
		state.touched.originalTransitions = structuredClone(data.roleAssignedSettings);
	},

	assignPermissionAttribute(state, payload: AssignAttributeParam) {
		setAssignedAttributes(state.roleAssignedPermissions, payload.identifier, payload.value);
		this.commit('setAttributeTouched', { identifier: payload.identifier, value: payload.value });
	},

	assignTransitions(state, payload: AssignTransitionValues) {
		setAssignedTransitions(state.transitions, payload.identifier, payload.values);
		this.commit('setTransitionsTouched', { identifier: payload.identifier, values: payload.values });
	},

	setAttributeTouched(state, payload: AssignAttributeParam) {
		const permissionCode = payload.identifier.permissionCode;
		const entityCode = payload.identifier.entityCode;
		const stageField = payload.identifier.stageField;
		const stageCode = payload.identifier.stageCode;

		const value = payload.value;
		let original = '';
		if (stageField)
		{
			original = state.touched.originalAttributes?.[entityCode]?.[permissionCode]?.[stageField]?.[stageCode];
		}
		else
		{
			original = state.touched.originalAttributes?.[entityCode]?.[permissionCode]?.['-'];
		}

		const hash = hashIdentifier(payload.identifier);

		if (original === value)
		{
			try
			{
				delete state.touched.touchedAttributes[hash];
			}
			catch {}
		}
		else
		{
			state.touched.touchedAttributes[hash] = {
				value,
				identifier: payload.identifier,
			};
		}
	},

	setTransitionsTouched(state, payload: AssignTransitionValues) {
		const entityCode = payload.identifier.entityCode;
		const stageField = payload.identifier.stageField;
		const stageCode = payload.identifier.stageCode;

		const values = payload.values;
		let original = [];
		if (stageField && stageCode)
		{
			original = state.touched.originalTransitions?.[entityCode]?.[stageField]?.[stageCode];
		}
		else
		{
			original = state.touched.originalTransitions?.[entityCode]?.['-'];
		}

		const hash = hashIdentifier(payload.identifier);

		if (Type.isArray(original) && isArraysEqual(original, values))
		{
			try
			{
				delete state.touched.touchedTransitions[hash];
			}
			catch {}
		}
		else
		{
			state.touched.touchedTransitions[hash] = {
				values,
				identifier: payload.identifier,
			};
		}
	},

	resetTouchedAttributes(state) {
		state.touched.originalAttributes = JSON.parse(JSON.stringify((state.roleAssignedPermissions)));
		state.touched.touchedAttributes = {};
	},

	resetTouchedTransitions(state) {
		state.touched.originalTransitions = JSON.parse(JSON.stringify((state.transitions)));
		state.touched.touchedTransitions = {};
	},

	toggleStagesVisibility(state, entity) {
		const hash = entityHash(entity);
		if (state.ui.expandedStageEntities[hash])
		{
			delete state.ui.expandedStageEntities[hash];
		}
		else
		{
			state.ui.expandedStageEntities[hash] = true;
		}
	},

	setRoleName(state, newName) {
		state.role.name = newName;
	},

	setSaveInProgress(state, value: boolean) {
		state.ui.isSaveInProgress = value;
	},

	setLastErrorMessage(state, message): string {
		state.ui.lastErrorMessage = message;
	},
};

function setAssignedAttributes(obj, identifier: PermissionEntityIdentifier, value)
{
	const permissionCode = identifier.permissionCode;
	const entityCode = identifier.entityCode;
	const stageField = identifier.stageField;
	const stageCode = identifier.stageCode;

	if (!obj[entityCode])
	{
		obj[entityCode] = {};
	}

	if (!obj[entityCode][permissionCode])
	{
		obj[entityCode][permissionCode] = {};
	}

	if (stageField)
	{
		if (!obj[entityCode][permissionCode][stageField])
		{
			obj[entityCode][permissionCode][stageField] = {};
		}

		obj[entityCode][permissionCode][stageField][stageCode] = value;

		return;
	}

	if (value === '')
	{
		delete obj[entityCode][permissionCode]['-'];
	}
	else
	{
		obj[entityCode][permissionCode]['-'] = value;
	}

	clearEmptyNodes(obj);
}

function clearEmptyNodes(node, nodeName, parent)
{
	if (!Type.isObject(node))
	{
		return;
	}

	for (const [childName, childNode] of Object.entries(node))
	{
		if (isObjectEmpty(childNode))
		{
			delete node[childName];
			continue;
		}

		clearEmptyNodes(childNode, childName, node);
	}

	if (parent && isObjectEmpty(node))
	{
		delete parent[nodeName];
	}
}

function isObjectEmpty(obj): boolean
{
	if (!obj)
	{
		return true;
	}

	return Object.keys(obj).length === 0;
}

function setAssignedTransitions(obj, identifier: PermissionEntityIdentifier, values)
{
	const entityCode = identifier.entityCode;
	const stageField = identifier.stageField;
	const stageCode = identifier.stageCode;

	if (!obj[entityCode])
	{
		obj[entityCode] = {};
	}

	if (stageField && stageCode)
	{
		if (!obj[entityCode][stageField])
		{
			obj[entityCode][stageField] = {};
		}

		obj[entityCode][stageField][stageCode] = values;

		return;
	}

	if (values === '')
	{
		delete obj[entityCode]['-'];
	}
	else
	{
		obj[entityCode]['-'] = values;
	}

	clearEmptyNodes(obj);
}

function isArraysEqual(first: Array, second: Array): boolean
{
	return first.sort().toString() === second.sort().toString();
}
