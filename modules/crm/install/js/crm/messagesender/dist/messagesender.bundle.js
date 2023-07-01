this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,main_core,crm_dataStructures) {
	'use strict';

	function ensureIsItemIdentifier(candidate) {
	  if (candidate instanceof crm_dataStructures.ItemIdentifier) {
	    return;
	  }
	  throw new Error('Argument should be an instance of ItemIdentifier');
	}
	function ensureIsReceiver(candidate) {
	  if (candidate instanceof Receiver) {
	    return;
	  }
	  throw new Error('Argument should be an instance of Receiver');
	}
	function ensureIsValidMultifieldValue(candidate) {
	  // noinspection OverlyComplexBooleanExpressionJS
	  const isValidValue = main_core.Type.isPlainObject(candidate) && (main_core.Type.isNil(candidate.id) || main_core.Type.isInteger(candidate.id)) && main_core.Type.isStringFilled(candidate.typeId) && main_core.Type.isStringFilled(candidate.valueType) && main_core.Type.isStringFilled(candidate.value);
	  if (isValidValue) {
	    return;
	  }
	  throw new Error('Argument should be an object of valid MultifieldValue structure');
	}
	function ensureIsValidSourceData(candidate) {
	  const isValid = main_core.Type.isPlainObject(candidate) && main_core.Type.isStringFilled(candidate.title);
	  if (isValid) {
	    return;
	  }
	  throw new Error('Argument should be an object of valid SourceData structure');
	}

	var _rootSource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rootSource");
	var _addressSource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addressSource");
	var _addressSourceData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addressSourceData");
	var _address = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("address");
	class Receiver {
	  constructor(rootSource, addressSource, address, addressSourceData = null) {
	    Object.defineProperty(this, _rootSource, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addressSource, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addressSourceData, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _address, {
	      writable: true,
	      value: void 0
	    });
	    ensureIsItemIdentifier(rootSource);
	    babelHelpers.classPrivateFieldLooseBase(this, _rootSource)[_rootSource] = rootSource;
	    ensureIsItemIdentifier(addressSource);
	    babelHelpers.classPrivateFieldLooseBase(this, _addressSource)[_addressSource] = addressSource;
	    ensureIsValidMultifieldValue(address);
	    babelHelpers.classPrivateFieldLooseBase(this, _address)[_address] = Object.freeze({
	      id: address.id,
	      typeId: address.typeId,
	      valueType: address.valueType,
	      value: address.value,
	      valueFormatted: address.valueFormatted
	    });
	    if (addressSourceData) {
	      ensureIsValidSourceData(addressSourceData);
	      babelHelpers.classPrivateFieldLooseBase(this, _addressSourceData)[_addressSourceData] = Object.freeze({
	        title: addressSourceData.title
	      });
	    }
	  }
	  static fromJSON(data) {
	    const rootSource = crm_dataStructures.ItemIdentifier.fromJSON(data == null ? void 0 : data.rootSource);
	    if (!rootSource) {
	      return null;
	    }
	    const addressSource = crm_dataStructures.ItemIdentifier.fromJSON(data == null ? void 0 : data.addressSource);
	    if (!addressSource) {
	      return null;
	    }
	    try {
	      return new Receiver(rootSource, addressSource, data == null ? void 0 : data.address, data == null ? void 0 : data.addressSourceData);
	    } catch (e) {
	      return null;
	    }
	  }
	  get rootSource() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _rootSource)[_rootSource];
	  }
	  get addressSource() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _addressSource)[_addressSource];
	  }
	  get addressSourceData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _addressSourceData)[_addressSourceData];
	  }
	  get address() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _address)[_address];
	  }
	  isEqualTo(another) {
	    if (!(another instanceof Receiver)) {
	      return false;
	    }

	    // noinspection OverlyComplexBooleanExpressionJS
	    return this.rootSource.isEqualTo(another.rootSource) && this.addressSource.isEqualTo(another.addressSource) && String(this.address.typeId) === String(another.address.typeId) && String(this.address.valueType) === String(another.address.valueType) && String(this.address.value) === String(another.address.value);
	  }
	}

	function extractReceivers(item, entityData) {
	  const receivers = [];
	  if (entityData != null && entityData.hasOwnProperty('MULTIFIELD_DATA')) {
	    receivers.push(...extractReceiversFromMultifieldData(item, entityData));
	  }
	  if (entityData != null && entityData.hasOwnProperty('CLIENT_INFO')) {
	    receivers.push(...extractReceiversFromClientInfo(item, entityData.CLIENT_INFO));
	  }
	  return unique(receivers);
	}
	function extractReceiversFromMultifieldData(item, entityData) {
	  const receivers = [];
	  const multifields = entityData.MULTIFIELD_DATA;
	  for (const multifieldTypeId in multifields) {
	    if (!multifields.hasOwnProperty(multifieldTypeId) || !main_core.Type.isPlainObject(multifields[multifieldTypeId])) {
	      continue;
	    }
	    for (const itemSlug in multifields[multifieldTypeId]) {
	      if (!multifields[multifieldTypeId].hasOwnProperty(itemSlug) || !main_core.Type.isArrayFilled(multifields[multifieldTypeId][itemSlug])) {
	        continue;
	      }
	      const [entityTypeId, entityId] = itemSlug.split('_');
	      let addressSource;
	      try {
	        addressSource = new crm_dataStructures.ItemIdentifier(main_core.Text.toInteger(entityTypeId), main_core.Text.toInteger(entityId));
	      } catch (e) {
	        continue;
	      }
	      const addressSourceTitle = getAddressSourceTitle(item, addressSource, entityData);
	      for (const singleMultifield of multifields[multifieldTypeId][itemSlug]) {
	        try {
	          receivers.push(new Receiver(item, addressSource, {
	            id: main_core.Text.toInteger(singleMultifield.ID),
	            typeId: String(multifieldTypeId),
	            valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
	            value: stringOrUndefined(singleMultifield.VALUE),
	            valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED)
	          }, {
	            title: addressSourceTitle
	          }));
	        } catch (e) {}
	      }
	    }
	  }
	  return receivers;
	}
	function getAddressSourceTitle(rootSource, addressSource, entityData) {
	  var _entityData$CLIENT_IN;
	  if (rootSource.isEqualTo(addressSource)) {
	    var _ref, _entityData$TITLE;
	    return (_ref = (_entityData$TITLE = entityData == null ? void 0 : entityData.TITLE) != null ? _entityData$TITLE : entityData.FORMATTED_NAME) != null ? _ref : '';
	  }
	  const clientDataKey = `${BX.CrmEntityType.resolveName(addressSource.entityTypeId)}_DATA`;
	  if (main_core.Type.isArrayFilled(entityData == null ? void 0 : (_entityData$CLIENT_IN = entityData.CLIENT_INFO) == null ? void 0 : _entityData$CLIENT_IN[clientDataKey])) {
	    const client = entityData.CLIENT_INFO[clientDataKey].find(clientInfo => {
	      return main_core.Text.toInteger(clientInfo.id) === addressSource.entityId;
	    });
	    if (main_core.Type.isString(client == null ? void 0 : client.title)) {
	      return client.title;
	    }
	  }
	  return '';
	}
	function extractReceiversFromClientInfo(item, clientInfo) {
	  const receivers = [];
	  for (const clientsOfSameType of Object.values(clientInfo)) {
	    if (!main_core.Type.isArrayFilled(clientsOfSameType)) {
	      continue;
	    }
	    for (const singleClient of clientsOfSameType) {
	      var _singleClient$advance;
	      if (!main_core.Type.isPlainObject(singleClient)) {
	        continue;
	      }
	      let addressSource;
	      try {
	        addressSource = new crm_dataStructures.ItemIdentifier(BX.CrmEntityType.resolveId(singleClient.typeName), singleClient.id);
	      } catch (e) {
	        continue;
	      }
	      const multifields = (_singleClient$advance = singleClient.advancedInfo) == null ? void 0 : _singleClient$advance.multiFields;
	      if (!main_core.Type.isArrayFilled(multifields)) {
	        continue;
	      }
	      for (const singleMultifield of multifields) {
	        try {
	          receivers.push(new Receiver(item, addressSource, {
	            id: main_core.Text.toInteger(singleMultifield.ID),
	            typeId: stringOrUndefined(singleMultifield.TYPE_ID),
	            valueType: stringOrUndefined(singleMultifield.VALUE_TYPE),
	            value: stringOrUndefined(singleMultifield.VALUE),
	            valueFormatted: stringOrUndefined(singleMultifield.VALUE_FORMATTED)
	          }, {
	            title: stringOrUndefined(singleClient.title)
	          }));
	        } catch (e) {}
	      }
	    }
	  }
	  return receivers;
	}
	function stringOrUndefined(value) {
	  return main_core.Type.isNil(value) ? undefined : String(value);
	}
	function unique(receivers) {
	  return receivers.filter((receiver, index) => {
	    const anotherIndex = receivers.findIndex(anotherReceiver => receiver.isEqualTo(anotherReceiver));
	    return anotherIndex === index;
	  });
	}

	const OBSERVED_EVENTS = new Set(['onCrmEntityCreate', 'onCrmEntityUpdate', 'onCrmEntityDelete']);

	/**
	 * @memberOf BX.Crm.MessageSender
	 * @mixes EventEmitter
	 *
	 * @emits BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged
	 * @emits BX.Crm.MessageSender.ReceiverRepository:OnItemDeleted
	 *
	 * Currently, this class is supposed to work only in the context of entity details tab.
	 * In the future, it can be extended to work on any page.
	 */
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _onDetailsTabChangeEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDetailsTabChangeEventHandler");
	var _storage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("storage");
	var _observedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observedItems");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _destroy = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroy");
	var _onCrmEntityChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCrmEntityChange");
	var _addReceivers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addReceivers");
	var _startObservingItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startObservingItem");
	class ReceiverRepository {
	  static get Instance() {
	    if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.MessageSender.ReceiverRepository')) {
	      return window.top.BX.Crm.MessageSender.ReceiverRepository;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance] = new ReceiverRepository();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance];
	  }

	  /**
	   * @internal This class is a singleton. Use Instance getter instead of constructing a new instance
	   */
	  constructor() {
	    Object.defineProperty(this, _startObservingItem, {
	      value: _startObservingItem2
	    });
	    Object.defineProperty(this, _addReceivers, {
	      value: _addReceivers2
	    });
	    Object.defineProperty(this, _onCrmEntityChange, {
	      value: _onCrmEntityChange2
	    });
	    Object.defineProperty(this, _destroy, {
	      value: _destroy2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _onDetailsTabChangeEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _storage, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _observedItems, {
	      writable: true,
	      value: {}
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance]) {
	      throw new Error('Attempt to make a new instance of a singleton');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  /**
	   * @internal
	   */
	  static onDetailsLoad(entityTypeId, entityId, receiversJSONString) {
	    let item;
	    try {
	      item = new crm_dataStructures.ItemIdentifier(entityTypeId, entityId);
	    } catch (e) {
	      return;
	    }
	    const instance = ReceiverRepository.Instance;
	    babelHelpers.classPrivateFieldLooseBase(instance, _startObservingItem)[_startObservingItem](item);
	    const receiversJSON = JSON.parse(receiversJSONString);
	    if (main_core.Type.isArrayFilled(receiversJSON)) {
	      const receivers = [];
	      for (const singleReceiverJSON of receiversJSON) {
	        const receiver = Receiver.fromJSON(singleReceiverJSON);
	        if (!main_core.Type.isNil(receiver)) {
	          receivers.push(receiver);
	        }
	      }
	      if (main_core.Type.isArrayFilled(receivers)) {
	        babelHelpers.classPrivateFieldLooseBase(instance, _addReceivers)[_addReceivers](item, receivers);
	      }
	    }
	  }
	  getReceivers(entityTypeId, entityId) {
	    try {
	      return this.getReceiversByIdentifier(new crm_dataStructures.ItemIdentifier(entityTypeId, entityId));
	    } catch (e) {
	      return [];
	    }
	  }
	  getReceiversByIdentifier(item) {
	    var _babelHelpers$classPr;
	    ensureIsItemIdentifier(item);
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash]) != null ? _babelHelpers$classPr : [];
	  }
	}
	function _init2() {
	  var _BX$SidePanel, _BX$SidePanel$Instanc;
	  main_core_events.EventEmitter.makeObservable(this, 'BX.Crm.MessageSender.ReceiverRepository');
	  babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler] = event => {
	    if (!(event instanceof main_core_events.BaseEvent)) {
	      console.error('unexpected event type', event);
	      return;
	    }
	    if (!main_core.Type.isArrayFilled(event.getData()) || !main_core.Type.isPlainObject(event.getData()[0])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _onCrmEntityChange)[_onCrmEntityChange](event.getType(), event.getData()[0]);
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler].bind(this);
	  for (const eventName of OBSERVED_EVENTS) {
	    main_core_events.EventEmitter.subscribe(eventName, babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler]);
	  }
	  if ((_BX$SidePanel = BX.SidePanel) != null && (_BX$SidePanel$Instanc = _BX$SidePanel.Instance) != null && _BX$SidePanel$Instanc.isOpen()) {
	    // we are on entity details slider
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onDestroy', babelHelpers.classPrivateFieldLooseBase(this, _destroy)[_destroy].bind(this));
	  }
	}
	function _destroy2() {
	  for (const eventName of OBSERVED_EVENTS) {
	    main_core_events.EventEmitter.unsubscribe(eventName, babelHelpers.classPrivateFieldLooseBase(this, _onDetailsTabChangeEventHandler)[_onDetailsTabChangeEventHandler]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(ReceiverRepository, _instance)[_instance] = null;
	}
	function _onCrmEntityChange2(eventType, {
	  entityTypeId,
	  entityId,
	  entityData
	}) {
	  var _babelHelpers$classPr2;
	  if (!((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][entityTypeId]) != null && _babelHelpers$classPr2.has(entityId))) {
	    return;
	  }
	  const item = new crm_dataStructures.ItemIdentifier(entityTypeId, entityId);
	  if (eventType.toLowerCase() === 'onCrmEntityCreate'.toLowerCase() || eventType.toLowerCase() === 'onCrmEntityUpdate'.toLowerCase()) {
	    var _babelHelpers$classPr3;
	    const oldReceivers = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash]) != null ? _babelHelpers$classPr3 : [];
	    const newReceivers = extractReceivers(item, entityData);
	    babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash] = newReceivers;
	    const added = newReceivers.filter(newReceiver => {
	      return main_core.Type.isNil(oldReceivers.find(oldReceiver => oldReceiver.isEqualTo(newReceiver)));
	    });
	    const deleted = oldReceivers.filter(oldReceiver => {
	      return main_core.Type.isNil(newReceivers.find(newReceiver => newReceiver.isEqualTo(oldReceiver)));
	    });
	    if (added.length > 0 || deleted.length > 0) {
	      this.emit('OnReceiversChanged', {
	        item,
	        previous: oldReceivers,
	        current: newReceivers,
	        added,
	        deleted
	      });
	    }
	  } else if (eventType.toLowerCase() === 'onCrmEntityDelete'.toLowerCase()) {
	    delete babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash];
	    babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId].delete(item.entityId);
	    this.emit('OnItemDeleted', {
	      item
	    });
	  } else {
	    console.error('unknown event type', eventType);
	  }
	}
	function _addReceivers2(item, receivers) {
	  ensureIsItemIdentifier(item);
	  babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash] = [];
	  for (const receiver of receivers) {
	    ensureIsReceiver(receiver);
	    babelHelpers.classPrivateFieldLooseBase(this, _storage)[_storage][item.hash].push(receiver);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _startObservingItem)[_startObservingItem](item);
	}
	function _startObservingItem2(item) {
	  var _babelHelpers$classPr4;
	  ensureIsItemIdentifier(item);
	  const observedItemsOfThisType = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId]) != null ? _babelHelpers$classPr4 : new Set();
	  observedItemsOfThisType.add(item.entityId);
	  babelHelpers.classPrivateFieldLooseBase(this, _observedItems)[_observedItems][item.entityTypeId] = observedItemsOfThisType;
	}
	Object.defineProperty(ReceiverRepository, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.ReceiverRepository = ReceiverRepository;
	exports.Receiver = Receiver;

}((this.BX.Crm.MessageSender = this.BX.Crm.MessageSender || {}),BX.Event,BX,BX.Crm.DataStructures));
//# sourceMappingURL=messagesender.bundle.js.map
