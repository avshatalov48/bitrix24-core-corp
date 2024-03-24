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
	class BBCodeNode {
	  constructor(options = {}) {
	    this[nameSymbol] = '#unknown';
	    this.children = [];
	    privateMap.set(this, {
	      delayedChildren: []
	    });
	    this.setName(options.name);
	    privateMap.get(this).scheme = options.scheme;
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
	        if (node.getType() === BBCodeNode.FRAGMENT_NODE) {
	          return node.getChildren();
	        }
	        return node;
	      });
	    }
	    return [];
	  }
	  setScheme(scheme, onUnknown) {
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
	      return node.getType() === BBCodeNode.ELEMENT_NODE && node.getName() === name;
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
	      return node.getType() === BBCodeNode.ELEMENT_NODE && node.getName() === name;
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
	  adjustChildren() {
	    this.setChildren(this.getChildren());
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
	    const flattenedChildren = BBCodeNode.flattenChildren(children);
	    flattenedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.push(node);
	    });
	  }
	  prependChild(...children) {
	    const flattenedChildren = BBCodeNode.flattenChildren(children);
	    flattenedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.unshift(node);
	    });
	  }
	  insertBefore(...nodes) {
	    if (this.hasParent() && Type.isArrayFilled(nodes)) {
	      const parent = this.getParent();
	      const parentChildren = parent.getChildren();
	      const currentNodeIndex = parentChildren.indexOf(this);
	      const deleteCount = 0;
	      parentChildren.splice(currentNodeIndex, deleteCount, ...nodes);
	      parent.setChildren(parentChildren);
	    }
	  }
	  insertAfter(...nodes) {
	    if (this.hasParent() && Type.isArrayFilled(nodes)) {
	      const parent = this.getParent();
	      const parentChildren = parent.getChildren();
	      const currentNodeIndex = parentChildren.indexOf(this);
	      const startIndex = currentNodeIndex + 1;
	      const deleteCount = 0;
	      parentChildren.splice(startIndex, deleteCount, ...nodes);
	      parent.setChildren(parentChildren);
	    }
	  }
	  propagateChild(...children) {
	    if (this.hasParent()) {
	      this.insertBefore(...children.filter(child => {
	        return !['#linebreak', '#tab'].includes(child.getName());
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
	        const flattenedChildren = BBCodeNode.flattenChildren(children);
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
	    return this.getScheme().createNode({
	      name: this.getName(),
	      parent: this.getParent(),
	      children
	    });
	  }
	  toPlainText() {
	    return this.getChildren().map(child => {
	      return child.toPlainText();
	    }).join('');
	  }
	  getTextContent() {
	    return this.toPlainText();
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
	    const node = BBCodeNode.flattenAst(this).find(child => {
	      if (child.getName() === '#text' || child.getName() === '#linebreak' || child.getName() === '#tab') {
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
	const formatNames = ['b', 'u', 'i', 's'];
	class BBCodeElementNode extends BBCodeNode {
	  constructor(options = {}) {
	    super(options);
	    this.attributes = {};
	    this.value = '';
	    this[inlineSymbol] = false;
	    this[voidSymbol] = false;
	    privateMap.get(this).type = BBCodeNode.ELEMENT_NODE;
	    const tagScheme = this.getTagScheme();
	    this[inlineSymbol] = tagScheme.isInline();
	    this[voidSymbol] = tagScheme.isVoid();
	    this.setValue(options.value);
	    this.setAttributes(options.attributes);
	  }
	  setScheme(scheme, onUnknown) {
	    this.getChildren().forEach(node => {
	      node.setScheme(scheme, onUnknown);
	    });
	    if (scheme.isAllowedTag(this.getName())) {
	      super.setScheme(scheme);
	      const tagScheme = this.getTagScheme();
	      this[inlineSymbol] = tagScheme.isInline();
	      this[voidSymbol] = tagScheme.isVoid();
	    } else {
	      super.setScheme(scheme);
	      onUnknown(this, scheme);
	    }
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
	      if (allowedChildren.includes(child.getName()) || allowedChildren.includes('#inline') && child.getType() === BBCodeNode.ELEMENT_NODE && child.isInline() || allowedChildren.includes('#block') && child.getType() === BBCodeNode.ELEMENT_NODE && child.isInline() === false || allowedChildren.includes('#void') && child.getType() === BBCodeNode.ELEMENT_NODE && child.isVoid() || allowedChildren.includes('#format') && formatNames.includes(child.getName())) {
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
	    const flattenedChildren = BBCodeNode.flattenChildren(children);
	    const filteredChildren = this.filterChildren(flattenedChildren);
	    const convertedChildren = this.convertChildren(filteredChildren.resolved);
	    convertedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.push(node);
	    });
	    if (Type.isArrayFilled(filteredChildren.unresolved)) {
	      if (this.getScheme().isAllowedUnresolvedNodesHoisting()) {
	        this.propagateChild(...filteredChildren.unresolved);
	      } else {
	        filteredChildren.unresolved.forEach(node => {
	          node.remove();
	        });
	      }
	    }
	  }
	  prependChild(...children) {
	    const flattenedChildren = BBCodeNode.flattenChildren(children);
	    const filteredChildren = this.filterChildren(flattenedChildren);
	    const convertedChildren = this.convertChildren(filteredChildren.resolved);
	    convertedChildren.forEach(node => {
	      node.remove();
	      node.setParent(this);
	      this.children.unshift(node);
	    });
	    if (Type.isArrayFilled(filteredChildren.unresolved)) {
	      if (this.getScheme().isAllowedUnresolvedNodesHoisting()) {
	        this.propagateChild(...filteredChildren.unresolved);
	      } else {
	        filteredChildren.unresolved.forEach(node => {
	          node.remove();
	        });
	      }
	    }
	  }
	  replaceChild(targetNode, ...children) {
	    this.children = this.children.flatMap(node => {
	      if (node === targetNode) {
	        node.setParent(null);
	        const flattenedChildren = BBCodeNode.flattenChildren(children);
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
	    return this.getScheme().createElement({
	      name: this.getName(),
	      void: this.isVoid(),
	      inline: this.isInline(),
	      value: this.getValue(),
	      attributes: {
	        ...this.getAttributes()
	      },
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

	class BBCodeRootNode extends BBCodeElementNode {
	  constructor(options) {
	    super({
	      ...options,
	      name: '#root'
	    });
	    privateMap.get(this).type = BBCodeNode.ROOT_NODE;
	    BBCodeRootNode.makeNonEnumerableProperty(this, 'value');
	    BBCodeRootNode.makeNonEnumerableProperty(this, 'attributes');
	    BBCodeRootNode.freezeProperty(this, nameSymbol, '#root');
	  }
	  setScheme(scheme, onUnknown) {
	    BBCodeNode.flattenAst(this).forEach(node => {
	      node.setScheme(scheme, onUnknown);
	    });
	    super.setScheme(scheme);
	    BBCodeNode.flattenAst(this).forEach(node => {
	      node.adjustChildren();
	    });
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
	    return this.getScheme().createRoot({
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

	class BBCodeFragmentNode extends BBCodeElementNode {
	  constructor(options) {
	    super({
	      ...options,
	      name: '#fragment'
	    });
	    privateMap.get(this).type = BBCodeNode.FRAGMENT_NODE;
	    BBCodeFragmentNode.makeNonEnumerableProperty(this, 'value');
	    BBCodeFragmentNode.makeNonEnumerableProperty(this, 'attributes');
	    BBCodeFragmentNode.freezeProperty(this, nameSymbol, '#fragment');
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
	    return this.getScheme().createFragment({
	      children
	    });
	  }
	}

	const contentSymbol = Symbol('content');
	class BBCodeTextNode extends BBCodeNode {
	  constructor(options = {}) {
	    const nodeOptions = Type.isString(options) ? {
	      content: options
	    } : options;
	    super(nodeOptions);
	    this[nameSymbol] = '#text';
	    this[contentSymbol] = '';
	    privateMap.get(this).type = BBCodeNode.TEXT_NODE;
	    this.setContent(nodeOptions.content);
	    BBCodeNode.makeNonEnumerableProperty(this, 'children');
	  }
	  static isTextNodeContent(value) {
	    return Type.isString(value) || Type.isNumber(value);
	  }
	  static decodeSpecialChars(content) {
	    return String(content).replaceAll('&#91;', '[').replaceAll('&#93;', ']');
	  }
	  setName(name) {}
	  setContent(content) {
	    if (BBCodeTextNode.isTextNodeContent(content)) {
	      this[contentSymbol] = BBCodeTextNode.decodeSpecialChars(content);
	    }
	  }
	  getContent() {
	    return BBCodeTextNode.decodeSpecialChars(this[contentSymbol]);
	  }
	  adjustChildren() {}
	  getLength() {
	    return String(this[contentSymbol]).length;
	  }
	  clone(options) {
	    return this.getScheme().createText({
	      content: this.getContent()
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

	class BBCodeNewLineNode extends BBCodeTextNode {
	  constructor(options = {}) {
	    super(options);
	    this[nameSymbol] = '#linebreak';
	    this[contentSymbol] = '\n';
	  }
	  setContent(options) {}
	  clone(options) {
	    return this.getScheme().createNewLine();
	  }
	}

	class BBCodeTabNode extends BBCodeTextNode {
	  constructor(options = {}) {
	    super(options);
	    this[nameSymbol] = '#tab';
	    this[contentSymbol] = '\t';
	  }
	  setContent(options) {}
	  clone(options) {
	    return this.getScheme().createTab();
	  }
	}

	class BBCodeNodeScheme {
	  constructor(options) {
	    this.name = [];
	    this.stringifier = null;
	    this.serializer = null;
	    if (!Type.isPlainObject(options)) {
	      throw new TypeError('options is not a object');
	    }
	    if (!Type.isArrayFilled(this.name) && !Type.isArrayFilled(options.name) && !Type.isStringFilled(options.name)) {
	      throw new TypeError('options.name is not specified');
	    }
	    this.setName(options.name);
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

	class BBCodeTagScheme extends BBCodeNodeScheme {
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
	      return nextSibling && nextSibling.getName() !== '#linebreak' && !(nextSibling.getType() === BBCodeNode.ELEMENT_NODE && !nextSibling.isInline());
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
	    return value instanceof BBCodeNodeScheme;
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
	    return new BBCodeRootNode({
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
	    return new BBCodeNode({
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
	    return new BBCodeElementNode({
	      ...options,
	      scheme: this
	    });
	  }
	  createText(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new BBCodeTextNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createNewLine(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new BBCodeNewLineNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createTab(options = {}) {
	    const preparedOptions = Type.isPlainObject(options) ? options : {
	      content: options
	    };
	    return new BBCodeTabNode({
	      ...preparedOptions,
	      scheme: this
	    });
	  }
	  createFragment(options = {}) {
	    return new BBCodeFragmentNode({
	      ...options,
	      scheme: this
	    });
	  }
	}
	BBCodeScheme.Case = {
	  LOWER: 'lower',
	  UPPER: 'upper'
	};

	class BBCodeTextScheme extends BBCodeNodeScheme {
	  constructor(options) {
	    super({
	      ...options,
	      name: ['#text']
	    });
	  }
	}

	class BBCodeNewLineScheme extends BBCodeNodeScheme {}

	class BBCodeTabScheme extends BBCodeNodeScheme {
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
	      tagSchemes: [new BBCodeTagScheme({
	        name: ['b', 'i', 'u', 's', 'span'],
	        inline: true,
	        allowedChildren: ['#text', '#linebreak', '#inline']
	      }), new BBCodeTagScheme({
	        name: ['img'],
	        inline: true,
	        allowedChildren: ['#text']
	      }), new BBCodeTagScheme({
	        name: ['url'],
	        inline: true,
	        allowedChildren: ['#text', '#format']
	      }), new BBCodeTagScheme({
	        name: 'p',
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'disk', 'video'],
	        stringify: BBCodeTagScheme.defaultBlockStringifier
	      }), new BBCodeTagScheme({
	        name: 'list',
	        inline: false,
	        allowedChildren: ['*'],
	        stringify: BBCodeTagScheme.defaultBlockStringifier
	      }), new BBCodeTagScheme({
	        name: ['*'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'list'],
	        stringify: node => {
	          const openingTag = node.getOpeningTag();
	          const content = node.getContent().trim();
	          return `${openingTag}${content}`;
	        }
	      }), new BBCodeTagScheme({
	        name: ['ul'],
	        inline: false,
	        allowedChildren: ['li']
	      }), new BBCodeTagScheme({
	        name: ['ol'],
	        inline: false,
	        allowedChildren: ['li']
	      }), new BBCodeTagScheme({
	        name: ['li'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'ul', 'ol']
	      }), new BBCodeTagScheme({
	        name: 'table',
	        inline: false,
	        allowedChildren: ['tr'],
	        stringify: BBCodeTagScheme.defaultBlockStringifier
	      }), new BBCodeTagScheme({
	        name: 'tr',
	        inline: false,
	        allowedChildren: ['th', 'td']
	      }), new BBCodeTagScheme({
	        name: ['th', 'td'],
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline']
	      }), new BBCodeTagScheme({
	        name: 'quote',
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'quote']
	      }), new BBCodeTagScheme({
	        name: 'code',
	        inline: false,
	        stringify: BBCodeTagScheme.defaultBlockStringifier,
	        convertChild: (child, scheme) => {
	          if (['#linebreak', '#tab', '#text'].includes(child.getName())) {
	            return child;
	          }
	          return scheme.createText(child.toString());
	        }
	      }), new BBCodeTagScheme({
	        name: 'video',
	        inline: false,
	        allowedChildren: ['#text']
	      }), new BBCodeTagScheme({
	        name: 'spoiler',
	        inline: false,
	        allowedChildren: ['#text', '#linebreak', '#inline', 'p', 'quote', 'code', 'table', 'disk', 'video', 'spoiler', 'list']
	      }), new BBCodeTagScheme({
	        name: ['user', 'project', 'department'],
	        inline: true,
	        allowedChildren: ['#text', '#format']
	      }), new BBCodeTagScheme({
	        name: 'disk',
	        void: true
	      }), new BBCodeTagScheme({
	        name: ['#root', '#fragment']
	      }), new BBCodeTabScheme({
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

	exports.BBCodeNode = BBCodeNode;
	exports.BBCodeRootNode = BBCodeRootNode;
	exports.BBCodeElementNode = BBCodeElementNode;
	exports.BBCodeFragmentNode = BBCodeFragmentNode;
	exports.BBCodeNewLineNode = BBCodeNewLineNode;
	exports.BBCodeTabNode = BBCodeTabNode;
	exports.BBCodeTextNode = BBCodeTextNode;
	exports.BBCodeScheme = BBCodeScheme;
	exports.BBCodeTagScheme = BBCodeTagScheme;
	exports.BBCodeTextScheme = BBCodeTextScheme;
	exports.BBCodeNewLineScheme = BBCodeNewLineScheme;
	exports.BBCodeTabScheme = BBCodeTabScheme;
	exports.DefaultBBCodeScheme = DefaultBBCodeScheme;

});