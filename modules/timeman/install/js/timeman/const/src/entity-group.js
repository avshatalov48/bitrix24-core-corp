import {Loc} from 'main.core';

export const EntityGroup = Object.freeze({
	working: {
		value: 'working',
		title: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_WORKING'),
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_WORKING_HINT'),
		primaryColor: '#8CEDA9',
		secondaryColor: '#bbf5cc',
		lightColor: '#f3fdf5'
	},
	workingCustom: {
		value: 'working-custom',
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL_HINT'),
	},
	personal: {
		value: 'personal',
		title: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL'),
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL_HINT'),
		primaryColor: '#b5bac0',
		secondaryColor: '#e3e9f0',
		lightColor: '#fafbfb'
	},
	inactive: {
		value: 'inactive',
		title: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_INACTIVE'),
		primaryColor: '#F5F5F5',
	},
	absence: {
		value: 'absence',
		title: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_ABSENCE'),
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_ABSENCE_HINT'),
	},
	other: {
		value: 'other',
		title: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_OTHER'),
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_OTHER_HINT'),
	},
	unknown: {
		value: 'unknown',
		hint: Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_UNKNOWN_HINT'),
	},
	getValues()
	{
		return [
			'working',
			'personal',
		]
	},
});