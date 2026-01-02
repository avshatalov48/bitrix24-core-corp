/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_companyStructure_api) {
	'use strict';

	var getInvitedUserData = function getInvitedUserData(item) {
	  var id = item.id,
	    title = item.title,
	    customData = item.customData;
	  var nodeId = customData.nodeId;
	  return {
	    id: id,
	    avatar: '',
	    name: title,
	    workPosition: '',
	    role: humanresources_companyStructure_api.memberRoles.employee,
	    url: "/company/personal/user/".concat(id, "/"),
	    isInvited: true,
	    nodeId: nodeId
	  };
	};
	var getUserDataBySelectorItem = function getUserDataBySelectorItem(item, role) {
	  var _item$getLink, _customData$get, _customData$get2;
	  var id = item.id,
	    avatar = item.avatar,
	    title = item.title,
	    customData = item.customData;
	  item.setLink(null);
	  var link = (_item$getLink = item.getLink()) !== null && _item$getLink !== void 0 ? _item$getLink : '';
	  var workPosition = (_customData$get = customData.get('position')) !== null && _customData$get !== void 0 ? _customData$get : '';
	  var isInvited = (_customData$get2 = customData.get('invited')) !== null && _customData$get2 !== void 0 ? _customData$get2 : false;
	  return {
	    id: id,
	    avatar: avatar,
	    name: title.text,
	    workPosition: workPosition,
	    role: role,
	    url: link,
	    isInvited: isInvited
	  };
	};

	var optionColor = Object.freeze({
	  paletteBlue50: {
	    tokenClass: '--ui-color-palette-blue-50',
	    color: '#2FC6F6'
	  },
	  paletteGreen55: {
	    tokenClass: '--ui-color-palette-green-55',
	    color: '#95C500'
	  },
	  paletteRed40: {
	    tokenClass: '--ui-color-palette-red-40',
	    color: '#FF9A97'
	  },
	  accentAqua: {
	    tokenClass: '--ui-color-accent-aqua',
	    color: '#55D0E0'
	  },
	  accentTurquoise: {
	    tokenClass: '--ui-color-accent-turquoise',
	    color: '#05b5ab'
	  },
	  paletteOrange40: {
	    tokenClass: '--ui-color-palette-orange-40',
	    color: '#FFC34D'
	  },
	  lightBlue: {
	    tokenClass: '--ui-color-accent-light-blue',
	    color: '#559be6'
	  },
	  extranetColor: {
	    tokenClass: '--ui-color-extranet',
	    color: '#e89b06'
	  },
	  whiteBase: {
	    tokenClass: '--ui-color-palette-white-base',
	    color: '#FFFFFF'
	  },
	  paletteGray70: {
	    tokenClass: '--ui-color-palette-gray-70',
	    color: '#828B95'
	  }
	});
	var getColorCode = function getColorCode(colorKey) {
	  var colorOption = optionColor[colorKey];
	  if (colorOption) {
	    var _getComputedStyle;
	    return document.body ? (_getComputedStyle = getComputedStyle(document.body)) === null || _getComputedStyle === void 0 ? void 0 : _getComputedStyle.getPropertyValue(colorOption.tokenClass) : colorOption.color;
	  }
	  return null;
	};

	/**
	 * Department type. The equivalent of Bitrix\HumanResources\Type\NodeEntityType
	 */
	var EntityTypes = Object.freeze({
	  department: 'DEPARTMENT',
	  team: 'TEAM'
	});
	var WizardApiEntityChangedDict = Object.freeze({
	  department: 'department',
	  employees: 'employees',
	  bindChats: 'bindChats',
	  settings: 'settings'
	});
	var NodeSettingsTypes = {
	  businessProcAuthority: 'BUSINESS_PROC_AUTHORITY',
	  reportsAuthority: 'REPORTS_AUTHORITY'
	};
	var ChatTypes = Object.freeze({
	  channel: 'CHANNEL',
	  chat: 'CHAT',
	  collab: 'COLLAB'
	});

	/**
	 * Type for color picker and structure
	 */

	var NodeColorsSettingsDict = Object.freeze({
	  blue: {
	    name: 'blue',
	    pickerColor: 'rgba(0, 117, 255, 1)',
	    headBackground: 'rgba(0, 117, 255, 0.23)',
	    treeHeadBackground: 'rgba(0, 117, 255, 0.23)',
	    namePlaceholder: 'rgba(0, 117, 255, 0.12)',
	    headPlaceholder: 'rgba(0, 117, 255, 0.15)',
	    previewBorder: 'rgba(0, 117, 255, 0.5)',
	    bubbleBackground: 'rgba(80, 156, 252, 1)',
	    focusedBorderColor: 'rgba(0, 117, 255, 0.25)',
	    expandedBorderColor: 'rgba(77, 158, 255, 1)',
	    avatarImage: 'placeholder-avatar-blue.svg'
	  },
	  green: {
	    name: 'green',
	    pickerColor: 'rgba(22, 221, 154, 1)',
	    headBackground: 'rgba(22, 221, 154, 0.23)',
	    treeHeadBackground: 'rgba(22, 221, 154, 0.27)',
	    namePlaceholder: 'rgba(22, 221, 154, 0.23)',
	    headPlaceholder: 'rgba(22, 221, 154, 0.2)',
	    previewBorder: 'rgba(22, 221, 154, 0.5)',
	    bubbleBackground: 'rgba(22, 221, 154, 1)',
	    focusedBorderColor: 'rgba(22, 221, 154, 0.25)',
	    expandedBorderColor: 'rgba(22, 221, 154, 1)',
	    avatarImage: 'placeholder-avatar-green.svg'
	  },
	  cyan: {
	    name: 'cyan',
	    pickerColor: 'rgba(25, 202, 212, 1)',
	    headBackground: 'rgba(25, 202, 212, 0.23)',
	    treeHeadBackground: 'rgba(25, 202, 212, 0.27)',
	    namePlaceholder: 'rgba(25, 202, 212, 0.23)',
	    headPlaceholder: 'rgba(25, 202, 212, 0.2)',
	    previewBorder: 'rgba(25, 202, 212, 0.5)',
	    bubbleBackground: 'rgba(25, 202, 212, 1)',
	    focusedBorderColor: 'rgba(25, 202, 212, 0.25)',
	    expandedBorderColor: 'rgba(25, 202, 212, 1)',
	    avatarImage: 'placeholder-avatar-cyan.svg'
	  },
	  orange: {
	    name: 'orange',
	    pickerColor: 'rgba(249, 172, 22, 1)',
	    headBackground: 'rgba(249, 172, 22, 0.23)',
	    treeHeadBackground: 'rgba(249, 172, 22, 0.27)',
	    namePlaceholder: 'rgba(249, 172, 22, 0.23)',
	    headPlaceholder: 'rgba(249, 172, 22, 0.2)',
	    previewBorder: 'rgba(249, 172, 22, 0.5)',
	    bubbleBackground: 'rgba(249, 172, 22, 1)',
	    focusedBorderColor: 'rgba(249, 172, 22, 0.25)',
	    expandedBorderColor: 'rgba(249, 172, 22, 1)',
	    avatarImage: 'placeholder-avatar-orange.svg'
	  },
	  purple: {
	    name: 'purple',
	    pickerColor: 'rgba(162, 139, 255, 1)',
	    headBackground: 'rgba(162, 139, 255, 0.23)',
	    treeHeadBackground: 'rgba(162, 139, 255, 0.27)',
	    namePlaceholder: 'rgba(162, 139, 255, 0.23)',
	    headPlaceholder: 'rgba(162, 139, 255, 0.2)',
	    previewBorder: 'rgba(162, 139, 255, 0.5)',
	    bubbleBackground: 'rgba(162, 139, 255, 1)',
	    focusedBorderColor: 'rgba(162, 139, 255, 0.25)',
	    expandedBorderColor: 'rgba(162, 139, 255, 1)',
	    avatarImage: 'placeholder-avatar-purple.svg'
	  },
	  pink: {
	    name: 'pink',
	    pickerColor: 'rgba(242, 126, 189, 1)',
	    headBackground: 'rgba(242, 126, 189, 0.23)',
	    treeHeadBackground: 'rgba(242, 126, 189, 0.27)',
	    namePlaceholder: 'rgba(242, 126, 189, 0.23)',
	    headPlaceholder: 'rgba(242, 126, 189, 0.2)',
	    previewBorder: 'rgba(242, 126, 189, 0.5)',
	    bubbleBackground: 'rgba(242, 126, 189, 1)',
	    focusedBorderColor: 'rgba(242, 126, 189, 0.25)',
	    expandedBorderColor: 'rgba(242, 126, 189, 1)',
	    avatarImage: 'placeholder-avatar-pink.svg'
	  }
	});
	var getNodeColorSettings = function getNodeColorSettings(colorName, entityType) {
	  var _NodeColorsSettingsDi;
	  if (entityType !== EntityTypes.team) {
	    return null;
	  }
	  return (_NodeColorsSettingsDi = NodeColorsSettingsDict[colorName]) !== null && _NodeColorsSettingsDi !== void 0 ? _NodeColorsSettingsDi : NodeColorsSettingsDict.blue;
	};

	exports.getUserDataBySelectorItem = getUserDataBySelectorItem;
	exports.getInvitedUserData = getInvitedUserData;
	exports.getColorCode = getColorCode;
	exports.EntityTypes = EntityTypes;
	exports.NodeColorsSettingsDict = NodeColorsSettingsDict;
	exports.getNodeColorSettings = getNodeColorSettings;
	exports.NodeSettingsTypes = NodeSettingsTypes;
	exports.WizardApiEntityChangedDict = WizardApiEntityChangedDict;
	exports.ChatTypes = ChatTypes;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure));
//# sourceMappingURL=utils.bundle.js.map
