/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports) {
	'use strict';

	var _limit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("limit");
	var _components = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("components");
	class Color {
	  constructor(color) {
	    Object.defineProperty(this, _limit, {
	      writable: true,
	      value: 255
	    });
	    Object.defineProperty(this, _components, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _components)[_components] = this.parse(color);
	  }
	  toRgb() {
	    const [r, g, b, a] = babelHelpers.classPrivateFieldLooseBase(this, _components)[_components];
	    return `rgb(${[r, g, b].map(x => limitComponent(setComponentAlpha(x, a), babelHelpers.classPrivateFieldLooseBase(this, _limit)[_limit])).join(', ')})`;
	  }
	  toRgba() {
	    const [r, g, b, a] = babelHelpers.classPrivateFieldLooseBase(this, _components)[_components].map(x => limitComponent(x, babelHelpers.classPrivateFieldLooseBase(this, _limit)[_limit]));
	    return `rgba(${r}, ${g}, ${b}, ${a})`;
	  }
	  setOpacity(alpha) {
	    babelHelpers.classPrivateFieldLooseBase(this, _components)[_components][3] = alpha;
	    return this;
	  }
	  limit(limit) {
	    babelHelpers.classPrivateFieldLooseBase(this, _limit)[_limit] = limit;
	    return this;
	  }
	  isDark() {
	    const [r, g, b] = babelHelpers.classPrivateFieldLooseBase(this, _components)[_components];
	    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
	    return brightness < 170;
	  }
	  parse(color) {
	    if (color.slice(0, 3) === 'rgb') {
	      const [r, g, b, a] = color.match(/\d+(\.\d+)?/g).map(x => parseFloat(x));
	      return [r, g, b, a != null ? a : 1];
	    }
	    const [r, g, b, a] = color.match(/\w\w/g).map(x => parseInt(x, 16));
	    return [r, g, b, a != null ? a : 1];
	  }
	}
	const setComponentAlpha = (x, a) => Math.round((a * (x / 255) + (1 - a)) * 255);
	const limitComponent = (x, limit) => x < limit ? x : limit;

	exports.Color = Color;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {})));
//# sourceMappingURL=color.bundle.js.map
