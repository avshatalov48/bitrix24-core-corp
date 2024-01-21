/**
 * Attention!
 * This file is generated automatically from the extension `ui.bbcode.model`.
 * Any manual changes to this file are not allowed.
 *
 * If you need to make some changes, then make edits to the `ui.bbcode.model`
 * and run the build using 'bitrix build'.
 * During the build, the code will be automatically ported to `bbcode/model'.
 */

/** @module bbcode/model */
jn.define('bbcode/model', (require, exports, module) => {
	const {Type} = require('type');

	const privateMap = new WeakMap();
	const nameSymbol = Symbol('name');
	class Node {
	  constructor(options = {}) {
	    this[nameSymbol] = '#unknown';
	    this.children = [];
	    privateMap.set(this, {
	      delayedChildren: []
	    });
	    this.setName(options.name);
	    this.setScheme(options.scheme);
	    this.setParent(options.parent);
	    this.setChildren(options.children);
	  }
	  static get ELEMENT_NODE() {
	    return 1;
	  }
	  static get TEXT_NODE() {
	    return 2;
	  }
	  static get ROOT_NODE() {
	    return 3;
	  }
	  static get FRAGMENT_NODE() {
	    return 4;
	  }
	  static freezeProperty(node, property, value, enumerable = true) {
	    Object.defineProperty(node, property, {
	      value,
	      writable: false,
	      configurable: false,
	      enumerable
	    });
	  }
	  static makeNonEnumerableProperty(node, property) {
	    Object.defineProperty(node, property, {
	      writable: false,
	      enumerable: false,
	      configurable: false
	    });
	  }
	  static flattenChildren(children) {
	    if (Type.isArrayFilled(children)) {
	      return children.flatMap(node => {
	        if (node.getType() === Node.FRAGMENT_NODE) {
	          return node.getChildren();
	        }
	        return node;
	      });
	    }
	    return [];
	  }
	  setScheme(scheme) {
	    privateMap.get(this).scheme = scheme;
	  }
	  getScheme() {
	    return privateMap.get(this).scheme;
	  }
	  getTagScheme() {
	    return this.getScheme().getTagScheme(this.getName());
	  }
	  prepareCase(value) {
	    const scheme = this.getScheme();
	    const currentCase = scheme.getOutputTagCase();
	    if (currentCase === 'upper') {
	      return value.toUpperCase();
	    }
	    return value.toLowerCase();
	  }
	  setName(name) {
	    if (Type.isString(name)) {
	      this[nameSymbol] = name.toLowerCase();
	    }
	  }
	  getName() {
	    return this[nameSymbol];
	  }
	  getDisplayedName() {
	    return this.prepareCase(this.getName());
	  }
	  setParent(parent = null) {
	    const mounted = !this.hasParent() && parent;
	    privateMap.get(this).parent = parent;
	    if (mounted) {
	      this.onNodeDidMount();
	    }
	  }
	  getParent() {
	    return privateMap.get(this).parent;
	  }
	  getType() {
	    return privateMap.get(this).type;
	  }
	  hasParent() {
	    return Boolean(privateMap.get(this).parent);
	  }
	  remove() {
	    if (this.hasParent()) {
	      this.getParent().removeChild(this);
	    }
	  }
	  setChildren(children) {
	    if (Type.isArray(children)) {
	      this.children = [];
	      this.appendChild(...children);
	    }
	  }
	  getChildren() {
	    return [...this.children];
	  }
	  getLastChild() {
	    return this.getChildren().at(-1);
	  }
	  getLastChildOfType(type) {
	    return this.getChildren().reverse().find(node => {
	      return node.getType() === type;
	    });
	  }
	  getLastChildOfName(name) {
	    return this.getChildren().reverse().find(node => {
	      return node.getType() === Node.ELEMENT_NODE && node.getName() === name;
	    });
	  }
	  getFirstChild() {
	    return this.getChildren().at(0);
	  }
	  getFirstChildOfType(type) {
	    return this.getChildren().find(node => {
	      return node.getType() === type;
	    });
	  }
	  getFirstChildOfName(name) {
	    return this.getChildren().find(node => {
	      return node.getType() === Node.ELEMENT_NODE && node.getName() === name;
	    });
	  }
	  getPreviewsSibling() {
	    if (this.hasParent()) {
	      const parentChildren = this.getParent().getChildren();
	      const currentIndex = parentChildren.indexOf(this);
	      if (currentIndex > 0) {
	        return parentChildren.at(currentIndex - 1);
	      }
	    }
	    return null;
	  }
	  getPreviewsSiblings() {
	    if (this.hasParent()) {
	      const parentChildren = this.getParent().getChildren();
	      const currentIndex = parentChildren.indexOf(this);
	      return parentChildren.filter((child, index) => {
	        return index < currentIndex;
	      });
	    }
	    return null;
	  }
	  getNextSibling() {
	    if (this.hasParent()) {
	      const parentChildren = this.getParent().getChildren();
	      const currentIndex = parentChildren.indexOf(this);
	      if (currentIndex !== -1 && currentIndex !== parentChildren.length) {
	        return parentChildren.at(currentIndex + 1);
	      }
	    }
	    return null;
	  }
	  getNextSiblings() {
	    if (this.hasParent()) {
	      const parentChildren = this.getParent().getChildren();
	      const currentIndex = parentChildren.indexOf(this);
	      return parentChildren.filter((child, index) => {
	        return index > currentIndex;
	      });
	    }
	    return null;
	  }
	  getChildrenCount() {
	    return this.children.length;
	  }
	  hasChildren() {
	    return this.getChildrenCount() > 0;
	  }
	  setDelayedChildren(children) {
	    if (Type.isArray(children)) {
	      privateMap.get(this).delayedChildren = children;
	    }
	  }
	  addDelayedChildren(children) {
	    if (Type.isArrayFilled(children)) {
	      this.setDelayedChildren([...this.getDelayedChildren(), ...children]);
	    }
	  }
	  hasDelayedChildren() {
	    return privateMap.get(this).delayedChildren.length > 0;
	  }
	  getDelayedChildren() {
	    return [...privateMap.get(this).delayedChildren];
	  }
	  appendChild(...children) {
	    const flattenedChildren = Node.flattenChildren(children);
	    flattenedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.push(node);
	    });
	  }
	  prependChild(...children) {
	    const flattenedChildren = Node.flattenChildren(children);
	    flattenedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.unshift(node);
	    });
	  }
	  propagateChild(...children) {
	    if (this.hasParent()) {
	      this.getParent().prependChild(...children.filter(node => {
	        return node.getType() === Node.ELEMENT_NODE || node.getName() === '#text';
	      }));
	    } else {
	      this.addDelayedChildren(children);
	    }
	  }
	  onNodeDidMount() {
	    const delayedChildren = this.getDelayedChildren();
	    if (Type.isArrayFilled(delayedChildren)) {
	      this.propagateChild(...delayedChildren);
	      this.setDelayedChildren([]);
	    }
	  }
	  removeChild(...children) {
	    this.children = this.children.reduce((acc, node) => {
	      if (children.includes(node)) {
	        node.setParent(null);
	        return acc;
	      }
	      return [...acc, node];
	    }, []);
	  }
	  replaceChild(targetNode, ...children) {
	    this.children = this.children.flatMap(node => {
	      if (node === targetNode) {
	        node.setParent(null);
	        const flattenedChildren = Node.flattenChildren(children);
	        return flattenedChildren.map(child => {
	          child.remove();
	          child.setParent(this);
	          return child;
	        });
	      }
	      return node;
	    });
	  }
	  replace(...children) {
	    if (this.hasParent()) {
	      const parent = this.getParent();
	      parent.replaceChild(this, ...children);
	    }
	  }
	  clone(options = {}) {
	    const children = (() => {
	      if (options.deep) {
	        return this.getChildren().map(child => {
	          return child.clone(options);
	        });
	      }
	      return [];
	    })();
	    return new Node({
	      name: this.getName(),
	      scheme: this.getScheme(),
	      parent: this.getParent(),
	      children
	    });
	  }
	  toPlainText() {
	    return this.getChildren().map(child => {
	      return child.toPlainText();
	    }).join('');
	  }
	  getPlainTextLength() {
	    return this.toPlainText().length;
	  }
	  removePreviewsSiblings() {
	    const removePreviewsSiblings = node => {
	      const previewsSiblings = node.getPreviewsSiblings();
	      if (Type.isArray(previewsSiblings)) {
	        previewsSiblings.forEach(sibling => {
	          sibling.remove();
	        });
	      }
	      const parent = node.getParent();
	      if (parent) {
	        removePreviewsSiblings(parent);
	      }
	    };
	    removePreviewsSiblings(this);
	  }
	  removeNextSiblings() {
	    const removeNextSiblings = node => {
	      const nextSiblings = node.getNextSiblings();
	      if (Type.isArray(nextSiblings)) {
	        nextSiblings.forEach(sibling => {
	          sibling.remove();
	        });
	      }
	      const parent = node.getParent();
	      if (parent) {
	        removeNextSiblings(parent);
	      }
	    };
	    removeNextSiblings(this);
	  }
	  findByTextIndex(index) {
	    let currentIndex = 0;
	    let startIndex = 0;
	    let endIndex = 0;
	    const node = Node.flattenAst(this).find(child => {
	      if (child.getName() === '#text') {
	        startIndex = currentIndex;
	        endIndex = startIndex + child.getLength();
	        currentIndex = endIndex;
	        return index >= startIndex && endIndex >= index;
	      }
	      return false;
	    });
	    if (node) {
	      return {
	        node,
	        startIndex,
	        endIndex
	      };
	    }
	    return null;
	  }
	  split(options) {
	    const {
	      offset,
	      byWord = false
	    } = options;
	    const plainTextLength = this.getPlainTextLength();
	    const leftTree = (() => {
	      if (plainTextLength === offset) {
	        return this.clone({
	          deep: true
	        });
	      }
	      if (offset <= 0 || offset > plainTextLength) {
	        return null;
	      }
	      const tree = this.clone({
	        deep: true
	      });
	      const {
	        node,
	        startIndex
	      } = tree.findByTextIndex(offset);
	      const [leftNode, rightNode] = node.split({
	        offset: offset - startIndex,
	        byWord
	      });
	      if (leftNode) {
	        node.replace(leftNode);
	        leftNode.removeNextSiblings();
	      } else if (rightNode) {
	        rightNode.removeNextSiblings();
	        rightNode.remove();
	      }
	      return tree;
	    })();
	    const rightTree = (() => {
	      if (plainTextLength === offset) {
	        return null;
	      }
	      if (offset === 0) {
	        return this.clone({
	          deep: true
	        });
	      }
	      const tree = this.clone({
	        deep: true
	      });
	      const {
	        node,
	        startIndex
	      } = tree.findByTextIndex(offset);
	      const [leftNode, rightNode] = node.split({
	        offset: offset - startIndex,
	        byWord
	      });
	      if (rightNode) {
	        node.replace(rightNode);
	        rightNode.removePreviewsSiblings();
	      } else if (leftNode) {
	        leftNode.removePreviewsSiblings();
	        if (leftNode.hasParent()) {
	          const parent = leftNode.getParent();
	          leftNode.remove();
	          if (parent.getChildrenCount() === 0) {
	            parent.remove();
	          }
	        }
	      }
	      return tree;
	    })();
	    return [leftTree, rightTree];
	  }
	  static flattenAst(ast) {
	    const flat = [];
	    const traverse = node => {
	      flat.push(node);
	      if (node.hasChildren()) {
	        node.getChildren().forEach(child => {
	          traverse(child);
	        });
	      }
	    };
	    if (ast.hasChildren()) {
	      ast.getChildren().forEach(child => {
	        traverse(child);
	      });
	    }
	    return flat;
	  }
	  toJSON() {
	    return {
	      name: this.getName(),
	      children: this.getChildren().map(child => {
	        return child.toJSON();
	      })
	    };
	  }
	}

	const inlineSymbol = Symbol('inline');
	const voidSymbol = Symbol('void');
	class ElementNode extends Node {
	  constructor(options = {}) {
	    super(options);
	    this.attributes = {};
	    this.value = '';
	    this[inlineSymbol] = false;
	    this[voidSymbol] = false;
	    privateMap.get(this).type = Node.ELEMENT_NODE;
	    const tagScheme = this.getTagScheme();
	    this[inlineSymbol] = tagScheme.isInline();
	    this[voidSymbol] = tagScheme.isVoid();
	    this.setValue(options.value);
	    this.setAttributes(options.attributes);
	  }
	  filterChildren(children) {
	    const filteredChildren = {
	      resolved: [],
	      unresolved: []
	    };
	    const tagScheme = this.getTagScheme();
	    const allowedChildren = tagScheme.getAllowedChildren();
	    if (allowedChildren.length === 0) {
	      filteredChildren.resolved = children;
	      return filteredChildren;
	    }
	    children.forEach(child => {
	      if (allowedChildren.includes(child.getName()) || allowedChildren.includes('#inline') && child.getType() === Node.ELEMENT_NODE && child.isInline() || allowedChildren.includes('#void') && child.getType() === Node.ELEMENT_NODE && child.isVoid()) {
	        filteredChildren.resolved.push(child);
	      } else {
	        filteredChildren.unresolved.push(child);
	      }
	    });
	    return filteredChildren;
	  }
	  convertChildren(children) {
	    const tagScheme = this.getTagScheme();
	    const childConverter = tagScheme.getChildConverter();
	    if (childConverter) {
	      const scheme = this.getScheme();
	      return children.map(child => {
	        return childConverter(child, scheme);
	      });
	    }
	    return children;
	  }
	  setValue(value) {
	    if (Type.isString(value) || Type.isNumber(value) || Type.isBoolean(value)) {
	      this.value = value;
	    }
	  }
	  getValue() {
	    return this.value;
	  }
	  isVoid() {
	    return this[voidSymbol];
	  }
	  isInline() {
	    return this[inlineSymbol];
	  }
	  setAttributes(attributes) {
	    if (Type.isPlainObject(attributes)) {
	      const entries = Object.entries(attributes).map(([key, value]) => {
	        return [key.toLowerCase(), value];
	      });
	      this.attributes = Object.fromEntries(entries);
	    }
	  }
	  setAttribute(name, value) {
	    if (Type.isStringFilled(name)) {
	      const preparedName = name.toLowerCase();
	      if (Type.isNil(value)) {
	        delete this.attributes[preparedName];
	      } else {
	        this.attributes[preparedName] = value;
	      }
	    }
	  }
	  getAttribute(name) {
	    if (Type.isString(name)) {
	      return this.attributes[name.toLowerCase()];
	    }
	    return null;
	  }
	  getAttributes() {
	    return {
	      ...this.attributes
	    };
	  }
	  appendChild(...children) {
	    const flattenedChildren = Node.flattenChildren(children);
	    const filteredChildren = this.filterChildren(flattenedChildren);
	    const convertedChildren = this.convertChildren(filteredChildren.resolved);
	    convertedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.push(node);
	    });
	    if (Type.isArrayFilled(filteredChildren.unresolved)) {
	      this.propagateChild(...filteredChildren.unresolved);
	    }
	  }
	  prependChild(...children) {
	    const flattenedChildren = Node.flattenChildren(children);
	    const filteredChildren = this.filterChildren(flattenedChildren);
	    const convertedChildren = this.convertChildren(filteredChildren.resolved);
	    convertedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.unshift(node);
	    });
	    if (Type.isArrayFilled(filteredChildren.unresolved)) {
	      this.propagateChild(...filteredChildren.unresolved);
	    }
	  }
	  replaceChild(targetNode, ...children) {
	    this.children = this.children.flatMap(node => {
	      if (node === targetNode) {
	        node.setParent(null);
	        const flattenedChildren = Node.flattenChildren(children);
	        const filteredChildren = this.filterChildren(flattenedChildren);
	        const convertedChildren = this.convertChildren(filteredChildren.resolved);
	        return convertedChildren.map(child => {
	          child.remove();
	          child.setParent(this);
	          return child;
	        });
	      }
	      return node;
	    });
	  }
	  toStringValue() {
	    const value = this.getValue();
	    return value ? `=${value}` : '';
	  }
	  toStringAttributes() {
	    return Object.entries(this.getAttributes()).map(([key, attrValue]) => {
	      const preparedKey = this.prepareCase(key);
	      return attrValue ? `${preparedKey}=${attrValue}` : preparedKey;
	    }).join(' ');
	  }
	  getContent() {
	    return this.getChildren().map(child => {
	      return child.toString();
	    }).join('');
	  }
	  getOpeningTag() {
	    const displayedName = this.getDisplayedName();
	    const tagValue = this.toStringValue();
	    const attributes = this.toStringAttributes();
	    const formattedAttributes = Type.isStringFilled(attributes) ? ` ${attributes}` : '';
	    return `[${displayedName}${tagValue}${formattedAttributes}]`;
	  }
	  getClosingTag() {
	    return `[/${this.getDisplayedName()}]`;
	  }
	  clone(options = {}) {
	    const children = (() => {
	      if (options.deep) {
	        return this.getChildren().map(child => {
	          return child.clone(options);
	        });
	      }
	      return [];
	    })();
	    return new ElementNode({
	      name: this.getName(),
	      void: this.isVoid(),
	      inline: this.isInline(),
	      value: this.getValue(),
	      attributes: {
	        ...this.getAttributes()
	      },
	      scheme: this.getScheme(),
	      children
	    });
	  }
	  splitByChildIndex(index) {
	    if (!Type.isNumber(index)) {
	      throw new TypeError('index is not a number');
	    }
	    const childrenCount = this.getChildrenCount();
	    if (index < 0 || index > childrenCount) {
	      throw new TypeError(`index '${index}' is out of range ${0}-${childrenCount}`);
	    }
	    const leftNode = (() => {
	      if (index === childrenCount) {
	        return this;
	      }
	      if (index === 0) {
	        return null;
	      }
	      const leftChildren = this.getChildren().filter((child, childIndex) => {
	        return childIndex < index;
	      });
	      const node = this.clone();
	      node.setChildren(leftChildren);
	      return node;
	    })();
	    const rightNode = (() => {
	      if (index === 0) {
	        return this;
	      }
	      if (index === childrenCount) {
	        return null;
	      }
	      const rightChildren = this.getChildren();
	      const node = this.clone();
	      node.setChildren(rightChildren);
	      return node;
	    })();
	    if (leftNode && rightNode) {
	      this.replace(leftNode, rightNode);
	    }
	    return [leftNode, rightNode];
	  }
	  toString() {
	    const tagScheme = this.getTagScheme();
	    const stringifier = tagScheme.getStringifier();
	    if (Type.isFunction(stringifier)) {
	      const scheme = this.getScheme();
	      return stringifier(this, scheme);
	    }
	    const openingTag = this.getOpeningTag();
	    const content = this.getContent();
	    if (this.isVoid()) {
	      return `${openingTag}${content}`;
	    }
	    const closingTag = this.getClosingTag();
	    return `${openingTag}${content}${closingTag}`;
	  }
	  toJSON() {
	    return {
	      ...super.toJSON(),
	      value: this.getValue(),
	      attributes: this.getAttributes(),
	      void: this.isVoid(),
	      inline: this.isInline()
	    };
	  }
	}

	class RootNode extends ElementNode {
	  constructor(options) {
	    super({
	      ...options,
	      name: '#root'
	    });
	    privateMap.get(this).type = Node.ROOT_NODE;
	    RootNode.makeNonEnumerableProperty(this, 'value');
	    RootNode.makeNonEnumerableProperty(this, 'attributes');
	    RootNode.freezeProperty(this, nameSymbol, '#root');
	  }
	  getParent() {
	    return null;
	  }
	  clone(options = {}) {
	    const children = (() => {
	      if (options.deep) {
	        return this.getChildren().map(child => {
	          return child.clone(options);
	        });
	      }
	      return [];
	    })();
	    return new RootNode({
	      children
	    });
	  }
	  toString() {
	    return this.getChildren().map(child => {
	      return child.toString();
	    }).join('');
	  }
	  toJSON() {
	    return this.getChildren().map(node => {
	      return node.toJSON();
	    });
	  }
	}

	class FragmentNode extends ElementNode {
	  constructor(options) {
	    super({
	      ...options,
	      name: '#fragment'
	    });
	    privateMap.get(this).type = Node.FRAGMENT_NODE;
	    FragmentNode.makeNonEnumerableProperty(this, 'value');
	    FragmentNode.makeNonEnumerableProperty(this, 'attributes');
	    FragmentNode.freezeProperty(this, nameSymbol, '#fragment');
	  }
	  clone(options = {}) {
	    const children = (() => {
	      if (options.deep) {
	        return this.getChildren().map(child => {
	          return child.clone(options);
	        });
	      }
	      return [];
	    })();
	    return new FragmentNode({
	      children,
	      scheme: this.getScheme()
	    });
	  }
	}

	const contentSymbol = Symbol('content');
	class TextNode extends Node {
	  constructor(options = {}) {
	    const nodeOptions = Type.isString(options) ? {
	      content: options
	    } : options;
	    super(nodeOptions);
	    this[nameSymbol] = '#text';
	    this[contentSymbol] = '';
	    privateMap.get(this).type = Node.TEXT_NODE;
	    this.setContent(nodeOptions.content);
	    Node.makeNonEnumerableProperty(this, 'children');
	  }
	  static isTextNodeContent(value) {
	    return Type.isString(value) || Type.isNumber(value);
	  }
	  static decodeSpecialChars(content) {
	    if (TextNode.isTextNodeContent(content)) {
	      return content.replaceAll('&#91;', '[').replaceAll('&#93;', ']');
	    }
	    return content;
	  }
	  setName(name) {}
	  setContent(content) {
	    if (TextNode.isTextNodeContent(content)) {
	      this[contentSymbol] = TextNode.decodeSpecialChars(content);
	    }
	  }
	  getContent() {
	    return TextNode.decodeSpecialChars(this[contentSymbol]);
	  }
	  getLength() {
	    return String(this[contentSymbol]).length;
	  }
	  clone(options) {
	    const Constructor = this.constructor;
	    return new Constructor({
	      content: this.getContent(),
	      scheme: this.getScheme()
	    });
	  }
	  split(options) {
	    const {
	      offset: sourceOffset,
	      byWord = false
	    } = options;
	    if (!Type.isNumber(sourceOffset)) {
	      throw new TypeError('offset is not a number');
	    }
	    const contentLength = this.getLength();
	    if (sourceOffset < 0 || sourceOffset > contentLength) {
	      throw new TypeError(`offset '${sourceOffset}' is out of range ${0}-${contentLength}`);
	    }
	    const content = this.getContent();
	    const offset = (() => {
	      if (byWord && sourceOffset !== contentLength) {
	        const lastIndex = content.lastIndexOf(' ', sourceOffset);
	        if (lastIndex !== -1) {
	          if (sourceOffset > lastIndex) {
	            return lastIndex + 1;
	          }
	          return lastIndex;
	        }
	        return 0;
	      }
	      return sourceOffset;
	    })();
	    const leftNode = (() => {
	      if (offset === contentLength) {
	        return this;
	      }
	      if (offset === 0) {
	        return null;
	      }
	      const node = this.clone();
	      node.setContent(content.slice(0, offset));
	      return node;
	    })();
	    const rightNode = (() => {
	      if (offset === 0) {
	        return this;
	      }
	      if (offset === contentLength) {
	        return null;
	      }
	      const node = this.clone();
	      node.setContent(content.slice(offset, contentLength));
	      return node;
	    })();
	    return [leftNode, rightNode];
	  }
	  toString() {
	    return this.getContent();
	  }
	  toPlainText() {
	    return this.toString();
	  }
	  toJSON() {
	    return {
	      name: this.getName(),
	      content: this.toString()
	    };
	  }
	}

	class NewLineNode extends TextNode {
	  constructor(options = {}) {
	    super(options);
	    this[nameSymbol] = '#linebreak';
	    this[contentSymbol] = '\n';
	  }
	  setContent(options) {}
	}

	class TabNode extends TextNode {
	  constructor(options = {}) {
	    super(options);
	    this[nameSymbol] = '#tab';
	    this[contentSymbol] = '\t';
	  }
	  setContent(options) {}
	}

	class NodeScheme {
	  constructor(options) {
	    this.name = [];
	    this.converter = null;
	    this.stringifier = null;
	    this.serializer = null;
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    if (!Type.isArrayFilled(this.name) && !Type.isArrayFilled(options.name) && !Type.isStringFilled(options.name)) {
	      throw new TypeError('options.name is not specified');
	    }
	    this.setName(options.name);
	    this.setConverter(options.convert);
	    this.setStringifier(options.stringify);
	    this.setSerializer(options.serialize);
	  }
	  setName(name) {
	    if (Type.isStringFilled(name)) {
	      this.name = [name];
	    }
	    if (Type.isArrayFilled(name)) {
	      this.name = name;
	    }
	  }
	  getName() {
	    return [...this.name];
	  }
	  removeName(...names) {
	    this.setName(this.getName().filter(name => {
	      return !names.includes(name);
	    }));
	  }
	  setConverter(converter) {
	    if (Type.isFunction(converter) || Type.isNull(converter)) {
	      this.converter = converter;
	    }
	  }
	  getConverter() {
	    return this.converter;
	  }
	  setStringifier(stringifier) {
	    if (Type.isFunction(stringifier) || Type.isNull(stringifier)) {
	      this.stringifier = stringifier;
	    }
	  }
	  getStringifier() {
	    return this.stringifier;
	  }
	  setSerializer(serializer) {
	    if (Type.isFunction(serializer) || Type.isNull(serializer)) {
	      this.serializer = serializer;
	    }
	  }
	  getSerializer() {
	    return this.serializer;
	  }
	}

	class TagScheme extends NodeScheme {
	  constructor(options) {
	    super(options);
	    this.inline = false;
	    this.void = false;
	    this.childConverter = null;
	    this.allowedChildren = [];
	    this.setInline(options.inline);
	    this.setVoid(options.void);
	    this.setChildConverter(options.convertChild);
	    this.setAllowedChildren(options.allowedChildren);
	  }
	  static defaultBlockStringifier(node) {
	    const isAllowNewlineBeforeOpeningTag = (() => {
	      const previewsSibling = node.getPreviewsSibling();
	      return previewsSibling && previewsSibling.getName() !== '#linebreak';
	    })();
	    const isAllowNewlineAfterOpeningTag = (() => {
	      const firstChild = node.getFirstChild();
	      return firstChild && firstChild.getName() !== '#linebreak';
	    })();
	    const isAllowNewlineBeforeClosingTag = (() => {
	      const lastChild = node.getLastChild();
	      return lastChild && lastChild.getName() !== '#linebreak';
	    })();
	    const isAllowNewlineAfterClosingTag = (() => {
	      const nextSibling = node.getNextSibling();
	      return nextSibling && nextSibling.getName() !== '#linebreak' && !(nextSibling.getType() === Node.ELEMENT_NODE && !nextSibling.isInline());
	    })();
	    const openingTag = node.getOpeningTag();
	    const content = node.getContent();
	    const closingTag = node.getClosingTag();
	    return [isAllowNewlineBeforeOpeningTag ? '\n' : '', openingTag, isAllowNewlineAfterOpeningTag ? '\n' : '', content, isAllowNewlineBeforeClosingTag ? '\n' : '', closingTag, isAllowNewlineAfterClosingTag ? '\n' : ''].join('');
	  }
	  setInline(value) {
	    if (Type.isBoolean(value)) {
	      this.inline = value;
	    }
	  }
	  isInline() {
	    return this.inline;
	  }
	  setVoid(value) {
	    if (Type.isBoolean(value)) {
	      this.void = value;
	    }
	  }
	  isVoid() {
	    return this.void;
	  }
	  setChildConverter(converter) {
	    if (Type.isFunction(converter) || Type.isNull(converter)) {
	      this.childConverter = converter;
	    }
	  }
	  getChildConverter() {
	    return this.childConverter;
	  }
	  setAllowedChildren(allowedChildren) {
	    if (Type.isArray(allowedChildren)) {
	      this.allowedChildren = allowedChildren;
	    }
	  }
	  getAllowedChildren() {
	    return [...this.allowedChildren];
	  }
	}

	class BBCodeScheme {
	  static isNodeScheme(value) {
	    return value instanceof NodeScheme;
	  }
	  constructor(options = {}) {
	    this.tagSchemes = [];
	    this.outputTagCase = BBCodeScheme.Case.LOWER;
	    this.unresolvedNodesHoisting = true;
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    this.setTagSchemes(options.tagSchemes);
	    this.setOutputTagCase(options.outputTagCase);
	    this.setUnresolvedNodesHoisting(options.unresolvedNodesHoisting);
	  }
	  setTagSchemes(tagSchemes) {
	    if (Type.isArray(tagSchemes)) {
	      const invalidSchemeIndex = tagSchemes.findIndex(scheme => {
	        return !BBCodeScheme.isNodeScheme(scheme);
	      });
	      if (invalidSchemeIndex > -1) {
	        throw new TypeError(`tagScheme #${invalidSchemeIndex} is not TagScheme instance`);
	      }
	      this.tagSchemes = [...tagSchemes];
	    }
	  }
	  setTagScheme(...tagSchemes) {
	    const invalidSchemeIndex = tagSchemes.findIndex(scheme => {
	      return !BBCodeScheme.isNodeScheme(scheme);
	    });
	    if (invalidSchemeIndex > -1) {
	      throw new TypeError(`tagScheme #${invalidSchemeIndex} is not TagScheme instance`);
	    }
	    const newTagSchemesNames = tagSchemes.flatMap(scheme => {
	      return scheme.getName();
	    });
	    const currentTagSchemes = this.getTagSchemes();
	    currentTagSchemes.forEach(scheme => {
	      scheme.removeName(...newTagSchemesNames);
	    });
	    const filteredCurrentTagSchemes = currentTagSchemes.filter(scheme => {
	      return Type.isArrayFilled(scheme.getName());
	    });
	    this.setTagSchemes([...filteredCurrentTagSchemes, ...tagSchemes]);
	  }
	  getTagSchemes() {
	    return [...this.tagSchemes];
	  }
	  getTagScheme(tagName) {
	    return this.getTagSchemes().find(scheme => {
	      return scheme.getName().includes(String(tagName).toLowerCase());
	    });
	  }
	  setOutputTagCase(tagCase) {
	    if (!Type.isNil(tagCase)) {
	      const allowedCases = Object.values(BBCodeScheme.Case);
	      if (allowedCases.includes(tagCase)) {
	        this.outputTagCase = tagCase;
	      } else {
	        throw new TypeError(`'${tagCase}' is not allowed`);
	      }
	    }
	  }
	  getOutputTagCase() {
	    return this.outputTagCase;
	  }
	  setUnresolvedNodesHoisting(value) {
	    if (!Type.isNil(value)) {
	      if (Type.isBoolean(value)) {
	        this.unresolvedNodesHoisting = value;
	      } else {
	        throw new TypeError(`'${value}' is not allowed value`);
	      }
	    }
	  }
	  isAllowedUnresolvedNodesHoisting() {
	    return this.unresolvedNodesHoisting;
	  }
	  getAllowedTags() {
	    return this.getTagSchemes().flatMap(tagScheme => {
	      return tagScheme.getName();
	    });
	  }
	  isAllowedTag(tagName) {
	    const allowedTags = this.getAllowedTags();
	    return allowedTags.includes(String(tagName).toLowerCase());
	  }
	  createRoot(options = {}) {
	    return new RootNode({
	      ...options,
	      scheme: this
	    });
	  }
	  createNode(options) {
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    if (!Type.isStringFilled(options.name)) {
	      throw new TypeError('options.name is required');
	    }
	    if (!this.isAllowedTag(options.name)) {
	      throw new TypeError(`Scheme for "${options.name}" tag is not specified.`);
	    }
	    return new Node({
	      ...options,
	      scheme: this
	    });
	  }
	  createElement(options = {}) {
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    if (!Type.isStringFilled(options.name)) {
	      throw new TypeError('options.name is required');
	    }
	    if (!this.isAllowedTag(options.name)) {
	      throw new TypeError(`Scheme for "${options.name}" tag is not specified.`);
	    }
	    return new ElementNode({
	      ...options,
	      scheme: this
	    });
	  }
	  createText(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new TextNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createNewLine(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new NewLineNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createTab(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new TabNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createFragment(options = {}) {
	    return new FragmentNode({
	      ...options,
	      scheme: this
	    });
	  }
	}
	BBCodeScheme.Case = {
	  LOWER: 'lower',
	  UPPER: 'upper'
	};

	class TextScheme extends NodeScheme {
	  constructor(options) {
	    super({
	      ...options,
	      name: ['#text']
	    });
	  }
	}

	class NewLineScheme extends NodeScheme {}

	class TabScheme extends NodeScheme {
	  constructor(options) {
	    super({
	      ...options,
	      name: ['tab']
	    });
	  }
	}

	class DefaultBBCodeScheme extends BBCodeScheme {
	  constructor(options = {}) {
	    super({
	      tagSchemes: [new TagScheme({
	        name: ['b', 'i', 'u', 's', 'span'],
	        inline: true,
	        allowedChildren: ['#text', '#linebreak', '#inline']
	      }), new TagScheme({
	        name: ['img', 'url'],
	        inline: true,
	        allowedChildren: ['#text']
	      }), new TagScheme({
	        name: 'p',
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'disk'],
	        stringify: TagScheme.defaultBlockStringifier
	      }), new TagScheme({
	        name: 'list',
	        inline: false,
	        allowedChildren: ['*'],
	        stringify: TagScheme.defaultBlockStringifier
	      }), new TagScheme({
	        name: ['*'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'list'],
	        stringify: node => {
	          const openingTag = node.getOpeningTag();
	          const content = node.getContent().trim();
	          return `${openingTag}${content}`;
	        }
	      }), new TagScheme({
	        name: ['ul'],
	        inline: false,
	        allowedChildren: ['li'],
	        convert: (node, scheme) => {
	          return scheme.createElement({
	            name: 'list',
	            attributes: node.getAttributes(),
	            children: node.getChildren()
	          });
	        }
	      }), new TagScheme({
	        name: ['ol'],
	        inline: false,
	        allowedChildren: ['li'],
	        convert: (node, scheme) => {
	          return scheme.createElement({
	            name: 'list',
	            value: '1',
	            attributes: node.getAttributes(),
	            children: node.getChildren()
	          });
	        }
	      }), new TagScheme({
	        name: ['li'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'ul', 'ol'],
	        convert: (node, scheme) => {
	          return scheme.createElement({
	            name: '*',
	            children: node.getChildren()
	          });
	        }
	      }), new TagScheme({
	        name: 'table',
	        inline: false,
	        allowedChildren: ['tr'],
	        stringify: TagScheme.defaultBlockStringifier
	      }), new TagScheme({
	        name: 'tr',
	        inline: false,
	        allowedChildren: ['th', 'td']
	      }), new TagScheme({
	        name: ['th', 'td'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline']
	      }), new TagScheme({
	        name: 'quote',
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'quote']
	      }), new TagScheme({
	        name: 'code',
	        inline: false,
	        stringify: TagScheme.defaultBlockStringifier,
	        convertChild: (child, scheme) => {
	          if (['#linebreak', '#tab', '#text'].includes(child.getName())) {
	            return child;
	          }
	          return scheme.createText(child.toString());
	        }
	      }), new TagScheme({
	        name: 'disk',
	        void: true
	      }), new TagScheme({
	        name: ['#root', '#fragment']
	      }), new TextScheme({
	        convert: (node, scheme) => {
	          return scheme.createText({
	            content: node.toString().replaceAll(' - ', '&mdash;')
	          });
	        }
	      }), new TabScheme({
	        stringify: () => {
	          return '';
	        }
	      })],
	      outputTagCase: BBCodeScheme.Case.LOWER,
	      unresolvedNodesHoisting: true
	    });
	    if (Type.isPlainObject(options)) {
	      this.setTagSchemes(options.tagSchemes);
	      this.setOutputTagCase(options.outputTagCase);
	      this.setUnresolvedNodesHoisting(options.unresolvedNodesHoisting);
	    }
	  }
	}

	exports.Node = Node;
	exports.RootNode = RootNode;
	exports.ElementNode = ElementNode;
	exports.FragmentNode = FragmentNode;
	exports.NewLineNode = NewLineNode;
	exports.TabNode = TabNode;
	exports.TextNode = TextNode;
	exports.BBCodeScheme = BBCodeScheme;
	exports.TagScheme = TagScheme;
	exports.TextScheme = TextScheme;
	exports.NewLineScheme = NewLineScheme;
	exports.TabScheme = TabScheme;
	exports.DefaultBBCodeScheme = DefaultBBCodeScheme;

});