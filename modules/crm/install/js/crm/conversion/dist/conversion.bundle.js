/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_integration_analytics,ui_analytics,ui_dialogs_messagebox,ui_forms,crm_categoryModel,ui_buttons,ui_entitySelector,main_core,main_core_events,main_popup) {
	'use strict';

	var _active = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("active");
	var _enableSync = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableSync");
	var _initData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initData");
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _internalizeBooleanValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("internalizeBooleanValue");
	/**
	 * @memberOf BX.Crm.Conversion
	 */
	class ConfigItem {
	  constructor(params) {
	    Object.defineProperty(this, _internalizeBooleanValue, {
	      value: _internalizeBooleanValue2
	    });
	    Object.defineProperty(this, _active, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _enableSync, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initData, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = Number(params.entityTypeId);
	    babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = babelHelpers.classPrivateFieldLooseBase(this, _internalizeBooleanValue)[_internalizeBooleanValue](params.active);
	    babelHelpers.classPrivateFieldLooseBase(this, _enableSync)[_enableSync] = babelHelpers.classPrivateFieldLooseBase(this, _internalizeBooleanValue)[_internalizeBooleanValue](params.enableSync);
	    if (main_core.Type.isPlainObject(params.initData)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initData)[_initData] = params.initData;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = String(params.title);
	  }
	  externalize() {
	    return {
	      entityTypeId: this.getEntityTypeId(),
	      title: this.getTitle(),
	      initData: this.getInitData(),
	      active: this.isActive() ? 'Y' : 'N',
	      enableSync: this.isEnableSync() ? 'Y' : 'N'
	    };
	  }
	  isActive() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _active)[_active];
	  }
	  setActive(active) {
	    babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = active;
	    return this;
	  }
	  isEnableSync() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _enableSync)[_enableSync];
	  }
	  setEnableSync(enableSync) {
	    babelHelpers.classPrivateFieldLooseBase(this, _enableSync)[_enableSync] = enableSync;
	    return this;
	  }
	  getInitData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _initData)[_initData] || {};
	  }
	  setInitData(data) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initData)[_initData] = data;
	    return this;
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId];
	  }
	  getTitle() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _title)[_title];
	  }
	  setTitle(title) {
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = title;
	    return this;
	  }
	}
	function _internalizeBooleanValue2(value) {
	  if (main_core.Type.isBoolean(value)) {
	    return value;
	  }
	  if (main_core.Type.isString(value)) {
	    return value === 'Y';
	  }
	  return Boolean(value);
	}

	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _name = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("name");
	var _entityTypeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeIds");
	var _phrase = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("phrase");
	var _availabilityLock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("availabilityLock");
	/**
	 * @memberOf BX.Crm.Conversion
	 */
	class SchemeItem {
	  constructor(params) {
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _name, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityTypeIds, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _phrase, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _availabilityLock, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = String(params.id);
	    babelHelpers.classPrivateFieldLooseBase(this, _name)[_name] = String(params.name);
	    babelHelpers.classPrivateFieldLooseBase(this, _phrase)[_phrase] = String(params.phrase);
	    babelHelpers.classPrivateFieldLooseBase(this, _availabilityLock)[_availabilityLock] = String(params.availabilityLock);
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeIds)[_entityTypeIds] = [];
	    if (main_core.Type.isArray(params.entityTypeIds)) {
	      params.entityTypeIds.forEach(entityTypeId => {
	        babelHelpers.classPrivateFieldLooseBase(this, _entityTypeIds)[_entityTypeIds].push(Number(entityTypeId));
	      });
	    }
	  }
	  getId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _id)[_id];
	  }
	  getName() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _name)[_name];
	  }
	  getEntityTypeIds() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeIds)[_entityTypeIds];
	  }
	  getPhrase() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _phrase)[_phrase];
	  }
	  getAvailabilityLock() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _availabilityLock)[_availabilityLock];
	  }
	}

	var _currentItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentItemId");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	/**
	 * @memberOf BX.Crm.Conversion
	 */
	class Scheme {
	  constructor(currentItemId, items) {
	    Object.defineProperty(this, _currentItemId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _currentItemId)[_currentItemId] = main_core.Type.isNull(currentItemId) ? currentItemId : String(currentItemId);
	    if (main_core.Type.isArray(items)) {
	      items.forEach(item => {
	        if (item instanceof SchemeItem) {
	          babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].push(item);
	        } else {
	          console.error(
	          // eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	          `SchemeItem is invalid in Scheme constructor. Expected instance of SchemeItem, got ${typeof item}`);
	        }
	      });
	    }
	  }
	  static create(params) {
	    const schemeItems = [];
	    params.items.forEach(item => {
	      schemeItems.push(new SchemeItem(item));
	    });
	    return new Scheme(params.currentItemId, schemeItems);
	  }
	  getCurrentItem() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] || babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].length === 0) {
	      return null;
	    }
	    const item = this.getItemById(babelHelpers.classPrivateFieldLooseBase(this, _currentItemId)[_currentItemId]);
	    return item || babelHelpers.classPrivateFieldLooseBase(this, _items)[_items][0];
	  }
	  setCurrentItemId(currentItemId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentItemId)[_currentItemId] = currentItemId;
	  }
	  getItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items)[_items];
	  }
	  getItemById(itemId) {
	    for (const item of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	      if (item.getId() === itemId) {
	        return item;
	      }
	    }
	    return null;
	  }
	  getItemForSingleEntityTypeId(entityTypeId) {
	    for (const item of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	      const entityTypeIds = item.getEntityTypeIds();
	      if (entityTypeIds.length === 1 && [...entityTypeIds][0] === entityTypeId) {
	        return item;
	      }
	    }
	    return null;
	  }
	  getItemForEntityTypeIds(entityTypeIds) {
	    const makeIntSet = input => {
	      // Set - to remove possible duplicates in the array
	      return new Set(input.map(id => main_core.Text.toInteger(id)));
	    };
	    const targetEntityTypeIds = [...makeIntSet(entityTypeIds)];
	    for (const item of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	      const itemSet = makeIntSet(item.getEntityTypeIds());
	      if (targetEntityTypeIds.length !== itemSet.size) {
	        continue;
	      }
	      const notFoundTargetIds = targetEntityTypeIds.filter(entityTypeId => !itemSet.has(entityTypeId));
	      if (notFoundTargetIds.length <= 0) {
	        return item;
	      }
	    }
	    return null;
	  }
	  getAllEntityTypeIds() {
	    const entityTypeIds = new Set();
	    for (const item of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	      for (const entityTypeId of item.getEntityTypeIds()) {
	        entityTypeIds.add(entityTypeId);
	      }
	    }
	    return [...entityTypeIds];
	  }
	}

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var _entityTypeId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _items$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _scheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scheme");
	class Config {
	  constructor(entityTypeId, items, scheme) {
	    Object.defineProperty(this, _entityTypeId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _items$1, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _scheme, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1] = Number(entityTypeId);
	    if (main_core.Type.isArray(items)) {
	      items.forEach(item => {
	        if (item instanceof ConfigItem) {
	          babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1].push(item);
	        } else {
	          console.error(
	          // eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	          `ConfigItem is invalid in Config constructor. Expected instance of ConfigItem, got ${typeof item}`);
	        }
	      });
	    }
	    if (scheme instanceof Scheme) {
	      babelHelpers.classPrivateFieldLooseBase(this, _scheme)[_scheme] = scheme;
	    } else {
	      // eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	      console.error(`Scheme is invalid in Config constructor. Expected instance of Scheme, got ${typeof scheme}`);
	    }
	  }
	  static create(entityTypeId, items, scheme) {
	    const configItems = [];
	    items.forEach(item => {
	      configItems.push(new ConfigItem(item));
	    });
	    return new Config(entityTypeId, configItems, scheme);
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$1)[_entityTypeId$1];
	  }
	  getItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1];
	  }
	  getActiveItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1].filter(item => item.isActive());
	  }
	  getScheme() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _scheme)[_scheme];
	  }
	  updateFromSchemeItem(schemeItem = null) {
	    let selectedSchemeItem = null;
	    if (schemeItem) {
	      selectedSchemeItem = schemeItem;
	      this.getScheme().setCurrentItemId(schemeItem.getId());
	    } else {
	      selectedSchemeItem = this.getScheme().getCurrentItem();
	    }
	    const activeEntityTypeIds = selectedSchemeItem.getEntityTypeIds();
	    babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1].forEach(item => {
	      const isActive = activeEntityTypeIds.includes(item.getEntityTypeId());
	      item.setEnableSync(isActive);
	      item.setActive(isActive);
	    });
	    return this;
	  }
	  getItemByEntityTypeId(entityTypeId) {
	    for (const item of babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1]) {
	      if (item.getEntityTypeId() === entityTypeId) {
	        return item;
	      }
	    }
	    return null;
	  }
	  externalize() {
	    const data = {};
	    this.getItems().forEach(item => {
	      data[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.externalize();
	    });
	    return data;
	  }
	  updateItems(items) {
	    babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1] = [];
	    items.forEach(item => {
	      babelHelpers.classPrivateFieldLooseBase(this, _items$1)[_items$1].push(new ConfigItem(item));
	    });
	    return this;
	  }
	}

	let instance = null;
	var _extensionSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensionSettings");
	var _storage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("storage");
	class CategoryRepository {
	  constructor() {
	    Object.defineProperty(this, _extensionSettings, {
	      writable: true,
	      value: main_core.Extension.getSettings('crm.conversion')
	    });
	    Object.defineProperty(this, _storage, {
	      writable: true,
	      value: new Map()
	    });
	  }
	  static get Instance() {
	    if (instance === null) {
	      instance = new CategoryRepository();
	    }
	    return instance;
	  }
	  isCategoriesEnabled(entityTypeId) {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _extensionSettings)[_extensionSettings].get(`isCategoriesEnabled.${entityTypeId}`, false));
	  }
	  getCategories(entityTypeId) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].has(entityTypeId)) {
	      return Promise.resolve(babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].get(entityTypeId));
	    }
	    return main_core.ajax.runAction('crm.conversion.getDstCategoryList', {
	      data: {
	        entityTypeId
	      }
	    }).then(({
	      data
	    }) => {
	      var _data$categories;
	      const models = [];
	      data == null ? void 0 : (_data$categories = data.categories) == null ? void 0 : _data$categories.forEach(categoryData => {
	        models.push(new crm_categoryModel.CategoryModel(categoryData));
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage].set(entityTypeId, models);
	      return models;
	    });
	  }
	}

	let _ = t => t,
	  _t,
	  _t2;
	// eslint-disable-next-line unicorn/numeric-separators-style
	const REQUIRED_FIELDS_INFOHELPER_CODE = 8233923;

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var _entityTypeId$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _config = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("config");
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _isProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isProgress");
	var _isSynchronisationAllowed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSynchronisationAllowed");
	var _fieldsSynchronizer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fieldsSynchronizer");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _request = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("request");
	var _sendAnalyticsData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsData");
	var _filterExternalAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterExternalAnalytics");
	var _onRequestSuccess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onRequestSuccess");
	var _collectAdditionalData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("collectAdditionalData");
	var _getCategoryForEntityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCategoryForEntityTypeId");
	var _isNeedToLoadCategories = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isNeedToLoadCategories");
	var _showCategorySelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCategorySelector");
	var _processRequiredAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processRequiredAction");
	var _synchronizeFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("synchronizeFields");
	var _getFieldsSynchronizer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsSynchronizer");
	var _askToFillRequiredFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("askToFillRequiredFields");
	var _getMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMessage");
	var _emitConvertedEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitConvertedEvent");
	class Converter {
	  constructor(_entityTypeId2, _config2, params) {
	    Object.defineProperty(this, _emitConvertedEvent, {
	      value: _emitConvertedEvent2
	    });
	    Object.defineProperty(this, _getMessage, {
	      value: _getMessage2
	    });
	    Object.defineProperty(this, _askToFillRequiredFields, {
	      value: _askToFillRequiredFields2
	    });
	    Object.defineProperty(this, _getFieldsSynchronizer, {
	      value: _getFieldsSynchronizer2
	    });
	    Object.defineProperty(this, _synchronizeFields, {
	      value: _synchronizeFields2
	    });
	    Object.defineProperty(this, _processRequiredAction, {
	      value: _processRequiredAction2
	    });
	    Object.defineProperty(this, _showCategorySelector, {
	      value: _showCategorySelector2
	    });
	    Object.defineProperty(this, _isNeedToLoadCategories, {
	      value: _isNeedToLoadCategories2
	    });
	    Object.defineProperty(this, _getCategoryForEntityTypeId, {
	      value: _getCategoryForEntityTypeId2
	    });
	    Object.defineProperty(this, _collectAdditionalData, {
	      value: _collectAdditionalData2
	    });
	    Object.defineProperty(this, _onRequestSuccess, {
	      value: _onRequestSuccess2
	    });
	    Object.defineProperty(this, _filterExternalAnalytics, {
	      value: _filterExternalAnalytics2
	    });
	    Object.defineProperty(this, _sendAnalyticsData, {
	      value: _sendAnalyticsData2
	    });
	    Object.defineProperty(this, _request, {
	      value: _request2
	    });
	    Object.defineProperty(this, _entityTypeId$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isProgress, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isSynchronisationAllowed, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fieldsSynchronizer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2] = Number(_entityTypeId2);
	    if (_config2 instanceof Config) {
	      babelHelpers.classPrivateFieldLooseBase(this, _config)[_config] = _config2;
	    } else {
	      console.error('Config is invalid in Converter constructor. Expected instance of Config', _config2, this);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params != null ? params : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id) ? babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id : main_core.Text.getRandom();
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics = babelHelpers.classPrivateFieldLooseBase(this, _filterExternalAnalytics)[_filterExternalAnalytics](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics);
	    babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _isSynchronisationAllowed)[_isSynchronisationAllowed] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = 0;
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2];
	  }
	  getConfig() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _config)[_config];
	  }
	  getId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id;
	  }
	  getServiceUrl() {
	    const serviceUrl = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].serviceUrl;
	    if (!serviceUrl) {
	      return null;
	    }
	    const additionalParams = {
	      action: 'convert'
	    };
	    this.getConfig().getItems().forEach(item => {
	      additionalParams[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.isActive() ? 'Y' : 'N';
	    });
	    return BX.util.add_url_param(serviceUrl, additionalParams);
	  }
	  getOriginUrl() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] && 'originUrl' in babelHelpers.classPrivateFieldLooseBase(this, _params)[_params]) {
	      return String(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].originUrl);
	    }
	    return null;
	  }
	  isRedirectToDetailPageEnabled() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] && 'isRedirectToDetailPageEnabled' in babelHelpers.classPrivateFieldLooseBase(this, _params)[_params]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isRedirectToDetailPageEnabled;
	    }
	    return true;
	  }

	  /**
	   * Overwrite current analytics[c_element] param.
	   * Note that you are not allowed to change analytics[c_sub_section] - its by design.
	   *
	   * @param c_element
	   * @returns {BX.Crm.Conversion.Converter}
	   */
	  // eslint-disable-next-line camelcase
	  setAnalyticsElement(c_element) {
	    // eslint-disable-next-line camelcase
	    const filtered = babelHelpers.classPrivateFieldLooseBase(this, _filterExternalAnalytics)[_filterExternalAnalytics]({
	      c_element
	    });
	    if ('c_element' in filtered) {
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics.c_element = filtered.c_element;
	    }
	    return this;
	  }
	  convertBySchemeItemId(schemeItemId, entityId, data) {
	    const targetSchemeItem = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].getScheme().getItemById(schemeItemId);
	    if (!targetSchemeItem) {
	      console.error('Scheme is not found', schemeItemId, this);
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].updateFromSchemeItem(targetSchemeItem);
	    this.convert(entityId, data);
	  }
	  convert(entityId, data) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	    const schemeItem = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].getScheme().getCurrentItem();
	    if (!schemeItem) {
	      console.error('Scheme is not found', this);
	      return;
	    }
	    if (main_core.Type.isStringFilled(schemeItem.getAvailabilityLock())) {
	      // eslint-disable-next-line no-eval
	      eval(schemeItem.getAvailabilityLock());
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].getActiveItems().forEach(item => {
	      babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData](item.getEntityTypeId(), crm_integration_analytics.Dictionary.STATUS_ATTEMPT);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _collectAdditionalData)[_collectAdditionalData](schemeItem).then(result => {
	      if (result.isCanceled) {
	        // pass it to next 'then' handler
	        return result;
	      }
	      return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]();
	    }).then(result => {
	      if (!result.isFinished) {
	        // dont need to register anything in statistics

	        return;
	      }
	      const status = result.isCanceled ? crm_integration_analytics.Dictionary.STATUS_CANCEL : crm_integration_analytics.Dictionary.STATUS_SUCCESS;
	      babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].getActiveItems().forEach(item => {
	        babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData](item.getEntityTypeId(), status);
	      });
	    }).catch(error => {
	      if (error) {
	        // eslint-disable-next-line no-console
	        console.log('Convert error', error, this);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].getActiveItems().forEach(item => {
	        babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsData)[_sendAnalyticsData](item.getEntityTypeId(), crm_integration_analytics.Dictionary.STATUS_ERROR);
	      });
	    });
	  }
	  /**
	   * @deprecated Will be removed soon
	   * @todo delete, replace with messages from config.php
	   */
	  getMessagePublic(phraseId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage](phraseId);
	  }
	}
	function _request2() {
	  const promise = new Promise((resolve, reject) => {
	    const serviceUrl = this.getServiceUrl();
	    if (!serviceUrl) {
	      console.error('Convert endpoint is not specifier');
	      reject();
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress]) {
	      console.error('Another request is in progress');
	      reject();
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = true;
	    main_core.ajax({
	      url: serviceUrl,
	      method: 'POST',
	      dataType: 'json',
	      data: {
	        MODE: 'CONVERT',
	        ENTITY_ID: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	        ENABLE_SYNCHRONIZATION: babelHelpers.classPrivateFieldLooseBase(this, _isSynchronisationAllowed)[_isSynchronisationAllowed] ? 'Y' : 'N',
	        ENABLE_REDIRECT_TO_SHOW: this.isRedirectToDetailPageEnabled() ? 'Y' : 'N',
	        CONFIG: this.getConfig().externalize(),
	        CONTEXT: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data],
	        ORIGIN_URL: this.getOriginUrl()
	      },
	      onsuccess: resolve,
	      onfailure: reject
	    });
	  });
	  return promise.then(response => {
	    babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = false;
	    return babelHelpers.classPrivateFieldLooseBase(this, _onRequestSuccess)[_onRequestSuccess](response);
	  }).catch(error => {
	    babelHelpers.classPrivateFieldLooseBase(this, _isProgress)[_isProgress] = false;
	    if (main_core.Type.isStringFilled(error)) {
	      // response may contain info about action required from user
	      ui_dialogs_messagebox.MessageBox.alert(main_core.Text.encode(error));
	    }

	    // pass error to next 'catch'
	    throw error;
	  });
	}
	function _sendAnalyticsData2(dstEntityTypeId, status) {
	  const builder = crm_integration_analytics.Builder.Entity.ConvertEvent.createDefault(babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2], dstEntityTypeId).setSection(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics.c_section).setSubSection(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics.c_sub_section).setElement(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics.c_element).setStatus(status);
	  ui_analytics.sendData(builder.buildData());
	}
	function _filterExternalAnalytics2(analytics) {
	  if (!main_core.Type.isPlainObject(analytics)) {
	    return {};
	  }
	  const allowedKeys = new Set(['c_section', 'c_sub_section', 'c_element']);
	  const result = {};
	  for (const [key, value] of Object.entries(analytics)) {
	    if (allowedKeys.has(key) && main_core.Type.isStringFilled(value)) {
	      result[key] = value;
	    }
	  }
	  return result;
	}
	function _onRequestSuccess2(response) {
	  return new Promise((resolve, reject) => {
	    if (response.ERROR) {
	      var _response$ERROR;
	      reject(((_response$ERROR = response.ERROR) == null ? void 0 : _response$ERROR.MESSAGE) || response.ERROR || 'Error during conversion');
	      return;
	    }
	    if (main_core.Type.isPlainObject(response.REQUIRED_ACTION)) {
	      resolve(babelHelpers.classPrivateFieldLooseBase(this, _processRequiredAction)[_processRequiredAction](response.REQUIRED_ACTION));
	      return;
	    }
	    const data = main_core.Type.isPlainObject(response.DATA) ? response.DATA : {};
	    if (!data) {
	      reject();
	      return;
	    }
	    const resolveResult = {
	      isCanceled: false,
	      isFinished: true
	    };
	    const redirectUrl = main_core.Type.isString(data.URL) ? data.URL : '';
	    if (data.IS_FINISHED === 'Y') {
	      // result entity was created on backend, conversion is finished
	      babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = {};
	      const wasRedirectedInExternalEventHandler = babelHelpers.classPrivateFieldLooseBase(this, _emitConvertedEvent)[_emitConvertedEvent](redirectUrl);
	      if (wasRedirectedInExternalEventHandler) {
	        resolve(resolveResult);
	        return;
	      }
	    } else {
	      // backend could not create result entity automatically, user interaction is required
	      resolveResult.isFinished = false;
	    }
	    if (redirectUrl) {
	      const redirectUrlObject = new main_core.Uri(redirectUrl);
	      const currentRedirectUrlAnalytics = redirectUrlObject.getQueryParam('st') || {};
	      redirectUrlObject.setQueryParam('st', {
	        ...babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics,
	        ...currentRedirectUrlAnalytics
	      });
	      BX.Crm.Page.open(redirectUrlObject.toString());
	    } else if (window.top !== window) ;
	    resolve(resolveResult);
	  });
	}
	function _collectAdditionalData2(schemeItem) {
	  const config = this.getConfig();
	  const promises = [];
	  schemeItem.getEntityTypeIds().forEach(entityTypeId => {
	    promises.push(() => {
	      return babelHelpers.classPrivateFieldLooseBase(this, _getCategoryForEntityTypeId)[_getCategoryForEntityTypeId](entityTypeId);
	    });
	  });
	  const result = {
	    isCanceled: false,
	    isFinished: true
	  };
	  const promiseIterator = (receivedPromises, index = 0) => {
	    return new Promise((resolve, reject) => {
	      if (result.isCanceled || !receivedPromises[index]) {
	        resolve(result);
	        return;
	      }
	      receivedPromises[index]().then(categoryResult => {
	        if (categoryResult.isCanceled) {
	          result.isCanceled = true;
	        } else if (categoryResult.category) {
	          const entityTypeId = categoryResult.category.getEntityTypeId();
	          const configItem = config.getItemByEntityTypeId(entityTypeId);
	          if (!configItem) {
	            console.error(`Scheme is not correct: configItem is not found for ${entityTypeId}`, this);
	            reject();
	            return;
	          }
	          const initData = configItem.getInitData();
	          initData.categoryId = categoryResult.category.getId();
	          configItem.setInitData(initData);
	        }
	        resolve(promiseIterator(receivedPromises, index + 1));
	      }).catch(reject);
	    });
	  };
	  return promiseIterator(promises);
	}
	function _getCategoryForEntityTypeId2(entityTypeId) {
	  return new Promise((resolve, reject) => {
	    const configItem = this.getConfig().getItemByEntityTypeId(entityTypeId);
	    if (!configItem) {
	      console.error(`Scheme is not correct: configItem is not found for ${entityTypeId}`, this);
	      reject();
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isNeedToLoadCategories)[_isNeedToLoadCategories](entityTypeId)) {
	      CategoryRepository.Instance.getCategories(entityTypeId).then(categories => {
	        if (categories.length > 1) {
	          resolve(babelHelpers.classPrivateFieldLooseBase(this, _showCategorySelector)[_showCategorySelector](categories, configItem.getTitle()));
	        } else {
	          resolve({
	            isCanceled: false,
	            category: categories[0]
	          });
	        }
	      }).catch(reject);
	    } else {
	      resolve({
	        isCanceled: false,
	        category: null
	      });
	    }
	  });
	}
	function _isNeedToLoadCategories2(entityTypeId) {
	  return CategoryRepository.Instance.isCategoriesEnabled(entityTypeId);
	}
	function _showCategorySelector2(categories, title) {
	  return new Promise(resolve => {
	    const categorySelectorContent = main_core.Tag.render(_t || (_t = _`
				<div class="crm-converter-category-selector ui-form ui-form-line">
					<div class="ui-form-row">
						<div class="crm-converter-category-selector-label ui-form-label">
							<div class="ui-ctl-label-text">${0}</div>
						</div>
						<div class="ui-form-content">
							<div class="crm-converter-category-selector-select ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element"></select>
							</div>
						</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('CRM_COMMON_CATEGORY'));
	    const select = categorySelectorContent.querySelector('select');
	    categories.forEach(category => {
	      main_core.Dom.append(main_core.Tag.render(_t2 || (_t2 = _`<option value="${0}">${0}</option>`), category.getId(), main_core.Text.encode(category.getName())), select);
	    });
	    const popup = new main_popup.Popup({
	      titleBar: main_core.Loc.getMessage('CRM_CONVERSION_CATEGORY_SELECTOR_TITLE', {
	        '#ENTITY#': main_core.Text.encode(title)
	      }),
	      content: categorySelectorContent,
	      closeByEsc: true,
	      closeIcon: true,
	      buttons: [new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_COMMON_ACTION_SAVE'),
	        color: ui_buttons.ButtonColor.SUCCESS,
	        onclick: () => {
	          const value = [...select.selectedOptions][0].value;
	          popup.destroy();
	          for (const category of categories) {
	            if (category.getId() === Number(value)) {
	              resolve({
	                category
	              });
	              return true;
	            }
	          }
	          console.error('Selected category not found', value, categories);
	          resolve({
	            isCanceled: true
	          });
	          return true;
	        }
	      }), new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_COMMON_ACTION_CANCEL'),
	        color: ui_buttons.ButtonColor.LIGHT,
	        onclick: () => {
	          popup.destroy();
	          resolve({
	            isCanceled: true
	          });
	          return true;
	        }
	      })],
	      events: {
	        onClose: () => {
	          resolve({
	            isCanceled: true
	          });
	        }
	      }
	    });
	    popup.show();
	  });
	}
	function _processRequiredAction2(action) {
	  const name = String(action.NAME);
	  const data = main_core.Type.isPlainObject(action.DATA) ? action.DATA : {};
	  if (name === 'SYNCHRONIZE') {
	    let newConfig = null;
	    if (main_core.Type.isArray(data.CONFIG)) {
	      newConfig = data.CONFIG;
	    } else if (main_core.Type.isPlainObject(data.CONFIG)) {
	      newConfig = Object.values(data.CONFIG);
	    }
	    if (newConfig) {
	      babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].updateItems(newConfig);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _synchronizeFields)[_synchronizeFields](main_core.Type.isArray(data.FIELD_NAMES) ? data.FIELD_NAMES : []);
	  }
	  if (name === 'CORRECT' && main_core.Type.isPlainObject(data.CHECK_ERRORS)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _askToFillRequiredFields)[_askToFillRequiredFields](data);
	  }
	  return Promise.resolve({
	    isCanceled: false,
	    isFinished: true
	  });
	}
	function _synchronizeFields2(fieldNames) {
	  const synchronizer = babelHelpers.classPrivateFieldLooseBase(this, _getFieldsSynchronizer)[_getFieldsSynchronizer](fieldNames);
	  return new Promise(resolve => {
	    const listener = (sender, args) => {
	      const isConversionCancelled = main_core.Type.isBoolean(args.isCanceled) && args.isCanceled === true;
	      if (isConversionCancelled) {
	        synchronizer.removeClosingListener(listener);
	        resolve({
	          isCanceled: true,
	          isFinished: true
	        });
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _isSynchronisationAllowed)[_isSynchronisationAllowed] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].updateItems(Object.values(babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer].getConfig()));
	      synchronizer.removeClosingListener(listener);
	      resolve(babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]());
	    };
	    synchronizer.addClosingListener(listener);
	    synchronizer.show();
	  });
	}
	function _getFieldsSynchronizer2(fieldNames) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer] = BX.CrmEntityFieldSynchronizationEditor.create(`crm_converter_fields_synchronizer_${this.getEntityTypeId()}`, {
	      config: babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].externalize(),
	      title: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('dialogTitle'),
	      fieldNames,
	      legend: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('syncEditorLegend'),
	      fieldListTitle: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('syncEditorFieldListTitle'),
	      entityListTitle: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('syncEditorEntityListTitle'),
	      continueButton: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('continueButton'),
	      cancelButton: babelHelpers.classPrivateFieldLooseBase(this, _getMessage)[_getMessage]('cancelButton')
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer].setConfig(babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].externalize());
	  babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer].setFieldNames(fieldNames);
	  return babelHelpers.classPrivateFieldLooseBase(this, _fieldsSynchronizer)[_fieldsSynchronizer];
	}
	function _askToFillRequiredFields2(data) {
	  // just in case that there is previous not yet closed editor
	  BX.Crm.PartialEditorDialog.close('entity-converter-editor');
	  const entityEditor = BX.Crm.PartialEditorDialog.create('entity-converter-editor', {
	    title: main_core.Loc.getMessage('CRM_CONVERSION_REQUIRED_FIELDS_POPUP_TITLE'),
	    entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2],
	    entityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	    fieldNames: Object.keys(data.CHECK_ERRORS),
	    helpData: {
	      text: main_core.Loc.getMessage('CRM_CONVERSION_MORE_ABOUT_REQUIRED_FIELDS'),
	      code: REQUIRED_FIELDS_INFOHELPER_CODE
	    },
	    context: data.CONTEXT
	  });
	  return new Promise(resolve => {
	    const handler = (sender, eventParams) => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId$2)[_entityTypeId$2] !== (eventParams == null ? void 0 : eventParams.entityTypeId) || babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] !== (eventParams == null ? void 0 : eventParams.entityId)) {
	        return;
	      }

	      // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	      BX.removeCustomEvent(window, 'Crm.PartialEditorDialog.Close', handler);

	      // yes, 'canceled' with double 'l' in this case
	      const isCanceled = main_core.Type.isBoolean(eventParams.isCancelled) ? eventParams.isCancelled : true;
	      if (isCanceled) {
	        resolve({
	          isCanceled: true,
	          isFinished: true
	        });
	      } else {
	        resolve(babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]());
	      }
	    };

	    // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	    BX.addCustomEvent(window, 'Crm.PartialEditorDialog.Close', handler);
	    entityEditor.open();
	  });
	}
	function _getMessage2(phraseId) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].messages) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].messages = {};
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].messages[phraseId] || phraseId;
	}
	function _emitConvertedEvent2(redirectUrl) {
	  const entityTypeId = this.getEntityTypeId();
	  const eventArgs = {
	    entityTypeId,
	    entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
	    entityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	    redirectUrl,
	    isRedirected: false
	  };
	  const current = BX.Crm.Page.getTopSlider();
	  if (current) {
	    eventArgs.sliderUrl = current.getUrl();
	  }

	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	  BX.onCustomEvent(window, 'Crm.EntityConverter.Converted', [this, eventArgs]);
	  BX.localStorage.set('onCrmEntityConvert', eventArgs, 10);
	  this.getConfig().getActiveItems().forEach(item => {
	    main_core_events.EventEmitter.emit('Crm.EntityConverter.SingleConverted', {
	      entityTypeName: BX.CrmEntityType.resolveName(item.getEntityTypeId())
	    });
	  });
	  return eventArgs.isRedirected;
	}

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var _converter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("converter");
	var _entityId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _dstEntityTypeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dstEntityTypeIds");
	var _target = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("target");
	var _dialogProp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialogProp");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _convert = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convert");
	var _handleItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemSelect");
	var _ensureOnlyOneItemOfEachTypeIsSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ensureOnlyOneItemOfEachTypeIsSelected");
	class EntitySelector {
	  constructor(converter, entityId, dstEntityTypeIds, target = null) {
	    Object.defineProperty(this, _handleItemSelect, {
	      value: _handleItemSelect2
	    });
	    Object.defineProperty(this, _convert, {
	      value: _convert2
	    });
	    Object.defineProperty(this, _dialog, {
	      get: _get_dialog,
	      set: void 0
	    });
	    Object.defineProperty(this, _converter, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dstEntityTypeIds, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialogProp, {
	      writable: true,
	      value: null
	    });
	    // this dont work in slider for some reason
	    // if (converter instanceof Converter)
	    // {
	    // 	this.#converter = converter;
	    // }
	    babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter] = converter;

	    // eslint-disable-next-line no-param-reassign
	    entityId = main_core.Text.toInteger(entityId);
	    if (entityId > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1] = entityId;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _dstEntityTypeIds)[_dstEntityTypeIds] = dstEntityTypeIds.map(x => main_core.Text.toInteger(x)).filter(entityTypeId => BX.CrmEntityType.isDefined(entityTypeId));
	    babelHelpers.classPrivateFieldLooseBase(this, _dstEntityTypeIds)[_dstEntityTypeIds].sort();
	    if (main_core.Type.isDomNode(target) || main_core.Type.isNil(target)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _target)[_target] = target;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter] || !babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1] || !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _dstEntityTypeIds)[_dstEntityTypeIds])) {
	      console.error('Invalid constructor params:', {
	        converter,
	        entityId,
	        dstEntityTypeIds
	      });
	      throw new Error('Invalid constructor params');
	    }
	  }
	  show() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].subscribeOnce('onShow', resolve);
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].show();
	    });
	  }
	  hide() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].subscribeOnce('onHide', resolve);
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	    });
	  }
	  destroy() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].unsubscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleItemSelect)[_handleItemSelect].bind(this));
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].destroy();
	      resolve();
	    });
	  }
	  getConverter() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter];
	  }
	}
	function _get_dialog() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp];
	  }
	  const applyButton = new ui_buttons.ApplyButton({
	    color: ui_buttons.ButtonColor.SUCCESS,
	    onclick: () => {
	      void this.hide();
	      babelHelpers.classPrivateFieldLooseBase(this, _convert)[_convert]();
	    }
	  });
	  const cancelButton = new ui_buttons.CancelButton({
	    onclick: () => {
	      void this.hide();
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp] = new ui_entitySelector.Dialog({
	    targetNode: babelHelpers.classPrivateFieldLooseBase(this, _target)[_target],
	    enableSearch: true,
	    context: `crm.converter.entity-selector.${babelHelpers.classPrivateFieldLooseBase(this, _dstEntityTypeIds)[_dstEntityTypeIds].join('-')}`,
	    entities: babelHelpers.classPrivateFieldLooseBase(this, _dstEntityTypeIds)[_dstEntityTypeIds].map(entityTypeId => {
	      return {
	        id: BX.CrmEntityType.resolveName(entityTypeId),
	        dynamicLoad: true,
	        dynamicSearch: true,
	        options: {
	          showTab: true,
	          excludeMyCompany: true
	        },
	        searchFields: [{
	          name: 'id'
	        }],
	        searchCacheLimits: ['^\\d+$']
	      };
	    }),
	    footer: [applyButton.render(), cancelButton.render()],
	    footerOptions: {
	      containerStyles: {
	        display: 'flex',
	        'justify-content': 'center'
	      }
	    },
	    tagSelectorOptions: {
	      textBoxWidth: 565 // same as default dialog width
	    }
	  });

	  babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp].subscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleItemSelect)[_handleItemSelect].bind(this));
	  return babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp];
	}
	function _convert2() {
	  const activeEntityTypeIds = new Set();
	  const data = {};
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems().forEach(item => {
	    activeEntityTypeIds.add(BX.CrmEntityType.resolveId(item.getEntityId().toUpperCase()));
	    data[item.getEntityId()] = item.getId();
	  });
	  const schemeItem = babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter].getConfig().getScheme().getItemForEntityTypeIds([...activeEntityTypeIds]);
	  if (!schemeItem) {
	    throw new Error(`Could not find a scheme item for destinations ${[...activeEntityTypeIds].join(', ')}`);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter].getConfig().updateFromSchemeItem(schemeItem);
	  babelHelpers.classPrivateFieldLooseBase(this, _converter)[_converter].convert(babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1], data);
	}
	function _handleItemSelect2(event) {
	  const {
	    item
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(EntitySelector, _ensureOnlyOneItemOfEachTypeIsSelected)[_ensureOnlyOneItemOfEachTypeIsSelected](babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog], item);
	}
	function _ensureOnlyOneItemOfEachTypeIsSelected2(dialog, justSelectedItem) {
	  dialog.getSelectedItems().forEach(item => {
	    if (item.getEntityId() === justSelectedItem.getEntityId() && main_core.Text.toInteger(item.getId()) !== main_core.Text.toInteger(justSelectedItem.getId())) {
	      item.deselect();
	    }
	  });
	}
	Object.defineProperty(EntitySelector, _ensureOnlyOneItemOfEachTypeIsSelected, {
	  value: _ensureOnlyOneItemOfEachTypeIsSelected2
	});

	let instance$1 = null;

	/**
	 * @memberOf BX.Crm.Conversion
	 */
	var _converters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("converters");
	class Manager {
	  constructor() {
	    Object.defineProperty(this, _converters, {
	      writable: true,
	      value: {}
	    });
	  }
	  static get Instance() {
	    if (instance$1 === null) {
	      instance$1 = new Manager();
	    }
	    return instance$1;
	  }
	  initializeConverter(entityTypeId, params) {
	    const config = Config.create(entityTypeId, params.configItems, Scheme.create(params.scheme));
	    const converter = new Converter(entityTypeId, config, params.params);
	    babelHelpers.classPrivateFieldLooseBase(this, _converters)[_converters][converter.getId()] = converter;
	    return converter;
	  }
	  getConverter(converterId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _converters)[_converters][converterId];
	  }
	  createEntitySelector(converterId, dstEntityTypeIds, entityId) {
	    const converter = this.getConverter(converterId);
	    if (!converter) {
	      console.error('Converter with given id not found', converterId, this);
	      return null;
	    }

	    // check whether converter supports this type of scheme
	    const schemeItem = converter.getConfig().getScheme().getItemForEntityTypeIds(dstEntityTypeIds);
	    if (!schemeItem) {
	      console.error('Could not find scheme item', dstEntityTypeIds, converter);
	      return null;
	    }
	    return new EntitySelector(converter, entityId, dstEntityTypeIds);
	  }
	}

	/**
	 * @memberOf BX.Crm.Conversion
	 * @mixes EventEmitter
	 */
	var _entityId$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _menuButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuButton");
	var _label = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("label");
	var _converter$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("converter");
	var _menuId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuId");
	var _isAutoConversionEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAutoConversionEnabled");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _initUI = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initUI");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _unbindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unbindEvents");
	var _handleContainerClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleContainerClick");
	var _handleMenuButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuButtonClick");
	var _showMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMenu");
	var _closeMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeMenu");
	var _getMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItems");
	var _prepareEntitySelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareEntitySelector");
	var _handleItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemClick");
	class SchemeSelector {
	  constructor(converter, params) {
	    Object.defineProperty(this, _handleItemClick, {
	      value: _handleItemClick2
	    });
	    Object.defineProperty(this, _prepareEntitySelector, {
	      value: _prepareEntitySelector2
	    });
	    Object.defineProperty(this, _getMenuItems, {
	      value: _getMenuItems2
	    });
	    Object.defineProperty(this, _closeMenu, {
	      value: _closeMenu2
	    });
	    Object.defineProperty(this, _showMenu, {
	      value: _showMenu2
	    });
	    Object.defineProperty(this, _handleMenuButtonClick, {
	      value: _handleMenuButtonClick2
	    });
	    Object.defineProperty(this, _handleContainerClick, {
	      value: _handleContainerClick2
	    });
	    Object.defineProperty(this, _unbindEvents, {
	      value: _unbindEvents2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _initUI, {
	      value: _initUI2
	    });
	    Object.defineProperty(this, _entityId$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _label, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _converter$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isAutoConversionEnabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1] = converter;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2] = Number(params.entityId);
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById(params.containerId);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuButton)[_menuButton] = document.getElementById(params.buttonId);
	    babelHelpers.classPrivateFieldLooseBase(this, _label)[_label] = document.getElementById(params.labelId);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuId)[_menuId] = `crm_conversion_scheme_selector_${babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2]}_${main_core.Text.getRandom()}`;
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoConversionEnabled)[_isAutoConversionEnabled] = false;
	    if (main_core.Type.isStringFilled(params.analytics.c_element)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].c_element = params.analytics.c_element;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2] || !babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] || !babelHelpers.classPrivateFieldLooseBase(this, _menuButton)[_menuButton] || !babelHelpers.classPrivateFieldLooseBase(this, _label)[_label] || !babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1]) {
	      console.error('Error SchemeSelector initializing', this);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _initUI)[_initUI]();
	      babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	    }
	    main_core_events.EventEmitter.makeObservable(this, 'BX.Crm.Conversion.SchemeSelector');
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _closeMenu)[_closeMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _unbindEvents)[_unbindEvents]();
	    this.unsubscribeAll();
	  }

	  /**
	   * Alias for 'destroy'
	   */
	  release() {
	    this.destroy();
	  }
	  enableAutoConversion() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoConversionEnabled)[_isAutoConversionEnabled] = true;
	  }
	  disableAutoConversion() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isAutoConversionEnabled)[_isAutoConversionEnabled] = false;
	  }
	}
	function _initUI2() {
	  const currentSchemeItem = babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getConfig().getScheme().getCurrentItem();
	  if (currentSchemeItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _label)[_label].innerText = currentSchemeItem.getPhrase();
	  }
	}
	function _bindEvents2() {
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleContainerClick)[_handleContainerClick].bind(this));
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _menuButton)[_menuButton], 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleMenuButtonClick)[_handleMenuButtonClick].bind(this));
	}
	function _unbindEvents2() {
	  main_core.Event.unbind(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleContainerClick)[_handleContainerClick].bind(this));
	  main_core.Event.unbind(babelHelpers.classPrivateFieldLooseBase(this, _menuButton)[_menuButton], 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleMenuButtonClick)[_handleMenuButtonClick].bind(this));
	}
	function _handleContainerClick2() {
	  const event = new main_core_events.BaseEvent({
	    data: {
	      isCanceled: false
	    }
	  });
	  this.emit('onContainerClick', event);
	  babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getConfig().updateFromSchemeItem();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isAutoConversionEnabled)[_isAutoConversionEnabled] && !event.getData().isCanceled) {
	    babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].setAnalyticsElement(babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].c_element);
	    babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].convert(babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2]);
	  }
	}
	function _handleMenuButtonClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _showMenu)[_showMenu]();
	}
	function _showMenu2() {
	  // eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
	  const anchorPos = BX.pos(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  main_popup.MenuManager.show({
	    id: babelHelpers.classPrivateFieldLooseBase(this, _menuId)[_menuId],
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _menuButton)[_menuButton],
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](),
	    closeByEsc: true,
	    cacheable: false,
	    offsetLeft: -anchorPos.width
	  });
	}
	function _closeMenu2() {
	  main_popup.MenuManager.destroy(babelHelpers.classPrivateFieldLooseBase(this, _menuId)[_menuId]);
	}
	function _getMenuItems2() {
	  const scheme = babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getConfig().getScheme();
	  const items = [];
	  for (const item of scheme.getItems()) {
	    items.push({
	      text: main_core.Text.encode(item.getPhrase()),
	      onclick: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _handleItemClick)[_handleItemClick](item);
	      }
	    });
	  }
	  const entitySelector = babelHelpers.classPrivateFieldLooseBase(this, _prepareEntitySelector)[_prepareEntitySelector](scheme);
	  if (entitySelector) {
	    items.push({
	      text: babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getMessagePublic('openEntitySelector'),
	      onclick: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _closeMenu)[_closeMenu]();
	        void entitySelector.show();
	      }
	    });
	  }
	  return items;
	}
	function _prepareEntitySelector2(scheme) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getEntityTypeId() !== BX.CrmEntityType.enumeration.lead) {
	    return null;
	  }
	  const allEntityTypeIdsInScheme = scheme.getAllEntityTypeIds();
	  const dstEntityTypeIds = [];
	  if (allEntityTypeIdsInScheme.includes(BX.CrmEntityType.enumeration.contact)) {
	    dstEntityTypeIds.push(BX.CrmEntityType.enumeration.contact);
	  }
	  if (allEntityTypeIdsInScheme.includes(BX.CrmEntityType.enumeration.company)) {
	    dstEntityTypeIds.push(BX.CrmEntityType.enumeration.company);
	  }
	  if (!main_core.Type.isArrayFilled(dstEntityTypeIds)) {
	    return null;
	  }
	  return new EntitySelector(babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1], babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2], dstEntityTypeIds);
	}
	function _handleItemClick2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _closeMenu)[_closeMenu]();
	  babelHelpers.classPrivateFieldLooseBase(this, _label)[_label].innerText = item.getPhrase();
	  babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].getConfig().updateFromSchemeItem(item);
	  const event = new main_core_events.BaseEvent({
	    data: {
	      isCanceled: false
	    }
	  });
	  this.emit('onSchemeSelected', event);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isAutoConversionEnabled)[_isAutoConversionEnabled] && !event.getData().isCanceled) {
	    babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].setAnalyticsElement(babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].c_element);
	    babelHelpers.classPrivateFieldLooseBase(this, _converter$1)[_converter$1].convert(babelHelpers.classPrivateFieldLooseBase(this, _entityId$2)[_entityId$2]);
	  }
	}

	/**
	 * @memberOf BX.Crm
	 */
	const Conversion = {
	  Scheme,
	  Config,
	  Converter,
	  Manager,
	  SchemeSelector,
	  EntitySelector
	};

	exports.Conversion = Conversion;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm.Integration.Analytics,BX.UI.Analytics,BX.UI.Dialogs,BX,BX.Crm.Models,BX.UI,BX.UI.EntitySelector,BX,BX.Event,BX.Main));
//# sourceMappingURL=conversion.bundle.js.map
