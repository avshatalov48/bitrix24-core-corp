/**
 * Attention!
 * This file is generated automatically from the extension `ui.bbcode.parser`.
 * Any manual changes to this file are not allowed.
 *
 * If you need to make some changes, then make edits to the `ui.bbcode.parser`
 * and run the build using 'bitrix build'.
 * During the build, the code will be automatically ported to `bbcode/parser'.
 */

/** @module bbcode/parser */
jn.define('bbcode/parser', (require, exports, module) => {
	const {Type} = jn.require('type');
	const Model = jn.require('bbcode/model');
	const {BBCodeEncoder} = jn.require('bbcode/encoder');
	const {AstProcessor} = jn.require('bbcode/ast-processor');
	const {Linkify} = jn.require('linkify');

	function getByIndex(array, index) {
	  if (!Type.isArray(array)) {
	    throw new TypeError('array is not a array');
	  }
	  if (!Type.isInteger(index)) {
	    throw new TypeError('index is not a integer');
	  }
	  const preparedIndex = index < 0 ? array.length + index : index;
	  return array[preparedIndex];
	}

	class ParserScheme extends Model.BBCodeScheme {
	  getTagScheme(tagName) {
	    return new Model.BBCodeTagScheme({
	      name: 'any'
	    });
	  }
	  isAllowedTag(tagName) {
	    return true;
	  }
	  isChildAllowed(parent, child) {
	    return true;
	  }
	}

	const TAG_REGEX = /\[\/?(?:\w+|\*).*?]/;
	const TAG_REGEX_GS = /\[(\/)?(\w+|\*)(.*?)]/gs;
	const isSpecialChar = symbol => {
	  return ['\n', '\t'].includes(symbol);
	};
	const isList = tagName => {
	  return ['list', 'ul', 'ol'].includes(String(tagName).toLowerCase());
	};
	const isListItem = tagName => {
	  return ['*', 'li'].includes(String(tagName).toLowerCase());
	};
	const parserScheme = new ParserScheme();
	class BBCodeParser {
	  constructor(options = {}) {
	    this.allowedLinkify = true;
	    if (options.scheme) {
	      this.setScheme(options.scheme);
	    } else {
	      this.setScheme(new Model.DefaultBBCodeScheme());
	    }
	    if (Type.isFunction(options.onUnknown)) {
	      this.setOnUnknown(options.onUnknown);
	    } else {
	      this.setOnUnknown(BBCodeParser.defaultOnUnknownHandler);
	    }
	    if (options.encoder instanceof BBCodeEncoder) {
	      this.setEncoder(options.encoder);
	    } else {
	      this.setEncoder(new BBCodeEncoder());
	    }
	    if (Type.isBoolean(options.linkify)) {
	      this.setIsAllowedLinkify(options.linkify);
	    }
	  }
	  setScheme(scheme) {
	    this.scheme = scheme;
	  }
	  getScheme() {
	    return this.scheme;
	  }
	  setOnUnknown(handler) {
	    if (!Type.isFunction(handler)) {
	      throw new TypeError('handler is not a function');
	    }
	    this.onUnknownHandler = handler;
	  }
	  getOnUnknownHandler() {
	    return this.onUnknownHandler;
	  }
	  setEncoder(encoder) {
	    if (encoder instanceof BBCodeEncoder) {
	      this.encoder = encoder;
	    } else {
	      throw new TypeError('encoder is not BBCodeEncoder instance');
	    }
	  }
	  getEncoder() {
	    return this.encoder;
	  }
	  setIsAllowedLinkify(value) {
	    this.allowedLinkify = Boolean(value);
	  }
	  isAllowedLinkify() {
	    return this.allowedLinkify;
	  }
	  canBeLinkified(node) {
	    if (node.getName() === '#text') {
	      const notAllowedNodeNames = ['url', 'img', 'video', 'code'];
	      const inNotAllowedNode = notAllowedNodeNames.some(name => {
	        return Boolean(AstProcessor.findParentNodeByName(node, name));
	      });
	      return !inNotAllowedNode;
	    }
	    return false;
	  }
	  static defaultOnUnknownHandler(node, scheme) {
	    if (node.getType() === Model.BBCodeNode.ELEMENT_NODE) {
	      const nodeName = node.getName();
	      if (['left', 'center', 'right', 'justify'].includes(nodeName)) {
	        const newNode = scheme.createElement({
	          name: 'p'
	        });
	        node.replace(newNode);
	        newNode.setChildren(node.getChildren());
	      } else if (['background', 'color', 'size'].includes(nodeName)) {
	        const newNode = scheme.createElement({
	          name: 'b'
	        });
	        node.replace(newNode);
	        newNode.setChildren(node.getChildren());
	      } else if (['span', 'font'].includes(nodeName)) {
	        const fragment = scheme.createFragment({
	          children: node.getChildren()
	        });
	        node.replace(fragment);
	      } else {
	        const openingTag = node.getOpeningTag();
	        const closingTag = node.getClosingTag();
	        node.replace(scheme.createText(openingTag), ...node.getChildren(), scheme.createText(closingTag));
	      }
	    }
	  }
	  static toLowerCase(value) {
	    if (Type.isStringFilled(value)) {
	      return value.toLowerCase();
	    }
	    return value;
	  }
	  parseText(text) {
	    if (Type.isStringFilled(text)) {
	      return [...text].reduce((acc, symbol) => {
	        if (isSpecialChar(symbol)) {
	          acc.push(symbol);
	        } else {
	          const lastItem = getByIndex(acc, -1);
	          if (isSpecialChar(lastItem) || Type.isNil(lastItem)) {
	            acc.push(symbol);
	          } else {
	            acc[acc.length - 1] += symbol;
	          }
	        }
	        return acc;
	      }, []).map(fragment => {
	        if (fragment === '\n') {
	          return parserScheme.createNewLine();
	        }
	        if (fragment === '\t') {
	          return parserScheme.createTab();
	        }
	        return parserScheme.createText({
	          content: this.getEncoder().decodeText(fragment)
	        });
	      });
	    }
	    return [];
	  }
	  static findNextTagIndex(bbcode, startIndex = 0) {
	    const nextContent = bbcode.slice(startIndex);
	    const matchResult = nextContent.match(new RegExp(TAG_REGEX));
	    if (matchResult) {
	      return matchResult.index + startIndex;
	    }
	    return -1;
	  }
	  static trimQuotes(value) {
	    const source = String(value);
	    if (/^["'].*["']$/g.test(source)) {
	      return source.slice(1, -1);
	    }
	    return value;
	  }
	  parseAttributes(sourceAttributes) {
	    const result = {
	      value: '',
	      attributes: []
	    };
	    if (Type.isStringFilled(sourceAttributes)) {
	      if (sourceAttributes.startsWith('=')) {
	        result.value = this.getEncoder().decodeAttribute(BBCodeParser.trimQuotes(sourceAttributes.slice(1)));
	        return result;
	      }
	      return sourceAttributes.trim().split(' ').filter(Boolean).reduce((acc, item) => {
	        const [key, value = ''] = item.split('=');
	        acc.attributes.push([BBCodeParser.toLowerCase(key), this.getEncoder().decodeAttribute(BBCodeParser.trimQuotes(value))]);
	        return acc;
	      }, result);
	    }
	    return result;
	  }
	  parse(bbcode) {
	    const result = parserScheme.createRoot();
	    const firstTagIndex = BBCodeParser.findNextTagIndex(bbcode);
	    if (firstTagIndex !== 0) {
	      const textBeforeFirstTag = firstTagIndex === -1 ? bbcode : bbcode.slice(0, firstTagIndex);
	      result.appendChild(...this.parseText(textBeforeFirstTag));
	    }
	    const stack = [result];
	    const wasOpened = [];
	    let current = null;
	    let level = 0;
	    bbcode.replace(TAG_REGEX_GS, (fullTag, slash, tagName, attrs, index) => {
	      const isOpeningTag = Boolean(slash) === false;
	      const startIndex = fullTag.length + index;
	      const nextContent = bbcode.slice(startIndex);
	      const attributes = this.parseAttributes(attrs);
	      const lowerCaseTagName = BBCodeParser.toLowerCase(tagName);
	      let parent = stack[level];
	      if (isOpeningTag) {
	        const isPotentiallyVoid = !nextContent.includes(`[/${tagName}]`);
	        if (isPotentiallyVoid && !isListItem(lowerCaseTagName)) {
	          const tagScheme = this.getScheme().getTagScheme(lowerCaseTagName);
	          const isAllowedVoidTag = tagScheme && tagScheme.isVoid();
	          if (isAllowedVoidTag) {
	            current = parserScheme.createElement({
	              name: lowerCaseTagName,
	              value: attributes.value,
	              attributes: Object.fromEntries(attributes.attributes)
	            });
	            current.setScheme(this.getScheme());
	            parent.appendChild(current);
	          } else {
	            parent.appendChild(parserScheme.createText(fullTag));
	          }
	          const nextTagIndex = BBCodeParser.findNextTagIndex(bbcode, startIndex);
	          if (nextTagIndex !== 0) {
	            const content = nextTagIndex === -1 ? nextContent : bbcode.slice(startIndex, nextTagIndex);
	            parent.appendChild(...this.parseText(content));
	          }
	        } else {
	          if (isListItem(lowerCaseTagName) && current && isListItem(current.getName())) {
	            level--;
	            parent = stack[level];
	          }
	          current = parserScheme.createElement({
	            name: lowerCaseTagName,
	            value: attributes.value,
	            attributes: Object.fromEntries(attributes.attributes)
	          });
	          const nextTagIndex = BBCodeParser.findNextTagIndex(bbcode, startIndex);
	          if (nextTagIndex !== 0) {
	            const content = nextTagIndex === -1 ? nextContent : bbcode.slice(startIndex, nextTagIndex);
	            current.appendChild(...this.parseText(content));
	          }
	          if (!parent) {
	            level++;
	            parent = stack[level];
	          }
	          parent.appendChild(current);
	          level++;
	          stack[level] = current;
	          wasOpened.push(lowerCaseTagName);
	        }
	      } else {
	        if (wasOpened.includes(lowerCaseTagName)) {
	          level--;
	          const openedTagIndex = wasOpened.indexOf(lowerCaseTagName);
	          wasOpened.splice(openedTagIndex, 1);
	        } else {
	          stack[level].appendChild(parserScheme.createText(fullTag));
	        }
	        if (isList(lowerCaseTagName) && level > 0) {
	          level--;
	        }
	        const nextTagIndex = BBCodeParser.findNextTagIndex(bbcode, startIndex);
	        if (nextTagIndex !== 0 && stack[level]) {
	          const content = nextTagIndex === -1 ? nextContent : bbcode.slice(startIndex, nextTagIndex);
	          stack[level].appendChild(...this.parseText(content));
	        }
	        if (level > 0 && isListItem(stack[level].getName())) {
	          level--;
	        }
	      }
	    });
	    const getFinalLineBreaksIndexes = node => {
	      let skip = false;
	      return node.getChildren().reduceRight((acc, child, index) => {
	        if (!skip && child.getName() === '#linebreak') {
	          acc.push(index);
	        } else if (!skip && child.getName() !== '#tab') {
	          skip = true;
	        }
	        return acc;
	      }, []);
	    };
	    Model.BBCodeNode.flattenAst(result).forEach(node => {
	      if (node.getName() === '*') {
	        const finalLinebreaksIndexes = getFinalLineBreaksIndexes(node);
	        if (finalLinebreaksIndexes.length === 1) {
	          node.setChildren(node.getChildren().slice(0, getByIndex(finalLinebreaksIndexes, 0)));
	        }
	        if (finalLinebreaksIndexes.length > 1 && (finalLinebreaksIndexes & 2) === 0) {
	          node.setChildren(node.getChildren().slice(0, getByIndex(finalLinebreaksIndexes, 0)));
	        }
	      }
	      if (this.isAllowedLinkify() && this.canBeLinkified(node)) {
	        const content = node.toString({
	          encode: false
	        });
	        const tokens = Linkify.tokenize(content);
	        const nodes = tokens.map(token => {
	          if (token.t === 'url') {
	            return parserScheme.createElement({
	              name: 'url',
	              value: token.toHref().replace(/^http:\/\//, 'https://'),
	              children: [parserScheme.createText(token.toString())]
	            });
	          }
	          if (token.t === 'email') {
	            return parserScheme.createElement({
	              name: 'url',
	              value: token.toHref(),
	              children: [parserScheme.createText(token.toString())]
	            });
	          }
	          return parserScheme.createText(token.toString());
	        });
	        node.replace(...nodes);
	      }
	    });
	    result.setScheme(this.getScheme(), this.getOnUnknownHandler());
	    return result;
	  }
	}

	exports.BBCodeParser = BBCodeParser;

});