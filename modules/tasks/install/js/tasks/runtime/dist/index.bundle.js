this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core_events,main_core) {
	'use strict';

	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	class Pool {
	  constructor() {
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: []
	    });
	  }
	  add(index, fields) {
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].push({
	      [index]: {
	        fields
	      }
	    });
	  }
	  getItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items)[_items];
	  }
	  clean() {
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = [];
	  }
	  count() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].length;
	  }
	  isEmpty() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].length === 0;
	  }
	}

	const Status = Object.freeze({
	  RUN: 'run',
	  NONE: 'none'
	});

	var _status = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _timeout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeout");
	var _pool = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pool");
	var _debounce = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("debounce");
	class DebouncedQueue extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    var _options$timeout;
	    super();
	    Object.defineProperty(this, _status, {
	      writable: true,
	      value: Status.NONE
	    });
	    Object.defineProperty(this, _timeout, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _pool, {
	      writable: true,
	      value: new Pool()
	    });
	    Object.defineProperty(this, _debounce, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Tasks.DebounceQueue');
	    options = main_core.Type.isPlainObject(options) ? options : {};
	    this.subscribeFromOptions(options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _timeout)[_timeout] = (_options$timeout = options.timeout) != null ? _options$timeout : 1000;
	    babelHelpers.classPrivateFieldLooseBase(this, _debounce)[_debounce] = main_core.Runtime.debounce(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] = Status.RUN;
	      this.commit().then(() => babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] = Status.NONE);
	    }, babelHelpers.classPrivateFieldLooseBase(this, _timeout)[_timeout]);
	  }
	  push(fields, index = 'default') {
	    babelHelpers.classPrivateFieldLooseBase(this, _pool)[_pool].add(index, fields);
	    // console.log('pool', this.#pool.getItems());
	    // console.log(JSON.stringify(this.#pool.items));
	    this.commitWithDebounce();
	  }
	  commit() {
	    return new Promise((resolve, reject) => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _pool)[_pool].isEmpty() === false) {
	        // console.log('count', this.#pool.count());

	        const poolItems = babelHelpers.classPrivateFieldLooseBase(this, _pool)[_pool].getItems();
	        babelHelpers.classPrivateFieldLooseBase(this, _pool)[_pool].clean();
	        this.emitAsync('onCommitAsync', {
	          poolItems
	        }).then(() => this.commit().then(() => resolve())).catch();
	      } else {
	        resolve();
	      }
	    });
	  }
	  commitWithDebounce() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] === Status.NONE) {
	      babelHelpers.classPrivateFieldLooseBase(this, _debounce)[_debounce]();
	    }
	  }
	}

	exports.Pool = Pool;
	exports.DebouncedQueue = DebouncedQueue;
	exports.Status = Status;

}((this.BX.Tasks.Runtime = this.BX.Tasks.Runtime || {}),BX.Event,BX));
//# sourceMappingURL=index.bundle.js.map
