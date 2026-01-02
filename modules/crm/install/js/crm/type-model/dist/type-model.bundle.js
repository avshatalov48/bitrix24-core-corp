/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_model,main_core) {
	'use strict';

	/**
	 * @memberOf BX.Crm.Models
	 */
	class TypeModel extends crm_model.Model {
	  constructor(data, params) {
	    super(data, params);
	  }
	  getModelName() {
	    return 'type';
	  }
	  getData() {
	    const data = super.getData();
	    if (!main_core.Type.isObject(data.linkedUserFields)) {
	      data.linkedUserFields = false;
	    }
	    data.relations = this.getRelations();
	    data.customSections = this.getCustomSections();
	    return data;
	  }
	  getTitle() {
	    return this.data.title;
	  }
	  setTitle(title) {
	    this.data.title = title;
	  }
	  getCreatedBy() {
	    return this.data.createdBy;
	  }
	  getIsCategoriesEnabled() {
	    return this.data.isCategoriesEnabled;
	  }
	  setIsCategoriesEnabled(isCategoriesEnabled) {
	    this.data.isCategoriesEnabled = isCategoriesEnabled === true;
	  }
	  getIsStagesEnabled() {
	    return this.data.isStagesEnabled;
	  }
	  setIsStagesEnabled(isStagesEnabled) {
	    this.data.isStagesEnabled = isStagesEnabled === true;
	  }
	  getIsBeginCloseDatesEnabled() {
	    return this.data.isBeginCloseDatesEnabled;
	  }
	  setIsBeginCloseDatesEnabled(isBeginCloseDatesEnabled) {
	    this.data.isBeginCloseDatesEnabled = isBeginCloseDatesEnabled === true;
	  }
	  getIsClientEnabled() {
	    return this.data.isBeginCloseDatesEnabled;
	  }
	  setIsClientEnabled(isClientEnabled) {
	    this.data.isClientEnabled = isClientEnabled === true;
	  }
	  getIsLinkWithProductsEnabled() {
	    return this.data.isLinkWithProductsEnabled;
	  }
	  setIsLinkWithProductsEnabled(isLinkWithProductsEnabled) {
	    this.data.isLinkWithProductsEnabled = isLinkWithProductsEnabled === true;
	  }
	  getIsCrmTrackingEnabled() {
	    return this.data.isCrmTrackingEnabled;
	  }
	  setIsCrmTrackingEnabled(isCrmTrackingEnabled) {
	    this.data.isCrmTrackingEnabled = isCrmTrackingEnabled === true;
	  }
	  getIsMycompanyEnabled() {
	    return this.data.isMycompanyEnabled;
	  }
	  setIsMycompanyEnabled(isMycompanyEnabled) {
	    this.data.isMycompanyEnabled = isMycompanyEnabled === true;
	  }
	  getIsDocumentsEnabled() {
	    return this.data.isDocumentsEnabled;
	  }
	  setIsDocumentsEnabled(isDocumentsEnabled) {
	    this.data.isDocumentsEnabled = isDocumentsEnabled === true;
	  }
	  getIsSourceEnabled() {
	    return this.data.isSourceEnabled;
	  }
	  setIsSourceEnabled(isSourceEnabled) {
	    this.data.isSourceEnabled = isSourceEnabled === true;
	  }
	  getIsUseInUserfieldEnabled() {
	    return this.data.isUseInUserfieldEnabled;
	  }
	  setIsUseInUserfieldEnabled(isUseInUserfieldEnabled) {
	    this.data.isUseInUserfieldEnabled = isUseInUserfieldEnabled === true;
	  }
	  getIsObserversEnabled() {
	    return this.data.isObserversEnabled;
	  }
	  setIsObserversEnabled(isObserversEnabled) {
	    this.data.isObserversEnabled = isObserversEnabled === true;
	  }
	  getIsRecurringEnabled() {
	    return this.data.isRecurringEnabled;
	  }
	  setIsRecurringEnabled(isRecurringEnabled) {
	    this.data.isRecurringEnabled = isRecurringEnabled === true;
	  }
	  getIsRecyclebinEnabled() {
	    return this.data.isRecyclebinEnabled;
	  }
	  setIsRecyclebinEnabled(isRecyclebinEnabled) {
	    this.data.isRecyclebinEnabled = isRecyclebinEnabled === true;
	  }
	  getIsAutomationEnabled() {
	    return this.data.isAutomationEnabled;
	  }
	  setIsAutomationEnabled(isAutomationEnabled) {
	    this.data.isAutomationEnabled = isAutomationEnabled === true;
	  }
	  getIsBizProcEnabled() {
	    return this.data.isBizProcEnabled;
	  }
	  setIsBizProcEnabled(isBizProcEnabled) {
	    this.data.isBizProcEnabled = isBizProcEnabled === true;
	  }
	  getIsSetOpenPermissions() {
	    return this.data.isSetOpenPermissions;
	  }
	  setIsSetOpenPermissions(isSetOpenPermissions) {
	    this.data.isSetOpenPermissions = isSetOpenPermissions === true;
	  }
	  getLinkedUserFields() {
	    return this.data.linkedUserFields;
	  }
	  setLinkedUserFields(linkedUserFields) {
	    this.data.linkedUserFields = linkedUserFields;
	  }
	  getCustomSectionId() {
	    if (this.data.hasOwnProperty('customSectionId')) {
	      return main_core.Text.toInteger(this.data.customSectionId);
	    }
	    return null;
	  }
	  setCustomSectionId(customSectionId) {
	    this.data.customSectionId = customSectionId;
	  }
	  getCustomSections() {
	    const customSections = this.data.customSections;
	    if (main_core.Type.isArray(customSections) && customSections.length === 0) {
	      return false;
	    }
	    return customSections;
	  }
	  setCustomSections(customSections) {
	    this.data.customSections = customSections;
	  }
	  setIsExternalDynamicalType(isExternal) {
	    this.data.isExternal = isExternal;
	  }
	  setIsSaveFromTypeDetail(isSaveFromTypeDetail) {
	    this.data.isSaveFromTypeDetail = isSaveFromTypeDetail;
	  }
	  setConversionMap({
	    sourceTypes,
	    destinationTypes
	  }) {
	    if (!this.data.hasOwnProperty('conversionMap')) {
	      this.data.conversionMap = {};
	    }
	    this.data.conversionMap.sourceTypes = this.normalizeTypes(sourceTypes);
	    this.data.conversionMap.destinationTypes = this.normalizeTypes(destinationTypes);
	  }
	  getConversionMap() {
	    if (main_core.Type.isUndefined(this.data.conversionMap)) {
	      return undefined;
	    }
	    const conversionMap = Object.assign({}, this.data.conversionMap);
	    if (!conversionMap.sourceTypes) {
	      conversionMap.sourceTypes = [];
	    }
	    if (!conversionMap.destinationTypes) {
	      conversionMap.destinationTypes = [];
	    }
	    return conversionMap;
	  }
	  setRelations(relations) {
	    this.data.relations = relations;
	  }
	  getRelations() {
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
	  getIsCountersEnabled() {
	    return this.data.isCountersEnabled;
	  }
	  setIsCountersEnabled(isCountersEnabled) {
	    this.data.isCountersEnabled = isCountersEnabled;
	  }

	  /**
	   * @protected
	   * @param types
	   * @return {false|number[]}
	   */
	  normalizeTypes(types) {
	    if (!main_core.Type.isArrayFilled(types)) {
	      return false;
	    }
	    const arrayOfIntegers = types.map(element => {
	      return parseInt(element, 10);
	    });
	    return arrayOfIntegers.filter(element => {
	      return element > 0;
	    });
	  }
	  static getBooleanFieldNames() {
	    return ['isCategoriesEnabled', 'isStagesEnabled', 'isBeginCloseDatesEnabled', 'isClientEnabled', 'isLinkWithProductsEnabled', 'isCrmTrackingEnabled', 'isMycompanyEnabled', 'isDocumentsEnabled', 'isSourceEnabled', 'isUseInUserfieldEnabled', 'isObserversEnabled', 'isRecurringEnabled', 'isRecyclebinEnabled', 'isAutomationEnabled', 'isBizProcEnabled', 'isSetOpenPermissions', 'isCountersEnabled'];
	  }
	}

	exports.TypeModel = TypeModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX.Crm,BX));
//# sourceMappingURL=type-model.bundle.js.map
