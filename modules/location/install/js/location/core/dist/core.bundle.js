this.BX = this.BX || {};
this.BX.Location = this.BX.Location || {};
(function (exports,main_core,location_core,main_core_events) {
	'use strict';

	var _type = new WeakMap();

	var Field = /*#__PURE__*/function () {
	  function Field(props) {
	    babelHelpers.classCallCheck(this, Field);

	    _type.set(this, {
	      writable: true,
	      value: void 0
	    });

	    if (typeof props.type === 'undefined') {
	      throw new Error('Field type must be defined');
	    }

	    babelHelpers.classPrivateFieldSet(this, _type, parseInt(props.type));
	  }

	  babelHelpers.createClass(Field, [{
	    key: "type",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _type);
	    }
	  }]);
	  return Field;
	}();

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var _fields = new WeakMap();

	var FieldCollection = /*#__PURE__*/function () {
	  function FieldCollection() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, FieldCollection);

	    _fields.set(this, {
	      writable: true,
	      value: {}
	    });

	    this.fields = props.fields ? props.fields : [];
	  }

	  babelHelpers.createClass(FieldCollection, [{
	    key: "isFieldExists",

	    /**
	     * Checks if field already exist in collection
	     * @param {int} type
	     * @returns {boolean}
	     */
	    value: function isFieldExists(type) {
	      return typeof babelHelpers.classPrivateFieldGet(this, _fields)[type] !== 'undefined';
	    }
	  }, {
	    key: "getField",
	    value: function getField(type) {
	      return this.isFieldExists(type) ? babelHelpers.classPrivateFieldGet(this, _fields)[type] : null;
	    }
	  }, {
	    key: "setField",
	    value: function setField(field) {
	      if (!(field instanceof Field)) {
	        throw new Error('Argument field must be instance of Field!');
	      }

	      babelHelpers.classPrivateFieldGet(this, _fields)[field.type] = field;
	      return this;
	    }
	  }, {
	    key: "getMaxFieldType",
	    value: function getMaxFieldType() {
	      var types = Object.keys(babelHelpers.classPrivateFieldGet(this, _fields)).sort(function (a, b) {
	        return parseInt(a) - parseInt(b);
	      });
	      var result = 0;

	      if (types.length > 0) {
	        result = types[types.length - 1];
	      }

	      return result;
	    }
	  }, {
	    key: "fields",
	    set: function set(fields) {
	      if (!Array.isArray(fields)) {
	        throw new Error('Items must be array!');
	      }

	      var _iterator = _createForOfIteratorHelper(fields),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var field = _step.value;
	          this.setField(field);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      return this;
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _fields);
	    }
	  }]);
	  return FieldCollection;
	}();

	var _value = new WeakMap();

	var AddressField = /*#__PURE__*/function (_Field) {
	  babelHelpers.inherits(AddressField, _Field);

	  //todo: Fields validation
	  function AddressField(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, AddressField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddressField).call(this, props));

	    _value.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _value, props.value || '');
	    return _this;
	  }

	  babelHelpers.createClass(AddressField, [{
	    key: "value",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _value);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _value, value);
	      return this;
	    }
	  }]);
	  return AddressField;
	}(Field);

	var AddressFieldCollection = /*#__PURE__*/function (_FieldCollection) {
	  babelHelpers.inherits(AddressFieldCollection, _FieldCollection);

	  function AddressFieldCollection() {
	    babelHelpers.classCallCheck(this, AddressFieldCollection);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddressFieldCollection).apply(this, arguments));
	  }

	  babelHelpers.createClass(AddressFieldCollection, [{
	    key: "getFieldValue",
	    value: function getFieldValue(type) {
	      var result = null;

	      if (this.isFieldExists(type)) {
	        var field = this.getField(type);

	        if (field) {
	          result = field.value;
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "setFieldValue",
	    value: function setFieldValue(type, value) {
	      this.setField(new AddressField({
	        type: type,
	        value: value
	      }));
	      return this;
	    }
	  }]);
	  return AddressFieldCollection;
	}(FieldCollection);

	var _entityId = new WeakMap();

	var _entityType = new WeakMap();

	var AddressLink = /*#__PURE__*/function () {
	  function AddressLink(props) {
	    babelHelpers.classCallCheck(this, AddressLink);

	    _entityId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _entityType.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _entityId, props.entityId);
	    babelHelpers.classPrivateFieldSet(this, _entityType, props.entityType);
	  }

	  babelHelpers.createClass(AddressLink, [{
	    key: "entityId",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityId);
	    }
	  }, {
	    key: "entityType",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _entityType);
	    }
	  }]);
	  return AddressLink;
	}();

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var _links = new WeakMap();

	var AddressLinkCollection = /*#__PURE__*/function () {
	  function AddressLinkCollection() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, AddressLinkCollection);

	    _links.set(this, {
	      writable: true,
	      value: []
	    });

	    this.links = !!props.links ? props.links : [];
	  }

	  babelHelpers.createClass(AddressLinkCollection, [{
	    key: "addLink",
	    value: function addLink(link) {
	      if (!(link instanceof AddressLink)) {
	        throw new Error('Argument link must be instance of Field!');
	      }

	      babelHelpers.classPrivateFieldGet(this, _links).push(link);
	    }
	  }, {
	    key: "clearLinks",
	    value: function clearLinks() {
	      babelHelpers.classPrivateFieldSet(this, _links, []);
	    }
	  }, {
	    key: "links",
	    set: function set(links) {
	      if (!Array.isArray(links)) {
	        throw new Error('links must be array!');
	      }

	      var _iterator = _createForOfIteratorHelper$1(links),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var link = _step.value;
	          this.addLink(link);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _links);
	    }
	  }]);
	  return AddressLinkCollection;
	}();

	var _sort = new WeakMap();

	var _name = new WeakMap();

	var _description = new WeakMap();

	var FormatField = /*#__PURE__*/function (_Field) {
	  babelHelpers.inherits(FormatField, _Field);

	  // todo: Fields validation
	  function FormatField(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, FormatField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FormatField).call(this, props));

	    _sort.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _name.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _description.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _sort, parseInt(props.sort));
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _name, props.name || '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _description, props.description || '');
	    return _this;
	  }

	  babelHelpers.createClass(FormatField, [{
	    key: "sort",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _sort);
	    },
	    set: function set(sort) {
	      babelHelpers.classPrivateFieldSet(this, _sort, sort);
	    }
	  }, {
	    key: "name",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _name);
	    },
	    set: function set(name) {
	      babelHelpers.classPrivateFieldSet(this, _name, name);
	    }
	  }, {
	    key: "description",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _description);
	    },
	    set: function set(description) {
	      babelHelpers.classPrivateFieldSet(this, _description, description);
	    }
	  }]);
	  return FormatField;
	}(Field);

	var FormatFieldCollection = /*#__PURE__*/function (_FieldCollection) {
	  babelHelpers.inherits(FormatFieldCollection, _FieldCollection);

	  function FormatFieldCollection() {
	    babelHelpers.classCallCheck(this, FormatFieldCollection);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FormatFieldCollection).apply(this, arguments));
	  }

	  babelHelpers.createClass(FormatFieldCollection, [{
	    key: "initFields",
	    value: function initFields(fieldsData) {
	      var _this = this;

	      if (Array.isArray(fieldsData)) {
	        fieldsData.forEach(function (data) {
	          var field = new FormatField(data);

	          if (field) {
	            _this.setField(field);
	          }
	        });
	      }
	    }
	  }]);
	  return FormatFieldCollection;
	}(FieldCollection);

	/**
	 * Class defines how the Address will look like
	 */

	var _code = new WeakMap();

	var _name$1 = new WeakMap();

	var _description$1 = new WeakMap();

	var _languageId = new WeakMap();

	var _template = new WeakMap();

	var _fieldCollection = new WeakMap();

	var _delimiter = new WeakMap();

	var _fieldForUnRecognized = new WeakMap();

	var Format$$1 = /*#__PURE__*/function () {
	  function Format$$1(props) {
	    babelHelpers.classCallCheck(this, Format$$1);

	    _code.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _name$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _description$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _languageId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _template.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _fieldCollection.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _delimiter.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _fieldForUnRecognized.set(this, {
	      writable: true,
	      value: void 0
	    });

	    if (main_core.Type.isUndefined(props.languageId)) {
	      throw new TypeError('LanguageId must be defined');
	    }

	    babelHelpers.classPrivateFieldSet(this, _languageId, props.languageId);
	    babelHelpers.classPrivateFieldSet(this, _code, props.code || '');
	    babelHelpers.classPrivateFieldSet(this, _name$1, props.name || '');
	    babelHelpers.classPrivateFieldSet(this, _template, props.template || '');
	    babelHelpers.classPrivateFieldSet(this, _description$1, props.description || '');
	    babelHelpers.classPrivateFieldSet(this, _delimiter, props.delimiter || ', ');
	    babelHelpers.classPrivateFieldSet(this, _fieldForUnRecognized, props.fieldForUnRecognized || AddressType.UNKNOWN);
	    babelHelpers.classPrivateFieldSet(this, _fieldCollection, new FormatFieldCollection());

	    if (main_core.Type.isObject(props.fieldCollection)) {
	      babelHelpers.classPrivateFieldGet(this, _fieldCollection).initFields(props.fieldCollection);
	    }
	  }

	  babelHelpers.createClass(Format$$1, [{
	    key: "getField",
	    value: function getField(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection).getField(type);
	    }
	  }, {
	    key: "isFieldExists",
	    value: function isFieldExists(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection).isFieldExists(type);
	    }
	  }, {
	    key: "languageId",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _languageId);
	    }
	  }, {
	    key: "name",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _name$1);
	    }
	  }, {
	    key: "description",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _description$1);
	    }
	  }, {
	    key: "code",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _code);
	    }
	  }, {
	    key: "fieldCollection",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection);
	    }
	  }, {
	    key: "template",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _template);
	    },
	    set: function set(template) {
	      babelHelpers.classPrivateFieldSet(this, _template, template);
	    }
	  }, {
	    key: "delimiter",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _delimiter);
	    },
	    set: function set(delimiter) {
	      babelHelpers.classPrivateFieldSet(this, _delimiter, delimiter);
	    }
	  }, {
	    key: "fieldForUnRecognized",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldForUnRecognized);
	    },
	    set: function set(fieldForUnRecognized) {
	      babelHelpers.classPrivateFieldSet(this, _fieldForUnRecognized, fieldForUnRecognized);
	    }
	  }]);
	  return Format$$1;
	}();

	var LocationType = function LocationType() {
	  babelHelpers.classCallCheck(this, LocationType);
	};

	babelHelpers.defineProperty(LocationType, "UNKNOWN", 0);
	babelHelpers.defineProperty(LocationType, "COUNTRY", 100);
	babelHelpers.defineProperty(LocationType, "ADM_LEVEL_1", 200);
	babelHelpers.defineProperty(LocationType, "ADM_LEVEL_2", 210);
	babelHelpers.defineProperty(LocationType, "ADM_LEVEL_3", 220);
	babelHelpers.defineProperty(LocationType, "ADM_LEVEL_4", 230);
	babelHelpers.defineProperty(LocationType, "LOCALITY", 300);
	babelHelpers.defineProperty(LocationType, "SUB_LOCALITY", 310);
	babelHelpers.defineProperty(LocationType, "SUB_LOCALITY_LEVEL_1", 320);
	babelHelpers.defineProperty(LocationType, "SUB_LOCALITY_LEVEL_2", 330);
	babelHelpers.defineProperty(LocationType, "STREET", 340);
	babelHelpers.defineProperty(LocationType, "BUILDING", 400);
	babelHelpers.defineProperty(LocationType, "ADDRESS_LINE_1", 410);
	babelHelpers.defineProperty(LocationType, "FLOOR", 420);
	babelHelpers.defineProperty(LocationType, "ROOM", 430);

	var AddressType = /*#__PURE__*/function (_LocationType) {
	  babelHelpers.inherits(AddressType, _LocationType);

	  function AddressType() {
	    babelHelpers.classCallCheck(this, AddressType);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddressType).apply(this, arguments));
	  }

	  return AddressType;
	}(LocationType);

	babelHelpers.defineProperty(AddressType, "POSTAL_CODE", 50);
	babelHelpers.defineProperty(AddressType, "ADDRESS_LINE_2", 600);
	babelHelpers.defineProperty(AddressType, "RECIPIENT_COMPANY", 700);
	babelHelpers.defineProperty(AddressType, "RECIPIENT", 710);
	babelHelpers.defineProperty(AddressType, "PO_BOX", 800);

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }

	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var StringConverter = /*#__PURE__*/function () {
	  function StringConverter() {
	    babelHelpers.classCallCheck(this, StringConverter);
	  }

	  babelHelpers.createClass(StringConverter, null, [{
	    key: "convertAddressToString",

	    /**
	     * Convert address to string
	     * @param {Address} address
	     * @param {Format} format
	     * @param {string} strategyType
	     * @param {string} contentType
	     * @returns {string}
	     */
	    value: function convertAddressToString(address, format, strategyType, contentType) {
	      var result;

	      if (strategyType === StringConverter.STRATEGY_TYPE_TEMPLATE) {
	        result = StringConverter.convertAddressToStringTemplate(address, format, contentType);
	      } else if (strategyType === StringConverter.STRATEGY_TYPE_FIELD_SORT) {
	        var fieldSorter = function fieldSorter(a, b) {
	          return a.sort - b.sort;
	        };

	        result = StringConverter.convertAddressToStringByField(address, format, fieldSorter, contentType);
	      } else if (strategyType === StringConverter.STRATEGY_TYPE_FIELD_TYPE) {
	        var _fieldSorter = function _fieldSorter(a, b) {
	          var sortResult; // We suggest that UNKNOWN must be the last

	          if (a.type === 0) {
	            sortResult = 1;
	          } else if (b.type === 0) {
	            sortResult = -1;
	          } else {
	            sortResult = a.type - b.type;
	          }

	          return sortResult;
	        };

	        result = StringConverter.convertAddressToStringByField(address, format, _fieldSorter, contentType);
	      } else {
	        throw TypeError('Wrong strategyType');
	      }

	      return result;
	    }
	    /**
	     * Convert address to string
	     * @param {Address} address
	     * @param {Format} format
	     * @param {string} contentType
	     * @returns {string}
	     */

	  }, {
	    key: "convertAddressToStringTemplate",
	    value: function convertAddressToStringTemplate(address, format, contentType) {
	      var result = format.template;

	      if (contentType === StringConverter.CONTENT_TYPE_HTML) {
	        result = result.replace(/\n/g, '<br/>');
	      }

	      var components = result.match(/{{[^]*?}}/gm);

	      if (!main_core.Type.isArray(components)) {
	        return '';
	      } // find placeholders witch looks like {{ ... }}


	      var _iterator = _createForOfIteratorHelper$2(components),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var component = _step.value;
	          // find placeholders wich looks like # ... #
	          var fields = component.match(/#([0-9A-Z_]*?)#/);

	          if (!main_core.Type.isArray(fields) || main_core.Type.isUndefined(fields[1])) {
	            continue;
	          }

	          if (main_core.Type.isUndefined(AddressType[fields[1]])) {
	            continue;
	          }

	          var type = AddressType[fields[1]];
	          var fieldValue = address.getFieldValue(type);

	          if (fieldValue === null) {
	            continue;
	          }

	          if (contentType === StringConverter.CONTENT_TYPE_HTML) {
	            fieldValue = main_core.Text.encode(fieldValue);
	          }

	          var componentReplacer = component.replace(fields[0], fieldValue);
	          componentReplacer = componentReplacer.replace('{{', '');
	          componentReplacer = componentReplacer.replace('}}', '');
	          result = result.replace(component, componentReplacer);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      result = result.replace(/({{[^]*?}})/gm, '');

	      if (contentType === StringConverter.CONTENT_TYPE_HTML) {
	        result = result.replace(/(<br\/>)+/g, '<br/>');
	      } else {
	        result = result.replace(/(\n)+/g, '\n');
	      }

	      return result;
	    }
	    /**
	     * Convert address to string
	     * @param {Address} address
	     * @param {Format} format
	     * @param {Function} fieldSorter
	     * @param {string} contentType
	     * @returns {string}
	     */

	  }, {
	    key: "convertAddressToStringByField",
	    value: function convertAddressToStringByField(address, format, fieldSorter, contentType) {
	      if (!(format instanceof Format$$1)) {
	        BX.debug('format must be instance of Format');
	      }

	      if (!(address instanceof Address)) {
	        BX.debug('address must be instance of Address');
	      }

	      var fieldCollection = format.fieldCollection;

	      if (!fieldCollection) {
	        return '';
	      }

	      var fields = Object.values(fieldCollection.fields); // todo: make only once or cache?

	      fields.sort(fieldSorter);
	      var result = '';

	      for (var _i = 0, _fields = fields; _i < _fields.length; _i++) {
	        var field = _fields[_i];
	        var value = address.getFieldValue(field.type);

	        if (value === null) {
	          continue;
	        }

	        if (contentType === StringConverter.CONTENT_TYPE_HTML) {
	          value = main_core.Text.encode(value);
	        }

	        if (result !== '') {
	          result += format.delimiter;
	        }

	        result += value;
	      }

	      return result;
	    }
	  }]);
	  return StringConverter;
	}();

	babelHelpers.defineProperty(StringConverter, "STRATEGY_TYPE_TEMPLATE", 'template');
	babelHelpers.defineProperty(StringConverter, "STRATEGY_TYPE_FIELD_SORT", 'field_sort');
	babelHelpers.defineProperty(StringConverter, "STRATEGY_TYPE_FIELD_TYPE", 'field_type');
	babelHelpers.defineProperty(StringConverter, "CONTENT_TYPE_HTML", 'html');
	babelHelpers.defineProperty(StringConverter, "CONTENT_TYPE_TEXT", 'text');

	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } return method; }

	var JsonConverter = /*#__PURE__*/function () {
	  function JsonConverter() {
	    babelHelpers.classCallCheck(this, JsonConverter);
	  }

	  babelHelpers.createClass(JsonConverter, null, [{
	    key: "convertJsonToAddress",

	    /**
	     * @param {Object} jsonData
	     * @returns {Address}
	     */
	    value: function convertJsonToAddress(jsonData) {
	      return new Address(jsonData);
	    }
	    /**
	     * @param {Address} address
	     * @returns {{languageId: string, location: ({"'...'"}|null), id: number, fieldCollection: {"'...'"}}} Json data
	     */

	  }, {
	    key: "convertAddressToJson",
	    value: function convertAddressToJson(address) {
	      var obj = {
	        id: address.id,
	        languageId: address.languageId,
	        latitude: address.latitude,
	        longitude: address.longitude,
	        fieldCollection: _classStaticPrivateMethodGet(JsonConverter, JsonConverter, _objectifyFieldCollection).call(JsonConverter, address.fieldCollection),
	        links: _classStaticPrivateMethodGet(JsonConverter, JsonConverter, _objectifyLinks).call(JsonConverter, address.links),
	        location: null
	      };

	      if (address.location) {
	        obj.location = JSON.parse(address.location.toJson());
	      }

	      return JSON.stringify(obj);
	    }
	    /**
	     * @param {AddressFieldCollection} fieldCollection
	     * @returns {Object}
	     */

	  }]);
	  return JsonConverter;
	}();

	var _objectifyLinks = function _objectifyLinks(links) {
	  return links.map(function (link) {
	    return {
	      entityId: link.entityId,
	      entityType: link.entityType
	    };
	  });
	};

	var _objectifyFieldCollection = function _objectifyFieldCollection(fieldCollection) {
	  var result = {};
	  Object.values(fieldCollection.fields).forEach(function (field) {
	    result[field.type] = field.value;
	  });
	  return result;
	};

	function _createForOfIteratorHelper$3(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$3(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$3(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$3(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$3(o, minLen); }

	function _arrayLikeToArray$3(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var _id = new WeakMap();

	var _languageId$1 = new WeakMap();

	var _latitude = new WeakMap();

	var _longitude = new WeakMap();

	var _fieldCollection$1 = new WeakMap();

	var _links$1 = new WeakMap();

	var _location = new WeakMap();

	var Address = /*#__PURE__*/function () {
	  /**
	   * @param {{...}} props
	   */
	  function Address(props) {
	    babelHelpers.classCallCheck(this, Address);

	    _id.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _languageId$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _latitude.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _longitude.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _fieldCollection$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _links$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _location.set(this, {
	      writable: true,
	      value: void 0
	    });

	    if (main_core.Type.isUndefined(props.languageId)) {
	      throw new TypeError('languageId must be defined');
	    }

	    babelHelpers.classPrivateFieldSet(this, _languageId$1, props.languageId);
	    babelHelpers.classPrivateFieldSet(this, _id, props.id || 0);
	    babelHelpers.classPrivateFieldSet(this, _latitude, props.latitude || 0);
	    babelHelpers.classPrivateFieldSet(this, _longitude, props.longitude || 0);
	    babelHelpers.classPrivateFieldSet(this, _fieldCollection$1, new AddressFieldCollection());

	    if (main_core.Type.isObject(props.fieldCollection)) {
	      for (var _i = 0, _Object$entries = Object.entries(props.fieldCollection); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            type = _Object$entries$_i[0],
	            value = _Object$entries$_i[1];

	        this.setFieldValue(type, value);
	      }
	    }

	    babelHelpers.classPrivateFieldSet(this, _links$1, new AddressLinkCollection());

	    if (main_core.Type.isArray(props.links)) {
	      var _iterator = _createForOfIteratorHelper$3(props.links),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var link = _step.value;
	          this.addLink(link.entityId, link.entityType);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }

	    babelHelpers.classPrivateFieldSet(this, _location, null);

	    if (props.location) {
	      if (props.location instanceof Location) {
	        babelHelpers.classPrivateFieldSet(this, _location, props.location);
	      } else if (main_core.Type.isObject(props.location)) {
	        babelHelpers.classPrivateFieldSet(this, _location, new Location(props.location));
	      } else {
	        BX.debug('Wrong typeof props.location');
	      }
	    }
	  }
	  /**
	   * @returns {int}
	   */


	  babelHelpers.createClass(Address, [{
	    key: "setFieldValue",

	    /**
	     * @param {number} type
	     * @param {mixed} value
	     */
	    value: function setFieldValue(type, value) {
	      babelHelpers.classPrivateFieldGet(this, _fieldCollection$1).setFieldValue(type, value);
	    }
	    /**
	     * @param {number} type
	     * @returns {?string}
	     */

	  }, {
	    key: "getFieldValue",
	    value: function getFieldValue(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$1).getFieldValue(type);
	    }
	    /**
	     * Check if field exist
	     * @param type
	     * @returns {boolean}
	     */

	  }, {
	    key: "isFieldExists",
	    value: function isFieldExists(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$1).isFieldExists(type);
	    }
	    /**
	     * @return {string} JSON
	     */

	  }, {
	    key: "toJson",
	    value: function toJson() {
	      return JsonConverter.convertAddressToJson(this);
	    }
	    /**
	     * @param {Format}format
	     * @param {?string}strategyType
	     * @param {?string}contentType
	     * @return {string}
	     */

	  }, {
	    key: "toString",
	    value: function toString(format, strategyType, contentType) {
	      if (!(format instanceof Format$$1)) {
	        throw new TypeError('format must be instance of Format');
	      }

	      var strategy = strategyType || StringConverter.STRATEGY_TYPE_TEMPLATE;
	      var type = contentType || StringConverter.CONTENT_TYPE_HTML;
	      var result = StringConverter.convertAddressToString(this, format, strategy, type);
	      return result;
	    }
	    /**
	     * @returns {?Location}
	     */

	  }, {
	    key: "toLocation",
	    value: function toLocation() {
	      var result = null;

	      if (this.location) {
	        var locationObj = JSON.parse(this.location.toJson());
	        locationObj.address = JSON.parse(this.toJson());
	        result = new Location(locationObj);
	      }

	      return result;
	    }
	    /**
	     * @return {number}
	     */

	  }, {
	    key: "getType",
	    value: function getType() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$1).getMaxFieldType();
	    }
	    /**
	     * @param {string} entityId
	     * @param {string} entityType
	     */

	  }, {
	    key: "addLink",
	    value: function addLink(entityId, entityType) {
	      babelHelpers.classPrivateFieldGet(this, _links$1).addLink(new AddressLink({
	        entityId: entityId,
	        entityType: entityType
	      }));
	    }
	  }, {
	    key: "clearLinks",
	    value: function clearLinks() {
	      babelHelpers.classPrivateFieldGet(this, _links$1).clearLinks();
	    }
	  }, {
	    key: "id",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _id);
	    }
	    /**
	     * @returns {Location}
	     */
	    ,

	    /**
	     * @param {int} id
	     */
	    set: function set(id) {
	      babelHelpers.classPrivateFieldSet(this, _id, id);
	    }
	    /**
	     * @param {Location} location
	     */

	  }, {
	    key: "location",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _location);
	    }
	    /**
	     * @returns {string}
	     */
	    ,
	    set: function set(location) {
	      babelHelpers.classPrivateFieldSet(this, _location, location);
	    }
	    /**
	     * @returns {string}
	     */

	  }, {
	    key: "languageId",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _languageId$1);
	    }
	    /**
	     * @returns {AddressFieldCollection}
	     */

	  }, {
	    key: "fieldCollection",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$1);
	    }
	  }, {
	    key: "latitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _latitude);
	    }
	    /**
	     * @param {string} latitude
	     */
	    ,
	    set: function set(latitude) {
	      babelHelpers.classPrivateFieldSet(this, _latitude, latitude);
	    }
	    /**
	     * @returns {string}
	     */

	  }, {
	    key: "longitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _longitude);
	    }
	    /**
	     * @param {string} longitude
	     */
	    ,
	    set: function set(longitude) {
	      babelHelpers.classPrivateFieldSet(this, _longitude, longitude);
	    }
	    /**
	     * @returns {AddressLinkCollection}
	     */

	  }, {
	    key: "links",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _links$1).links;
	    }
	  }]);
	  return Address;
	}();

	var _value$1 = new WeakMap();

	var LocationField = /*#__PURE__*/function (_Field) {
	  babelHelpers.inherits(LocationField, _Field);

	  // todo: Fields validation
	  function LocationField(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, LocationField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(LocationField).call(this, props));

	    _value$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _value$1, props.value || '');
	    return _this;
	  }

	  babelHelpers.createClass(LocationField, [{
	    key: "value",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _value$1);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _value$1, value);
	    }
	  }]);
	  return LocationField;
	}(Field);

	var LocationFieldCollection = /*#__PURE__*/function (_FieldCollection) {
	  babelHelpers.inherits(LocationFieldCollection, _FieldCollection);

	  function LocationFieldCollection() {
	    babelHelpers.classCallCheck(this, LocationFieldCollection);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(LocationFieldCollection).apply(this, arguments));
	  }

	  babelHelpers.createClass(LocationFieldCollection, [{
	    key: "getFieldValue",
	    value: function getFieldValue(type) {
	      var result = null;

	      if (this.isFieldExists(type)) {
	        var field = this.getField(type);

	        if (field) {
	          result = field.value;
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "setFieldValue",
	    value: function setFieldValue(type, value) {
	      this.setField(new LocationField({
	        type: type,
	        value: value
	      }));
	      return this;
	    }
	  }]);
	  return LocationFieldCollection;
	}(FieldCollection);

	function _classStaticPrivateMethodGet$1(receiver, classConstructor, method) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } return method; }

	var LocationObjectConverter = /*#__PURE__*/function () {
	  function LocationObjectConverter() {
	    babelHelpers.classCallCheck(this, LocationObjectConverter);
	  }

	  babelHelpers.createClass(LocationObjectConverter, null, [{
	    key: "convertLocationToObject",
	    value: function convertLocationToObject(location) {
	      if (!(location instanceof Location)) {
	        throw new TypeError('location must be type of location');
	      }

	      var obj = {
	        id: location.id,
	        code: location.code,
	        externalId: location.externalId,
	        sourceCode: location.sourceCode,
	        type: location.type,
	        name: location.name,
	        languageId: location.languageId,
	        latitude: location.latitude,
	        longitude: location.longitude,
	        fieldCollection: _classStaticPrivateMethodGet$1(LocationObjectConverter, LocationObjectConverter, _objectifyFieldCollection$1).call(LocationObjectConverter, location.fieldCollection),
	        address: null
	      };

	      if (location.address) {
	        obj.address = JSON.parse(location.address.toJson());
	      }

	      return obj;
	    }
	  }]);
	  return LocationObjectConverter;
	}();

	var _objectifyFieldCollection$1 = function _objectifyFieldCollection(fieldCollection) {
	  var result = {};
	  Object.values(fieldCollection.fields).forEach(function (field) {
	    result[field.type] = field.value;
	  });
	  return result;
	};

	var LocationJsonConverter = /*#__PURE__*/function () {
	  function LocationJsonConverter() {
	    babelHelpers.classCallCheck(this, LocationJsonConverter);
	  }

	  babelHelpers.createClass(LocationJsonConverter, null, [{
	    key: "convertJsonToLocation",

	    /**
	     * @param {{...}}jsonData
	     * @returns {Location}
	     */
	    value: function convertJsonToLocation(jsonData) {
	      var initData = babelHelpers.objectSpread({}, jsonData);

	      if (jsonData.address) {
	        initData.address = new Address(jsonData.address);
	      }

	      return new Location(initData);
	    }
	    /**
	     * @param {Location} location
	     * @returns {{...}}
	     */

	  }, {
	    key: "convertLocationToJson",
	    value: function convertLocationToJson(location) {
	      if (!(location instanceof Location)) {
	        throw new TypeError('location must be type of location');
	      }

	      var obj = LocationObjectConverter.convertLocationToObject(location);
	      return obj ? JSON.stringify(obj) : '';
	    }
	  }]);
	  return LocationJsonConverter;
	}();

	var _id$1 = new WeakMap();

	var _code$1 = new WeakMap();

	var _externalId = new WeakMap();

	var _sourceCode = new WeakMap();

	var _type$1 = new WeakMap();

	var _name$2 = new WeakMap();

	var _languageId$2 = new WeakMap();

	var _latitude$1 = new WeakMap();

	var _longitude$1 = new WeakMap();

	var _address = new WeakMap();

	var _fieldCollection$2 = new WeakMap();

	var Location = /*#__PURE__*/function () {
	  function Location() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Location);

	    _id$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _code$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _externalId.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _sourceCode.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _type$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _name$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _languageId$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _latitude$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _longitude$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _address.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _fieldCollection$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _id$1, parseInt(props.id) || 0);
	    babelHelpers.classPrivateFieldSet(this, _code$1, props.code || '');
	    babelHelpers.classPrivateFieldSet(this, _externalId, props.externalId || '');
	    babelHelpers.classPrivateFieldSet(this, _sourceCode, props.sourceCode || '');
	    babelHelpers.classPrivateFieldSet(this, _type$1, parseInt(props.type) || 0);
	    babelHelpers.classPrivateFieldSet(this, _name$2, props.name || '');
	    babelHelpers.classPrivateFieldSet(this, _languageId$2, props.languageId || '');
	    babelHelpers.classPrivateFieldSet(this, _latitude$1, props.latitude || '');
	    babelHelpers.classPrivateFieldSet(this, _longitude$1, props.longitude || '');
	    babelHelpers.classPrivateFieldSet(this, _fieldCollection$2, new LocationFieldCollection());

	    if (main_core.Type.isObject(props.fieldCollection)) {
	      for (var _i = 0, _Object$entries = Object.entries(props.fieldCollection); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            type = _Object$entries$_i[0],
	            value = _Object$entries$_i[1];

	        this.setFieldValue(type, value);
	      }
	    }

	    babelHelpers.classPrivateFieldSet(this, _address, null);

	    if (props.address) {
	      if (props.address instanceof Address) {
	        babelHelpers.classPrivateFieldSet(this, _address, props.address);
	      } else if (babelHelpers.typeof(props.address) === 'object') {
	        babelHelpers.classPrivateFieldSet(this, _address, new Address(props.address));
	      } else {
	        BX.debug('Wrong typeof props.address');
	      }
	    }
	  }

	  babelHelpers.createClass(Location, [{
	    key: "toJson",
	    value: function toJson() {
	      return LocationJsonConverter.convertLocationToJson(this);
	    }
	  }, {
	    key: "toAddress",
	    value: function toAddress() {
	      var result = null;

	      if (this.address) {
	        var addressObj = JSON.parse(this.address.toJson());
	        addressObj.location = JSON.parse(this.toJson());
	        result = new Address(addressObj);
	      }

	      return result;
	    }
	  }, {
	    key: "setFieldValue",
	    value: function setFieldValue(type, value) {
	      babelHelpers.classPrivateFieldGet(this, _fieldCollection$2).setFieldValue(type, value);
	    }
	  }, {
	    key: "getFieldValue",
	    value: function getFieldValue(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$2).getFieldValue(type);
	    }
	  }, {
	    key: "isFieldExists",
	    value: function isFieldExists(type) {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$2).isFieldExists(type);
	    }
	  }, {
	    key: "id",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _id$1);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _id$1, value);
	    }
	  }, {
	    key: "code",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _code$1);
	    },
	    set: function set(code) {
	      babelHelpers.classPrivateFieldSet(this, _code$1, code);
	    }
	  }, {
	    key: "externalId",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _externalId);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _externalId, value);
	    }
	  }, {
	    key: "sourceCode",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _sourceCode);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _sourceCode, value);
	    }
	  }, {
	    key: "type",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _type$1);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _type$1, value);
	    }
	  }, {
	    key: "name",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _name$2);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _name$2, value);
	    }
	  }, {
	    key: "languageId",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _languageId$2);
	    },
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _languageId$2, value);
	    }
	  }, {
	    key: "latitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _latitude$1);
	    },
	    set: function set(latitude) {
	      babelHelpers.classPrivateFieldSet(this, _latitude$1, latitude);
	    }
	  }, {
	    key: "longitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _longitude$1);
	    },
	    set: function set(longitude) {
	      babelHelpers.classPrivateFieldSet(this, _longitude$1, longitude);
	    }
	  }, {
	    key: "address",
	    set: function set(address) {
	      babelHelpers.classPrivateFieldSet(this, _address, address);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _address);
	    }
	  }, {
	    key: "fieldCollection",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldCollection$2);
	    }
	  }]);
	  return Location;
	}();

	var _path = new WeakMap();

	var ActionRunner = /*#__PURE__*/function () {
	  function ActionRunner(props) {
	    babelHelpers.classCallCheck(this, ActionRunner);

	    _path.set(this, {
	      writable: true,
	      value: ''
	    });

	    if (!props.path) {
	      throw new Error('props.path must not be empty!');
	    }

	    babelHelpers.classPrivateFieldSet(this, _path, props.path);
	  }

	  babelHelpers.createClass(ActionRunner, [{
	    key: "run",
	    value: function run(action, data) {
	      if (!action) {
	        throw new Error('action can not be empty!');
	      }

	      return BX.ajax.runAction("".concat(babelHelpers.classPrivateFieldGet(this, _path), ".").concat(action), {
	        data: data
	      });
	    }
	  }]);
	  return ActionRunner;
	}();

	function _createForOfIteratorHelper$4(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$4(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$4(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$4(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$4(o, minLen); }

	function _arrayLikeToArray$4(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	var _actionRunner = new WeakMap();

	var BaseRepository = /*#__PURE__*/function () {
	  function BaseRepository() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, BaseRepository);

	    _actionRunner.set(this, {
	      writable: true,
	      value: null
	    });

	    this._path = props.path;

	    if (props.actionRunner && props.actionRunner instanceof ActionRunner) {
	      babelHelpers.classPrivateFieldSet(this, _actionRunner, props.actionRunner);
	    } else {
	      babelHelpers.classPrivateFieldSet(this, _actionRunner, new ActionRunner({
	        path: this._path
	      }));
	    }
	  }

	  babelHelpers.createClass(BaseRepository, [{
	    key: "processResponse",
	    value: function processResponse(response) {
	      if (response.status !== 'success') {
	        BX.debug('Request was not successful');
	        var message = '';

	        if (Array.isArray(response.errors) && response.errors.length > 0) {
	          var _iterator = _createForOfIteratorHelper$4(response.errors),
	              _step;

	          try {
	            for (_iterator.s(); !(_step = _iterator.n()).done;) {
	              var error = _step.value;

	              if (typeof error.message === 'string' && error.message !== '') {
	                message += "".concat(error, "\n");
	              }
	            }
	          } catch (err) {
	            _iterator.e(err);
	          } finally {
	            _iterator.f();
	          }
	        }

	        throw new Error(message);
	      }

	      return response.data ? response.data : null;
	    }
	  }, {
	    key: "path",
	    get: function get() {
	      return this._path;
	    }
	  }, {
	    key: "actionRunner",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _actionRunner);
	    }
	  }]);
	  return BaseRepository;
	}();

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _convertCollection = new WeakSet();

	var _convertLocation = new WeakSet();

	var LocationRepository = /*#__PURE__*/function (_BaseRepository) {
	  babelHelpers.inherits(LocationRepository, _BaseRepository);

	  function LocationRepository() {
	    var _this;

	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, LocationRepository);
	    props.path = props.path || 'location.api.location';
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(LocationRepository).call(this, props));

	    _convertLocation.add(babelHelpers.assertThisInitialized(_this));

	    _convertCollection.add(babelHelpers.assertThisInitialized(_this));

	    return _this;
	  }

	  babelHelpers.createClass(LocationRepository, [{
	    key: "findParents",
	    value: function findParents(location) {
	      if (!(location instanceof Location)) {
	        throw new TypeError('location must be type of Location');
	      }

	      return this.actionRunner.run('findParents', {
	        location: LocationObjectConverter.convertLocationToObject(location)
	      }).then(this.processResponse.bind(this)).then(_classPrivateMethodGet(this, _convertCollection, _convertCollection2).bind(this));
	    }
	  }, {
	    key: "findByExternalId",
	    value: function findByExternalId(externalId, sourceCode, languageId) {
	      if (!externalId || !sourceCode || !languageId) {
	        throw new Error('externalId and sourceCode and languageId must be defined');
	      }

	      return this.actionRunner.run('findByExternalId', {
	        externalId: externalId,
	        sourceCode: sourceCode,
	        languageId: languageId
	      }).then(this.processResponse.bind(this)).then(_classPrivateMethodGet(this, _convertLocation, _convertLocation2).bind(this));
	    }
	  }, {
	    key: "findById",
	    value: function findById(locationId, languageId) {
	      if (!locationId || !languageId) {
	        throw new Error('locationId and languageId must be defined');
	      }

	      return this.actionRunner.run('findById', {
	        id: locationId,
	        languageId: languageId
	      }).then(this.processResponse.bind(this)).then(_classPrivateMethodGet(this, _convertLocation, _convertLocation2).bind(this));
	    }
	  }]);
	  return LocationRepository;
	}(BaseRepository);

	var _convertCollection2 = function _convertCollection2(collectionJsonData) {
	  var _this2 = this;

	  if (!Array.isArray(collectionJsonData)) {
	    throw new Error('Can\'t convert location collection data');
	  }

	  var result = [];
	  collectionJsonData.forEach(function (location) {
	    result.push(_classPrivateMethodGet(_this2, _convertLocation, _convertLocation2).call(_this2, location));
	  });
	  return result;
	};

	var _convertLocation2 = function _convertLocation2(locationData) {
	  if (!locationData) {
	    return null;
	  }

	  if (babelHelpers.typeof(locationData) !== 'object') {
	    throw new Error('Can\'t convert location data');
	  }

	  return LocationJsonConverter.convertJsonToLocation(locationData);
	};

	var AddressRepository = /*#__PURE__*/function (_BaseRepository) {
	  babelHelpers.inherits(AddressRepository, _BaseRepository);

	  function AddressRepository() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, AddressRepository);
	    props.path = 'location.api.address';
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AddressRepository).call(this, props));
	  }

	  babelHelpers.createClass(AddressRepository, [{
	    key: "findById",
	    value: function findById(addressId) {
	      var _this = this;

	      if (addressId <= 0) {
	        throw new Error('addressId must be more than zero');
	      }

	      return this.actionRunner.run('findById', {
	        addressId: addressId
	      }).then(this.processResponse).then(function (address) {
	        // address json data or null
	        var result = null;

	        if (address) {
	          result = _this.convertJsonToAddress(address);
	        }

	        return result;
	      });
	    }
	  }, {
	    key: "save",
	    value: function save(address) {
	      var _this2 = this;

	      if (!address) {
	        throw new Error('address must be defined');
	      }

	      return this.actionRunner.run('save', {
	        address: address
	      }).then(this.processResponse).then(function (response) {
	        //Address json data
	        var result = null;

	        if (babelHelpers.typeof(response) === 'object') {
	          result = _this2.convertJsonToAddress(response);
	        }

	        return result;
	      });
	    }
	  }, {
	    key: "convertJsonToAddress",
	    value: function convertJsonToAddress(jsonData) {
	      return new location_core.Address(jsonData);
	    }
	  }]);
	  return AddressRepository;
	}(BaseRepository);

	/**
	 * Class responsible for the addresses format obtaining.
	 */

	var FormatRepository = /*#__PURE__*/function (_BaseRepository) {
	  babelHelpers.inherits(FormatRepository, _BaseRepository);

	  function FormatRepository() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, FormatRepository);
	    props.path = 'location.api.format';
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FormatRepository).call(this, props));
	  }
	  /**
	   * Find all available formats
	   * @param {string} languageId
	   * @returns {Promise}
	   */


	  babelHelpers.createClass(FormatRepository, [{
	    key: "findAll",
	    value: function findAll(languageId) {
	      var _this = this;

	      if (!main_core.Type.isString(languageId)) {
	        throw new TypeError('languageId must be type of string');
	      }

	      return this.actionRunner.run('findAll', {
	        languageId: languageId
	      }).then(this.processResponse).then(function (data) {
	        return _this.convertFormatCollection(data);
	      });
	    }
	    /**
	     * Find address format by its code
	     * @param {string} formatCode
	     * @param {string} languageId
	     * @returns {Promise}
	     */

	  }, {
	    key: "findByCode",
	    value: function findByCode(formatCode, languageId) {
	      if (!main_core.Type.isString(formatCode)) {
	        throw new TypeError('formatCode must be type of string');
	      }

	      if (!main_core.Type.isString(languageId)) {
	        throw new TypeError('languageId must be type of string');
	      }

	      return this.actionRunner.run('findByCode', {
	        formatCode: formatCode,
	        languageId: languageId
	      }).then(this.processResponse).then(this.convertFormatData);
	    }
	    /**
	     * Find default address format
	     * @param {string} languageId
	     * @returns {Promise}
	     */

	  }, {
	    key: "findDefault",
	    value: function findDefault(languageId) {
	      if (!main_core.Type.isString(languageId)) {
	        throw new TypeError('languageId must be type of string');
	      }

	      return this.actionRunner.run('findDefault', {
	        languageId: languageId
	      }).then(this.processResponse).then(this.convertFormatData);
	    }
	  }, {
	    key: "convertFormatCollection",
	    value: function convertFormatCollection(formatDataCollection) {
	      var _this2 = this;

	      if (!main_core.Type.isArray(formatDataCollection)) {
	        throw new TypeError('Can\'t convert format collection data');
	      }

	      var result = [];
	      formatDataCollection.forEach(function (format) {
	        result.push(_this2.convertFormatData(format));
	      });
	      return result;
	    }
	  }, {
	    key: "convertFormatData",
	    value: function convertFormatData(formatData) {
	      if (!main_core.Type.isObject(formatData)) {
	        throw new TypeError('Can\'t convert format data');
	      }

	      return new Format$$1(formatData);
	    }
	  }]);
	  return FormatRepository;
	}(BaseRepository);

	var SourceRepository = /*#__PURE__*/function (_BaseRepository) {
	  babelHelpers.inherits(SourceRepository, _BaseRepository);

	  function SourceRepository() {
	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, SourceRepository);
	    props.path = 'location.api.source';
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SourceRepository).call(this, props));
	  }

	  babelHelpers.createClass(SourceRepository, [{
	    key: "getProps",
	    value: function getProps() {
	      return this.actionRunner.run('getProps', {}).then(this.processResponse);
	    }
	  }]);
	  return SourceRepository;
	}(BaseRepository);

	/**
	 * Base class for autocomplete source services
	 */

	var AutocompleteServiceBase = /*#__PURE__*/function () {
	  function AutocompleteServiceBase() {
	    babelHelpers.classCallCheck(this, AutocompleteServiceBase);
	  }

	  babelHelpers.createClass(AutocompleteServiceBase, [{
	    key: "autocomplete",
	    value: function autocomplete(text, params) {
	      throw new Error('Method autocomplete() Must be implemented');
	    }
	  }]);
	  return AutocompleteServiceBase;
	}();

	var PhotoServiceBase = /*#__PURE__*/function () {
	  function PhotoServiceBase() {
	    babelHelpers.classCallCheck(this, PhotoServiceBase);
	  }

	  babelHelpers.createClass(PhotoServiceBase, [{
	    key: "requestPhotos",
	    value: function requestPhotos(props) {
	      throw new Error('Must be implemented');
	    }
	  }]);
	  return PhotoServiceBase;
	}();

	/**
	 * Base class for source maps
	 */

	var MapBase = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(MapBase, _EventEmitter);

	  function MapBase() {
	    var _this;

	    babelHelpers.classCallCheck(this, MapBase);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MapBase).call(this));

	    _this.setEventNamespace('BX.Location.Core.MapBase');

	    return _this;
	  }

	  babelHelpers.createClass(MapBase, [{
	    key: "render",
	    value: function render(props) {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "onLocationChangedEventSubscribe",
	    value: function onLocationChangedEventSubscribe(listener) {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {}
	  }, {
	    key: "location",
	    set: function set(location) {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "mode",
	    set: function set(mode) {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "zoom",
	    set: function set(zoom) {
	      throw new Error('Must be implemented');
	    }
	  }]);
	  return MapBase;
	}(main_core_events.EventEmitter);

	/**
	 * Base class for the sources
	 */

	var SourceBase = /*#__PURE__*/function () {
	  function SourceBase() {
	    babelHelpers.classCallCheck(this, SourceBase);
	  }

	  babelHelpers.createClass(SourceBase, [{
	    key: "sourceCode",
	    get: function get() {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "map",
	    get: function get() {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "autocompleteService",
	    get: function get() {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "photoService",
	    get: function get() {
	      throw new Error('Must be implemented');
	    }
	  }, {
	    key: "geocodingService",
	    get: function get() {
	      throw new Error('Must be implemented');
	    }
	  }]);
	  return SourceBase;
	}();

	/**
	 * Base class for the source geocoding service
	 */

	var GeocodingServiceBase = /*#__PURE__*/function () {
	  function GeocodingServiceBase() {
	    babelHelpers.classCallCheck(this, GeocodingServiceBase);
	  }

	  babelHelpers.createClass(GeocodingServiceBase, [{
	    key: "geocode",
	    value: function geocode(addressString) {
	      throw new Error('Method geocode() must be implemented');
	    }
	  }]);
	  return GeocodingServiceBase;
	}();

	var ControlMode = /*#__PURE__*/function () {
	  function ControlMode() {
	    babelHelpers.classCallCheck(this, ControlMode);
	  }

	  babelHelpers.createClass(ControlMode, null, [{
	    key: "isValid",
	    value: function isValid(mode) {
	      return mode === ControlMode.edit || mode === ControlMode.view;
	    }
	  }, {
	    key: "edit",
	    get: function get() {
	      return 'edit';
	    }
	  }, {
	    key: "view",
	    get: function get() {
	      return 'view';
	    }
	  }]);
	  return ControlMode;
	}();

	var LocationFieldType = function LocationFieldType() {
	  babelHelpers.classCallCheck(this, LocationFieldType);
	};

	babelHelpers.defineProperty(LocationFieldType, "POSTAL_CODE", 50);
	babelHelpers.defineProperty(LocationFieldType, "ISO_3166_1_ALPHA_2", 1000);

	var SourceCreationError = /*#__PURE__*/function (_Error) {
	  babelHelpers.inherits(SourceCreationError, _Error);

	  function SourceCreationError() {
	    babelHelpers.classCallCheck(this, SourceCreationError);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SourceCreationError).apply(this, arguments));
	  }

	  return SourceCreationError;
	}( /*#__PURE__*/babelHelpers.wrapNativeSuper(Error));
	var MethodNotImplemented = /*#__PURE__*/function (_Error2) {
	  babelHelpers.inherits(MethodNotImplemented, _Error2);

	  function MethodNotImplemented() {
	    babelHelpers.classCallCheck(this, MethodNotImplemented);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MethodNotImplemented).apply(this, arguments));
	  }

	  return MethodNotImplemented;
	}( /*#__PURE__*/babelHelpers.wrapNativeSuper(Error));

	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } return value; }

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	var ErrorPublisher = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ErrorPublisher, _EventEmitter);
	  babelHelpers.createClass(ErrorPublisher, null, [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (_classStaticPrivateFieldSpecGet(ErrorPublisher, ErrorPublisher, _instance) === null) {
	        _classStaticPrivateFieldSpecSet(ErrorPublisher, ErrorPublisher, _instance, new ErrorPublisher());
	      }

	      return _classStaticPrivateFieldSpecGet(ErrorPublisher, ErrorPublisher, _instance);
	    }
	  }]);

	  function ErrorPublisher() {
	    var _this;

	    babelHelpers.classCallCheck(this, ErrorPublisher);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ErrorPublisher).call(this));

	    _this.setEventNamespace('BX.Location.Core.ErrorPublisher');

	    return _this;
	  }

	  babelHelpers.createClass(ErrorPublisher, [{
	    key: "notify",
	    value: function notify(errors) {
	      this.emit(_classStaticPrivateFieldSpecGet(ErrorPublisher, ErrorPublisher, _onErrorEvent), {
	        errors: errors
	      });
	    }
	  }, {
	    key: "subscribe",
	    value: function subscribe(listener) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(ErrorPublisher.prototype), "subscribe", this).call(this, _classStaticPrivateFieldSpecGet(ErrorPublisher, ErrorPublisher, _onErrorEvent), listener);
	    }
	  }]);
	  return ErrorPublisher;
	}(main_core_events.EventEmitter);

	var _instance = {
	  writable: true,
	  value: null
	};
	var _onErrorEvent = {
	  writable: true,
	  value: 'onError'
	};

	var Limit = /*#__PURE__*/function () {
	  function Limit() {
	    babelHelpers.classCallCheck(this, Limit);
	  }

	  babelHelpers.createClass(Limit, null, [{
	    key: "isAddressLimitReached",
	    value: function isAddressLimitReached() {
	      return BX.message('LOCATION_IS_ADDRESS_LIMIT_REACHED');
	    }
	  }]);
	  return Limit;
	}();

	var _latitude$2 = new WeakMap();

	var _longitude$2 = new WeakMap();

	/**
	 * Base class for the working with latitude and longitude
	 */
	var Point = /*#__PURE__*/function () {
	  /** {String} */

	  /** {String} */
	  function Point(latitude, longitude) {
	    babelHelpers.classCallCheck(this, Point);

	    _latitude$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _longitude$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _latitude$2, latitude);
	    babelHelpers.classPrivateFieldSet(this, _longitude$2, longitude);
	  }

	  babelHelpers.createClass(Point, [{
	    key: "toArray",
	    value: function toArray() {
	      return [this.latitude, this.longitude];
	    }
	  }, {
	    key: "latitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _latitude$2);
	    }
	  }, {
	    key: "longitude",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _longitude$2);
	    }
	  }]);
	  return Point;
	}();

	exports.Location = Location;
	exports.Address = Address;
	exports.Format = Format$$1;
	exports.AddressType = AddressType;
	exports.LocationType = LocationType;
	exports.LocationFieldType = LocationFieldType;
	exports.LocationRepository = LocationRepository;
	exports.AddressRepository = AddressRepository;
	exports.FormatRepository = FormatRepository;
	exports.SourceRepository = SourceRepository;
	exports.AddressStringConverter = StringConverter;
	exports.AutocompleteServiceBase = AutocompleteServiceBase;
	exports.PhotoServiceBase = PhotoServiceBase;
	exports.BaseSource = SourceBase;
	exports.MapBase = MapBase;
	exports.GeocodingServiceBase = GeocodingServiceBase;
	exports.ControlMode = ControlMode;
	exports.SourceCreationError = SourceCreationError;
	exports.MethodNotImplemented = MethodNotImplemented;
	exports.ErrorPublisher = ErrorPublisher;
	exports.Limit = Limit;
	exports.Point = Point;

}((this.BX.Location.Core = this.BX.Location.Core || {}),BX,BX.Location.Core,BX.Event));
//# sourceMappingURL=core.bundle.js.map
