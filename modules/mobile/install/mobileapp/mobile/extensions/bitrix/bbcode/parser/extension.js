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

	const TAG_REGEX = /\[(\/)?(\w+|\*)([\s\w./:=]+)?]/gs;
	const isSpecialChar = symbol => {
	  return ['\n', '\t'].includes(symbol);
	};
	const isList = tagName => {
	  return ['list', 'ul', 'ol'].includes(tagName);
	};
	const isListItem = tagName => {
	  return ['*', 'li'].includes(tagName);
	};
	class Parser {
	  constructor(options = {}) {
	    if (options.scheme) {
	      this.setScheme(options.scheme);
	    } else {
	      this.setScheme(Model.defaultBbcodeScheme);
	    }
	  }
	  setScheme(scheme) {
	    this.scheme = scheme;
	  }
	  getScheme() {
	    return this.scheme;
	  }
	  static toLowerCase(value) {
	    if (Type.isStringFilled(value)) {
	      return value.toLowerCase();
	    }
	    return value;
	  }
	  parseText(text) {
	    const scheme = this.getScheme();
	    if (Type.isStringFilled(text)) {
	      return [...text].reduce((acc, symbol) => {
	        if (isSpecialChar(symbol)) {
	          acc.push(symbol);
	        } else {
	          const lastItem = acc.at(-1);
	          if (isSpecialChar(lastItem) || Type.isNil(lastItem)) {
	            acc.push(symbol);
	          } else {
	            acc[acc.length - 1] += symbol;
	          }
	        }
	        return acc;
	      }, []).map(fragment => {
	        if (fragment === '\n') {
	          return scheme.createNewLine();
	        }
	        if (fragment === '\t') {
	          return scheme.createTab();
	        }
	        return scheme.createText({
	          content: fragment
	        });
	      });
	    }
	    return [];
	  }
	  static findNextTagIndex(bbcode, startIndex = 0) {
	    const nextContent = bbcode.slice(startIndex);
	    const [nextTag] = nextContent.match(new RegExp(TAG_REGEX)) || [];
	    if (nextTag) {
	      return bbcode.indexOf(nextTag, startIndex);
	    }
	    return -1;
	  }
	  parseAttributes(sourceAttributes) {
	    const result = {
	      value: '',
	      attributes: []
	    };
	    if (Type.isStringFilled(sourceAttributes)) {
	      return sourceAttributes.trim().split(' ').filter(Boolean).reduce((acc, item) => {
	        if (item.startsWith('=')) {
	          acc.value = item.slice(1);
	          return acc;
	        }
	        const [key, value = ''] = item.split('=');
	        acc.attributes.push([Parser.toLowerCase(key), value]);
	        return acc;
	      }, result);
	    }
	    return result;
	  }
	  parse(bbcode) {
	    const scheme = this.getScheme();
	    const result = scheme.createRoot();
	    const stack = [];
	    let current = null;
	    let level = -1;
	    const firstTagIndex = Parser.findNextTagIndex(bbcode);
	    if (firstTagIndex !== 0) {
	      const textBeforeFirstTag = firstTagIndex === -1 ? bbcode : bbcode.slice(0, firstTagIndex);
	      result.appendChild(...this.parseText(textBeforeFirstTag));
	    }
	    bbcode.replace(TAG_REGEX, (fullTag, slash, tagName, attrs, index) => {
	      const isOpenTag = Boolean(slash) === false;
	      const startIndex = fullTag.length + index;
	      const nextContent = bbcode.slice(startIndex);
	      const attributes = this.parseAttributes(attrs);
	      const lowerCaseTagName = Parser.toLowerCase(tagName);
	      let parent = null;
	      if (isOpenTag) {
	        level++;
	        if (nextContent.includes(`[/${tagName}]`) || isListItem(lowerCaseTagName)) {
	          current = scheme.createElement({
	            name: lowerCaseTagName,
	            value: attributes.value,
	            attributes: Object.fromEntries(attributes.attributes)
	          });
	          const nextTagIndex = Parser.findNextTagIndex(bbcode, startIndex);
	          if (nextTagIndex !== 0) {
	            const content = nextTagIndex === -1 ? nextContent : bbcode.slice(startIndex, nextTagIndex);
	            current.appendChild(...this.parseText(content));
	          }
	        } else {
	          const tagScheme = scheme.getTagScheme(lowerCaseTagName);
	          if (tagScheme.isVoid()) {
	            current = scheme.createElement({
	              name: lowerCaseTagName,
	              value: attributes.value,
	              attributes: Object.fromEntries(attributes.attributes)
	            });
	          } else {
	            current = scheme.createText(fullTag);
	          }
	        }
	        if (level === 0) {
	          result.appendChild(current);
	        }
	        parent = stack[level - 1];
	        if (isList(current.getName())) {
	          if (parent && isList(parent.getName())) {
	            stack[level].appendChild(current);
	          }
	        } else if (parent && isList(parent.getName()) && !isListItem(current.getName())) {
	          const lastItem = parent.getChildren().at(-1);
	          if (lastItem) {
	            lastItem.appendChild(current);
	          }
	        } else if (parent) {
	          parent.appendChild(current);
	        }
	        stack[level] = current;
	        if (isListItem(lowerCaseTagName) && level > -1) {
	          level--;
	          current = level === -1 ? result : stack[level];
	        }
	      }
	      if (current.getName() === '#text') {
	        level--;
	      }
	      if (!isOpenTag || current.getName() === '#text' || current.isVoid()) {
	        if (level > -1 && current.getName() === lowerCaseTagName) {
	          level--;
	          current = level === -1 ? result : stack[level];
	        }
	        const nextTagIndex = Parser.findNextTagIndex(bbcode, startIndex);
	        if (nextTagIndex !== startIndex) {
	          parent = level === -1 ? result : stack[level];
	          const content = bbcode.slice(startIndex, nextTagIndex === -1 ? undefined : nextTagIndex);
	          if (isList(parent.getName())) {
	            const lastItem = parent.getChildren().at(-1);
	            if (lastItem) {
	              lastItem.appendChild(...this.parseText(content));
	            }
	          } else {
	            parent.appendChild(...this.parseText(content));
	          }
	        }
	      }
	    });
	    return result;
	  }
	}

	exports.Parser = Parser;

});