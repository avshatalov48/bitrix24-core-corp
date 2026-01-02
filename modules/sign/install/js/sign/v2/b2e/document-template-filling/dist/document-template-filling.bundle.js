/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,ui_vue3,main_core,main_date,sign_type,sign_v2_b2e_signSettingsTemplates,ui_vue3_pinia,crm_router,sign_v2_b2e_vueUtil,ui_vue3_components_switcher,ui_switcher,ui_vue3_components_hint) {
	'use strict';

	// @vue/component
	var DocumentPreview = {
	  name: 'DocumentPreview',
	  props: {
	    previewSrc: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: false,
	      "default": ''
	    },
	    documentId: {
	      type: Number,
	      required: true
	    },
	    previewImageClass: {
	      type: String,
	      required: false,
	      "default": ''
	    },
	    isIconVisible: {
	      type: Boolean,
	      required: false,
	      "default": false
	    }
	  },
	  methods: {
	    previewDocument: function previewDocument() {
	      if (this.documentId < 1) {
	        return Promise.resolve();
	      }
	      return crm_router.Router.openSlider("/sign/b2e/preview/0/?documentId=".concat(this.documentId, "&noRedirect=Y"), {
	        width: 800,
	        cacheable: false
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"sign-b2e_document_preview_container\">\n\t\t\t<div v-if=\"isIconVisible\" @click=\"previewDocument()\" class=\"sign-b2e_document_preview_icon\"></div>\n\t\t\t<img v-if=\"previewSrc\" @click=\"previewDocument()\" :class=\"previewImageClass\" :src=\"previewSrc\" :alt=\"title\">\n\t\t</div>\t\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }

	// @vue/component
	var DocumentPreviewList = {
	  name: 'DocumentPreviewList',
	  components: {
	    DocumentPreview: DocumentPreview
	  },
	  mixins: [sign_v2_b2e_vueUtil.LocMixin],
	  props: {
	    documentList: {
	      type: Array,
	      required: true
	    },
	    maxPreviewCount: {
	      type: Number,
	      "default": 7
	    }
	  },
	  data: function data() {
	    return {
	      isShowMoreButtonVisible: true,
	      previewCount: 0
	    };
	  },
	  computed: {
	    showMoreCount: function showMoreCount() {
	      return this.documentCount - this.maxPreviewCount;
	    },
	    isShowMoreVisible: function isShowMoreVisible() {
	      return this.showMoreCount > 0;
	    },
	    documentCount: function documentCount() {
	      return this.documentList.length;
	    },
	    documentPreviewList: function documentPreviewList() {
	      var result = [];
	      var _iterator = _createForOfIteratorHelper(this.documentList),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var document = _step.value;
	          result.push(document);
	          if (result.length >= this.previewCount) {
	            break;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return result;
	    }
	  },
	  created: function created() {
	    this.previewCount = this.maxPreviewCount;
	  },
	  methods: {
	    onShowMoreClick: function onShowMoreClick() {
	      this.previewCount = this.documentCount;
	      this.isShowMoreButtonVisible = false;
	    },
	    onShowLessClick: function onShowLessClick() {
	      this.previewCount = this.maxPreviewCount;
	      this.isShowMoreButtonVisible = true;
	    }
	  },
	  template: "\n\t\t<div class=\"sign-b2e-settings__item sign-b2e-regional-settings_apply_for_all_list\">\n\t\t\t<template v-for=\"document in documentPreviewList\">\n\t\t\t\t<div class=\"sign-b2e-regional-settings_apply_for_all_settings_preview_block\">\n\t\t\t\t\t<DocumentPreview\n\t\t\t\t\t\t:document-id=\"document?.documentId\"\n\t\t\t\t\t\t:preview-src=\"document?.previewUrl\"\n\t\t\t\t\t\t:is-icon-visible=\"true\"\n\t\t\t\t\t\tpreview-image-class=\"sign-b2e-regional-settings_apply_for_all_settings_preview\">\n\t\t\t\t\t</DocumentPreview>\n\t\t\t\t\t<div class=\"sign-b2e-regional-settings_apply_for_all_settings_preview_title\">\n\t\t\t\t\t\t{{ document.title }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<div v-if=\"isShowMoreVisible\" class=\"sign-b2e-regional-settings_apply_for_all_settings_preview_show_more_less\">\n\t\t\t\t<span v-if=\"isShowMoreButtonVisible\" @click=\"onShowMoreClick\" class=\"sign-b2e-regional-settings_apply_for_all_settings_more_less_button\">\n\t\t\t\t\t{{ loc('SIGN_V2_B2E_DOCUMENT_PREVIEW_LIST_MORE_TITLE', { '#COUNT#': String(showMoreCount) }) }}\n\t\t\t\t</span>\n\t\t\t\t<span v-else @click=\"onShowLessClick\" class=\"sign-b2e-regional-settings_apply_for_all_settings_more_less_button\">\n\t\t\t\t\t{{ loc('SIGN_V2_B2E_DOCUMENT_PREVIEW_LIST_LESS_TITLE') }}\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	// @vue/component
	var Switcher = {
	  // eslint-disable-next-line vue/multi-word-component-names
	  name: 'Switcher',
	  components: {
	    UiSwitcher: ui_vue3_components_switcher.Switcher,
	    Hint: ui_vue3_components_hint.Hint
	  },
	  props: {
	    modelValue: {
	      type: Boolean,
	      "default": false
	    },
	    title: {
	      type: String,
	      required: false,
	      "default": ''
	    },
	    hint: {
	      type: String,
	      required: false,
	      "default": ''
	    },
	    isEnabled: {
	      type: Boolean,
	      "default": true
	    }
	  },
	  emits: ['update:modelValue'],
	  data: function data() {
	    return {
	      isChecked: false,
	      switcherSize: ui_switcher.SwitcherSize.extraSmall
	    };
	  },
	  watch: {
	    modelValue: function modelValue(newValue) {
	      this.isChecked = newValue;
	    }
	  },
	  mounted: function mounted() {
	    this.isChecked = this.isEnabled && this.modelValue;
	  },
	  methods: {
	    setValue: function setValue(value) {
	      if (this.isEnabled === false) {
	        return;
	      }
	      this.isChecked = value;
	      this.$emit('update:modelValue', value);
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<UiSwitcher class=\"sign-b2e_switcher\"\n\t\t\t\t\t  :isChecked=\"isChecked\"\n\t\t\t\t\t  :options=\"{ size: switcherSize }\"\n\t\t\t\t\t  @check=\"setValue(true)\" \n\t\t\t\t\t  @uncheck=\"setValue(false)\"\n\t\t\t/>\n\t\t\t<span @click=\"setValue(!isChecked)\" class=\"sign-b2e-settings__item_title sign-b2e_switcher__title\">{{ title }}</span>\n\t\t\t<Hint v-if=\"hint\" class=\"sign-b2e_switcher__hint\" :text=\"hint\"/>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _regeneratorRuntime() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == babelHelpers["typeof"](value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ExternalSourceType = {
	  HCMLINK: 'hcmlink',
	  MANUAL: 'manual'
	};
	var defaultSigningDateMonth = 3;

	// @vue/component
	var DocumentFillingApp = {
	  name: 'DocumentFillingApp',
	  components: {
	    DateSelector: sign_v2_b2e_vueUtil.DateSelector,
	    DocumentPreview: DocumentPreview,
	    Switcher: Switcher,
	    DocumentPreviewList: DocumentPreviewList
	  },
	  mixins: [sign_v2_b2e_vueUtil.LocMixin],
	  data: function data() {
	    return {
	      isApplySettingsForAll: false,
	      commonSettings: null,
	      documentsSettings: {},
	      signUntilDateErrorByDoc: {},
	      signUntilDateErrorCommon: '',
	      isValid: true,
	      isValidMap: {}
	    };
	  },
	  computed: {
	    documents: function documents() {
	      return ui_vue3_pinia.mapState(sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore, ['documents']).documents();
	    },
	    ruRegionFieldsVisible: function ruRegionFieldsVisible() {
	      return ui_vue3_pinia.mapState(sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore, ['ruRegionFieldsVisible']).ruRegionFieldsVisible();
	    },
	    documentPreviewList: function documentPreviewList() {
	      var _this = this;
	      return this.documents.map(function (doc) {
	        return {
	          uid: doc.uid,
	          documentId: _this.getSmartDocumentId(doc),
	          previewUrl: _this.getPreviewUrl(doc),
	          title: doc.title
	        };
	      });
	    },
	    signingMinMinutes: function signingMinMinutes() {
	      var settings = main_core.Extension.getSettings('sign.v2.b2e.document-template-filling');
	      return settings.get('signingMinMinutes', 5);
	    },
	    signingMaxMonth: function signingMaxMonth() {
	      var settings = main_core.Extension.getSettings('sign.v2.b2e.document-template-filling');
	      return settings.get('signingMaxMonth', 3);
	    },
	    switcherAllHint: function switcherAllHint() {
	      return this.ruRegionFieldsVisible ? this.loc('SIGN_B2E_DOCUMENT_FILLING_APPLY_FOR_ALL_HINT') : '';
	    }
	  },
	  watch: {
	    documents: {
	      immediate: true,
	      handler: function handler(newDocs) {
	        var _this2 = this;
	        var newSettings = {};
	        newDocs.forEach(function (doc) {
	          var defaultRegistrationNumber = _this2.getDefaultRegistrationNumber(doc);
	          newSettings[doc.uid] = _objectSpread(_objectSpread({}, _this2.makeDefaultDocumentSettings(doc)), {}, {
	            registrationNumber: defaultRegistrationNumber
	          });
	        });
	        this.documentsSettings = newSettings;
	      }
	    },
	    isApplySettingsForAll: function isApplySettingsForAll(newVal) {
	      if (newVal) {
	        this.signUntilDateErrorByDoc = {};
	        this.signUntilDateErrorCommon = '';
	        this.isValid = true;
	        this.isValidMap = {};
	        this.setStorageSettingsFromCommon();
	      }
	    },
	    documentsSettings: {
	      handler: function handler(newValue) {
	        sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore().setSettings(newValue);
	      },
	      immediate: true,
	      deep: true
	    },
	    commonSettings: {
	      handler: function handler() {
	        if (this.isApplySettingsForAll) {
	          this.setStorageSettingsFromCommon();
	        }
	      },
	      deep: true
	    }
	  },
	  created: function created() {
	    this.commonSettings = this.makeDefaultDocumentSettings();
	  },
	  methods: {
	    removeDocument: function removeDocument(uid) {
	      sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore().removeDocument(uid);
	    },
	    selectCreationDate: function selectCreationDate(uid, date) {
	      if (this.documentsSettings[uid]) {
	        this.documentsSettings[uid].creationDate = date;
	      }
	    },
	    selectSigningDate: function selectSigningDate(uid, date) {
	      var _this3 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
	        var isValid;
	        return _regeneratorRuntime().wrap(function _callee$(_context) {
	          while (1) switch (_context.prev = _context.next) {
	            case 0:
	              isValid = _this3.validateDateSignUntil(date, uid);
	              if (isValid) {
	                _context.next = 3;
	                break;
	              }
	              return _context.abrupt("return");
	            case 3:
	              if (_this3.documentsSettings[uid]) {
	                _this3.documentsSettings[uid].signingDate = _this3.convertFromUTCtoUserDate(date);
	              }
	            case 4:
	            case "end":
	              return _context.stop();
	          }
	        }, _callee);
	      }))();
	    },
	    selectCreationDateForAll: function selectCreationDateForAll(date) {
	      this.commonSettings.creationDate = date;
	    },
	    selectSigningDateForAll: function selectSigningDateForAll(date) {
	      var _this4 = this;
	      return babelHelpers.asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
	        var isValid;
	        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
	          while (1) switch (_context2.prev = _context2.next) {
	            case 0:
	              isValid = _this4.validateDateSignUntil(date);
	              if (isValid) {
	                _context2.next = 3;
	                break;
	              }
	              return _context2.abrupt("return");
	            case 3:
	              _this4.commonSettings.signingDate = _this4.convertFromUTCtoUserDate(date);
	            case 4:
	            case "end":
	              return _context2.stop();
	          }
	        }, _callee2);
	      }))();
	    },
	    getSmartDocumentId: function getSmartDocumentId(doc) {
	      var _doc$id;
	      return (_doc$id = doc === null || doc === void 0 ? void 0 : doc.id) !== null && _doc$id !== void 0 ? _doc$id : 0;
	    },
	    getPreviewUrl: function getPreviewUrl(doc) {
	      var _doc$previewUrl;
	      return (_doc$previewUrl = doc === null || doc === void 0 ? void 0 : doc.previewUrl) !== null && _doc$previewUrl !== void 0 ? _doc$previewUrl : '';
	    },
	    getCurrentSettings: function getCurrentSettings(uid) {
	      return this.isApplySettingsForAll ? this.commonSettings : this.documentsSettings[uid];
	    },
	    isExternalIdFromIntegration: function isExternalIdFromIntegration(doc) {
	      return doc.externalIdSourceType === ExternalSourceType.HCMLINK;
	    },
	    isExternalDateFromIntegration: function isExternalDateFromIntegration(doc) {
	      return doc.externalDateCreateSourceType === ExternalSourceType.HCMLINK;
	    },
	    areAllExternalIdsFromIntegration: function areAllExternalIdsFromIntegration() {
	      return this.documents.every(function (doc) {
	        return doc.externalIdSourceType === ExternalSourceType.HCMLINK;
	      });
	    },
	    areAllExternalDatesFromIntegration: function areAllExternalDatesFromIntegration() {
	      return this.documents.every(function (doc) {
	        return doc.externalDateCreateSourceType === ExternalSourceType.HCMLINK;
	      });
	    },
	    validateDateSignUntil: function validateDateSignUntil(date, uid) {
	      if (uid) {
	        this.signUntilDateErrorByDoc[uid] = '';
	      } else {
	        this.signUntilDateErrorCommon = '';
	      }
	      var creationDateSource = uid ? this.documentsSettings[uid].creationDate : this.commonSettings.creationDate;
	      var validationErrorMessage = '';
	      if (!creationDateSource) {
	        console.error('Creation date is not available');
	        return false;
	      }
	      var creationDate = new Date(creationDateSource);
	      var selectedUserDate = this.convertFromUTCtoUserDate(date);
	      var minValidDateTime = new Date(creationDate.getTime());
	      minValidDateTime.setMinutes(minValidDateTime.getMinutes() + this.signingMinMinutes);
	      if (selectedUserDate.getTime() < minValidDateTime.getTime()) {
	        validationErrorMessage = main_core.Loc.getMessagePlural('PERIOD_TOO_SHORT', this.signingMinMinutes, {
	          '#MIN_PERIOD#': this.signingMinMinutes
	        });
	      } else {
	        var maxValidDate = new Date(creationDate.getTime());
	        maxValidDate.setMonth(maxValidDate.getMonth() + this.signingMaxMonth);
	        if (selectedUserDate.getTime() > maxValidDate.getTime()) {
	          validationErrorMessage = main_core.Loc.getMessagePlural('PERIOD_TOO_LONG', this.signingMaxMonth, {
	            '#MAX_PERIOD#': this.signingMaxMonth
	          });
	        }
	      }
	      if (validationErrorMessage) {
	        if (this.isApplySettingsForAll) {
	          this.signUntilDateErrorCommon = validationErrorMessage;
	        } else {
	          this.signUntilDateErrorByDoc[uid] = validationErrorMessage;
	        }
	        return false;
	      }
	      return true;
	    },
	    makeDefaultDocumentSettings: function makeDefaultDocumentSettings(doc) {
	      return {
	        registrationNumber: this.getDefaultRegistrationNumber(),
	        creationDate: main_date.Timezone.UserTime.getDate(),
	        signingDate: doc && doc.dateSignUntilUserTime ? new Date(doc.dateSignUntilUserTime) : this.getFallbackSignigningDate()
	      };
	    },
	    getFallbackSignigningDate: function getFallbackSignigningDate() {
	      var signingDate = main_date.Timezone.UserTime.getDate();
	      signingDate.setMonth(signingDate.getMonth() + defaultSigningDateMonth);
	      return signingDate;
	    },
	    getDefaultRegistrationNumber: function getDefaultRegistrationNumber(doc) {
	      var shouldUseHcmLinkPlaceholder = doc ? this.isExternalIdFromIntegration(doc) : this.areAllExternalIdsFromIntegration();
	      return this.loc(shouldUseHcmLinkPlaceholder ? 'SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_PLACEHOLDER_HCMLINK' : 'SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_PLACEHOLDER');
	    },
	    setStorageSettingsFromCommon: function setStorageSettingsFromCommon() {
	      var _this5 = this;
	      var newSettings = {};
	      this.documents.forEach(function (doc) {
	        newSettings[doc.uid] = {
	          registrationNumber: _this5.commonSettings.registrationNumber,
	          signingDate: _this5.commonSettings.signingDate,
	          creationDate: _this5.commonSettings.creationDate
	        };
	      });
	      sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore().setSettings(newSettings);
	    },
	    validate: function validate() {
	      if (this.isApplySettingsForAll) {
	        var value = this.commonSettings.registrationNumber;
	        this.isValid = Boolean(value) && value.trim() !== '';
	        return this.isValid;
	      }
	      this.isValid = true;
	      this.isValidMap = {};
	      var _iterator = _createForOfIteratorHelper$1(this.documents),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _this$documentsSettin;
	          var doc = _step.value;
	          var _value = (_this$documentsSettin = this.documentsSettings[doc.uid]) === null || _this$documentsSettin === void 0 ? void 0 : _this$documentsSettin.registrationNumber;
	          var isFilled = Boolean(_value) && _value.trim() !== '';
	          this.isValidMap[doc.uid] = isFilled;
	          if (!isFilled) {
	            this.isValid = false;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      return this.isValid;
	    },
	    convertFromUTCtoUserDate: function convertFromUTCtoUserDate(dateInUTC) {
	      var year = dateInUTC.getUTCFullYear();
	      var month = dateInUTC.getUTCMonth();
	      var day = dateInUTC.getUTCDate();
	      var hours = dateInUTC.getUTCHours();
	      var minutes = dateInUTC.getUTCMinutes();
	      return new Date(year, month, day, hours, minutes, 0, 0);
	    },
	    isGosKeyProvider: function isGosKeyProvider(doc) {
	      if (doc) {
	        return doc.providerCode === sign_type.ProviderCode.goskey;
	      }
	      return this.documents.some(function (item) {
	        return item.providerCode === sign_type.ProviderCode.goskey;
	      });
	    }
	  },
	  template: "\n\t\t<h1 class=\"sign-b2e-settings__header\">\n\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_TITLE_HEAD_LABEL') }}\n\t\t</h1>\n\n\t\t<template v-if=\"documents.length > 1\">\n\t\t\t<div class=\"sign-b2e-document-filling__item\">\n\t\t\t\t<Switcher\n\t\t\t\t\tv-model=\"isApplySettingsForAll\"\n\t\t\t\t\t:title=\"loc('SIGN_B2E_DOCUMENT_FILLING_APPLY_FOR_ALL_TITLE')\"\n\t\t\t\t\t:hint=\"switcherAllHint\"\n\t\t\t\t/>\n\n\t\t\t\t<template v-if=\"isApplySettingsForAll\">\n\t\t\t\t\t<div class=\"sign-b2e-document-filling__item-text\">\n\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DOCUMENT_IN_PROCESS') }}\n\t\t\t\t\t</div>\n\t\t\t\t\t<DocumentPreviewList\n\t\t\t\t\t\t:document-list=\"documentPreviewList\"\n\t\t\t\t\t\t:max-preview-count=\"7\"\n\t\t\t\t\t/>\n\t\t\t\t\t<div class=\"sign-b2e-document-filling__item-content\">\n\t\t\t\t\t\t<div v-if=\"ruRegionFieldsVisible\">\n\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text\">\n\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_LABEL') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\tv-model=\"commonSettings.registrationNumber\"\n\t\t\t\t\t\t\t\t:title=\"areAllExternalIdsFromIntegration()\n\t\t\t\t\t\t\t\t\t? loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_HCMLINK_HINT')\n\t\t\t\t\t\t\t\t\t: ''\"\n\t\t\t\t\t\t\t\t:disabled=\"areAllExternalIdsFromIntegration()\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element sign-b2e-document-filling-reg-number-input\"\n\t\t\t\t\t\t\t\t:class=\"{ '--error': isApplySettingsForAll && !isValid }\"\n\t\t\t\t\t\t\t\tmaxlength=\"255\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-row\">\n\t\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-item\" v-if=\"ruRegionFieldsVisible\">\n\t\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text-date\">\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_LABEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tv-if=\"areAllExternalDatesFromIntegration()\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector-text\"\n\t\t\t\t\t\t\t\t\t:title=\"areAllExternalDatesFromIntegration() \n\t\t\t\t\t\t\t\t\t? loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK_HINT')\n\t\t\t\t\t\t\t\t\t: ''\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<DateSelector\n\t\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t\t\t:value=\"commonSettings.creationDate\"\n\t\t\t\t\t\t\t\t\t@onSelect=\"date => selectCreationDateForAll(date)\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-item\">\n\t\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text-date\">\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_EXPIRE_LABEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<DateSelector\n\t\t\t\t\t\t\t\t\t:value=\"commonSettings.signingDate\"\n\t\t\t\t\t\t\t\t\tshowTime\n\t\t\t\t\t\t\t\t\t@onSelect=\"date => selectSigningDateForAll(date)\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector\"\n\t\t\t\t\t\t\t\t\t:class=\"{ '--error': signUntilDateErrorCommon }\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-if=\"signUntilDateErrorCommon\" class=\"sign-b2e-settings__date-error\">\n\t\t\t\t\t\t\t\t{{ signUntilDateErrorCommon }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<p v-if=\"isGosKeyProvider()\" class=\"sign-b2e-document-filling__notice\">\n\t\t\t\t\t\t\t{{ loc('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_GOSKEY_ALERT') }}\n\t\t\t\t\t\t</p>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</template>\n\n\t\t<template v-if=\"!isApplySettingsForAll\">\n\t\t\t<div\n\t\t\t\tv-for=\"doc in documents\"\n\t\t\t\t:key=\"doc.uid\"\n\t\t\t\tclass=\"sign-b2e-document-filling__item\"\n\t\t\t>\n\t\t\t\t<div class=\"sign-b2e-document-filling__item-container\">\n\t\t\t\t\t<div class=\"sign-b2e-document-filling__item-preview\">\n\t\t\t\t\t\t<DocumentPreview\n\t\t\t\t\t\t\t:document-id=\"getSmartDocumentId(doc)\"\n\t\t\t\t\t\t\t:preview-src=\"getPreviewUrl(doc)\"\n\t\t\t\t\t\t\t:is-icon-visible=\"true\"\n\t\t\t\t\t\t\tclass=\"sign-b2e_document_preview_container\"\n\t\t\t\t\t\t\tpreview-image-class=\"sign-b2e_document_preview_image sign-b2e-document-filling__preview\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"sign-b2e-document-filling__item-content\">\n\t\t\t\t\t\t<div class=\"sign-b2e-document-filling__header\">\n\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-title\">\n\t\t\t\t\t\t\t\t{{ doc.title }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\tv-if=\"documents.length > 1\"\n\t\t\t\t\t\t\t\tclass=\"sign-b2e-document-filling__remove-btn\"\n\t\t\t\t\t\t\t\t@click=\"removeDocument(doc.uid)\"\n\t\t\t\t\t\t\t\t:title=\"loc('SIGN_B2E_DOCUMENT_FILLING_REMOVE_TEMPLATE')\"\n\t\t\t\t\t\t\t></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"ruRegionFieldsVisible\">\n\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text\">\n\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_LABEL') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\tv-model=\"documentsSettings[doc.uid].registrationNumber\"\n\t\t\t\t\t\t\t\t:title=\"isExternalIdFromIntegration(doc) \n\t\t\t\t\t\t\t\t? loc('SIGN_B2E_DOCUMENT_FILLING_INPUT_REG_NUMBER_HCMLINK_HINT')\n\t\t\t\t\t\t\t\t: ''\"\n\t\t\t\t\t\t\t\t:disabled=\"isExternalIdFromIntegration(doc)\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element sign-b2e-document-filling-reg-number-input\"\n\t\t\t\t\t\t\t\t:class=\"{ '--error': !isApplySettingsForAll && isValidMap[doc.uid] === false }\"\n\t\t\t\t\t\t\t\tmaxlength=\"255\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-row\">\n\t\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-item\" v-if=\"ruRegionFieldsVisible\">\n\t\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text-date\">\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_LABEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t\tv-if=\"isExternalDateFromIntegration(doc)\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector-text\"\n\t\t\t\t\t\t\t\t\t:title=\"isExternalIdFromIntegration(doc) \n\t\t\t\t\t\t\t\t\t? loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK_HINT')\n\t\t\t\t\t\t\t\t\t: ''\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_CREATE_HCMLINK') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<DateSelector\n\t\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t\t\t:value=\"documentsSettings[doc.uid].creationDate\"\n\t\t\t\t\t\t\t\t\t@onSelect=\"date => selectCreationDate(doc.uid, date)\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"sign-b2e-settings__date-item\">\n\t\t\t\t\t\t\t\t<span class=\"sign-b2e-document-filling__item-text-date\">\n\t\t\t\t\t\t\t\t\t{{ loc('SIGN_B2E_DOCUMENT_FILLING_DATE_EXPIRE_LABEL') }}\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<DateSelector\n\t\t\t\t\t\t\t\t\t:value=\"documentsSettings[doc.uid].signingDate\"\n\t\t\t\t\t\t\t\t\tshowTime\n\t\t\t\t\t\t\t\t\t@onSelect=\"date => selectSigningDate(doc.uid, date)\"\n\t\t\t\t\t\t\t\t\t:class=\"{ '--error': signUntilDateErrorByDoc[doc.uid] }\"\n\t\t\t\t\t\t\t\t\tclass=\"sign-b2e-settings__date-selector\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<p v-if=\"isGosKeyProvider(doc)\" class=\"sign-b2e-document-filling__notice\">\n\t\t\t\t\t\t\t{{ loc('SIGN_DOCUMENT_SEND_DATETIME_LIMIT_SELECTOR_GOSKEY_ALERT') }}\n\t\t\t\t\t\t</p>\n\t\t\t\t\t\t<div v-if=\"signUntilDateErrorByDoc[doc.uid]\" class=\"sign-b2e-settings__date-error --individual\">\n\t\t\t\t\t\t\t{{ signUntilDateErrorByDoc[doc.uid] }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</template>\n\t"
	};

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _app = /*#__PURE__*/new WeakMap();
	var _vueApp = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var _options = /*#__PURE__*/new WeakMap();
	var _createApp = /*#__PURE__*/new WeakSet();
	var DocumentTemplateFilling = /*#__PURE__*/function () {
	  function DocumentTemplateFilling(documentFillingOptions) {
	    babelHelpers.classCallCheck(this, DocumentTemplateFilling);
	    _classPrivateMethodInitSpec(this, _createApp);
	    _classPrivateFieldInitSpec(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _vueApp, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _options, documentFillingOptions);
	  }
	  babelHelpers.createClass(DocumentTemplateFilling, [{
	    key: "getLayout",
	    value: function getLayout() {
	      var _BX$UI;
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        return babelHelpers.classPrivateFieldGet(this, _container);
	      }
	      babelHelpers.classPrivateFieldSet(this, _container, BX.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div></div>"]))));
	      _classPrivateMethodGet(this, _createApp, _createApp2).call(this, babelHelpers.classPrivateFieldGet(this, _container));
	      if ((_BX$UI = BX.UI) !== null && _BX$UI !== void 0 && _BX$UI.Hint) {
	        BX.UI.Hint.init(babelHelpers.classPrivateFieldGet(this, _container));
	      }
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "validate",
	    value: function validate() {
	      return babelHelpers.classPrivateFieldGet(this, _vueApp).validate();
	    }
	  }, {
	    key: "unmount",
	    value: function unmount() {
	      var _babelHelpers$classPr;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _app)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.unmount();
	    }
	  }]);
	  return DocumentTemplateFilling;
	}();
	function _createApp2(container) {
	  babelHelpers.classPrivateFieldSet(this, _app, ui_vue3.BitrixVue.createApp(DocumentFillingApp));
	  babelHelpers.classPrivateFieldGet(this, _app).use(babelHelpers.classPrivateFieldGet(this, _options).store);
	  babelHelpers.classPrivateFieldSet(this, _vueApp, babelHelpers.classPrivateFieldGet(this, _app).mount(container));
	}

	exports.DocumentTemplateFilling = DocumentTemplateFilling;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Vue3,BX,BX.Main,BX.Sign,BX.Sign.V2.B2e,BX.Vue3.Pinia,BX.Crm,BX.Sign.V2.B2e,BX.UI.Vue3.Components,BX.UI,BX.Vue3.Components));
