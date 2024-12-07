/**
 * Attention!
 * This file is generated automatically from the extension `ui.bbcode.formatter`.
 * Any manual changes to this file are not allowed.
 *
 * If you need to make some changes, then make edits to the `ui.bbcode.formatter`
 * and run the build using 'bitrix build'.
 * During the build, the code will be automatically ported to `bbcode/formatter'.
 */

/** @module bbcode/formatter */
jn.define('bbcode/formatter', (require, exports, module) => {
    const Model = require('bbcode/model');
    const { BBCodeParser } = require('bbcode/parser');
    const { Type } = require('type');

	const nameSymbol = Symbol('name');
	const groupSymbol = Symbol('group');
	const validateSymbol = Symbol('validate');
	const beforeSymbol = Symbol('before');
	const convertSymbol = Symbol('convert');
	const forChildSymbol = Symbol('forChild');
	const afterSymbol = Symbol('after');
	const formatterSymbol = Symbol('formatter');
	const defaultValidator = () => true;
	const defaultNodeConverter = ({
	  node
	}) => node;
	const defaultElementConverter = ({
	  element
	}) => element;
	class NodeFormatter {
	  constructor(options = {}) {
	    this[nameSymbol] = 'unknown';
	    this[groupSymbol] = null;
	    this[beforeSymbol] = null;
	    this[convertSymbol] = null;
	    this[forChildSymbol] = null;
	    this[afterSymbol] = null;
	    if (Type.isArray(options.name)) {
	      this[groupSymbol] = [...options.name];
	    } else {
	      this.setName(options.name);
	    }
	    if (!Type.isNil(options.formatter)) {
	      this.setFormatter(options.formatter);
	    }
	    this.setValidate(options.validate);
	    this.setBefore(options.before);
	    this.setConvert(options.convert);
	    this.setForChild(options.forChild);
	    this.setAfter(options.after);
	  }
	  setName(name) {
	    if (!Type.isStringFilled(name)) {
	      throw new TypeError('Name is not a string');
	    }
	    this[nameSymbol] = name;
	  }
	  getName() {
	    return this[nameSymbol];
	  }
	  setValidate(callback) {
	    if (Type.isFunction(callback)) {
	      this[validateSymbol] = callback;
	    } else {
	      this[validateSymbol] = defaultValidator;
	    }
	  }
	  validate(options) {
	    const result = this[validateSymbol](options);
	    if (Type.isBoolean(result)) {
	      return result;
	    }
	    throw new TypeError(`Validate callback for "${this.getName()}" returned not boolean`);
	  }
	  setBefore(callback) {
	    if (Type.isFunction(callback)) {
	      this[beforeSymbol] = callback;
	    } else {
	      this[beforeSymbol] = defaultNodeConverter;
	    }
	  }
	  runBefore(options) {
	    return this[beforeSymbol](options);
	  }
	  setConvert(callback) {
	    if (!Type.isFunction(callback)) {
	      throw new TypeError('Convert is not a function');
	    }
	    this[convertSymbol] = callback;
	  }
	  runConvert(options) {
	    return this[convertSymbol](options);
	  }
	  setForChild(callback) {
	    if (Type.isFunction(callback)) {
	      this[forChildSymbol] = callback;
	    } else {
	      this[forChildSymbol] = defaultElementConverter;
	    }
	  }
	  runForChild(options) {
	    return this[forChildSymbol](options);
	  }
	  setAfter(callback) {
	    if (Type.isFunction(callback)) {
	      this[afterSymbol] = callback;
	    } else {
	      this[afterSymbol] = defaultElementConverter;
	    }
	  }
	  runAfter(options) {
	    return this[afterSymbol](options);
	  }
	  setFormatter(formatter) {
	    this[formatterSymbol] = formatter;
	  }
	  getFormatter() {
	    return this[formatterSymbol];
	  }
	}

	const formattersSymbol = Symbol('formatters');
	const onUnknownSymbol = Symbol('onUnknown');
	const dataSymbol = Symbol('data');

	/**
	 * @memberOf BX.UI.BBCode
	 */
	class Formatter {
	  constructor(options = {}) {
	    this[formattersSymbol] = new Map();
	    this[onUnknownSymbol] = null;
	    this[dataSymbol] = null;
	    this.setNodeFormatters(options.formatters);
	    if (Type.isNil(options.onUnknown)) {
	      this.setOnUnknown(this.getDefaultUnknownNodeCallback());
	    } else {
	      this.setOnUnknown(options.onUnknown);
	    }
	  }
	  isElement(source) {
	    return Type.isObject(source) && Type.isFunction(source.appendChild);
	  }
	  static prepareSourceNode(source) {
	    if (source instanceof Model.BBCodeNode) {
	      return source;
	    }
	    if (Type.isString(source)) {
	      return new BBCodeParser().parse(source);
	    }
	    return null;
	  }
	  setData(data) {
	    this[dataSymbol] = data;
	  }
	  getData() {
	    return this[dataSymbol];
	  }
	  setNodeFormatters(formatters) {
	    if (Type.isArrayFilled(formatters)) {
	      formatters.forEach(formatter => {
	        this.setNodeFormatter(formatter);
	      });
	    }
	  }
	  setNodeFormatter(formatter) {
	    if (formatter instanceof NodeFormatter) {
	      this[formattersSymbol].set(formatter.getName(), formatter);
	    } else {
	      throw new TypeError('formatter is not a NodeFormatter instance.');
	    }
	  }
	  getDefaultUnknownNodeCallback() {
	    throw new TypeError('Must be implemented in subclass');
	  }
	  setOnUnknown(callback) {
	    if (Type.isFunction(callback)) {
	      this[onUnknownSymbol] = callback;
	    } else {
	      throw new TypeError('OnUnknown callback is not a function.');
	    }
	  }
	  runOnUnknown(options) {
	    const result = this[onUnknownSymbol](options);
	    if (result instanceof NodeFormatter || Type.isNull(result)) {
	      return result;
	    }
	    throw new TypeError('OnUnknown callback returned not NodeFormatter instance or null.');
	  }
	  getNodeFormatter(node) {
	    const formatter = this[formattersSymbol].get(node.getName());
	    if (formatter instanceof NodeFormatter) {
	      return formatter;
	    }
	    return this.runOnUnknown({
	      node,
	      formatter: this
	    });
	  }
	  getNodeFormatters() {
	    return this[formattersSymbol];
	  }
	  format(options) {
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    const {
	      source,
	      data = {}
	    } = options;
	    if (!Type.isUndefined(data) && !Type.isPlainObject(data)) {
	      throw new TypeError('options.data is not a object');
	    }
	    this.setData(data);
	    const sourceNode = Formatter.prepareSourceNode(source);
	    if (Type.isNull(sourceNode)) {
	      throw new TypeError('options.source is not a BBCodeNode or string');
	    }
	    const nodeFormatter = this.getNodeFormatter(sourceNode);
	    const isValidNode = nodeFormatter.validate({
	      node: sourceNode,
	      formatter: this,
	      data
	    });
	    if (!isValidNode) {
	      return null;
	    }
	    const preparedNode = nodeFormatter.runBefore({
	      node: sourceNode,
	      formatter: this,
	      data
	    });
	    if (Type.isNull(preparedNode)) {
	      return null;
	    }
	    const convertedElement = nodeFormatter.runConvert({
	      node: preparedNode,
	      formatter: this,
	      data
	    });
	    if (Type.isNull(convertedElement)) {
	      return null;
	    }
	    preparedNode.getChildren().forEach(childNode => {
	      const childElement = this.format({
	        source: childNode,
	        data
	      });
	      if (childElement !== null) {
	        const convertedChildElement = nodeFormatter.runForChild({
	          node: childNode,
	          element: childElement,
	          formatter: this,
	          data
	        });
	        if (convertedChildElement !== null && this.isElement(convertedElement)) {
	          convertedElement.appendChild(convertedChildElement);
	        }
	      }
	    });
	    return nodeFormatter.runAfter({
	      node: preparedNode,
	      element: convertedElement,
	      formatter: this,
	      data
	    });
	  }
	}

	exports.Formatter = Formatter;
	exports.NodeFormatter = NodeFormatter;

});