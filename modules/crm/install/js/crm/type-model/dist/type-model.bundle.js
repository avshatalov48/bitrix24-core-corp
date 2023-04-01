this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,crm_model) {
	'use strict';

	/**
	 * @memberOf BX.Crm.Models
	 */
	var TypeModel = /*#__PURE__*/function (_Model) {
	  babelHelpers.inherits(TypeModel, _Model);
	  function TypeModel(data, params) {
	    babelHelpers.classCallCheck(this, TypeModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TypeModel).call(this, data, params));
	  }
	  babelHelpers.createClass(TypeModel, [{
	    key: "getModelName",
	    value: function getModelName() {
	      return 'type';
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      var data = babelHelpers.get(babelHelpers.getPrototypeOf(TypeModel.prototype), "getData", this).call(this);
	      if (!main_core.Type.isObject(data.linkedUserFields)) {
	        data.linkedUserFields = false;
	      }
	      data.relations = this.getRelations();
	      data.customSections = this.getCustomSections();
	      return data;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.data.title;
	    }
	  }, {
	    key: "setTitle",
	    value: function setTitle(title) {
	      this.data.title = title;
	    }
	  }, {
	    key: "getCreatedBy",
	    value: function getCreatedBy() {
	      return this.data.createdBy;
	    }
	  }, {
	    key: "getIsCategoriesEnabled",
	    value: function getIsCategoriesEnabled() {
	      return this.data.isCategoriesEnabled;
	    }
	  }, {
	    key: "setIsCategoriesEnabled",
	    value: function setIsCategoriesEnabled(isCategoriesEnabled) {
	      this.data.isCategoriesEnabled = isCategoriesEnabled === true;
	    }
	  }, {
	    key: "getIsStagesEnabled",
	    value: function getIsStagesEnabled() {
	      return this.data.isStagesEnabled;
	    }
	  }, {
	    key: "setIsStagesEnabled",
	    value: function setIsStagesEnabled(isStagesEnabled) {
	      this.data.isStagesEnabled = isStagesEnabled === true;
	    }
	  }, {
	    key: "getIsBeginCloseDatesEnabled",
	    value: function getIsBeginCloseDatesEnabled() {
	      return this.data.isBeginCloseDatesEnabled;
	    }
	  }, {
	    key: "setIsBeginCloseDatesEnabled",
	    value: function setIsBeginCloseDatesEnabled(isBeginCloseDatesEnabled) {
	      this.data.isBeginCloseDatesEnabled = isBeginCloseDatesEnabled === true;
	    }
	  }, {
	    key: "getIsClientEnabled",
	    value: function getIsClientEnabled() {
	      return this.data.isBeginCloseDatesEnabled;
	    }
	  }, {
	    key: "setIsClientEnabled",
	    value: function setIsClientEnabled(isClientEnabled) {
	      this.data.isClientEnabled = isClientEnabled === true;
	    }
	  }, {
	    key: "getIsLinkWithProductsEnabled",
	    value: function getIsLinkWithProductsEnabled() {
	      return this.data.isLinkWithProductsEnabled;
	    }
	  }, {
	    key: "setIsLinkWithProductsEnabled",
	    value: function setIsLinkWithProductsEnabled(isLinkWithProductsEnabled) {
	      this.data.isLinkWithProductsEnabled = isLinkWithProductsEnabled === true;
	    }
	  }, {
	    key: "getIsCrmTrackingEnabled",
	    value: function getIsCrmTrackingEnabled() {
	      return this.data.isCrmTrackingEnabled;
	    }
	  }, {
	    key: "setIsCrmTrackingEnabled",
	    value: function setIsCrmTrackingEnabled(isCrmTrackingEnabled) {
	      this.data.isCrmTrackingEnabled = isCrmTrackingEnabled === true;
	    }
	  }, {
	    key: "getIsMycompanyEnabled",
	    value: function getIsMycompanyEnabled() {
	      return this.data.isMycompanyEnabled;
	    }
	  }, {
	    key: "setIsMycompanyEnabled",
	    value: function setIsMycompanyEnabled(isMycompanyEnabled) {
	      this.data.isMycompanyEnabled = isMycompanyEnabled === true;
	    }
	  }, {
	    key: "getIsDocumentsEnabled",
	    value: function getIsDocumentsEnabled() {
	      return this.data.isDocumentsEnabled;
	    }
	  }, {
	    key: "setIsDocumentsEnabled",
	    value: function setIsDocumentsEnabled(isDocumentsEnabled) {
	      this.data.isDocumentsEnabled = isDocumentsEnabled === true;
	    }
	  }, {
	    key: "getIsSourceEnabled",
	    value: function getIsSourceEnabled() {
	      return this.data.isSourceEnabled;
	    }
	  }, {
	    key: "setIsSourceEnabled",
	    value: function setIsSourceEnabled(isSourceEnabled) {
	      this.data.isSourceEnabled = isSourceEnabled === true;
	    }
	  }, {
	    key: "getIsUseInUserfieldEnabled",
	    value: function getIsUseInUserfieldEnabled() {
	      return this.data.isUseInUserfieldEnabled;
	    }
	  }, {
	    key: "setIsUseInUserfieldEnabled",
	    value: function setIsUseInUserfieldEnabled(isUseInUserfieldEnabled) {
	      this.data.isUseInUserfieldEnabled = isUseInUserfieldEnabled === true;
	    }
	  }, {
	    key: "getIsObserversEnabled",
	    value: function getIsObserversEnabled() {
	      return this.data.isObserversEnabled;
	    }
	  }, {
	    key: "setIsObserversEnabled",
	    value: function setIsObserversEnabled(isObserversEnabled) {
	      this.data.isObserversEnabled = isObserversEnabled === true;
	    }
	  }, {
	    key: "getIsRecyclebinEnabled",
	    value: function getIsRecyclebinEnabled() {
	      return this.data.isRecyclebinEnabled;
	    }
	  }, {
	    key: "setIsRecyclebinEnabled",
	    value: function setIsRecyclebinEnabled(isRecyclebinEnabled) {
	      this.data.isRecyclebinEnabled = isRecyclebinEnabled === true;
	    }
	  }, {
	    key: "getIsAutomationEnabled",
	    value: function getIsAutomationEnabled() {
	      return this.data.isAutomationEnabled;
	    }
	  }, {
	    key: "setIsAutomationEnabled",
	    value: function setIsAutomationEnabled(isAutomationEnabled) {
	      this.data.isAutomationEnabled = isAutomationEnabled === true;
	    }
	  }, {
	    key: "getIsBizProcEnabled",
	    value: function getIsBizProcEnabled() {
	      return this.data.isBizProcEnabled;
	    }
	  }, {
	    key: "setIsBizProcEnabled",
	    value: function setIsBizProcEnabled(isBizProcEnabled) {
	      this.data.isBizProcEnabled = isBizProcEnabled === true;
	    }
	  }, {
	    key: "getIsSetOpenPermissions",
	    value: function getIsSetOpenPermissions() {
	      return this.data.isSetOpenPermissions;
	    }
	  }, {
	    key: "setIsSetOpenPermissions",
	    value: function setIsSetOpenPermissions(isSetOpenPermissions) {
	      this.data.isSetOpenPermissions = isSetOpenPermissions === true;
	    }
	  }, {
	    key: "getLinkedUserFields",
	    value: function getLinkedUserFields() {
	      return this.data.linkedUserFields;
	    }
	  }, {
	    key: "setLinkedUserFields",
	    value: function setLinkedUserFields(linkedUserFields) {
	      this.data.linkedUserFields = linkedUserFields;
	    }
	  }, {
	    key: "getCustomSectionId",
	    value: function getCustomSectionId() {
	      if (this.data.hasOwnProperty('customSectionId')) {
	        return main_core.Text.toInteger(this.data.customSectionId);
	      }
	      return null;
	    }
	  }, {
	    key: "setCustomSectionId",
	    value: function setCustomSectionId(customSectionId) {
	      this.data.customSectionId = customSectionId;
	    }
	  }, {
	    key: "getCustomSections",
	    value: function getCustomSections() {
	      var customSections = this.data.customSections;
	      if (main_core.Type.isArray(customSections) && customSections.length === 0) {
	        return false;
	      }
	      return customSections;
	    }
	  }, {
	    key: "setCustomSections",
	    value: function setCustomSections(customSections) {
	      this.data.customSections = customSections;
	    }
	  }, {
	    key: "setConversionMap",
	    value: function setConversionMap(_ref) {
	      var sourceTypes = _ref.sourceTypes,
	        destinationTypes = _ref.destinationTypes;
	      if (!this.data.hasOwnProperty('conversionMap')) {
	        this.data.conversionMap = {};
	      }
	      this.data.conversionMap.sourceTypes = this.normalizeTypes(sourceTypes);
	      this.data.conversionMap.destinationTypes = this.normalizeTypes(destinationTypes);
	    }
	  }, {
	    key: "getConversionMap",
	    value: function getConversionMap() {
	      if (main_core.Type.isUndefined(this.data.conversionMap)) {
	        return undefined;
	      }
	      var conversionMap = Object.assign({}, this.data.conversionMap);
	      if (!conversionMap.sourceTypes) {
	        conversionMap.sourceTypes = [];
	      }
	      if (!conversionMap.destinationTypes) {
	        conversionMap.destinationTypes = [];
	      }
	      return conversionMap;
	    }
	  }, {
	    key: "setRelations",
	    value: function setRelations(relations) {
	      this.data.relations = relations;
	    }
	  }, {
	    key: "getRelations",
	    value: function getRelations() {
	      if (!this.data.relations) {
	        return null;
	      }
	      if (!main_core.Type.isArray(this.data.relations.parent) || !this.data.relations.parent.length) {
	        this.data.relations.parent = false;
	      }
	      if (!main_core.Type.isArray(this.data.relations.child) || !this.data.relations.child.length) {
	        this.data.relations.child = false;
	      }
	      return this.data.relations;
	    }
	  }, {
	    key: "getIsCountersEnabled",
	    value: function getIsCountersEnabled() {
	      return this.data.isCountersEnabled;
	    }
	  }, {
	    key: "setIsCountersEnabled",
	    value: function setIsCountersEnabled(isCountersEnabled) {
	      this.data.isCountersEnabled = isCountersEnabled;
	    }
	    /**
	     * @protected
	     * @param types
	     * @return {false|number[]}
	     */
	  }, {
	    key: "normalizeTypes",
	    value: function normalizeTypes(types) {
	      if (!main_core.Type.isArrayFilled(types)) {
	        return false;
	      }
	      var arrayOfIntegers = types.map(function (element) {
	        return parseInt(element, 10);
	      });
	      return arrayOfIntegers.filter(function (element) {
	        return element > 0;
	      });
	    }
	  }], [{
	    key: "getBooleanFieldNames",
	    value: function getBooleanFieldNames() {
	      return ['isCategoriesEnabled', 'isStagesEnabled', 'isBeginCloseDatesEnabled', 'isClientEnabled', 'isLinkWithProductsEnabled', 'isCrmTrackingEnabled', 'isMycompanyEnabled', 'isDocumentsEnabled', 'isSourceEnabled', 'isUseInUserfieldEnabled', 'isObserversEnabled', 'isRecyclebinEnabled', 'isAutomationEnabled', 'isBizProcEnabled', 'isSetOpenPermissions', 'isCountersEnabled'];
	    }
	  }]);
	  return TypeModel;
	}(crm_model.Model);

	exports.TypeModel = TypeModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX,BX.Crm));
//# sourceMappingURL=type-model.bundle.js.map
