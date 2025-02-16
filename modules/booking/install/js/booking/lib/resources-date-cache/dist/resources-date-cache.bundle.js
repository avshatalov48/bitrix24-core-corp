/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	class ResourcesDateCache {
	  constructor() {
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: {}
	    });
	  }
	  upsertIds(dateTs, ids) {
	    const currentIds = this.getIdsByDateTs(dateTs);
	    const newIds = ids.filter(id => !currentIds.includes(id));
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache][dateTs].push(...newIds);
	  }
	  isDateLoaded(dateTs, ids) {
	    const loadedResourcesIds = this.getIdsByDateTs(dateTs);
	    return ids.every(id => loadedResourcesIds.includes(id));
	  }
	  getIdsByDateTs(dateTs) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache])[dateTs]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[dateTs] = [];
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache][dateTs];
	  }
	}
	const resourcesDateCache = new ResourcesDateCache();

	exports.resourcesDateCache = resourcesDateCache;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {})));
//# sourceMappingURL=resources-date-cache.bundle.js.map
