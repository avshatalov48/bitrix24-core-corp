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
	  }
	});
	var getColorCode = function getColorCode(colorKey) {
	  var colorOption = optionColor[colorKey];
	  if (colorOption) {
	    return getComputedStyle(document.body).getPropertyValue(colorOption.tokenClass) || colorOption.color;
	  }
	  return null;
	};

	exports.getUserDataBySelectorItem = getUserDataBySelectorItem;
	exports.getInvitedUserData = getInvitedUserData;
	exports.getColorCode = getColorCode;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {}),BX.Humanresources.CompanyStructure));
//# sourceMappingURL=utils.bundle.js.map
