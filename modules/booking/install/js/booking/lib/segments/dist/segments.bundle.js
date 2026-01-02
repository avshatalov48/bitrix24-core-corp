/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	var _segments = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("segments");
	class Segments {
	  constructor(segments = []) {
	    Object.defineProperty(this, _segments, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments] = segments;
	  }
	  toArray() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments];
	  }
	  add(segment) {
	    // <...[+++]...> new segment doesnt add anything
	    if (babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].some(it => segment[0] >= it[0] && segment[1] <= it[1])) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments];
	    }
	    const intersects = babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].some(it => segment[1] > it[0] && segment[0] < it[1]);
	    if (!intersects) {
	      babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments] = [...babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments], segment];
	      return babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments] = babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].map(it => {
	      // <...[...>+++] extend right
	      if (segment[0] >= it[0] && segment[0] <= it[1] && segment[1] >= it[1]) {
	        return [it[0], segment[1]];
	      }

	      // [+++<...]...> extend left
	      if (segment[1] >= it[0] && segment[1] <= it[1] && segment[1] <= it[0]) {
	        return [segment[0], it[1]];
	      }

	      // [+++<...>+++] replace
	      if (segment[0] < it[0] && segment[1] > it[1]) {
	        return segment;
	      }
	      return it;
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments];
	  }
	  subtract(segment) {
	    babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments] = babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments].flatMap(it => {
	      // <...[--->---] cut right
	      if (segment[0] >= it[0] && segment[0] <= it[1] && segment[1] >= it[1]) {
	        return [[it[0], segment[0]]];
	      }

	      // [---<---]...> cut left
	      if (segment[1] >= it[0] && segment[1] <= it[1] && segment[1] <= it[0]) {
	        return [[segment[1], it[1]]];
	      }

	      // <...[---]...> split into 2
	      if (segment[0] > it[0] && segment[1] < it[1]) {
	        return [[it[0], segment[0]], [segment[1], it[1]]];
	      }

	      // [---<--->---] remove
	      if (segment[0] < it[0] && segment[1] > it[1]) {
	        return null;
	      }
	      return [it];
	    }).filter(it => it !== null && it[1] - it[0] > 0);
	    return babelHelpers.classPrivateFieldLooseBase(this, _segments)[_segments];
	  }
	}

	exports.Segments = Segments;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=segments.bundle.js.map
