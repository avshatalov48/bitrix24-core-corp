this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core) {
	'use strict';

	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	class ItemIdentifier {
	  constructor(entityTypeId, entityId) {
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    // noinspection AssignmentToFunctionParameterJS
	    entityTypeId = main_core.Text.toInteger(entityTypeId);
	    // noinspection AssignmentToFunctionParameterJS
	    entityId = main_core.Text.toInteger(entityId);
	    if (!BX.CrmEntityType.isDefined(entityTypeId)) {
	      throw new Error('entityTypeId is not a valid crm entity type');
	    }
	    if (entityId <= 0) {
	      throw new Error('entityId must be greater than 0');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = entityId;
	  }
	  static fromJSON(data) {
	    try {
	      return new ItemIdentifier(main_core.Text.toInteger(data == null ? void 0 : data.entityTypeId), main_core.Text.toInteger(data == null ? void 0 : data.entityId));
	    } catch (e) {
	      return null;
	    }
	  }
	  get entityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId];
	  }
	  get entityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	  }
	  get hash() {
	    return `type_${this.entityTypeId}_id_${this.entityId}`;
	  }
	  isEqualTo(another) {
	    if (!(another instanceof ItemIdentifier)) {
	      return false;
	    }
	    return this.hash === another.hash;
	  }
	}

	exports.ItemIdentifier = ItemIdentifier;

}((this.BX.Crm.DataStructures = this.BX.Crm.DataStructures || {}),BX));
//# sourceMappingURL=data-structures.bundle.js.map
