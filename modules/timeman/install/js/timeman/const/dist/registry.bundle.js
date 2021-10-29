this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
(function (exports,main_core) {
	'use strict';

	var EntityGroup = Object.freeze({
	  working: {
	    value: 'working',
	    title: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_WORKING'),
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_WORKING_HINT'),
	    primaryColor: '#8CEDA9',
	    secondaryColor: '#bbf5cc',
	    lightColor: '#f3fdf5'
	  },
	  workingCustom: {
	    value: 'working-custom',
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL_HINT')
	  },
	  personal: {
	    value: 'personal',
	    title: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL'),
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_PERSONAL_HINT'),
	    primaryColor: '#b5bac0',
	    secondaryColor: '#e3e9f0',
	    lightColor: '#fafbfb'
	  },
	  inactive: {
	    value: 'inactive',
	    title: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_INACTIVE'),
	    primaryColor: '#F5F5F5'
	  },
	  absence: {
	    value: 'absence',
	    title: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_ABSENCE'),
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_ABSENCE_HINT')
	  },
	  other: {
	    value: 'other',
	    title: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_OTHER'),
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_OTHER_HINT')
	  },
	  unknown: {
	    value: 'unknown',
	    hint: main_core.Loc.getMessage('TIMEMAN_CONST_ENTITY_GROUP_UNKNOWN_HINT')
	  },
	  getValues: function getValues() {
	    return ['working', 'personal'];
	  }
	});

	var EntityType = Object.freeze({
	  app: 'app',
	  site: 'site',
	  absence: 'absence',
	  incognito: 'incognito',
	  absenceShort: 'absenceShort',
	  other: 'other',
	  unknown: 'unknown',
	  group: 'group',
	  custom: 'custom'
	});

	var DayState = Object.freeze({
	  expired: 'EXPIRED',
	  opened: 'OPENED',
	  paused: 'PAUSED',
	  closed: 'CLOSED',
	  unknown: 'UNKNOWN'
	});

	exports.EntityGroup = EntityGroup;
	exports.EntityType = EntityType;
	exports.DayState = DayState;

}((this.BX.Timeman.Const = this.BX.Timeman.Const || {}),BX));
//# sourceMappingURL=registry.bundle.js.map
