(function (exports) {
    'use strict';

    /*!
     * Vue.js v2.6.10
     * (c) 2014-2019 Evan You
     * Released under the MIT License.
     */

    /**
     * Modify list for integration with Bitrix Framework:
     * - change default export to local for work in Bitrix CoreJS extensions;
     */
    var t = Object.freeze({});

    function e(t) {
      return null == t;
    }

    function n(t) {
      return null != t;
    }

    function o(t) {
      return !0 === t;
    }

    function r(t) {
      return "string" == typeof t || "number" == typeof t || "symbol" == babelHelpers.typeof(t) || "boolean" == typeof t;
    }

    function s(t) {
      return null !== t && "object" == babelHelpers.typeof(t);
    }

    var i = Object.prototype.toString;

    function a(t) {
      return "[object Object]" === i.call(t);
    }

    function c(t) {
      var e = parseFloat(String(t));
      return e >= 0 && Math.floor(e) === e && isFinite(t);
    }

    function l(t) {
      return n(t) && "function" == typeof t.then && "function" == typeof t.catch;
    }

    function u(t) {
      return null == t ? "" : Array.isArray(t) || a(t) && t.toString === i ? JSON.stringify(t, null, 2) : String(t);
    }

    function f(t) {
      var e = parseFloat(t);
      return isNaN(e) ? t : e;
    }

    function d(t, e) {
      var n = Object.create(null),
          o = t.split(",");

      for (var _t2 = 0; _t2 < o.length; _t2++) {
        n[o[_t2]] = !0;
      }

      return e ? function (t) {
        return n[t.toLowerCase()];
      } : function (t) {
        return n[t];
      };
    }

    var p = d("slot,component", !0),
        h = d("key,ref,slot,slot-scope,is");

    function m(t, e) {
      if (t.length) {
        var _n2 = t.indexOf(e);

        if (_n2 > -1) return t.splice(_n2, 1);
      }
    }

    var y = Object.prototype.hasOwnProperty;

    function g(t, e) {
      return y.call(t, e);
    }

    function v(t) {
      var e = Object.create(null);
      return function (n) {
        return e[n] || (e[n] = t(n));
      };
    }

    var $ = /-(\w)/g,
        _ = v(function (t) {
      return t.replace($, function (t, e) {
        return e ? e.toUpperCase() : "";
      });
    }),
        b = v(function (t) {
      return t.charAt(0).toUpperCase() + t.slice(1);
    }),
        w = /\B([A-Z])/g,
        C = v(function (t) {
      return t.replace(w, "-$1").toLowerCase();
    });

    var x = Function.prototype.bind ? function (t, e) {
      return t.bind(e);
    } : function (t, e) {
      function n(n) {
        var o = arguments.length;
        return o ? o > 1 ? t.apply(e, arguments) : t.call(e, n) : t.call(e);
      }

      return n._length = t.length, n;
    };

    function k(t, e) {
      e = e || 0;
      var n = t.length - e;
      var o = new Array(n);

      for (; n--;) {
        o[n] = t[n + e];
      }

      return o;
    }

    function A(t, e) {
      for (var _n3 in e) {
        t[_n3] = e[_n3];
      }

      return t;
    }

    function O(t) {
      var e = {};

      for (var _n4 = 0; _n4 < t.length; _n4++) {
        t[_n4] && A(e, t[_n4]);
      }

      return e;
    }

    function S(t, e, n) {}

    var T = function T(t, e, n) {
      return !1;
    },
        E = function E(t) {
      return t;
    };

    function N(t, e) {
      if (t === e) return !0;
      var n = s(t),
          o = s(e);
      if (!n || !o) return !n && !o && String(t) === String(e);

      try {
        var _n5 = Array.isArray(t),
            _o2 = Array.isArray(e);

        if (_n5 && _o2) return t.length === e.length && t.every(function (t, n) {
          return N(t, e[n]);
        });
        if (t instanceof Date && e instanceof Date) return t.getTime() === e.getTime();
        if (_n5 || _o2) return !1;
        {
          var _n6 = Object.keys(t),
              _o3 = Object.keys(e);

          return _n6.length === _o3.length && _n6.every(function (n) {
            return N(t[n], e[n]);
          });
        }
      } catch (t) {
        return !1;
      }
    }

    function j(t, e) {
      for (var _n7 = 0; _n7 < t.length; _n7++) {
        if (N(t[_n7], e)) return _n7;
      }

      return -1;
    }

    function D(t) {
      var e = !1;
      return function () {
        e || (e = !0, t.apply(this, arguments));
      };
    }

    var L = "data-server-rendered",
        M = ["component", "directive", "filter"],
        I = ["beforeCreate", "created", "beforeMount", "mounted", "beforeUpdate", "updated", "beforeDestroy", "destroyed", "activated", "deactivated", "errorCaptured", "serverPrefetch"];
    var F = {
      optionMergeStrategies: Object.create(null),
      silent: !1,
      productionTip: !1,
      devtools: !1,
      performance: !1,
      errorHandler: null,
      warnHandler: null,
      ignoredElements: [],
      keyCodes: Object.create(null),
      isReservedTag: T,
      isReservedAttr: T,
      isUnknownElement: T,
      getTagNamespace: S,
      parsePlatformTagName: E,
      mustUseProp: T,
      async: !0,
      _lifecycleHooks: I
    };
    var P = /a-zA-Z\u00B7\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u037D\u037F-\u1FFF\u200C-\u200D\u203F-\u2040\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD/;

    function R(t) {
      var e = (t + "").charCodeAt(0);
      return 36 === e || 95 === e;
    }

    function H(t, e, n, o) {
      Object.defineProperty(t, e, {
        value: n,
        enumerable: !!o,
        writable: !0,
        configurable: !0
      });
    }

    var B = new RegExp("[^".concat(P.source, ".$_\\d]"));
    var U = "__proto__" in {},
        z = "undefined" != typeof window,
        V = "undefined" != typeof WXEnvironment && !!WXEnvironment.platform,
        K = V && WXEnvironment.platform.toLowerCase(),
        J = z && window.navigator.userAgent.toLowerCase(),
        q = J && /msie|trident/.test(J),
        W = J && J.indexOf("msie 9.0") > 0,
        Z = J && J.indexOf("edge/") > 0,
        G = (J && J.indexOf("android"), J && /iphone|ipad|ipod|ios/.test(J) || "ios" === K),
        X = (J && /chrome\/\d+/.test(J), J && /phantomjs/.test(J), J && J.match(/firefox\/(\d+)/)),
        Y = {}.watch;
    var Q,
        tt = !1;
    if (z) try {
      var _t3 = {};
      Object.defineProperty(_t3, "passive", {
        get: function get() {
          tt = !0;
        }
      }), window.addEventListener("test-passive", null, _t3);
    } catch (t) {}

    var et = function et() {
      return void 0 === Q && (Q = !z && !V && "undefined" != typeof global && global.process && "server" === global.process.env.VUE_ENV), Q;
    },
        nt = z && window.__VUE_DEVTOOLS_GLOBAL_HOOK__;

    function ot(t) {
      return "function" == typeof t && /native code/.test(t.toString());
    }

    var rt = "undefined" != typeof Symbol && ot(Symbol) && "undefined" != typeof Reflect && ot(Reflect.ownKeys);
    var st;
    st = "undefined" != typeof Set && ot(Set) ? Set :
    /*#__PURE__*/
    function () {
      function _class() {
        babelHelpers.classCallCheck(this, _class);
        this.set = Object.create(null);
      }

      babelHelpers.createClass(_class, [{
        key: "has",
        value: function has(t) {
          return !0 === this.set[t];
        }
      }, {
        key: "add",
        value: function add(t) {
          this.set[t] = !0;
        }
      }, {
        key: "clear",
        value: function clear() {
          this.set = Object.create(null);
        }
      }]);
      return _class;
    }();
    var it = S,
        at = 0;

    var ct =
    /*#__PURE__*/
    function () {
      function ct() {
        babelHelpers.classCallCheck(this, ct);
        this.id = at++, this.subs = [];
      }

      babelHelpers.createClass(ct, [{
        key: "addSub",
        value: function addSub(t) {
          this.subs.push(t);
        }
      }, {
        key: "removeSub",
        value: function removeSub(t) {
          m(this.subs, t);
        }
      }, {
        key: "depend",
        value: function depend() {
          ct.target && ct.target.addDep(this);
        }
      }, {
        key: "notify",
        value: function notify() {
          var t = this.subs.slice();

          for (var _e2 = 0, _n8 = t.length; _e2 < _n8; _e2++) {
            t[_e2].update();
          }
        }
      }]);
      return ct;
    }();

    ct.target = null;
    var lt = [];

    function ut(t) {
      lt.push(t), ct.target = t;
    }

    function ft() {
      lt.pop(), ct.target = lt[lt.length - 1];
    }

    var dt =
    /*#__PURE__*/
    function () {
      function dt(t, e, n, o, r, s, i, a) {
        babelHelpers.classCallCheck(this, dt);
        this.tag = t, this.data = e, this.children = n, this.text = o, this.elm = r, this.ns = void 0, this.context = s, this.fnContext = void 0, this.fnOptions = void 0, this.fnScopeId = void 0, this.key = e && e.key, this.componentOptions = i, this.componentInstance = void 0, this.parent = void 0, this.raw = !1, this.isStatic = !1, this.isRootInsert = !0, this.isComment = !1, this.isCloned = !1, this.isOnce = !1, this.asyncFactory = a, this.asyncMeta = void 0, this.isAsyncPlaceholder = !1;
      }

      babelHelpers.createClass(dt, [{
        key: "child",
        get: function get() {
          return this.componentInstance;
        }
      }]);
      return dt;
    }();

    var pt = function pt() {
      var t = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "";
      var e = new dt();
      return e.text = t, e.isComment = !0, e;
    };

    function ht(t) {
      return new dt(void 0, void 0, void 0, String(t));
    }

    function mt(t) {
      var e = new dt(t.tag, t.data, t.children && t.children.slice(), t.text, t.elm, t.context, t.componentOptions, t.asyncFactory);
      return e.ns = t.ns, e.isStatic = t.isStatic, e.key = t.key, e.isComment = t.isComment, e.fnContext = t.fnContext, e.fnOptions = t.fnOptions, e.fnScopeId = t.fnScopeId, e.asyncMeta = t.asyncMeta, e.isCloned = !0, e;
    }

    var yt = Array.prototype,
        gt = Object.create(yt);
    ["push", "pop", "shift", "unshift", "splice", "sort", "reverse"].forEach(function (t) {
      var e = yt[t];
      H(gt, t, function () {
        for (var _len = arguments.length, n = new Array(_len), _key = 0; _key < _len; _key++) {
          n[_key] = arguments[_key];
        }

        var o = e.apply(this, n),
            r = this.__ob__;
        var s;

        switch (t) {
          case "push":
          case "unshift":
            s = n;
            break;

          case "splice":
            s = n.slice(2);
        }

        return s && r.observeArray(s), r.dep.notify(), o;
      });
    });
    var vt = Object.getOwnPropertyNames(gt);
    var $t = !0;

    function _t(t) {
      $t = t;
    }

    var bt =
    /*#__PURE__*/
    function () {
      function bt(t) {
        babelHelpers.classCallCheck(this, bt);
        var e;
        this.value = t, this.dep = new ct(), this.vmCount = 0, H(t, "__ob__", this), Array.isArray(t) ? (U ? (e = gt, t.__proto__ = e) : function (t, e, n) {
          for (var _o4 = 0, _r2 = n.length; _o4 < _r2; _o4++) {
            var _r3 = n[_o4];
            H(t, _r3, e[_r3]);
          }
        }(t, gt, vt), this.observeArray(t)) : this.walk(t);
      }

      babelHelpers.createClass(bt, [{
        key: "walk",
        value: function walk(t) {
          var e = Object.keys(t);

          for (var _n9 = 0; _n9 < e.length; _n9++) {
            Ct(t, e[_n9]);
          }
        }
      }, {
        key: "observeArray",
        value: function observeArray(t) {
          for (var _e3 = 0, _n10 = t.length; _e3 < _n10; _e3++) {
            wt(t[_e3]);
          }
        }
      }]);
      return bt;
    }();

    function wt(t, e) {
      if (!s(t) || t instanceof dt) return;
      var n;
      return g(t, "__ob__") && t.__ob__ instanceof bt ? n = t.__ob__ : $t && !et() && (Array.isArray(t) || a(t)) && Object.isExtensible(t) && !t._isVue && (n = new bt(t)), e && n && n.vmCount++, n;
    }

    function Ct(t, e, n, o, r) {
      var s = new ct(),
          i = Object.getOwnPropertyDescriptor(t, e);
      if (i && !1 === i.configurable) return;
      var a = i && i.get,
          c = i && i.set;
      a && !c || 2 !== arguments.length || (n = t[e]);
      var l = !r && wt(n);
      Object.defineProperty(t, e, {
        enumerable: !0,
        configurable: !0,
        get: function get() {
          var e = a ? a.call(t) : n;
          return ct.target && (s.depend(), l && (l.dep.depend(), Array.isArray(e) && function t(e) {
            for (var _n11, _o5 = 0, _r4 = e.length; _o5 < _r4; _o5++) {
              (_n11 = e[_o5]) && _n11.__ob__ && _n11.__ob__.dep.depend(), Array.isArray(_n11) && t(_n11);
            }
          }(e))), e;
        },
        set: function set(e) {
          var o = a ? a.call(t) : n;
          e === o || e != e && o != o || a && !c || (c ? c.call(t, e) : n = e, l = !r && wt(e), s.notify());
        }
      });
    }

    function xt(t, e, n) {
      if (Array.isArray(t) && c(e)) return t.length = Math.max(t.length, e), t.splice(e, 1, n), n;
      if (e in t && !(e in Object.prototype)) return t[e] = n, n;
      var o = t.__ob__;
      return t._isVue || o && o.vmCount ? n : o ? (Ct(o.value, e, n), o.dep.notify(), n) : (t[e] = n, n);
    }

    function kt(t, e) {
      if (Array.isArray(t) && c(e)) return void t.splice(e, 1);
      var n = t.__ob__;
      t._isVue || n && n.vmCount || g(t, e) && (delete t[e], n && n.dep.notify());
    }

    var At = F.optionMergeStrategies;

    function Ot(t, e) {
      if (!e) return t;
      var n, o, r;
      var s = rt ? Reflect.ownKeys(e) : Object.keys(e);

      for (var _i2 = 0; _i2 < s.length; _i2++) {
        "__ob__" !== (n = s[_i2]) && (o = t[n], r = e[n], g(t, n) ? o !== r && a(o) && a(r) && Ot(o, r) : xt(t, n, r));
      }

      return t;
    }

    function St(t, e, n) {
      return n ? function () {
        var o = "function" == typeof e ? e.call(n, n) : e,
            r = "function" == typeof t ? t.call(n, n) : t;
        return o ? Ot(o, r) : r;
      } : e ? t ? function () {
        return Ot("function" == typeof e ? e.call(this, this) : e, "function" == typeof t ? t.call(this, this) : t);
      } : e : t;
    }

    function Tt(t, e) {
      var n = e ? t ? t.concat(e) : Array.isArray(e) ? e : [e] : t;
      return n ? function (t) {
        var e = [];

        for (var _n12 = 0; _n12 < t.length; _n12++) {
          -1 === e.indexOf(t[_n12]) && e.push(t[_n12]);
        }

        return e;
      }(n) : n;
    }

    function Et(t, e, n, o) {
      var r = Object.create(t || null);
      return e ? A(r, e) : r;
    }

    At.data = function (t, e, n) {
      return n ? St(t, e, n) : e && "function" != typeof e ? t : St(t, e);
    }, I.forEach(function (t) {
      At[t] = Tt;
    }), M.forEach(function (t) {
      At[t + "s"] = Et;
    }), At.watch = function (t, e, n, o) {
      if (t === Y && (t = void 0), e === Y && (e = void 0), !e) return Object.create(t || null);
      if (!t) return e;
      var r = {};
      A(r, t);

      for (var _t4 in e) {
        var _n13 = r[_t4];
        var _o6 = e[_t4];
        _n13 && !Array.isArray(_n13) && (_n13 = [_n13]), r[_t4] = _n13 ? _n13.concat(_o6) : Array.isArray(_o6) ? _o6 : [_o6];
      }

      return r;
    }, At.props = At.methods = At.inject = At.computed = function (t, e, n, o) {
      if (!t) return e;
      var r = Object.create(null);
      return A(r, t), e && A(r, e), r;
    }, At.provide = St;

    var Nt = function Nt(t, e) {
      return void 0 === e ? t : e;
    };

    function jt(t, e, n) {
      if ("function" == typeof e && (e = e.options), function (t, e) {
        var n = t.props;
        if (!n) return;
        var o = {};
        var r, s, i;
        if (Array.isArray(n)) for (r = n.length; r--;) {
          "string" == typeof (s = n[r]) && (o[i = _(s)] = {
            type: null
          });
        } else if (a(n)) for (var _t5 in n) {
          s = n[_t5], o[i = _(_t5)] = a(s) ? s : {
            type: s
          };
        }
        t.props = o;
      }(e), function (t, e) {
        var n = t.inject;
        if (!n) return;
        var o = t.inject = {};
        if (Array.isArray(n)) for (var _t6 = 0; _t6 < n.length; _t6++) {
          o[n[_t6]] = {
            from: n[_t6]
          };
        } else if (a(n)) for (var _t7 in n) {
          var _e4 = n[_t7];
          o[_t7] = a(_e4) ? A({
            from: _t7
          }, _e4) : {
            from: _e4
          };
        }
      }(e), function (t) {
        var e = t.directives;
        if (e) for (var _t8 in e) {
          var _n14 = e[_t8];
          "function" == typeof _n14 && (e[_t8] = {
            bind: _n14,
            update: _n14
          });
        }
      }(e), !e._base && (e.extends && (t = jt(t, e.extends, n)), e.mixins)) for (var _o7 = 0, _r5 = e.mixins.length; _o7 < _r5; _o7++) {
        t = jt(t, e.mixins[_o7], n);
      }
      var o = {};
      var r;

      for (r in t) {
        s(r);
      }

      for (r in e) {
        g(t, r) || s(r);
      }

      function s(r) {
        var s = At[r] || Nt;
        o[r] = s(t[r], e[r], n, r);
      }

      return o;
    }

    function Dt(t, e, n, o) {
      if ("string" != typeof n) return;
      var r = t[e];
      if (g(r, n)) return r[n];

      var s = _(n);

      if (g(r, s)) return r[s];
      var i = b(s);
      return g(r, i) ? r[i] : r[n] || r[s] || r[i];
    }

    function Lt(t, e, n, o) {
      var r = e[t],
          s = !g(n, t);
      var i = n[t];
      var a = Ft(Boolean, r.type);
      if (a > -1) if (s && !g(r, "default")) i = !1;else if ("" === i || i === C(t)) {
        var _t9 = Ft(String, r.type);

        (_t9 < 0 || a < _t9) && (i = !0);
      }

      if (void 0 === i) {
        i = function (t, e, n) {
          if (!g(e, "default")) return;
          var o = e.default;
          if (t && t.$options.propsData && void 0 === t.$options.propsData[n] && void 0 !== t._props[n]) return t._props[n];
          return "function" == typeof o && "Function" !== Mt(e.type) ? o.call(t) : o;
        }(o, r, t);

        var _e5 = $t;
        _t(!0), wt(i), _t(_e5);
      }

      return i;
    }

    function Mt(t) {
      var e = t && t.toString().match(/^\s*function (\w+)/);
      return e ? e[1] : "";
    }

    function It(t, e) {
      return Mt(t) === Mt(e);
    }

    function Ft(t, e) {
      if (!Array.isArray(e)) return It(e, t) ? 0 : -1;

      for (var _n15 = 0, _o8 = e.length; _n15 < _o8; _n15++) {
        if (It(e[_n15], t)) return _n15;
      }

      return -1;
    }

    function Pt(t, e, n) {
      ut();

      try {
        if (e) {
          var _o9 = e;

          for (; _o9 = _o9.$parent;) {
            var _r6 = _o9.$options.errorCaptured;
            if (_r6) for (var _s2 = 0; _s2 < _r6.length; _s2++) {
              try {
                if (!1 === _r6[_s2].call(_o9, t, e, n)) return;
              } catch (t) {
                Ht(t, _o9, "errorCaptured hook");
              }
            }
          }
        }

        Ht(t, e, n);
      } finally {
        ft();
      }
    }

    function Rt(t, e, n, o, r) {
      var s;

      try {
        (s = n ? t.apply(e, n) : t.call(e)) && !s._isVue && l(s) && !s._handled && (s.catch(function (t) {
          return Pt(t, o, r + " (Promise/async)");
        }), s._handled = !0);
      } catch (t) {
        Pt(t, o, r);
      }

      return s;
    }

    function Ht(t, e, n) {
      if (F.errorHandler) try {
        return F.errorHandler.call(null, t, e, n);
      } catch (e) {
        e !== t && Bt(e, null, "config.errorHandler");
      }
      Bt(t, e, n);
    }

    function Bt(t, e, n) {
      if (!z && !V || "undefined" == typeof console) throw t;
      console.error(t);
    }

    var Ut = !1;
    var zt = [];
    var Vt,
        Kt = !1;

    function Jt() {
      Kt = !1;
      var t = zt.slice(0);
      zt.length = 0;

      for (var _e6 = 0; _e6 < t.length; _e6++) {
        t[_e6]();
      }
    }

    if ("undefined" != typeof Promise && ot(Promise)) {
      var _t10 = Promise.resolve();

      Vt = function Vt() {
        _t10.then(Jt), G && setTimeout(S);
      }, Ut = !0;
    } else if (q || "undefined" == typeof MutationObserver || !ot(MutationObserver) && "[object MutationObserverConstructor]" !== MutationObserver.toString()) Vt = "undefined" != typeof setImmediate && ot(setImmediate) ? function () {
      setImmediate(Jt);
    } : function () {
      setTimeout(Jt, 0);
    };else {
      var _t11 = 1;

      var _e7 = new MutationObserver(Jt),
          _n16 = document.createTextNode(String(_t11));

      _e7.observe(_n16, {
        characterData: !0
      }), Vt = function Vt() {
        _t11 = (_t11 + 1) % 2, _n16.data = String(_t11);
      }, Ut = !0;
    }

    function qt(t, e) {
      var n;
      if (zt.push(function () {
        if (t) try {
          t.call(e);
        } catch (t) {
          Pt(t, e, "nextTick");
        } else n && n(e);
      }), Kt || (Kt = !0, Vt()), !t && "undefined" != typeof Promise) return new Promise(function (t) {
        n = t;
      });
    }

    var Wt = new st();

    function Zt(t) {
      !function t(e, n) {
        var o, r;
        var i = Array.isArray(e);
        if (!i && !s(e) || Object.isFrozen(e) || e instanceof dt) return;

        if (e.__ob__) {
          var _t12 = e.__ob__.dep.id;
          if (n.has(_t12)) return;
          n.add(_t12);
        }

        if (i) for (o = e.length; o--;) {
          t(e[o], n);
        } else for (r = Object.keys(e), o = r.length; o--;) {
          t(e[r[o]], n);
        }
      }(t, Wt), Wt.clear();
    }

    var Gt = v(function (t) {
      var e = "&" === t.charAt(0),
          n = "~" === (t = e ? t.slice(1) : t).charAt(0),
          o = "!" === (t = n ? t.slice(1) : t).charAt(0);
      return {
        name: t = o ? t.slice(1) : t,
        once: n,
        capture: o,
        passive: e
      };
    });

    function Xt(t, e) {
      function n() {
        var t = n.fns;
        if (!Array.isArray(t)) return Rt(t, null, arguments, e, "v-on handler");
        {
          var _n17 = t.slice();

          for (var _t13 = 0; _t13 < _n17.length; _t13++) {
            Rt(_n17[_t13], null, arguments, e, "v-on handler");
          }
        }
      }

      return n.fns = t, n;
    }

    function Yt(t, n, r, s, i, a) {
      var c, l, u, f, d;

      for (c in t) {
        l = u = t[c], f = n[c], d = Gt(c), e(u) || (e(f) ? (e(u.fns) && (u = t[c] = Xt(u, a)), o(d.once) && (u = t[c] = i(d.name, u, d.capture)), r(d.name, u, d.capture, d.passive, d.params)) : u !== f && (f.fns = u, t[c] = f));
      }

      for (c in n) {
        e(t[c]) && s((d = Gt(c)).name, n[c], d.capture);
      }
    }

    function Qt(t, r, s) {
      var i;
      t instanceof dt && (t = t.data.hook || (t.data.hook = {}));
      var a = t[r];

      function c() {
        s.apply(this, arguments), m(i.fns, c);
      }

      e(a) ? i = Xt([c]) : n(a.fns) && o(a.merged) ? (i = a).fns.push(c) : i = Xt([a, c]), i.merged = !0, t[r] = i;
    }

    function te(t, e, o, r, s) {
      if (n(e)) {
        if (g(e, o)) return t[o] = e[o], s || delete e[o], !0;
        if (g(e, r)) return t[o] = e[r], s || delete e[r], !0;
      }

      return !1;
    }

    function ee(t) {
      return r(t) ? [ht(t)] : Array.isArray(t) ? function t(s, i) {
        var a = [];
        var c, l, u, f;

        for (c = 0; c < s.length; c++) {
          e(l = s[c]) || "boolean" == typeof l || (u = a.length - 1, f = a[u], Array.isArray(l) ? l.length > 0 && (ne((l = t(l, "".concat(i || "", "_").concat(c)))[0]) && ne(f) && (a[u] = ht(f.text + l[0].text), l.shift()), a.push.apply(a, l)) : r(l) ? ne(f) ? a[u] = ht(f.text + l) : "" !== l && a.push(ht(l)) : ne(l) && ne(f) ? a[u] = ht(f.text + l.text) : (o(s._isVList) && n(l.tag) && e(l.key) && n(i) && (l.key = "__vlist".concat(i, "_").concat(c, "__")), a.push(l)));
        }

        return a;
      }(t) : void 0;
    }

    function ne(t) {
      return n(t) && n(t.text) && !1 === t.isComment;
    }

    function oe(t, e) {
      if (t) {
        var _n18 = Object.create(null),
            _o10 = rt ? Reflect.ownKeys(t) : Object.keys(t);

        for (var _r7 = 0; _r7 < _o10.length; _r7++) {
          var _s3 = _o10[_r7];
          if ("__ob__" === _s3) continue;
          var _i3 = t[_s3].from;
          var _a = e;

          for (; _a;) {
            if (_a._provided && g(_a._provided, _i3)) {
              _n18[_s3] = _a._provided[_i3];
              break;
            }

            _a = _a.$parent;
          }

          if (!_a && "default" in t[_s3]) {
            var _o11 = t[_s3].default;
            _n18[_s3] = "function" == typeof _o11 ? _o11.call(e) : _o11;
          }
        }

        return _n18;
      }
    }

    function re(t, e) {
      if (!t || !t.length) return {};
      var n = {};

      for (var _o12 = 0, _r8 = t.length; _o12 < _r8; _o12++) {
        var _r9 = t[_o12],
            _s4 = _r9.data;
        if (_s4 && _s4.attrs && _s4.attrs.slot && delete _s4.attrs.slot, _r9.context !== e && _r9.fnContext !== e || !_s4 || null == _s4.slot) (n.default || (n.default = [])).push(_r9);else {
          var _t14 = _s4.slot,
              _e8 = n[_t14] || (n[_t14] = []);

          "template" === _r9.tag ? _e8.push.apply(_e8, _r9.children || []) : _e8.push(_r9);
        }
      }

      for (var _t15 in n) {
        n[_t15].every(se) && delete n[_t15];
      }

      return n;
    }

    function se(t) {
      return t.isComment && !t.asyncFactory || " " === t.text;
    }

    function ie(e, n, o) {
      var r;
      var s = Object.keys(n).length > 0,
          i = e ? !!e.$stable : !s,
          a = e && e.$key;

      if (e) {
        if (e._normalized) return e._normalized;
        if (i && o && o !== t && a === o.$key && !s && !o.$hasNormal) return o;
        r = {};

        for (var _t16 in e) {
          e[_t16] && "$" !== _t16[0] && (r[_t16] = ae(n, _t16, e[_t16]));
        }
      } else r = {};

      for (var _t17 in n) {
        _t17 in r || (r[_t17] = ce(n, _t17));
      }

      return e && Object.isExtensible(e) && (e._normalized = r), H(r, "$stable", i), H(r, "$key", a), H(r, "$hasNormal", s), r;
    }

    function ae(t, e, n) {
      var o = function o() {
        var t = arguments.length ? n.apply(null, arguments) : n({});
        return (t = t && "object" == babelHelpers.typeof(t) && !Array.isArray(t) ? [t] : ee(t)) && (0 === t.length || 1 === t.length && t[0].isComment) ? void 0 : t;
      };

      return n.proxy && Object.defineProperty(t, e, {
        get: o,
        enumerable: !0,
        configurable: !0
      }), o;
    }

    function ce(t, e) {
      return function () {
        return t[e];
      };
    }

    function le(t, e) {
      var o, r, i, a, c;
      if (Array.isArray(t) || "string" == typeof t) for (o = new Array(t.length), r = 0, i = t.length; r < i; r++) {
        o[r] = e(t[r], r);
      } else if ("number" == typeof t) for (o = new Array(t), r = 0; r < t; r++) {
        o[r] = e(r + 1, r);
      } else if (s(t)) if (rt && t[Symbol.iterator]) {
        o = [];

        var _n19 = t[Symbol.iterator]();

        var _r10 = _n19.next();

        for (; !_r10.done;) {
          o.push(e(_r10.value, o.length)), _r10 = _n19.next();
        }
      } else for (a = Object.keys(t), o = new Array(a.length), r = 0, i = a.length; r < i; r++) {
        c = a[r], o[r] = e(t[c], c, r);
      }
      return n(o) || (o = []), o._isVList = !0, o;
    }

    function ue(t, e, n, o) {
      var r = this.$scopedSlots[t];
      var s;
      r ? (n = n || {}, o && (n = A(A({}, o), n)), s = r(n) || e) : s = this.$slots[t] || e;
      var i = n && n.slot;
      return i ? this.$createElement("template", {
        slot: i
      }, s) : s;
    }

    function fe(t) {
      return Dt(this.$options, "filters", t) || E;
    }

    function de(t, e) {
      return Array.isArray(t) ? -1 === t.indexOf(e) : t !== e;
    }

    function pe(t, e, n, o, r) {
      var s = F.keyCodes[e] || n;
      return r && o && !F.keyCodes[e] ? de(r, o) : s ? de(s, t) : o ? C(o) !== e : void 0;
    }

    function he(t, e, n, o, r) {
      if (n) if (s(n)) {
        var _s5;

        Array.isArray(n) && (n = O(n));

        var _loop = function _loop(_i4) {
          if ("class" === _i4 || "style" === _i4 || h(_i4)) _s5 = t;else {
            var _n20 = t.attrs && t.attrs.type;

            _s5 = o || F.mustUseProp(e, _n20, _i4) ? t.domProps || (t.domProps = {}) : t.attrs || (t.attrs = {});
          }

          var a = _(_i4),
              c = C(_i4);

          if (!(a in _s5 || c in _s5) && (_s5[_i4] = n[_i4], r)) {
            (t.on || (t.on = {}))["update:".concat(_i4)] = function (t) {
              n[_i4] = t;
            };
          }
        };

        for (var _i4 in n) {
          _loop(_i4);
        }
      }
      return t;
    }

    function me(t, e) {
      var n = this._staticTrees || (this._staticTrees = []);
      var o = n[t];
      return o && !e ? o : (ge(o = n[t] = this.$options.staticRenderFns[t].call(this._renderProxy, null, this), "__static__".concat(t), !1), o);
    }

    function ye(t, e, n) {
      return ge(t, "__once__".concat(e).concat(n ? "_".concat(n) : ""), !0), t;
    }

    function ge(t, e, n) {
      if (Array.isArray(t)) for (var _o13 = 0; _o13 < t.length; _o13++) {
        t[_o13] && "string" != typeof t[_o13] && ve(t[_o13], "".concat(e, "_").concat(_o13), n);
      } else ve(t, e, n);
    }

    function ve(t, e, n) {
      t.isStatic = !0, t.key = e, t.isOnce = n;
    }

    function $e(t, e) {
      if (e) if (a(e)) {
        var _n21 = t.on = t.on ? A({}, t.on) : {};

        for (var _t18 in e) {
          var _o14 = _n21[_t18],
              _r11 = e[_t18];
          _n21[_t18] = _o14 ? [].concat(_o14, _r11) : _r11;
        }
      }
      return t;
    }

    function _e(t, e, n, o) {
      e = e || {
        $stable: !n
      };

      for (var _o15 = 0; _o15 < t.length; _o15++) {
        var _r12 = t[_o15];
        Array.isArray(_r12) ? _e(_r12, e, n) : _r12 && (_r12.proxy && (_r12.fn.proxy = !0), e[_r12.key] = _r12.fn);
      }

      return o && (e.$key = o), e;
    }

    function be(t, e) {
      for (var _n22 = 0; _n22 < e.length; _n22 += 2) {
        var _o16 = e[_n22];
        "string" == typeof _o16 && _o16 && (t[e[_n22]] = e[_n22 + 1]);
      }

      return t;
    }

    function we(t, e) {
      return "string" == typeof t ? e + t : t;
    }

    function Ce(t) {
      t._o = ye, t._n = f, t._s = u, t._l = le, t._t = ue, t._q = N, t._i = j, t._m = me, t._f = fe, t._k = pe, t._b = he, t._v = ht, t._e = pt, t._u = _e, t._g = $e, t._d = be, t._p = we;
    }

    function xe(e, n, r, s, i) {
      var _this = this;

      var a = i.options;
      var c;
      g(s, "_uid") ? (c = Object.create(s))._original = s : (c = s, s = s._original);
      var l = o(a._compiled),
          u = !l;
      this.data = e, this.props = n, this.children = r, this.parent = s, this.listeners = e.on || t, this.injections = oe(a.inject, s), this.slots = function () {
        return _this.$slots || ie(e.scopedSlots, _this.$slots = re(r, s)), _this.$slots;
      }, Object.defineProperty(this, "scopedSlots", {
        enumerable: !0,
        get: function get() {
          return ie(e.scopedSlots, this.slots());
        }
      }), l && (this.$options = a, this.$slots = this.slots(), this.$scopedSlots = ie(e.scopedSlots, this.$slots)), a._scopeId ? this._c = function (t, e, n, o) {
        var r = De(c, t, e, n, o, u);
        return r && !Array.isArray(r) && (r.fnScopeId = a._scopeId, r.fnContext = s), r;
      } : this._c = function (t, e, n, o) {
        return De(c, t, e, n, o, u);
      };
    }

    function ke(t, e, n, o, r) {
      var s = mt(t);
      return s.fnContext = n, s.fnOptions = o, e.slot && ((s.data || (s.data = {})).slot = e.slot), s;
    }

    function Ae(t, e) {
      for (var _n23 in e) {
        t[_(_n23)] = e[_n23];
      }
    }

    Ce(xe.prototype);
    var Oe = {
      init: function init(t, e) {
        if (t.componentInstance && !t.componentInstance._isDestroyed && t.data.keepAlive) {
          var _e9 = t;
          Oe.prepatch(_e9, _e9);
        } else {
          (t.componentInstance = function (t, e) {
            var o = {
              _isComponent: !0,
              _parentVnode: t,
              parent: e
            },
                r = t.data.inlineTemplate;
            n(r) && (o.render = r.render, o.staticRenderFns = r.staticRenderFns);
            return new t.componentOptions.Ctor(o);
          }(t, ze)).$mount(e ? t.elm : void 0, e);
        }
      },
      prepatch: function prepatch(e, n) {
        var o = n.componentOptions;
        !function (e, n, o, r, s) {
          var i = r.data.scopedSlots,
              a = e.$scopedSlots,
              c = !!(i && !i.$stable || a !== t && !a.$stable || i && e.$scopedSlots.$key !== i.$key),
              l = !!(s || e.$options._renderChildren || c);
          e.$options._parentVnode = r, e.$vnode = r, e._vnode && (e._vnode.parent = r);

          if (e.$options._renderChildren = s, e.$attrs = r.data.attrs || t, e.$listeners = o || t, n && e.$options.props) {
            _t(!1);

            var _t19 = e._props,
                _o17 = e.$options._propKeys || [];

            for (var _r13 = 0; _r13 < _o17.length; _r13++) {
              var _s6 = _o17[_r13],
                  _i5 = e.$options.props;
              _t19[_s6] = Lt(_s6, _i5, n, e);
            }

            _t(!0), e.$options.propsData = n;
          }

          o = o || t;
          var u = e.$options._parentListeners;
          e.$options._parentListeners = o, Ue(e, o, u), l && (e.$slots = re(s, r.context), e.$forceUpdate());
        }(n.componentInstance = e.componentInstance, o.propsData, o.listeners, n, o.children);
      },
      insert: function insert(t) {
        var e = t.context,
            n = t.componentInstance;
        var o;
        n._isMounted || (n._isMounted = !0, qe(n, "mounted")), t.data.keepAlive && (e._isMounted ? ((o = n)._inactive = !1, Ze.push(o)) : Je(n, !0));
      },
      destroy: function destroy(t) {
        var e = t.componentInstance;
        e._isDestroyed || (t.data.keepAlive ? function t(e, n) {
          if (n && (e._directInactive = !0, Ke(e))) return;

          if (!e._inactive) {
            e._inactive = !0;

            for (var _n24 = 0; _n24 < e.$children.length; _n24++) {
              t(e.$children[_n24]);
            }

            qe(e, "deactivated");
          }
        }(e, !0) : e.$destroy());
      }
    },
        Se = Object.keys(Oe);

    function Te(r, i, a, c, u) {
      if (e(r)) return;
      var f = a.$options._base;
      if (s(r) && (r = f.extend(r)), "function" != typeof r) return;
      var d;
      if (e(r.cid) && void 0 === (r = function (t, r) {
        if (o(t.error) && n(t.errorComp)) return t.errorComp;
        if (n(t.resolved)) return t.resolved;
        var i = Me;
        i && n(t.owners) && -1 === t.owners.indexOf(i) && t.owners.push(i);
        if (o(t.loading) && n(t.loadingComp)) return t.loadingComp;

        if (i && !n(t.owners)) {
          var _o18 = t.owners = [i];

          var _a2 = !0,
              _c = null,
              _u = null;

          i.$on("hook:destroyed", function () {
            return m(_o18, i);
          });

          var _f = function _f(t) {
            for (var _t20 = 0, _e10 = _o18.length; _t20 < _e10; _t20++) {
              _o18[_t20].$forceUpdate();
            }

            t && (_o18.length = 0, null !== _c && (clearTimeout(_c), _c = null), null !== _u && (clearTimeout(_u), _u = null));
          },
              _d = D(function (e) {
            t.resolved = Ie(e, r), _a2 ? _o18.length = 0 : _f(!0);
          }),
              _p = D(function (e) {
            n(t.errorComp) && (t.error = !0, _f(!0));
          }),
              _h = t(_d, _p);

          return s(_h) && (l(_h) ? e(t.resolved) && _h.then(_d, _p) : l(_h.component) && (_h.component.then(_d, _p), n(_h.error) && (t.errorComp = Ie(_h.error, r)), n(_h.loading) && (t.loadingComp = Ie(_h.loading, r), 0 === _h.delay ? t.loading = !0 : _c = setTimeout(function () {
            _c = null, e(t.resolved) && e(t.error) && (t.loading = !0, _f(!1));
          }, _h.delay || 200)), n(_h.timeout) && (_u = setTimeout(function () {
            _u = null, e(t.resolved) && _p(null);
          }, _h.timeout)))), _a2 = !1, t.loading ? t.loadingComp : t.resolved;
        }
      }(d = r, f))) return function (t, e, n, o, r) {
        var s = pt();
        return s.asyncFactory = t, s.asyncMeta = {
          data: e,
          context: n,
          children: o,
          tag: r
        }, s;
      }(d, i, a, c, u);
      i = i || {}, mn(r), n(i.model) && function (t, e) {
        var o = t.model && t.model.prop || "value",
            r = t.model && t.model.event || "input";
        (e.attrs || (e.attrs = {}))[o] = e.model.value;
        var s = e.on || (e.on = {}),
            i = s[r],
            a = e.model.callback;
        n(i) ? (Array.isArray(i) ? -1 === i.indexOf(a) : i !== a) && (s[r] = [a].concat(i)) : s[r] = a;
      }(r.options, i);

      var p = function (t, o, r) {
        var s = o.options.props;
        if (e(s)) return;
        var i = {},
            a = t.attrs,
            c = t.props;
        if (n(a) || n(c)) for (var _t21 in s) {
          var _e11 = C(_t21);

          te(i, c, _t21, _e11, !0) || te(i, a, _t21, _e11, !1);
        }
        return i;
      }(i, r);

      if (o(r.options.functional)) return function (e, o, r, s, i) {
        var a = e.options,
            c = {},
            l = a.props;
        if (n(l)) for (var _e12 in l) {
          c[_e12] = Lt(_e12, l, o || t);
        } else n(r.attrs) && Ae(c, r.attrs), n(r.props) && Ae(c, r.props);
        var u = new xe(r, c, i, s, e),
            f = a.render.call(null, u._c, u);
        if (f instanceof dt) return ke(f, r, u.parent, a);

        if (Array.isArray(f)) {
          var _t22 = ee(f) || [],
              _e13 = new Array(_t22.length);

          for (var _n25 = 0; _n25 < _t22.length; _n25++) {
            _e13[_n25] = ke(_t22[_n25], r, u.parent, a);
          }

          return _e13;
        }
      }(r, p, i, a, c);
      var h = i.on;

      if (i.on = i.nativeOn, o(r.options.abstract)) {
        var _t23 = i.slot;
        i = {}, _t23 && (i.slot = _t23);
      }

      !function (t) {
        var e = t.hook || (t.hook = {});

        for (var _t24 = 0; _t24 < Se.length; _t24++) {
          var _n26 = Se[_t24],
              _o19 = e[_n26],
              _r14 = Oe[_n26];
          _o19 === _r14 || _o19 && _o19._merged || (e[_n26] = _o19 ? Ee(_r14, _o19) : _r14);
        }
      }(i);
      var y = r.options.name || u;
      return new dt("vue-component-".concat(r.cid).concat(y ? "-".concat(y) : ""), i, void 0, void 0, void 0, a, {
        Ctor: r,
        propsData: p,
        listeners: h,
        tag: u,
        children: c
      }, d);
    }

    function Ee(t, e) {
      var n = function n(_n27, o) {
        t(_n27, o), e(_n27, o);
      };

      return n._merged = !0, n;
    }

    var Ne = 1,
        je = 2;

    function De(t, i, a, c, l, u) {
      return (Array.isArray(a) || r(a)) && (l = c, c = a, a = void 0), o(u) && (l = je), function (t, r, i, a, c) {
        if (n(i) && n(i.__ob__)) return pt();
        n(i) && n(i.is) && (r = i.is);
        if (!r) return pt();
        Array.isArray(a) && "function" == typeof a[0] && ((i = i || {}).scopedSlots = {
          default: a[0]
        }, a.length = 0);
        c === je ? a = ee(a) : c === Ne && (a = function (t) {
          for (var _e14 = 0; _e14 < t.length; _e14++) {
            if (Array.isArray(t[_e14])) return Array.prototype.concat.apply([], t);
          }

          return t;
        }(a));
        var l, u;

        if ("string" == typeof r) {
          var _e15;

          u = t.$vnode && t.$vnode.ns || F.getTagNamespace(r), l = F.isReservedTag(r) ? new dt(F.parsePlatformTagName(r), i, a, void 0, void 0, t) : i && i.pre || !n(_e15 = Dt(t.$options, "components", r)) ? new dt(r, i, a, void 0, void 0, t) : Te(_e15, i, t, a, r);
        } else l = Te(r, i, t, a);

        return Array.isArray(l) ? l : n(l) ? (n(u) && function t(r, s, i) {
          r.ns = s;
          "foreignObject" === r.tag && (s = void 0, i = !0);
          if (n(r.children)) for (var _a3 = 0, _c2 = r.children.length; _a3 < _c2; _a3++) {
            var _c3 = r.children[_a3];
            n(_c3.tag) && (e(_c3.ns) || o(i) && "svg" !== _c3.tag) && t(_c3, s, i);
          }
        }(l, u), n(i) && function (t) {
          s(t.style) && Zt(t.style);
          s(t.class) && Zt(t.class);
        }(i), l) : pt();
      }(t, i, a, c, l);
    }

    var Le,
        Me = null;

    function Ie(t, e) {
      return (t.__esModule || rt && "Module" === t[Symbol.toStringTag]) && (t = t.default), s(t) ? e.extend(t) : t;
    }

    function Fe(t) {
      return t.isComment && t.asyncFactory;
    }

    function Pe(t) {
      if (Array.isArray(t)) for (var _e16 = 0; _e16 < t.length; _e16++) {
        var _o20 = t[_e16];
        if (n(_o20) && (n(_o20.componentOptions) || Fe(_o20))) return _o20;
      }
    }

    function Re(t, e) {
      Le.$on(t, e);
    }

    function He(t, e) {
      Le.$off(t, e);
    }

    function Be(t, e) {
      var n = Le;
      return function o() {
        null !== e.apply(null, arguments) && n.$off(t, o);
      };
    }

    function Ue(t, e, n) {
      Le = t, Yt(e, n || {}, Re, He, Be, t), Le = void 0;
    }

    var ze = null;

    function Ve(t) {
      var e = ze;
      return ze = t, function () {
        ze = e;
      };
    }

    function Ke(t) {
      for (; t && (t = t.$parent);) {
        if (t._inactive) return !0;
      }

      return !1;
    }

    function Je(t, e) {
      if (e) {
        if (t._directInactive = !1, Ke(t)) return;
      } else if (t._directInactive) return;

      if (t._inactive || null === t._inactive) {
        t._inactive = !1;

        for (var _e17 = 0; _e17 < t.$children.length; _e17++) {
          Je(t.$children[_e17]);
        }

        qe(t, "activated");
      }
    }

    function qe(t, e) {
      ut();
      var n = t.$options[e],
          o = "".concat(e, " hook");
      if (n) for (var _e18 = 0, _r15 = n.length; _e18 < _r15; _e18++) {
        Rt(n[_e18], t, null, t, o);
      }
      t._hasHookEvent && t.$emit("hook:" + e), ft();
    }

    var We = [],
        Ze = [];
    var Ge = {},
        Xe = !1,
        Ye = !1,
        Qe = 0;
    var tn = 0,
        en = Date.now;

    if (z && !q) {
      var _t25 = window.performance;
      _t25 && "function" == typeof _t25.now && en() > document.createEvent("Event").timeStamp && (en = function en() {
        return _t25.now();
      });
    }

    function nn() {
      var t, e;

      for (tn = en(), Ye = !0, We.sort(function (t, e) {
        return t.id - e.id;
      }), Qe = 0; Qe < We.length; Qe++) {
        (t = We[Qe]).before && t.before(), e = t.id, Ge[e] = null, t.run();
      }

      var n = Ze.slice(),
          o = We.slice();
      Qe = We.length = Ze.length = 0, Ge = {}, Xe = Ye = !1, function (t) {
        for (var _e19 = 0; _e19 < t.length; _e19++) {
          t[_e19]._inactive = !0, Je(t[_e19], !0);
        }
      }(n), function (t) {
        var e = t.length;

        for (; e--;) {
          var _n28 = t[e],
              _o21 = _n28.vm;
          _o21._watcher === _n28 && _o21._isMounted && !_o21._isDestroyed && qe(_o21, "updated");
        }
      }(o), nt && F.devtools && nt.emit("flush");
    }

    var on = 0;

    var rn =
    /*#__PURE__*/
    function () {
      function rn(t, e, n, o, r) {
        babelHelpers.classCallCheck(this, rn);
        this.vm = t, r && (t._watcher = this), t._watchers.push(this), o ? (this.deep = !!o.deep, this.user = !!o.user, this.lazy = !!o.lazy, this.sync = !!o.sync, this.before = o.before) : this.deep = this.user = this.lazy = this.sync = !1, this.cb = n, this.id = ++on, this.active = !0, this.dirty = this.lazy, this.deps = [], this.newDeps = [], this.depIds = new st(), this.newDepIds = new st(), this.expression = "", "function" == typeof e ? this.getter = e : (this.getter = function (t) {
          if (B.test(t)) return;
          var e = t.split(".");
          return function (t) {
            for (var _n29 = 0; _n29 < e.length; _n29++) {
              if (!t) return;
              t = t[e[_n29]];
            }

            return t;
          };
        }(e), this.getter || (this.getter = S)), this.value = this.lazy ? void 0 : this.get();
      }

      babelHelpers.createClass(rn, [{
        key: "get",
        value: function get() {
          var t;
          ut(this);
          var e = this.vm;

          try {
            t = this.getter.call(e, e);
          } catch (t) {
            if (!this.user) throw t;
            Pt(t, e, "getter for watcher \"".concat(this.expression, "\""));
          } finally {
            this.deep && Zt(t), ft(), this.cleanupDeps();
          }

          return t;
        }
      }, {
        key: "addDep",
        value: function addDep(t) {
          var e = t.id;
          this.newDepIds.has(e) || (this.newDepIds.add(e), this.newDeps.push(t), this.depIds.has(e) || t.addSub(this));
        }
      }, {
        key: "cleanupDeps",
        value: function cleanupDeps() {
          var t = this.deps.length;

          for (; t--;) {
            var _e20 = this.deps[t];
            this.newDepIds.has(_e20.id) || _e20.removeSub(this);
          }

          var e = this.depIds;
          this.depIds = this.newDepIds, this.newDepIds = e, this.newDepIds.clear(), e = this.deps, this.deps = this.newDeps, this.newDeps = e, this.newDeps.length = 0;
        }
      }, {
        key: "update",
        value: function update() {
          this.lazy ? this.dirty = !0 : this.sync ? this.run() : function (t) {
            var e = t.id;

            if (null == Ge[e]) {
              if (Ge[e] = !0, Ye) {
                var _e21 = We.length - 1;

                for (; _e21 > Qe && We[_e21].id > t.id;) {
                  _e21--;
                }

                We.splice(_e21 + 1, 0, t);
              } else We.push(t);

              Xe || (Xe = !0, qt(nn));
            }
          }(this);
        }
      }, {
        key: "run",
        value: function run() {
          if (this.active) {
            var _t26 = this.get();

            if (_t26 !== this.value || s(_t26) || this.deep) {
              var _e22 = this.value;
              if (this.value = _t26, this.user) try {
                this.cb.call(this.vm, _t26, _e22);
              } catch (t) {
                Pt(t, this.vm, "callback for watcher \"".concat(this.expression, "\""));
              } else this.cb.call(this.vm, _t26, _e22);
            }
          }
        }
      }, {
        key: "evaluate",
        value: function evaluate() {
          this.value = this.get(), this.dirty = !1;
        }
      }, {
        key: "depend",
        value: function depend() {
          var t = this.deps.length;

          for (; t--;) {
            this.deps[t].depend();
          }
        }
      }, {
        key: "teardown",
        value: function teardown() {
          if (this.active) {
            this.vm._isBeingDestroyed || m(this.vm._watchers, this);
            var _t27 = this.deps.length;

            for (; _t27--;) {
              this.deps[_t27].removeSub(this);
            }

            this.active = !1;
          }
        }
      }]);
      return rn;
    }();

    var sn = {
      enumerable: !0,
      configurable: !0,
      get: S,
      set: S
    };

    function an(t, e, n) {
      sn.get = function () {
        return this[e][n];
      }, sn.set = function (t) {
        this[e][n] = t;
      }, Object.defineProperty(t, n, sn);
    }

    function cn(t) {
      t._watchers = [];
      var e = t.$options;
      e.props && function (t, e) {
        var n = t.$options.propsData || {},
            o = t._props = {},
            r = t.$options._propKeys = [];
        t.$parent && _t(!1);

        for (var _s7 in e) {
          r.push(_s7);

          var _i6 = Lt(_s7, e, n, t);

          Ct(o, _s7, _i6), _s7 in t || an(t, "_props", _s7);
        }

        _t(!0);
      }(t, e.props), e.methods && function (t, e) {
        t.$options.props;

        for (var _n30 in e) {
          t[_n30] = "function" != typeof e[_n30] ? S : x(e[_n30], t);
        }
      }(t, e.methods), e.data ? function (t) {
        var e = t.$options.data;
        a(e = t._data = "function" == typeof e ? function (t, e) {
          ut();

          try {
            return t.call(e, e);
          } catch (t) {
            return Pt(t, e, "data()"), {};
          } finally {
            ft();
          }
        }(e, t) : e || {}) || (e = {});
        var n = Object.keys(e),
            o = t.$options.props;
        t.$options.methods;
        var r = n.length;

        for (; r--;) {
          var _e23 = n[r];
          o && g(o, _e23) || R(_e23) || an(t, "_data", _e23);
        }

        wt(e, !0);
      }(t) : wt(t._data = {}, !0), e.computed && function (t, e) {
        var n = t._computedWatchers = Object.create(null),
            o = et();

        for (var _r16 in e) {
          var _s8 = e[_r16],
              _i7 = "function" == typeof _s8 ? _s8 : _s8.get;

          o || (n[_r16] = new rn(t, _i7 || S, S, ln)), _r16 in t || un(t, _r16, _s8);
        }
      }(t, e.computed), e.watch && e.watch !== Y && function (t, e) {
        for (var _n31 in e) {
          var _o22 = e[_n31];
          if (Array.isArray(_o22)) for (var _e24 = 0; _e24 < _o22.length; _e24++) {
            pn(t, _n31, _o22[_e24]);
          } else pn(t, _n31, _o22);
        }
      }(t, e.watch);
    }

    var ln = {
      lazy: !0
    };

    function un(t, e, n) {
      var o = !et();
      "function" == typeof n ? (sn.get = o ? fn(e) : dn(n), sn.set = S) : (sn.get = n.get ? o && !1 !== n.cache ? fn(e) : dn(n.get) : S, sn.set = n.set || S), Object.defineProperty(t, e, sn);
    }

    function fn(t) {
      return function () {
        var e = this._computedWatchers && this._computedWatchers[t];
        if (e) return e.dirty && e.evaluate(), ct.target && e.depend(), e.value;
      };
    }

    function dn(t) {
      return function () {
        return t.call(this, this);
      };
    }

    function pn(t, e, n, o) {
      return a(n) && (o = n, n = n.handler), "string" == typeof n && (n = t[n]), t.$watch(e, n, o);
    }

    var hn = 0;

    function mn(t) {
      var e = t.options;

      if (t.super) {
        var _n32 = mn(t.super);

        if (_n32 !== t.superOptions) {
          t.superOptions = _n32;

          var _o23 = function (t) {
            var e;
            var n = t.options,
                o = t.sealedOptions;

            for (var _t28 in n) {
              n[_t28] !== o[_t28] && (e || (e = {}), e[_t28] = n[_t28]);
            }

            return e;
          }(t);

          _o23 && A(t.extendOptions, _o23), (e = t.options = jt(_n32, t.extendOptions)).name && (e.components[e.name] = t);
        }
      }

      return e;
    }

    function yn(t) {
      this._init(t);
    }

    function gn(t) {
      t.cid = 0;
      var e = 1;

      t.extend = function (t) {
        t = t || {};
        var n = this,
            o = n.cid,
            r = t._Ctor || (t._Ctor = {});
        if (r[o]) return r[o];

        var s = t.name || n.options.name,
            i = function i(t) {
          this._init(t);
        };

        return (i.prototype = Object.create(n.prototype)).constructor = i, i.cid = e++, i.options = jt(n.options, t), i.super = n, i.options.props && function (t) {
          var e = t.options.props;

          for (var _n33 in e) {
            an(t.prototype, "_props", _n33);
          }
        }(i), i.options.computed && function (t) {
          var e = t.options.computed;

          for (var _n34 in e) {
            un(t.prototype, _n34, e[_n34]);
          }
        }(i), i.extend = n.extend, i.mixin = n.mixin, i.use = n.use, M.forEach(function (t) {
          i[t] = n[t];
        }), s && (i.options.components[s] = i), i.superOptions = n.options, i.extendOptions = t, i.sealedOptions = A({}, i.options), r[o] = i, i;
      };
    }

    function vn(t) {
      return t && (t.Ctor.options.name || t.tag);
    }

    function $n(t, e) {
      return Array.isArray(t) ? t.indexOf(e) > -1 : "string" == typeof t ? t.split(",").indexOf(e) > -1 : (n = t, "[object RegExp]" === i.call(n) && t.test(e));
      var n;
    }

    function _n(t, e) {
      var n = t.cache,
          o = t.keys,
          r = t._vnode;

      for (var _t29 in n) {
        var _s9 = n[_t29];

        if (_s9) {
          var _i8 = vn(_s9.componentOptions);

          _i8 && !e(_i8) && bn(n, _t29, o, r);
        }
      }
    }

    function bn(t, e, n, o) {
      var r = t[e];
      !r || o && r.tag === o.tag || r.componentInstance.$destroy(), t[e] = null, m(n, e);
    }

    !function (e) {
      e.prototype._init = function (e) {
        var n = this;
        n._uid = hn++, n._isVue = !0, e && e._isComponent ? function (t, e) {
          var n = t.$options = Object.create(t.constructor.options),
              o = e._parentVnode;
          n.parent = e.parent, n._parentVnode = o;
          var r = o.componentOptions;
          n.propsData = r.propsData, n._parentListeners = r.listeners, n._renderChildren = r.children, n._componentTag = r.tag, e.render && (n.render = e.render, n.staticRenderFns = e.staticRenderFns);
        }(n, e) : n.$options = jt(mn(n.constructor), e || {}, n), n._renderProxy = n, n._self = n, function (t) {
          var e = t.$options;
          var n = e.parent;

          if (n && !e.abstract) {
            for (; n.$options.abstract && n.$parent;) {
              n = n.$parent;
            }

            n.$children.push(t);
          }

          t.$parent = n, t.$root = n ? n.$root : t, t.$children = [], t.$refs = {}, t._watcher = null, t._inactive = null, t._directInactive = !1, t._isMounted = !1, t._isDestroyed = !1, t._isBeingDestroyed = !1;
        }(n), function (t) {
          t._events = Object.create(null), t._hasHookEvent = !1;
          var e = t.$options._parentListeners;
          e && Ue(t, e);
        }(n), function (e) {
          e._vnode = null, e._staticTrees = null;
          var n = e.$options,
              o = e.$vnode = n._parentVnode,
              r = o && o.context;
          e.$slots = re(n._renderChildren, r), e.$scopedSlots = t, e._c = function (t, n, o, r) {
            return De(e, t, n, o, r, !1);
          }, e.$createElement = function (t, n, o, r) {
            return De(e, t, n, o, r, !0);
          };
          var s = o && o.data;
          Ct(e, "$attrs", s && s.attrs || t, null, !0), Ct(e, "$listeners", n._parentListeners || t, null, !0);
        }(n), qe(n, "beforeCreate"), function (t) {
          var e = oe(t.$options.inject, t);
          e && (_t(!1), Object.keys(e).forEach(function (n) {
            Ct(t, n, e[n]);
          }), _t(!0));
        }(n), cn(n), function (t) {
          var e = t.$options.provide;
          e && (t._provided = "function" == typeof e ? e.call(t) : e);
        }(n), qe(n, "created"), n.$options.el && n.$mount(n.$options.el);
      };
    }(yn), function (t) {
      var e = {
        get: function get() {
          return this._data;
        }
      },
          n = {
        get: function get() {
          return this._props;
        }
      };
      Object.defineProperty(t.prototype, "$data", e), Object.defineProperty(t.prototype, "$props", n), t.prototype.$set = xt, t.prototype.$delete = kt, t.prototype.$watch = function (t, e, n) {
        var o = this;
        if (a(e)) return pn(o, t, e, n);
        (n = n || {}).user = !0;
        var r = new rn(o, t, e, n);
        if (n.immediate) try {
          e.call(o, r.value);
        } catch (t) {
          Pt(t, o, "callback for immediate watcher \"".concat(r.expression, "\""));
        }
        return function () {
          r.teardown();
        };
      };
    }(yn), function (t) {
      var e = /^hook:/;
      t.prototype.$on = function (t, n) {
        var o = this;
        if (Array.isArray(t)) for (var _e25 = 0, _r17 = t.length; _e25 < _r17; _e25++) {
          o.$on(t[_e25], n);
        } else (o._events[t] || (o._events[t] = [])).push(n), e.test(t) && (o._hasHookEvent = !0);
        return o;
      }, t.prototype.$once = function (t, e) {
        var n = this;

        function o() {
          n.$off(t, o), e.apply(n, arguments);
        }

        return o.fn = e, n.$on(t, o), n;
      }, t.prototype.$off = function (t, e) {
        var n = this;
        if (!arguments.length) return n._events = Object.create(null), n;

        if (Array.isArray(t)) {
          for (var _o24 = 0, _r18 = t.length; _o24 < _r18; _o24++) {
            n.$off(t[_o24], e);
          }

          return n;
        }

        var o = n._events[t];
        if (!o) return n;
        if (!e) return n._events[t] = null, n;
        var r,
            s = o.length;

        for (; s--;) {
          if ((r = o[s]) === e || r.fn === e) {
            o.splice(s, 1);
            break;
          }
        }

        return n;
      }, t.prototype.$emit = function (t) {
        var e = this;
        var n = e._events[t];

        if (n) {
          n = n.length > 1 ? k(n) : n;

          var _o25 = k(arguments, 1),
              _r19 = "event handler for \"".concat(t, "\"");

          for (var _t30 = 0, _s10 = n.length; _t30 < _s10; _t30++) {
            Rt(n[_t30], e, _o25, e, _r19);
          }
        }

        return e;
      };
    }(yn), function (t) {
      t.prototype._update = function (t, e) {
        var n = this,
            o = n.$el,
            r = n._vnode,
            s = Ve(n);
        n._vnode = t, n.$el = r ? n.__patch__(r, t) : n.__patch__(n.$el, t, e, !1), s(), o && (o.__vue__ = null), n.$el && (n.$el.__vue__ = n), n.$vnode && n.$parent && n.$vnode === n.$parent._vnode && (n.$parent.$el = n.$el);
      }, t.prototype.$forceUpdate = function () {
        var t = this;
        t._watcher && t._watcher.update();
      }, t.prototype.$destroy = function () {
        var t = this;
        if (t._isBeingDestroyed) return;
        qe(t, "beforeDestroy"), t._isBeingDestroyed = !0;
        var e = t.$parent;
        !e || e._isBeingDestroyed || t.$options.abstract || m(e.$children, t), t._watcher && t._watcher.teardown();
        var n = t._watchers.length;

        for (; n--;) {
          t._watchers[n].teardown();
        }

        t._data.__ob__ && t._data.__ob__.vmCount--, t._isDestroyed = !0, t.__patch__(t._vnode, null), qe(t, "destroyed"), t.$off(), t.$el && (t.$el.__vue__ = null), t.$vnode && (t.$vnode.parent = null);
      };
    }(yn), function (t) {
      Ce(t.prototype), t.prototype.$nextTick = function (t) {
        return qt(t, this);
      }, t.prototype._render = function () {
        var t = this,
            _t$$options = t.$options,
            e = _t$$options.render,
            n = _t$$options._parentVnode;
        var o;
        n && (t.$scopedSlots = ie(n.data.scopedSlots, t.$slots, t.$scopedSlots)), t.$vnode = n;

        try {
          Me = t, o = e.call(t._renderProxy, t.$createElement);
        } catch (e) {
          Pt(e, t, "render"), o = t._vnode;
        } finally {
          Me = null;
        }

        return Array.isArray(o) && 1 === o.length && (o = o[0]), o instanceof dt || (o = pt()), o.parent = n, o;
      };
    }(yn);
    var wn = [String, RegExp, Array];
    var Cn = {
      KeepAlive: {
        name: "keep-alive",
        abstract: !0,
        props: {
          include: wn,
          exclude: wn,
          max: [String, Number]
        },
        created: function created() {
          this.cache = Object.create(null), this.keys = [];
        },
        destroyed: function destroyed() {
          for (var _t31 in this.cache) {
            bn(this.cache, _t31, this.keys);
          }
        },
        mounted: function mounted() {
          var _this2 = this;

          this.$watch("include", function (t) {
            _n(_this2, function (e) {
              return $n(t, e);
            });
          }), this.$watch("exclude", function (t) {
            _n(_this2, function (e) {
              return !$n(t, e);
            });
          });
        },
        render: function render() {
          var t = this.$slots.default,
              e = Pe(t),
              n = e && e.componentOptions;

          if (n) {
            var _t32 = vn(n),
                _o26 = this.include,
                _r20 = this.exclude;

            if (_o26 && (!_t32 || !$n(_o26, _t32)) || _r20 && _t32 && $n(_r20, _t32)) return e;

            var _s11 = this.cache,
                _i9 = this.keys,
                _a4 = null == e.key ? n.Ctor.cid + (n.tag ? "::".concat(n.tag) : "") : e.key;

            _s11[_a4] ? (e.componentInstance = _s11[_a4].componentInstance, m(_i9, _a4), _i9.push(_a4)) : (_s11[_a4] = e, _i9.push(_a4), this.max && _i9.length > parseInt(this.max) && bn(_s11, _i9[0], _i9, this._vnode)), e.data.keepAlive = !0;
          }

          return e || t && t[0];
        }
      }
    };
    !function (t) {
      var e = {
        get: function get() {
          return F;
        }
      };
      Object.defineProperty(t, "config", e), t.util = {
        warn: it,
        extend: A,
        mergeOptions: jt,
        defineReactive: Ct
      }, t.set = xt, t.delete = kt, t.nextTick = qt, t.observable = function (t) {
        return wt(t), t;
      }, t.options = Object.create(null), M.forEach(function (e) {
        t.options[e + "s"] = Object.create(null);
      }), t.options._base = t, A(t.options.components, Cn), function (t) {
        t.use = function (t) {
          var e = this._installedPlugins || (this._installedPlugins = []);
          if (e.indexOf(t) > -1) return this;
          var n = k(arguments, 1);
          return n.unshift(this), "function" == typeof t.install ? t.install.apply(t, n) : "function" == typeof t && t.apply(null, n), e.push(t), this;
        };
      }(t), function (t) {
        t.mixin = function (t) {
          return this.options = jt(this.options, t), this;
        };
      }(t), gn(t), function (t) {
        M.forEach(function (e) {
          t[e] = function (t, n) {
            return n ? ("component" === e && a(n) && (n.name = n.name || t, n = this.options._base.extend(n)), "directive" === e && "function" == typeof n && (n = {
              bind: n,
              update: n
            }), this.options[e + "s"][t] = n, n) : this.options[e + "s"][t];
          };
        });
      }(t);
    }(yn), Object.defineProperty(yn.prototype, "$isServer", {
      get: et
    }), Object.defineProperty(yn.prototype, "$ssrContext", {
      get: function get() {
        return this.$vnode && this.$vnode.ssrContext;
      }
    }), Object.defineProperty(yn, "FunctionalRenderContext", {
      value: xe
    }), yn.version = "2.6.10";

    var xn = d("style,class"),
        kn = d("input,textarea,option,select,progress"),
        An = function An(t, e, n) {
      return "value" === n && kn(t) && "button" !== e || "selected" === n && "option" === t || "checked" === n && "input" === t || "muted" === n && "video" === t;
    },
        On = d("contenteditable,draggable,spellcheck"),
        Sn = d("events,caret,typing,plaintext-only"),
        Tn = function Tn(t, e) {
      return Ln(e) || "false" === e ? "false" : "contenteditable" === t && Sn(e) ? e : "true";
    },
        En = d("allowfullscreen,async,autofocus,autoplay,checked,compact,controls,declare,default,defaultchecked,defaultmuted,defaultselected,defer,disabled,enabled,formnovalidate,hidden,indeterminate,inert,ismap,itemscope,loop,multiple,muted,nohref,noresize,noshade,novalidate,nowrap,open,pauseonexit,readonly,required,reversed,scoped,seamless,selected,sortable,translate,truespeed,typemustmatch,visible"),
        Nn = "http://www.w3.org/1999/xlink",
        jn = function jn(t) {
      return ":" === t.charAt(5) && "xlink" === t.slice(0, 5);
    },
        Dn = function Dn(t) {
      return jn(t) ? t.slice(6, t.length) : "";
    },
        Ln = function Ln(t) {
      return null == t || !1 === t;
    };

    function Mn(t) {
      var e = t.data,
          o = t,
          r = t;

      for (; n(r.componentInstance);) {
        (r = r.componentInstance._vnode) && r.data && (e = In(r.data, e));
      }

      for (; n(o = o.parent);) {
        o && o.data && (e = In(e, o.data));
      }

      return function (t, e) {
        if (n(t) || n(e)) return Fn(t, Pn(e));
        return "";
      }(e.staticClass, e.class);
    }

    function In(t, e) {
      return {
        staticClass: Fn(t.staticClass, e.staticClass),
        class: n(t.class) ? [t.class, e.class] : e.class
      };
    }

    function Fn(t, e) {
      return t ? e ? t + " " + e : t : e || "";
    }

    function Pn(t) {
      return Array.isArray(t) ? function (t) {
        var e,
            o = "";

        for (var _r21 = 0, _s12 = t.length; _r21 < _s12; _r21++) {
          n(e = Pn(t[_r21])) && "" !== e && (o && (o += " "), o += e);
        }

        return o;
      }(t) : s(t) ? function (t) {
        var e = "";

        for (var _n35 in t) {
          t[_n35] && (e && (e += " "), e += _n35);
        }

        return e;
      }(t) : "string" == typeof t ? t : "";
    }

    var Rn = {
      svg: "http://www.w3.org/2000/svg",
      math: "http://www.w3.org/1998/Math/MathML"
    },
        Hn = d("html,body,base,head,link,meta,style,title,address,article,aside,footer,header,h1,h2,h3,h4,h5,h6,hgroup,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,rtc,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,menuitem,summary,content,element,shadow,template,blockquote,iframe,tfoot"),
        Bn = d("svg,animate,circle,clippath,cursor,defs,desc,ellipse,filter,font-face,foreignObject,g,glyph,image,line,marker,mask,missing-glyph,path,pattern,polygon,polyline,rect,switch,symbol,text,textpath,tspan,use,view", !0),
        Un = function Un(t) {
      return Hn(t) || Bn(t);
    };

    function zn(t) {
      return Bn(t) ? "svg" : "math" === t ? "math" : void 0;
    }

    var Vn = Object.create(null);
    var Kn = d("text,number,password,search,email,tel,url");

    function Jn(t) {
      if ("string" == typeof t) {
        var _e26 = document.querySelector(t);

        return _e26 || document.createElement("div");
      }

      return t;
    }

    var qn = Object.freeze({
      createElement: function createElement(t, e) {
        var n = document.createElement(t);
        return "select" !== t ? n : (e.data && e.data.attrs && void 0 !== e.data.attrs.multiple && n.setAttribute("multiple", "multiple"), n);
      },
      createElementNS: function createElementNS(t, e) {
        return document.createElementNS(Rn[t], e);
      },
      createTextNode: function createTextNode(t) {
        return document.createTextNode(t);
      },
      createComment: function createComment(t) {
        return document.createComment(t);
      },
      insertBefore: function insertBefore(t, e, n) {
        t.insertBefore(e, n);
      },
      removeChild: function removeChild(t, e) {
        t.removeChild(e);
      },
      appendChild: function appendChild(t, e) {
        t.appendChild(e);
      },
      parentNode: function parentNode(t) {
        return t.parentNode;
      },
      nextSibling: function nextSibling(t) {
        return t.nextSibling;
      },
      tagName: function tagName(t) {
        return t.tagName;
      },
      setTextContent: function setTextContent(t, e) {
        t.textContent = e;
      },
      setStyleScope: function setStyleScope(t, e) {
        t.setAttribute(e, "");
      }
    }),
        Wn = {
      create: function create(t, e) {
        Zn(e);
      },
      update: function update(t, e) {
        t.data.ref !== e.data.ref && (Zn(t, !0), Zn(e));
      },
      destroy: function destroy(t) {
        Zn(t, !0);
      }
    };

    function Zn(t, e) {
      var o = t.data.ref;
      if (!n(o)) return;
      var r = t.context,
          s = t.componentInstance || t.elm,
          i = r.$refs;
      e ? Array.isArray(i[o]) ? m(i[o], s) : i[o] === s && (i[o] = void 0) : t.data.refInFor ? Array.isArray(i[o]) ? i[o].indexOf(s) < 0 && i[o].push(s) : i[o] = [s] : i[o] = s;
    }

    var Gn = new dt("", {}, []),
        Xn = ["create", "activate", "update", "remove", "destroy"];

    function Yn(t, r) {
      return t.key === r.key && (t.tag === r.tag && t.isComment === r.isComment && n(t.data) === n(r.data) && function (t, e) {
        if ("input" !== t.tag) return !0;
        var o;
        var r = n(o = t.data) && n(o = o.attrs) && o.type,
            s = n(o = e.data) && n(o = o.attrs) && o.type;
        return r === s || Kn(r) && Kn(s);
      }(t, r) || o(t.isAsyncPlaceholder) && t.asyncFactory === r.asyncFactory && e(r.asyncFactory.error));
    }

    function Qn(t, e, o) {
      var r, s;
      var i = {};

      for (r = e; r <= o; ++r) {
        n(s = t[r].key) && (i[s] = r);
      }

      return i;
    }

    var to = {
      create: eo,
      update: eo,
      destroy: function destroy(t) {
        eo(t, Gn);
      }
    };

    function eo(t, e) {
      (t.data.directives || e.data.directives) && function (t, e) {
        var n = t === Gn,
            o = e === Gn,
            r = oo(t.data.directives, t.context),
            s = oo(e.data.directives, e.context),
            i = [],
            a = [];
        var c, l, u;

        for (c in s) {
          l = r[c], u = s[c], l ? (u.oldValue = l.value, u.oldArg = l.arg, so(u, "update", e, t), u.def && u.def.componentUpdated && a.push(u)) : (so(u, "bind", e, t), u.def && u.def.inserted && i.push(u));
        }

        if (i.length) {
          var _o27 = function _o27() {
            for (var _n36 = 0; _n36 < i.length; _n36++) {
              so(i[_n36], "inserted", e, t);
            }
          };

          n ? Qt(e, "insert", _o27) : _o27();
        }

        a.length && Qt(e, "postpatch", function () {
          for (var _n37 = 0; _n37 < a.length; _n37++) {
            so(a[_n37], "componentUpdated", e, t);
          }
        });
        if (!n) for (c in r) {
          s[c] || so(r[c], "unbind", t, t, o);
        }
      }(t, e);
    }

    var no = Object.create(null);

    function oo(t, e) {
      var n = Object.create(null);
      if (!t) return n;
      var o, r;

      for (o = 0; o < t.length; o++) {
        (r = t[o]).modifiers || (r.modifiers = no), n[ro(r)] = r, r.def = Dt(e.$options, "directives", r.name);
      }

      return n;
    }

    function ro(t) {
      return t.rawName || "".concat(t.name, ".").concat(Object.keys(t.modifiers || {}).join("."));
    }

    function so(t, e, n, o, r) {
      var s = t.def && t.def[e];
      if (s) try {
        s(n.elm, t, n, o, r);
      } catch (o) {
        Pt(o, n.context, "directive ".concat(t.name, " ").concat(e, " hook"));
      }
    }

    var io = [Wn, to];

    function ao(t, o) {
      var r = o.componentOptions;
      if (n(r) && !1 === r.Ctor.options.inheritAttrs) return;
      if (e(t.data.attrs) && e(o.data.attrs)) return;
      var s, i, a;
      var c = o.elm,
          l = t.data.attrs || {};
      var u = o.data.attrs || {};

      for (s in n(u.__ob__) && (u = o.data.attrs = A({}, u)), u) {
        i = u[s], (a = l[s]) !== i && co(c, s, i);
      }

      for (s in (q || Z) && u.value !== l.value && co(c, "value", u.value), l) {
        e(u[s]) && (jn(s) ? c.removeAttributeNS(Nn, Dn(s)) : On(s) || c.removeAttribute(s));
      }
    }

    function co(t, e, n) {
      t.tagName.indexOf("-") > -1 ? lo(t, e, n) : En(e) ? Ln(n) ? t.removeAttribute(e) : (n = "allowfullscreen" === e && "EMBED" === t.tagName ? "true" : e, t.setAttribute(e, n)) : On(e) ? t.setAttribute(e, Tn(e, n)) : jn(e) ? Ln(n) ? t.removeAttributeNS(Nn, Dn(e)) : t.setAttributeNS(Nn, e, n) : lo(t, e, n);
    }

    function lo(t, e, n) {
      if (Ln(n)) t.removeAttribute(e);else {
        if (q && !W && "TEXTAREA" === t.tagName && "placeholder" === e && "" !== n && !t.__ieph) {
          var _e27 = function _e27(n) {
            n.stopImmediatePropagation(), t.removeEventListener("input", _e27);
          };

          t.addEventListener("input", _e27), t.__ieph = !0;
        }

        t.setAttribute(e, n);
      }
    }

    var uo = {
      create: ao,
      update: ao
    };

    function fo(t, o) {
      var r = o.elm,
          s = o.data,
          i = t.data;
      if (e(s.staticClass) && e(s.class) && (e(i) || e(i.staticClass) && e(i.class))) return;
      var a = Mn(o);
      var c = r._transitionClasses;
      n(c) && (a = Fn(a, Pn(c))), a !== r._prevClass && (r.setAttribute("class", a), r._prevClass = a);
    }

    var po = {
      create: fo,
      update: fo
    };
    var ho = /[\w).+\-_$\]]/;

    function mo(t) {
      var e,
          n,
          o,
          r,
          s,
          i = !1,
          a = !1,
          c = !1,
          l = !1,
          u = 0,
          f = 0,
          d = 0,
          p = 0;

      for (o = 0; o < t.length; o++) {
        if (n = e, e = t.charCodeAt(o), i) 39 === e && 92 !== n && (i = !1);else if (a) 34 === e && 92 !== n && (a = !1);else if (c) 96 === e && 92 !== n && (c = !1);else if (l) 47 === e && 92 !== n && (l = !1);else if (124 !== e || 124 === t.charCodeAt(o + 1) || 124 === t.charCodeAt(o - 1) || u || f || d) {
          switch (e) {
            case 34:
              a = !0;
              break;

            case 39:
              i = !0;
              break;

            case 96:
              c = !0;
              break;

            case 40:
              d++;
              break;

            case 41:
              d--;
              break;

            case 91:
              f++;
              break;

            case 93:
              f--;
              break;

            case 123:
              u++;
              break;

            case 125:
              u--;
          }

          if (47 === e) {
            var _e28 = void 0,
                _n38 = o - 1;

            for (; _n38 >= 0 && " " === (_e28 = t.charAt(_n38)); _n38--) {
            }

            _e28 && ho.test(_e28) || (l = !0);
          }
        } else void 0 === r ? (p = o + 1, r = t.slice(0, o).trim()) : h();
      }

      function h() {
        (s || (s = [])).push(t.slice(p, o).trim()), p = o + 1;
      }

      if (void 0 === r ? r = t.slice(0, o).trim() : 0 !== p && h(), s) for (o = 0; o < s.length; o++) {
        r = yo(r, s[o]);
      }
      return r;
    }

    function yo(t, e) {
      var n = e.indexOf("(");
      if (n < 0) return "_f(\"".concat(e, "\")(").concat(t, ")");
      {
        var _o28 = e.slice(0, n),
            _r22 = e.slice(n + 1);

        return "_f(\"".concat(_o28, "\")(").concat(t).concat(")" !== _r22 ? "," + _r22 : _r22);
      }
    }

    function go(t, e) {
      console.error("[Vue compiler]: ".concat(t));
    }

    function vo(t, e) {
      return t ? t.map(function (t) {
        return t[e];
      }).filter(function (t) {
        return t;
      }) : [];
    }

    function $o(t, e, n, o, r) {
      (t.props || (t.props = [])).push(So({
        name: e,
        value: n,
        dynamic: r
      }, o)), t.plain = !1;
    }

    function _o(t, e, n, o, r) {
      (r ? t.dynamicAttrs || (t.dynamicAttrs = []) : t.attrs || (t.attrs = [])).push(So({
        name: e,
        value: n,
        dynamic: r
      }, o)), t.plain = !1;
    }

    function bo(t, e, n, o) {
      t.attrsMap[e] = n, t.attrsList.push(So({
        name: e,
        value: n
      }, o));
    }

    function wo(t, e, n, o, r, s, i, a) {
      (t.directives || (t.directives = [])).push(So({
        name: e,
        rawName: n,
        value: o,
        arg: r,
        isDynamicArg: s,
        modifiers: i
      }, a)), t.plain = !1;
    }

    function Co(t, e, n) {
      return n ? "_p(".concat(e, ",\"").concat(t, "\")") : t + e;
    }

    function xo(e, n, o, r, s, i, a, c) {
      var l;
      (r = r || t).right ? c ? n = "(".concat(n, ")==='click'?'contextmenu':(").concat(n, ")") : "click" === n && (n = "contextmenu", delete r.right) : r.middle && (c ? n = "(".concat(n, ")==='click'?'mouseup':(").concat(n, ")") : "click" === n && (n = "mouseup")), r.capture && (delete r.capture, n = Co("!", n, c)), r.once && (delete r.once, n = Co("~", n, c)), r.passive && (delete r.passive, n = Co("&", n, c)), r.native ? (delete r.native, l = e.nativeEvents || (e.nativeEvents = {})) : l = e.events || (e.events = {});
      var u = So({
        value: o.trim(),
        dynamic: c
      }, a);
      r !== t && (u.modifiers = r);
      var f = l[n];
      Array.isArray(f) ? s ? f.unshift(u) : f.push(u) : l[n] = f ? s ? [u, f] : [f, u] : u, e.plain = !1;
    }

    function ko(t, e, n) {
      var o = Ao(t, ":" + e) || Ao(t, "v-bind:" + e);
      if (null != o) return mo(o);

      if (!1 !== n) {
        var _n39 = Ao(t, e);

        if (null != _n39) return JSON.stringify(_n39);
      }
    }

    function Ao(t, e, n) {
      var o;

      if (null != (o = t.attrsMap[e])) {
        var _n40 = t.attrsList;

        for (var _t33 = 0, _o29 = _n40.length; _t33 < _o29; _t33++) {
          if (_n40[_t33].name === e) {
            _n40.splice(_t33, 1);

            break;
          }
        }
      }

      return n && delete t.attrsMap[e], o;
    }

    function Oo(t, e) {
      var n = t.attrsList;

      for (var _t34 = 0, _o30 = n.length; _t34 < _o30; _t34++) {
        var _o31 = n[_t34];
        if (e.test(_o31.name)) return n.splice(_t34, 1), _o31;
      }
    }

    function So(t, e) {
      return e && (null != e.start && (t.start = e.start), null != e.end && (t.end = e.end)), t;
    }

    function To(t, e, n) {
      var _ref = n || {},
          o = _ref.number,
          r = _ref.trim;

      var s = "$$v";
      r && (s = "(typeof $$v === 'string'? $$v.trim(): $$v)"), o && (s = "_n(".concat(s, ")"));
      var i = Eo(e, s);
      t.model = {
        value: "(".concat(e, ")"),
        expression: JSON.stringify(e),
        callback: "function ($$v) {".concat(i, "}")
      };
    }

    function Eo(t, e) {
      var n = function (t) {
        if (t = t.trim(), No = t.length, t.indexOf("[") < 0 || t.lastIndexOf("]") < No - 1) return (Lo = t.lastIndexOf(".")) > -1 ? {
          exp: t.slice(0, Lo),
          key: '"' + t.slice(Lo + 1) + '"'
        } : {
          exp: t,
          key: null
        };
        jo = t, Lo = Mo = Io = 0;

        for (; !Po();) {
          Ro(Do = Fo()) ? Bo(Do) : 91 === Do && Ho(Do);
        }

        return {
          exp: t.slice(0, Mo),
          key: t.slice(Mo + 1, Io)
        };
      }(t);

      return null === n.key ? "".concat(t, "=").concat(e) : "$set(".concat(n.exp, ", ").concat(n.key, ", ").concat(e, ")");
    }

    var No, jo, Do, Lo, Mo, Io;

    function Fo() {
      return jo.charCodeAt(++Lo);
    }

    function Po() {
      return Lo >= No;
    }

    function Ro(t) {
      return 34 === t || 39 === t;
    }

    function Ho(t) {
      var e = 1;

      for (Mo = Lo; !Po();) {
        if (Ro(t = Fo())) Bo(t);else if (91 === t && e++, 93 === t && e--, 0 === e) {
          Io = Lo;
          break;
        }
      }
    }

    function Bo(t) {
      var e = t;

      for (; !Po() && (t = Fo()) !== e;) {
      }
    }

    var Uo = "__r",
        zo = "__c";
    var Vo;

    function Ko(t, e, n) {
      var o = Vo;
      return function r() {
        null !== e.apply(null, arguments) && Wo(t, r, n, o);
      };
    }

    var Jo = Ut && !(X && Number(X[1]) <= 53);

    function qo(t, e, n, o) {
      if (Jo) {
        var _t35 = tn,
            _n41 = e;

        e = _n41._wrapper = function (e) {
          if (e.target === e.currentTarget || e.timeStamp >= _t35 || e.timeStamp <= 0 || e.target.ownerDocument !== document) return _n41.apply(this, arguments);
        };
      }

      Vo.addEventListener(t, e, tt ? {
        capture: n,
        passive: o
      } : n);
    }

    function Wo(t, e, n, o) {
      (o || Vo).removeEventListener(t, e._wrapper || e, n);
    }

    function Zo(t, o) {
      if (e(t.data.on) && e(o.data.on)) return;
      var r = o.data.on || {},
          s = t.data.on || {};
      Vo = o.elm, function (t) {
        if (n(t[Uo])) {
          var _e29 = q ? "change" : "input";

          t[_e29] = [].concat(t[Uo], t[_e29] || []), delete t[Uo];
        }

        n(t[zo]) && (t.change = [].concat(t[zo], t.change || []), delete t[zo]);
      }(r), Yt(r, s, qo, Wo, Ko, o.context), Vo = void 0;
    }

    var Go = {
      create: Zo,
      update: Zo
    };
    var Xo;

    function Yo(t, o) {
      if (e(t.data.domProps) && e(o.data.domProps)) return;
      var r, s;
      var i = o.elm,
          a = t.data.domProps || {};
      var c = o.data.domProps || {};

      for (r in n(c.__ob__) && (c = o.data.domProps = A({}, c)), a) {
        r in c || (i[r] = "");
      }

      for (r in c) {
        if (s = c[r], "textContent" === r || "innerHTML" === r) {
          if (o.children && (o.children.length = 0), s === a[r]) continue;
          1 === i.childNodes.length && i.removeChild(i.childNodes[0]);
        }

        if ("value" === r && "PROGRESS" !== i.tagName) {
          i._value = s;

          var _t36 = e(s) ? "" : String(s);

          Qo(i, _t36) && (i.value = _t36);
        } else if ("innerHTML" === r && Bn(i.tagName) && e(i.innerHTML)) {
          (Xo = Xo || document.createElement("div")).innerHTML = "<svg>".concat(s, "</svg>");
          var _t37 = Xo.firstChild;

          for (; i.firstChild;) {
            i.removeChild(i.firstChild);
          }

          for (; _t37.firstChild;) {
            i.appendChild(_t37.firstChild);
          }
        } else if (s !== a[r]) try {
          i[r] = s;
        } catch (t) {}
      }
    }

    function Qo(t, e) {
      return !t.composing && ("OPTION" === t.tagName || function (t, e) {
        var n = !0;

        try {
          n = document.activeElement !== t;
        } catch (t) {}

        return n && t.value !== e;
      }(t, e) || function (t, e) {
        var o = t.value,
            r = t._vModifiers;

        if (n(r)) {
          if (r.number) return f(o) !== f(e);
          if (r.trim) return o.trim() !== e.trim();
        }

        return o !== e;
      }(t, e));
    }

    var tr = {
      create: Yo,
      update: Yo
    };
    var er = v(function (t) {
      var e = {},
          n = /:(.+)/;
      return t.split(/;(?![^(]*\))/g).forEach(function (t) {
        if (t) {
          var _o32 = t.split(n);

          _o32.length > 1 && (e[_o32[0].trim()] = _o32[1].trim());
        }
      }), e;
    });

    function nr(t) {
      var e = or(t.style);
      return t.staticStyle ? A(t.staticStyle, e) : e;
    }

    function or(t) {
      return Array.isArray(t) ? O(t) : "string" == typeof t ? er(t) : t;
    }

    var rr = /^--/,
        sr = /\s*!important$/,
        ir = function ir(t, e, n) {
      if (rr.test(e)) t.style.setProperty(e, n);else if (sr.test(n)) t.style.setProperty(C(e), n.replace(sr, ""), "important");else {
        var _o33 = lr(e);

        if (Array.isArray(n)) for (var _e30 = 0, _r23 = n.length; _e30 < _r23; _e30++) {
          t.style[_o33] = n[_e30];
        } else t.style[_o33] = n;
      }
    },
        ar = ["Webkit", "Moz", "ms"];

    var cr;
    var lr = v(function (t) {
      if (cr = cr || document.createElement("div").style, "filter" !== (t = _(t)) && t in cr) return t;
      var e = t.charAt(0).toUpperCase() + t.slice(1);

      for (var _t38 = 0; _t38 < ar.length; _t38++) {
        var _n42 = ar[_t38] + e;

        if (_n42 in cr) return _n42;
      }
    });

    function ur(t, o) {
      var r = o.data,
          s = t.data;
      if (e(r.staticStyle) && e(r.style) && e(s.staticStyle) && e(s.style)) return;
      var i, a;
      var c = o.elm,
          l = s.staticStyle,
          u = s.normalizedStyle || s.style || {},
          f = l || u,
          d = or(o.data.style) || {};
      o.data.normalizedStyle = n(d.__ob__) ? A({}, d) : d;

      var p = function (t, e) {
        var n = {};
        var o;

        if (e) {
          var _e31 = t;

          for (; _e31.componentInstance;) {
            (_e31 = _e31.componentInstance._vnode) && _e31.data && (o = nr(_e31.data)) && A(n, o);
          }
        }

        (o = nr(t.data)) && A(n, o);
        var r = t;

        for (; r = r.parent;) {
          r.data && (o = nr(r.data)) && A(n, o);
        }

        return n;
      }(o, !0);

      for (a in f) {
        e(p[a]) && ir(c, a, "");
      }

      for (a in p) {
        (i = p[a]) !== f[a] && ir(c, a, null == i ? "" : i);
      }
    }

    var fr = {
      create: ur,
      update: ur
    };
    var dr = /\s+/;

    function pr(t, e) {
      if (e && (e = e.trim())) if (t.classList) e.indexOf(" ") > -1 ? e.split(dr).forEach(function (e) {
        return t.classList.add(e);
      }) : t.classList.add(e);else {
        var _n43 = " ".concat(t.getAttribute("class") || "", " ");

        _n43.indexOf(" " + e + " ") < 0 && t.setAttribute("class", (_n43 + e).trim());
      }
    }

    function hr(t, e) {
      if (e && (e = e.trim())) if (t.classList) e.indexOf(" ") > -1 ? e.split(dr).forEach(function (e) {
        return t.classList.remove(e);
      }) : t.classList.remove(e), t.classList.length || t.removeAttribute("class");else {
        var _n44 = " ".concat(t.getAttribute("class") || "", " ");

        var _o34 = " " + e + " ";

        for (; _n44.indexOf(_o34) >= 0;) {
          _n44 = _n44.replace(_o34, " ");
        }

        (_n44 = _n44.trim()) ? t.setAttribute("class", _n44) : t.removeAttribute("class");
      }
    }

    function mr(t) {
      if (t) {
        if ("object" == babelHelpers.typeof(t)) {
          var _e32 = {};
          return !1 !== t.css && A(_e32, yr(t.name || "v")), A(_e32, t), _e32;
        }

        return "string" == typeof t ? yr(t) : void 0;
      }
    }

    var yr = v(function (t) {
      return {
        enterClass: "".concat(t, "-enter"),
        enterToClass: "".concat(t, "-enter-to"),
        enterActiveClass: "".concat(t, "-enter-active"),
        leaveClass: "".concat(t, "-leave"),
        leaveToClass: "".concat(t, "-leave-to"),
        leaveActiveClass: "".concat(t, "-leave-active")
      };
    }),
        gr = z && !W,
        vr = "transition",
        $r = "animation";
    var _r = "transition",
        br = "transitionend",
        wr = "animation",
        Cr = "animationend";
    gr && (void 0 === window.ontransitionend && void 0 !== window.onwebkittransitionend && (_r = "WebkitTransition", br = "webkitTransitionEnd"), void 0 === window.onanimationend && void 0 !== window.onwebkitanimationend && (wr = "WebkitAnimation", Cr = "webkitAnimationEnd"));
    var xr = z ? window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : setTimeout : function (t) {
      return t();
    };

    function kr(t) {
      xr(function () {
        xr(t);
      });
    }

    function Ar(t, e) {
      var n = t._transitionClasses || (t._transitionClasses = []);
      n.indexOf(e) < 0 && (n.push(e), pr(t, e));
    }

    function Or(t, e) {
      t._transitionClasses && m(t._transitionClasses, e), hr(t, e);
    }

    function Sr(t, e, n) {
      var _Er = Er(t, e),
          o = _Er.type,
          r = _Er.timeout,
          s = _Er.propCount;

      if (!o) return n();
      var i = o === vr ? br : Cr;
      var a = 0;

      var c = function c() {
        t.removeEventListener(i, l), n();
      },
          l = function l(e) {
        e.target === t && ++a >= s && c();
      };

      setTimeout(function () {
        a < s && c();
      }, r + 1), t.addEventListener(i, l);
    }

    var Tr = /\b(transform|all)(,|$)/;

    function Er(t, e) {
      var n = window.getComputedStyle(t),
          o = (n[_r + "Delay"] || "").split(", "),
          r = (n[_r + "Duration"] || "").split(", "),
          s = Nr(o, r),
          i = (n[wr + "Delay"] || "").split(", "),
          a = (n[wr + "Duration"] || "").split(", "),
          c = Nr(i, a);
      var l,
          u = 0,
          f = 0;
      return e === vr ? s > 0 && (l = vr, u = s, f = r.length) : e === $r ? c > 0 && (l = $r, u = c, f = a.length) : f = (l = (u = Math.max(s, c)) > 0 ? s > c ? vr : $r : null) ? l === vr ? r.length : a.length : 0, {
        type: l,
        timeout: u,
        propCount: f,
        hasTransform: l === vr && Tr.test(n[_r + "Property"])
      };
    }

    function Nr(t, e) {
      for (; t.length < e.length;) {
        t = t.concat(t);
      }

      return Math.max.apply(null, e.map(function (e, n) {
        return jr(e) + jr(t[n]);
      }));
    }

    function jr(t) {
      return 1e3 * Number(t.slice(0, -1).replace(",", "."));
    }

    function Dr(t, o) {
      var r = t.elm;
      n(r._leaveCb) && (r._leaveCb.cancelled = !0, r._leaveCb());
      var i = mr(t.data.transition);
      if (e(i)) return;
      if (n(r._enterCb) || 1 !== r.nodeType) return;
      var a = i.css,
          c = i.type,
          l = i.enterClass,
          u = i.enterToClass,
          d = i.enterActiveClass,
          p = i.appearClass,
          h = i.appearToClass,
          m = i.appearActiveClass,
          y = i.beforeEnter,
          g = i.enter,
          v = i.afterEnter,
          $ = i.enterCancelled,
          _ = i.beforeAppear,
          b = i.appear,
          w = i.afterAppear,
          C = i.appearCancelled,
          x = i.duration;
      var k = ze,
          A = ze.$vnode;

      for (; A && A.parent;) {
        k = A.context, A = A.parent;
      }

      var O = !k._isMounted || !t.isRootInsert;
      if (O && !b && "" !== b) return;
      var S = O && p ? p : l,
          T = O && m ? m : d,
          E = O && h ? h : u,
          N = O && _ || y,
          j = O && "function" == typeof b ? b : g,
          L = O && w || v,
          M = O && C || $,
          I = f(s(x) ? x.enter : x),
          F = !1 !== a && !W,
          P = Ir(j),
          R = r._enterCb = D(function () {
        F && (Or(r, E), Or(r, T)), R.cancelled ? (F && Or(r, S), M && M(r)) : L && L(r), r._enterCb = null;
      });
      t.data.show || Qt(t, "insert", function () {
        var e = r.parentNode,
            n = e && e._pending && e._pending[t.key];
        n && n.tag === t.tag && n.elm._leaveCb && n.elm._leaveCb(), j && j(r, R);
      }), N && N(r), F && (Ar(r, S), Ar(r, T), kr(function () {
        Or(r, S), R.cancelled || (Ar(r, E), P || (Mr(I) ? setTimeout(R, I) : Sr(r, c, R)));
      })), t.data.show && (o && o(), j && j(r, R)), F || P || R();
    }

    function Lr(t, o) {
      var r = t.elm;
      n(r._enterCb) && (r._enterCb.cancelled = !0, r._enterCb());
      var i = mr(t.data.transition);
      if (e(i) || 1 !== r.nodeType) return o();
      if (n(r._leaveCb)) return;

      var a = i.css,
          c = i.type,
          l = i.leaveClass,
          u = i.leaveToClass,
          d = i.leaveActiveClass,
          p = i.beforeLeave,
          h = i.leave,
          m = i.afterLeave,
          y = i.leaveCancelled,
          g = i.delayLeave,
          v = i.duration,
          $ = !1 !== a && !W,
          _ = Ir(h),
          b = f(s(v) ? v.leave : v),
          w = r._leaveCb = D(function () {
        r.parentNode && r.parentNode._pending && (r.parentNode._pending[t.key] = null), $ && (Or(r, u), Or(r, d)), w.cancelled ? ($ && Or(r, l), y && y(r)) : (o(), m && m(r)), r._leaveCb = null;
      });

      function C() {
        w.cancelled || (!t.data.show && r.parentNode && ((r.parentNode._pending || (r.parentNode._pending = {}))[t.key] = t), p && p(r), $ && (Ar(r, l), Ar(r, d), kr(function () {
          Or(r, l), w.cancelled || (Ar(r, u), _ || (Mr(b) ? setTimeout(w, b) : Sr(r, c, w)));
        })), h && h(r, w), $ || _ || w());
      }

      g ? g(C) : C();
    }

    function Mr(t) {
      return "number" == typeof t && !isNaN(t);
    }

    function Ir(t) {
      if (e(t)) return !1;
      var o = t.fns;
      return n(o) ? Ir(Array.isArray(o) ? o[0] : o) : (t._length || t.length) > 1;
    }

    function Fr(t, e) {
      !0 !== e.data.show && Dr(e);
    }

    var Pr = function (t) {
      var s, i;
      var a = {},
          c = t.modules,
          l = t.nodeOps;

      for (s = 0; s < Xn.length; ++s) {
        for (a[Xn[s]] = [], i = 0; i < c.length; ++i) {
          n(c[i][Xn[s]]) && a[Xn[s]].push(c[i][Xn[s]]);
        }
      }

      function u(t) {
        var e = l.parentNode(t);
        n(e) && l.removeChild(e, t);
      }

      function f(t, e, r, s, i, c, u) {
        if (n(t.elm) && n(c) && (t = c[u] = mt(t)), t.isRootInsert = !i, function (t, e, r, s) {
          var i = t.data;

          if (n(i)) {
            var _c4 = n(t.componentInstance) && i.keepAlive;

            if (n(i = i.hook) && n(i = i.init) && i(t, !1), n(t.componentInstance)) return p(t, e), h(r, t.elm, s), o(_c4) && function (t, e, o, r) {
              var s,
                  i = t;

              for (; i.componentInstance;) {
                if (i = i.componentInstance._vnode, n(s = i.data) && n(s = s.transition)) {
                  for (s = 0; s < a.activate.length; ++s) {
                    a.activate[s](Gn, i);
                  }

                  e.push(i);
                  break;
                }
              }

              h(o, t.elm, r);
            }(t, e, r, s), !0;
          }
        }(t, e, r, s)) return;
        var f = t.data,
            d = t.children,
            y = t.tag;
        n(y) ? (t.elm = t.ns ? l.createElementNS(t.ns, y) : l.createElement(y, t), v(t), m(t, d, e), n(f) && g(t, e), h(r, t.elm, s)) : o(t.isComment) ? (t.elm = l.createComment(t.text), h(r, t.elm, s)) : (t.elm = l.createTextNode(t.text), h(r, t.elm, s));
      }

      function p(t, e) {
        n(t.data.pendingInsert) && (e.push.apply(e, t.data.pendingInsert), t.data.pendingInsert = null), t.elm = t.componentInstance.$el, y(t) ? (g(t, e), v(t)) : (Zn(t), e.push(t));
      }

      function h(t, e, o) {
        n(t) && (n(o) ? l.parentNode(o) === t && l.insertBefore(t, e, o) : l.appendChild(t, e));
      }

      function m(t, e, n) {
        if (Array.isArray(e)) for (var _o35 = 0; _o35 < e.length; ++_o35) {
          f(e[_o35], n, t.elm, null, !0, e, _o35);
        } else r(t.text) && l.appendChild(t.elm, l.createTextNode(String(t.text)));
      }

      function y(t) {
        for (; t.componentInstance;) {
          t = t.componentInstance._vnode;
        }

        return n(t.tag);
      }

      function g(t, e) {
        for (var _e33 = 0; _e33 < a.create.length; ++_e33) {
          a.create[_e33](Gn, t);
        }

        n(s = t.data.hook) && (n(s.create) && s.create(Gn, t), n(s.insert) && e.push(t));
      }

      function v(t) {
        var e;
        if (n(e = t.fnScopeId)) l.setStyleScope(t.elm, e);else {
          var _o36 = t;

          for (; _o36;) {
            n(e = _o36.context) && n(e = e.$options._scopeId) && l.setStyleScope(t.elm, e), _o36 = _o36.parent;
          }
        }
        n(e = ze) && e !== t.context && e !== t.fnContext && n(e = e.$options._scopeId) && l.setStyleScope(t.elm, e);
      }

      function $(t, e, n, o, r, s) {
        for (; o <= r; ++o) {
          f(n[o], s, t, e, !1, n, o);
        }
      }

      function _(t) {
        var e, o;
        var r = t.data;
        if (n(r)) for (n(e = r.hook) && n(e = e.destroy) && e(t), e = 0; e < a.destroy.length; ++e) {
          a.destroy[e](t);
        }
        if (n(e = t.children)) for (o = 0; o < t.children.length; ++o) {
          _(t.children[o]);
        }
      }

      function b(t, e, o, r) {
        for (; o <= r; ++o) {
          var _t39 = e[o];
          n(_t39) && (n(_t39.tag) ? (w(_t39), _(_t39)) : u(_t39.elm));
        }
      }

      function w(t, e) {
        if (n(e) || n(t.data)) {
          var _o37;

          var _r24 = a.remove.length + 1;

          for (n(e) ? e.listeners += _r24 : e = function (t, e) {
            function n() {
              0 == --n.listeners && u(t);
            }

            return n.listeners = e, n;
          }(t.elm, _r24), n(_o37 = t.componentInstance) && n(_o37 = _o37._vnode) && n(_o37.data) && w(_o37, e), _o37 = 0; _o37 < a.remove.length; ++_o37) {
            a.remove[_o37](t, e);
          }

          n(_o37 = t.data.hook) && n(_o37 = _o37.remove) ? _o37(t, e) : e();
        } else u(t.elm);
      }

      function C(t, e, o, r) {
        for (var _s13 = o; _s13 < r; _s13++) {
          var _o38 = e[_s13];
          if (n(_o38) && Yn(t, _o38)) return _s13;
        }
      }

      function x(t, r, s, i, c, u) {
        if (t === r) return;
        n(r.elm) && n(i) && (r = i[c] = mt(r));
        var d = r.elm = t.elm;
        if (o(t.isAsyncPlaceholder)) return void (n(r.asyncFactory.resolved) ? O(t.elm, r, s) : r.isAsyncPlaceholder = !0);
        if (o(r.isStatic) && o(t.isStatic) && r.key === t.key && (o(r.isCloned) || o(r.isOnce))) return void (r.componentInstance = t.componentInstance);
        var p;
        var h = r.data;
        n(h) && n(p = h.hook) && n(p = p.prepatch) && p(t, r);
        var m = t.children,
            g = r.children;

        if (n(h) && y(r)) {
          for (p = 0; p < a.update.length; ++p) {
            a.update[p](t, r);
          }

          n(p = h.hook) && n(p = p.update) && p(t, r);
        }

        e(r.text) ? n(m) && n(g) ? m !== g && function (t, o, r, s, i) {
          var a,
              c,
              u,
              d,
              p = 0,
              h = 0,
              m = o.length - 1,
              y = o[0],
              g = o[m],
              v = r.length - 1,
              _ = r[0],
              w = r[v];
          var k = !i;

          for (; p <= m && h <= v;) {
            e(y) ? y = o[++p] : e(g) ? g = o[--m] : Yn(y, _) ? (x(y, _, s, r, h), y = o[++p], _ = r[++h]) : Yn(g, w) ? (x(g, w, s, r, v), g = o[--m], w = r[--v]) : Yn(y, w) ? (x(y, w, s, r, v), k && l.insertBefore(t, y.elm, l.nextSibling(g.elm)), y = o[++p], w = r[--v]) : Yn(g, _) ? (x(g, _, s, r, h), k && l.insertBefore(t, g.elm, y.elm), g = o[--m], _ = r[++h]) : (e(a) && (a = Qn(o, p, m)), e(c = n(_.key) ? a[_.key] : C(_, o, p, m)) ? f(_, s, t, y.elm, !1, r, h) : Yn(u = o[c], _) ? (x(u, _, s, r, h), o[c] = void 0, k && l.insertBefore(t, u.elm, y.elm)) : f(_, s, t, y.elm, !1, r, h), _ = r[++h]);
          }

          p > m ? $(t, d = e(r[v + 1]) ? null : r[v + 1].elm, r, h, v, s) : h > v && b(0, o, p, m);
        }(d, m, g, s, u) : n(g) ? (n(t.text) && l.setTextContent(d, ""), $(d, null, g, 0, g.length - 1, s)) : n(m) ? b(0, m, 0, m.length - 1) : n(t.text) && l.setTextContent(d, "") : t.text !== r.text && l.setTextContent(d, r.text), n(h) && n(p = h.hook) && n(p = p.postpatch) && p(t, r);
      }

      function k(t, e, r) {
        if (o(r) && n(t.parent)) t.parent.data.pendingInsert = e;else for (var _t40 = 0; _t40 < e.length; ++_t40) {
          e[_t40].data.hook.insert(e[_t40]);
        }
      }

      var A = d("attrs,class,staticClass,staticStyle,key");

      function O(t, e, r, s) {
        var i;
        var a = e.tag,
            c = e.data,
            l = e.children;
        if (s = s || c && c.pre, e.elm = t, o(e.isComment) && n(e.asyncFactory)) return e.isAsyncPlaceholder = !0, !0;
        if (n(c) && (n(i = c.hook) && n(i = i.init) && i(e, !0), n(i = e.componentInstance))) return p(e, r), !0;

        if (n(a)) {
          if (n(l)) if (t.hasChildNodes()) {
            if (n(i = c) && n(i = i.domProps) && n(i = i.innerHTML)) {
              if (i !== t.innerHTML) return !1;
            } else {
              var _e34 = !0,
                  _n45 = t.firstChild;

              for (var _t41 = 0; _t41 < l.length; _t41++) {
                if (!_n45 || !O(_n45, l[_t41], r, s)) {
                  _e34 = !1;
                  break;
                }

                _n45 = _n45.nextSibling;
              }

              if (!_e34 || _n45) return !1;
            }
          } else m(e, l, r);

          if (n(c)) {
            var _t42 = !1;

            for (var _n46 in c) {
              if (!A(_n46)) {
                _t42 = !0, g(e, r);
                break;
              }
            }

            !_t42 && c.class && Zt(c.class);
          }
        } else t.data !== e.text && (t.data = e.text);

        return !0;
      }

      return function (t, r, s, i) {
        if (e(r)) return void (n(t) && _(t));
        var c = !1;
        var u = [];
        if (e(t)) c = !0, f(r, u);else {
          var _e35 = n(t.nodeType);

          if (!_e35 && Yn(t, r)) x(t, r, u, null, null, i);else {
            if (_e35) {
              if (1 === t.nodeType && t.hasAttribute(L) && (t.removeAttribute(L), s = !0), o(s) && O(t, r, u)) return k(r, u, !0), t;
              d = t, t = new dt(l.tagName(d).toLowerCase(), {}, [], void 0, d);
            }

            var _i10 = t.elm,
                _c5 = l.parentNode(_i10);

            if (f(r, u, _i10._leaveCb ? null : _c5, l.nextSibling(_i10)), n(r.parent)) {
              var _t43 = r.parent;

              var _e36 = y(r);

              for (; _t43;) {
                for (var _e37 = 0; _e37 < a.destroy.length; ++_e37) {
                  a.destroy[_e37](_t43);
                }

                if (_t43.elm = r.elm, _e36) {
                  for (var _e39 = 0; _e39 < a.create.length; ++_e39) {
                    a.create[_e39](Gn, _t43);
                  }

                  var _e38 = _t43.data.hook.insert;
                  if (_e38.merged) for (var _t44 = 1; _t44 < _e38.fns.length; _t44++) {
                    _e38.fns[_t44]();
                  }
                } else Zn(_t43);

                _t43 = _t43.parent;
              }
            }

            n(_c5) ? b(0, [t], 0, 0) : n(t.tag) && _(t);
          }
        }
        var d;
        return k(r, u, c), r.elm;
      };
    }({
      nodeOps: qn,
      modules: [uo, po, Go, tr, fr, z ? {
        create: Fr,
        activate: Fr,
        remove: function remove(t, e) {
          !0 !== t.data.show ? Lr(t, e) : e();
        }
      } : {}].concat(io)
    });

    W && document.addEventListener("selectionchange", function () {
      var t = document.activeElement;
      t && t.vmodel && Jr(t, "input");
    });
    var Rr = {
      inserted: function inserted(t, e, n, o) {
        "select" === n.tag ? (o.elm && !o.elm._vOptions ? Qt(n, "postpatch", function () {
          Rr.componentUpdated(t, e, n);
        }) : Hr(t, e, n.context), t._vOptions = [].map.call(t.options, zr)) : ("textarea" === n.tag || Kn(t.type)) && (t._vModifiers = e.modifiers, e.modifiers.lazy || (t.addEventListener("compositionstart", Vr), t.addEventListener("compositionend", Kr), t.addEventListener("change", Kr), W && (t.vmodel = !0)));
      },
      componentUpdated: function componentUpdated(t, e, n) {
        if ("select" === n.tag) {
          Hr(t, e, n.context);

          var _o39 = t._vOptions,
              _r25 = t._vOptions = [].map.call(t.options, zr);

          if (_r25.some(function (t, e) {
            return !N(t, _o39[e]);
          })) {
            (t.multiple ? e.value.some(function (t) {
              return Ur(t, _r25);
            }) : e.value !== e.oldValue && Ur(e.value, _r25)) && Jr(t, "change");
          }
        }
      }
    };

    function Hr(t, e, n) {
      Br(t, e, n), (q || Z) && setTimeout(function () {
        Br(t, e, n);
      }, 0);
    }

    function Br(t, e, n) {
      var o = e.value,
          r = t.multiple;
      if (r && !Array.isArray(o)) return;
      var s, i;

      for (var _e40 = 0, _n47 = t.options.length; _e40 < _n47; _e40++) {
        if (i = t.options[_e40], r) s = j(o, zr(i)) > -1, i.selected !== s && (i.selected = s);else if (N(zr(i), o)) return void (t.selectedIndex !== _e40 && (t.selectedIndex = _e40));
      }

      r || (t.selectedIndex = -1);
    }

    function Ur(t, e) {
      return e.every(function (e) {
        return !N(e, t);
      });
    }

    function zr(t) {
      return "_value" in t ? t._value : t.value;
    }

    function Vr(t) {
      t.target.composing = !0;
    }

    function Kr(t) {
      t.target.composing && (t.target.composing = !1, Jr(t.target, "input"));
    }

    function Jr(t, e) {
      var n = document.createEvent("HTMLEvents");
      n.initEvent(e, !0, !0), t.dispatchEvent(n);
    }

    function qr(t) {
      return !t.componentInstance || t.data && t.data.transition ? t : qr(t.componentInstance._vnode);
    }

    var Wr = {
      model: Rr,
      show: {
        bind: function bind(t, _ref2, n) {
          var e = _ref2.value;
          var o = (n = qr(n)).data && n.data.transition,
              r = t.__vOriginalDisplay = "none" === t.style.display ? "" : t.style.display;
          e && o ? (n.data.show = !0, Dr(n, function () {
            t.style.display = r;
          })) : t.style.display = e ? r : "none";
        },
        update: function update(t, _ref3, o) {
          var e = _ref3.value,
              n = _ref3.oldValue;
          if (!e == !n) return;
          (o = qr(o)).data && o.data.transition ? (o.data.show = !0, e ? Dr(o, function () {
            t.style.display = t.__vOriginalDisplay;
          }) : Lr(o, function () {
            t.style.display = "none";
          })) : t.style.display = e ? t.__vOriginalDisplay : "none";
        },
        unbind: function unbind(t, e, n, o, r) {
          r || (t.style.display = t.__vOriginalDisplay);
        }
      }
    };
    var Zr = {
      name: String,
      appear: Boolean,
      css: Boolean,
      mode: String,
      type: String,
      enterClass: String,
      leaveClass: String,
      enterToClass: String,
      leaveToClass: String,
      enterActiveClass: String,
      leaveActiveClass: String,
      appearClass: String,
      appearActiveClass: String,
      appearToClass: String,
      duration: [Number, String, Object]
    };

    function Gr(t) {
      var e = t && t.componentOptions;
      return e && e.Ctor.options.abstract ? Gr(Pe(e.children)) : t;
    }

    function Xr(t) {
      var e = {},
          n = t.$options;

      for (var _o40 in n.propsData) {
        e[_o40] = t[_o40];
      }

      var o = n._parentListeners;

      for (var _t45 in o) {
        e[_(_t45)] = o[_t45];
      }

      return e;
    }

    function Yr(t, e) {
      if (/\d-keep-alive$/.test(e.tag)) return t("keep-alive", {
        props: e.componentOptions.propsData
      });
    }

    var Qr = function Qr(t) {
      return t.tag || Fe(t);
    },
        ts = function ts(t) {
      return "show" === t.name;
    };

    var es = {
      name: "transition",
      props: Zr,
      abstract: !0,
      render: function render(t) {
        var _this3 = this;

        var e = this.$slots.default;
        if (!e) return;
        if (!(e = e.filter(Qr)).length) return;
        var n = this.mode,
            o = e[0];
        if (function (t) {
          for (; t = t.parent;) {
            if (t.data.transition) return !0;
          }
        }(this.$vnode)) return o;
        var s = Gr(o);
        if (!s) return o;
        if (this._leaving) return Yr(t, o);
        var i = "__transition-".concat(this._uid, "-");
        s.key = null == s.key ? s.isComment ? i + "comment" : i + s.tag : r(s.key) ? 0 === String(s.key).indexOf(i) ? s.key : i + s.key : s.key;
        var a = (s.data || (s.data = {})).transition = Xr(this),
            c = this._vnode,
            l = Gr(c);

        if (s.data.directives && s.data.directives.some(ts) && (s.data.show = !0), l && l.data && !function (t, e) {
          return e.key === t.key && e.tag === t.tag;
        }(s, l) && !Fe(l) && (!l.componentInstance || !l.componentInstance._vnode.isComment)) {
          var _e41 = l.data.transition = A({}, a);

          if ("out-in" === n) return this._leaving = !0, Qt(_e41, "afterLeave", function () {
            _this3._leaving = !1, _this3.$forceUpdate();
          }), Yr(t, o);

          if ("in-out" === n) {
            if (Fe(s)) return c;

            var _t46;

            var _n48 = function _n48() {
              _t46();
            };

            Qt(a, "afterEnter", _n48), Qt(a, "enterCancelled", _n48), Qt(_e41, "delayLeave", function (e) {
              _t46 = e;
            });
          }
        }

        return o;
      }
    };
    var ns = A({
      tag: String,
      moveClass: String
    }, Zr);

    function os(t) {
      t.elm._moveCb && t.elm._moveCb(), t.elm._enterCb && t.elm._enterCb();
    }

    function rs(t) {
      t.data.newPos = t.elm.getBoundingClientRect();
    }

    function ss(t) {
      var e = t.data.pos,
          n = t.data.newPos,
          o = e.left - n.left,
          r = e.top - n.top;

      if (o || r) {
        t.data.moved = !0;
        var _e42 = t.elm.style;
        _e42.transform = _e42.WebkitTransform = "translate(".concat(o, "px,").concat(r, "px)"), _e42.transitionDuration = "0s";
      }
    }

    delete ns.mode;
    var is = {
      Transition: es,
      TransitionGroup: {
        props: ns,
        beforeMount: function beforeMount() {
          var _this4 = this;

          var t = this._update;

          this._update = function (e, n) {
            var o = Ve(_this4);
            _this4.__patch__(_this4._vnode, _this4.kept, !1, !0), _this4._vnode = _this4.kept, o(), t.call(_this4, e, n);
          };
        },
        render: function render(t) {
          var e = this.tag || this.$vnode.data.tag || "span",
              n = Object.create(null),
              o = this.prevChildren = this.children,
              r = this.$slots.default || [],
              s = this.children = [],
              i = Xr(this);

          for (var _t47 = 0; _t47 < r.length; _t47++) {
            var _e43 = r[_t47];
            _e43.tag && null != _e43.key && 0 !== String(_e43.key).indexOf("__vlist") && (s.push(_e43), n[_e43.key] = _e43, (_e43.data || (_e43.data = {})).transition = i);
          }

          if (o) {
            var _r26 = [],
                _s14 = [];

            for (var _t48 = 0; _t48 < o.length; _t48++) {
              var _e44 = o[_t48];
              _e44.data.transition = i, _e44.data.pos = _e44.elm.getBoundingClientRect(), n[_e44.key] ? _r26.push(_e44) : _s14.push(_e44);
            }

            this.kept = t(e, null, _r26), this.removed = _s14;
          }

          return t(e, null, s);
        },
        updated: function updated() {
          var t = this.prevChildren,
              e = this.moveClass || (this.name || "v") + "-move";
          t.length && this.hasMove(t[0].elm, e) && (t.forEach(os), t.forEach(rs), t.forEach(ss), this._reflow = document.body.offsetHeight, t.forEach(function (t) {
            if (t.data.moved) {
              var _n49 = t.elm,
                  _o41 = _n49.style;
              Ar(_n49, e), _o41.transform = _o41.WebkitTransform = _o41.transitionDuration = "", _n49.addEventListener(br, _n49._moveCb = function t(o) {
                o && o.target !== _n49 || o && !/transform$/.test(o.propertyName) || (_n49.removeEventListener(br, t), _n49._moveCb = null, Or(_n49, e));
              });
            }
          }));
        },
        methods: {
          hasMove: function hasMove(t, e) {
            if (!gr) return !1;
            if (this._hasMove) return this._hasMove;
            var n = t.cloneNode();
            t._transitionClasses && t._transitionClasses.forEach(function (t) {
              hr(n, t);
            }), pr(n, e), n.style.display = "none", this.$el.appendChild(n);
            var o = Er(n);
            return this.$el.removeChild(n), this._hasMove = o.hasTransform;
          }
        }
      }
    };
    yn.config.mustUseProp = An, yn.config.isReservedTag = Un, yn.config.isReservedAttr = xn, yn.config.getTagNamespace = zn, yn.config.isUnknownElement = function (t) {
      if (!z) return !0;
      if (Un(t)) return !1;
      if (t = t.toLowerCase(), null != Vn[t]) return Vn[t];
      var e = document.createElement(t);
      return t.indexOf("-") > -1 ? Vn[t] = e.constructor === window.HTMLUnknownElement || e.constructor === window.HTMLElement : Vn[t] = /HTMLUnknownElement/.test(e.toString());
    }, A(yn.options.directives, Wr), A(yn.options.components, is), yn.prototype.__patch__ = z ? Pr : S, yn.prototype.$mount = function (t, e) {
      return function (t, e, n) {
        var o;
        return t.$el = e, t.$options.render || (t.$options.render = pt), qe(t, "beforeMount"), o = function o() {
          t._update(t._render(), n);
        }, new rn(t, o, S, {
          before: function before() {
            t._isMounted && !t._isDestroyed && qe(t, "beforeUpdate");
          }
        }, !0), n = !1, null == t.$vnode && (t._isMounted = !0, qe(t, "mounted")), t;
      }(this, t = t && z ? Jn(t) : void 0, e);
    }, z && setTimeout(function () {
      F.devtools && nt && nt.emit("init", yn);
    }, 0);
    var as = /\{\{((?:.|\r?\n)+?)\}\}/g,
        cs = /[-.*+?^${}()|[\]\/\\]/g,
        ls = v(function (t) {
      var e = t[0].replace(cs, "\\$&"),
          n = t[1].replace(cs, "\\$&");
      return new RegExp(e + "((?:.|\\n)+?)" + n, "g");
    });
    var us = {
      staticKeys: ["staticClass"],
      transformNode: function transformNode(t, e) {
        e.warn;
        var n = Ao(t, "class");
        n && (t.staticClass = JSON.stringify(n));
        var o = ko(t, "class", !1);
        o && (t.classBinding = o);
      },
      genData: function genData(t) {
        var e = "";
        return t.staticClass && (e += "staticClass:".concat(t.staticClass, ",")), t.classBinding && (e += "class:".concat(t.classBinding, ",")), e;
      }
    };
    var fs = {
      staticKeys: ["staticStyle"],
      transformNode: function transformNode(t, e) {
        e.warn;
        var n = Ao(t, "style");
        n && (t.staticStyle = JSON.stringify(er(n)));
        var o = ko(t, "style", !1);
        o && (t.styleBinding = o);
      },
      genData: function genData(t) {
        var e = "";
        return t.staticStyle && (e += "staticStyle:".concat(t.staticStyle, ",")), t.styleBinding && (e += "style:(".concat(t.styleBinding, "),")), e;
      }
    };
    var ds;
    var ps = {
      decode: function decode(t) {
        return (ds = ds || document.createElement("div")).innerHTML = t, ds.textContent;
      }
    };

    var hs = d("area,base,br,col,embed,frame,hr,img,input,isindex,keygen,link,meta,param,source,track,wbr"),
        ms = d("colgroup,dd,dt,li,options,p,td,tfoot,th,thead,tr,source"),
        ys = d("address,article,aside,base,blockquote,body,caption,col,colgroup,dd,details,dialog,div,dl,dt,fieldset,figcaption,figure,footer,form,h1,h2,h3,h4,h5,h6,head,header,hgroup,hr,html,legend,li,menuitem,meta,optgroup,option,param,rp,rt,source,style,summary,tbody,td,tfoot,th,thead,title,tr,track"),
        gs = /^\s*([^\s"'<>\/=]+)(?:\s*(=)\s*(?:"([^"]*)"+|'([^']*)'+|([^\s"'=<>`]+)))?/,
        vs = /^\s*((?:v-[\w-]+:|@|:|#)\[[^=]+\][^\s"'<>\/=]*)(?:\s*(=)\s*(?:"([^"]*)"+|'([^']*)'+|([^\s"'=<>`]+)))?/,
        $s = "[a-zA-Z_][\\-\\.0-9_a-zA-Z".concat(P.source, "]*"),
        _s = "((?:".concat($s, "\\:)?").concat($s, ")"),
        bs = new RegExp("^<".concat(_s)),
        ws = /^\s*(\/?)>/,
        Cs = new RegExp("^<\\/".concat(_s, "[^>]*>")),
        xs = /^<!DOCTYPE [^>]+>/i,
        ks = /^<!\--/,
        As = /^<!\[/,
        Os = d("script,style,textarea", !0),
        Ss = {},
        Ts = {
      "&lt;": "<",
      "&gt;": ">",
      "&quot;": '"',
      "&amp;": "&",
      "&#10;": "\n",
      "&#9;": "\t",
      "&#39;": "'"
    },
        Es = /&(?:lt|gt|quot|amp|#39);/g,
        Ns = /&(?:lt|gt|quot|amp|#39|#10|#9);/g,
        js = d("pre,textarea", !0),
        Ds = function Ds(t, e) {
      return t && js(t) && "\n" === e[0];
    };

    function Ls(t, e) {
      var n = e ? Ns : Es;
      return t.replace(n, function (t) {
        return Ts[t];
      });
    }

    var Ms = /^@|^v-on:/,
        Is = /^v-|^@|^:/,
        Fs = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,
        Ps = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/,
        Rs = /^\(|\)$/g,
        Hs = /^\[.*\]$/,
        Bs = /:(.*)$/,
        Us = /^:|^\.|^v-bind:/,
        zs = /\.[^.\]]+(?=[^\]]*$)/g,
        Vs = /^v-slot(:|$)|^#/,
        Ks = /[\r\n]/,
        Js = /\s+/g,
        qs = v(ps.decode),
        Ws = "_empty_";
    var Zs, Gs, Xs, Ys, Qs, ti, ei, ni;

    function oi(t, e, n) {
      return {
        type: 1,
        tag: t,
        attrsList: e,
        attrsMap: ui(e),
        rawAttrsMap: {},
        parent: n,
        children: []
      };
    }

    function ri(t, e) {
      Zs = e.warn || go, ti = e.isPreTag || T, ei = e.mustUseProp || T, ni = e.getTagNamespace || T;
      e.isReservedTag;
      Xs = vo(e.modules, "transformNode"), Ys = vo(e.modules, "preTransformNode"), Qs = vo(e.modules, "postTransformNode"), Gs = e.delimiters;
      var n = [],
          o = !1 !== e.preserveWhitespace,
          r = e.whitespace;
      var s,
          i,
          a = !1,
          c = !1;

      function l(t) {
        if (u(t), a || t.processed || (t = si(t, e)), n.length || t === s || s.if && (t.elseif || t.else) && ai(s, {
          exp: t.elseif,
          block: t
        }), i && !t.forbidden) if (t.elseif || t.else) !function (t, e) {
          var n = function (t) {
            var e = t.length;

            for (; e--;) {
              if (1 === t[e].type) return t[e];
              t.pop();
            }
          }(e.children);

          n && n.if && ai(n, {
            exp: t.elseif,
            block: t
          });
        }(t, i);else {
          if (t.slotScope) {
            var _e45 = t.slotTarget || '"default"';

            (i.scopedSlots || (i.scopedSlots = {}))[_e45] = t;
          }

          i.children.push(t), t.parent = i;
        }
        t.children = t.children.filter(function (t) {
          return !t.slotScope;
        }), u(t), t.pre && (a = !1), ti(t.tag) && (c = !1);

        for (var _n50 = 0; _n50 < Qs.length; _n50++) {
          Qs[_n50](t, e);
        }
      }

      function u(t) {
        if (!c) {
          var _e46;

          for (; (_e46 = t.children[t.children.length - 1]) && 3 === _e46.type && " " === _e46.text;) {
            t.children.pop();
          }
        }
      }

      return function (t, e) {
        var n = [],
            o = e.expectHTML,
            r = e.isUnaryTag || T,
            s = e.canBeLeftOpenTag || T;
        var i,
            a,
            c = 0;

        for (; t;) {
          if (i = t, a && Os(a)) {
            (function () {
              var n = 0;
              var o = a.toLowerCase(),
                  r = Ss[o] || (Ss[o] = new RegExp("([\\s\\S]*?)(</" + o + "[^>]*>)", "i")),
                  s = t.replace(r, function (t, r, s) {
                return n = s.length, Os(o) || "noscript" === o || (r = r.replace(/<!\--([\s\S]*?)-->/g, "$1").replace(/<!\[CDATA\[([\s\S]*?)]]>/g, "$1")), Ds(o, r) && (r = r.slice(1)), e.chars && e.chars(r), "";
              });
              c += t.length - s.length, t = s, d(o, c - n, c);
            })();
          } else {
            var _n51 = void 0,
                _o42 = void 0,
                _r27 = void 0,
                _s15 = t.indexOf("<");

            if (0 === _s15) {
              if (ks.test(t)) {
                var _n53 = t.indexOf("--\x3e");

                if (_n53 >= 0) {
                  e.shouldKeepComment && e.comment(t.substring(4, _n53), c, c + _n53 + 3), l(_n53 + 3);
                  continue;
                }
              }

              if (As.test(t)) {
                var _e47 = t.indexOf("]>");

                if (_e47 >= 0) {
                  l(_e47 + 2);
                  continue;
                }
              }

              var _n52 = t.match(xs);

              if (_n52) {
                l(_n52[0].length);
                continue;
              }

              var _o43 = t.match(Cs);

              if (_o43) {
                var _t49 = c;
                l(_o43[0].length), d(_o43[1], _t49, c);
                continue;
              }

              var _r28 = u();

              if (_r28) {
                f(_r28), Ds(_r28.tagName, t) && l(1);
                continue;
              }
            }

            if (_s15 >= 0) {
              for (_o42 = t.slice(_s15); !(Cs.test(_o42) || bs.test(_o42) || ks.test(_o42) || As.test(_o42) || (_r27 = _o42.indexOf("<", 1)) < 0);) {
                _s15 += _r27, _o42 = t.slice(_s15);
              }

              _n51 = t.substring(0, _s15);
            }

            _s15 < 0 && (_n51 = t), _n51 && l(_n51.length), e.chars && _n51 && e.chars(_n51, c - _n51.length, c);
          }

          if (t === i) {
            e.chars && e.chars(t);
            break;
          }
        }

        function l(e) {
          c += e, t = t.substring(e);
        }

        function u() {
          var e = t.match(bs);

          if (e) {
            var _n54 = {
              tagName: e[1],
              attrs: [],
              start: c
            };

            var _o44, _r29;

            for (l(e[0].length); !(_o44 = t.match(ws)) && (_r29 = t.match(vs) || t.match(gs));) {
              _r29.start = c, l(_r29[0].length), _r29.end = c, _n54.attrs.push(_r29);
            }

            if (_o44) return _n54.unarySlash = _o44[1], l(_o44[0].length), _n54.end = c, _n54;
          }
        }

        function f(t) {
          var i = t.tagName,
              c = t.unarySlash;
          o && ("p" === a && ys(i) && d(a), s(i) && a === i && d(i));
          var l = r(i) || !!c,
              u = t.attrs.length,
              f = new Array(u);

          for (var _n55 = 0; _n55 < u; _n55++) {
            var _o45 = t.attrs[_n55],
                _r30 = _o45[3] || _o45[4] || _o45[5] || "",
                _s16 = "a" === i && "href" === _o45[1] ? e.shouldDecodeNewlinesForHref : e.shouldDecodeNewlines;

            f[_n55] = {
              name: _o45[1],
              value: Ls(_r30, _s16)
            };
          }

          l || (n.push({
            tag: i,
            lowerCasedTag: i.toLowerCase(),
            attrs: f,
            start: t.start,
            end: t.end
          }), a = i), e.start && e.start(i, f, l, t.start, t.end);
        }

        function d(t, o, r) {
          var s, i;
          if (null == o && (o = c), null == r && (r = c), t) for (i = t.toLowerCase(), s = n.length - 1; s >= 0 && n[s].lowerCasedTag !== i; s--) {
          } else s = 0;

          if (s >= 0) {
            for (var _t50 = n.length - 1; _t50 >= s; _t50--) {
              e.end && e.end(n[_t50].tag, o, r);
            }

            n.length = s, a = s && n[s - 1].tag;
          } else "br" === i ? e.start && e.start(t, [], !0, o, r) : "p" === i && (e.start && e.start(t, [], !1, o, r), e.end && e.end(t, o, r));
        }

        d();
      }(t, {
        warn: Zs,
        expectHTML: e.expectHTML,
        isUnaryTag: e.isUnaryTag,
        canBeLeftOpenTag: e.canBeLeftOpenTag,
        shouldDecodeNewlines: e.shouldDecodeNewlines,
        shouldDecodeNewlinesForHref: e.shouldDecodeNewlinesForHref,
        shouldKeepComment: e.comments,
        outputSourceRange: e.outputSourceRange,
        start: function start(t, o, r, u, f) {
          var d = i && i.ns || ni(t);
          q && "svg" === d && (o = function (t) {
            var e = [];

            for (var _n56 = 0; _n56 < t.length; _n56++) {
              var _o46 = t[_n56];
              fi.test(_o46.name) || (_o46.name = _o46.name.replace(di, ""), e.push(_o46));
            }

            return e;
          }(o));
          var p = oi(t, o, i);
          var h;
          d && (p.ns = d), "style" !== (h = p).tag && ("script" !== h.tag || h.attrsMap.type && "text/javascript" !== h.attrsMap.type) || et() || (p.forbidden = !0);

          for (var _t51 = 0; _t51 < Ys.length; _t51++) {
            p = Ys[_t51](p, e) || p;
          }

          a || (!function (t) {
            null != Ao(t, "v-pre") && (t.pre = !0);
          }(p), p.pre && (a = !0)), ti(p.tag) && (c = !0), a ? function (t) {
            var e = t.attrsList,
                n = e.length;

            if (n) {
              var _o47 = t.attrs = new Array(n);

              for (var _t52 = 0; _t52 < n; _t52++) {
                _o47[_t52] = {
                  name: e[_t52].name,
                  value: JSON.stringify(e[_t52].value)
                }, null != e[_t52].start && (_o47[_t52].start = e[_t52].start, _o47[_t52].end = e[_t52].end);
              }
            } else t.pre || (t.plain = !0);
          }(p) : p.processed || (ii(p), function (t) {
            var e = Ao(t, "v-if");
            if (e) t.if = e, ai(t, {
              exp: e,
              block: t
            });else {
              null != Ao(t, "v-else") && (t.else = !0);

              var _e48 = Ao(t, "v-else-if");

              _e48 && (t.elseif = _e48);
            }
          }(p), function (t) {
            null != Ao(t, "v-once") && (t.once = !0);
          }(p)), s || (s = p), r ? l(p) : (i = p, n.push(p));
        },
        end: function end(t, e, o) {
          var r = n[n.length - 1];
          n.length -= 1, i = n[n.length - 1], l(r);
        },
        chars: function chars(t, e, n) {
          if (!i) return;
          if (q && "textarea" === i.tag && i.attrsMap.placeholder === t) return;
          var s = i.children;
          var l;

          if (t = c || t.trim() ? "script" === (l = i).tag || "style" === l.tag ? t : qs(t) : s.length ? r ? "condense" === r && Ks.test(t) ? "" : " " : o ? " " : "" : "") {
            var _e49, _n57;

            c || "condense" !== r || (t = t.replace(Js, " ")), !a && " " !== t && (_e49 = function (t, e) {
              var n = e ? ls(e) : as;
              if (!n.test(t)) return;
              var o = [],
                  r = [];
              var s,
                  i,
                  a,
                  c = n.lastIndex = 0;

              for (; s = n.exec(t);) {
                (i = s.index) > c && (r.push(a = t.slice(c, i)), o.push(JSON.stringify(a)));

                var _e50 = mo(s[1].trim());

                o.push("_s(".concat(_e50, ")")), r.push({
                  "@binding": _e50
                }), c = i + s[0].length;
              }

              return c < t.length && (r.push(a = t.slice(c)), o.push(JSON.stringify(a))), {
                expression: o.join("+"),
                tokens: r
              };
            }(t, Gs)) ? _n57 = {
              type: 2,
              expression: _e49.expression,
              tokens: _e49.tokens,
              text: t
            } : " " === t && s.length && " " === s[s.length - 1].text || (_n57 = {
              type: 3,
              text: t
            }), _n57 && s.push(_n57);
          }
        },
        comment: function comment(t, e, n) {
          if (i) {
            var _e51 = {
              type: 3,
              text: t,
              isComment: !0
            };
            i.children.push(_e51);
          }
        }
      }), s;
    }

    function si(t, e) {
      var n;
      !function (t) {
        var e = ko(t, "key");
        e && (t.key = e);
      }(t), t.plain = !t.key && !t.scopedSlots && !t.attrsList.length, function (t) {
        var e = ko(t, "ref");
        e && (t.ref = e, t.refInFor = function (t) {
          var e = t;

          for (; e;) {
            if (void 0 !== e.for) return !0;
            e = e.parent;
          }

          return !1;
        }(t));
      }(t), function (t) {
        var e;
        "template" === t.tag ? (e = Ao(t, "scope"), t.slotScope = e || Ao(t, "slot-scope")) : (e = Ao(t, "slot-scope")) && (t.slotScope = e);
        var n = ko(t, "slot");
        n && (t.slotTarget = '""' === n ? '"default"' : n, t.slotTargetDynamic = !(!t.attrsMap[":slot"] && !t.attrsMap["v-bind:slot"]), "template" === t.tag || t.slotScope || _o(t, "slot", n, function (t, e) {
          return t.rawAttrsMap[":" + e] || t.rawAttrsMap["v-bind:" + e] || t.rawAttrsMap[e];
        }(t, "slot")));

        if ("template" === t.tag) {
          var _e52 = Oo(t, Vs);

          if (_e52) {
            var _ci = ci(_e52),
                _n58 = _ci.name,
                _o48 = _ci.dynamic;

            t.slotTarget = _n58, t.slotTargetDynamic = _o48, t.slotScope = _e52.value || Ws;
          }
        } else {
          var _e53 = Oo(t, Vs);

          if (_e53) {
            var _n59 = t.scopedSlots || (t.scopedSlots = {}),
                _ci2 = ci(_e53),
                _o49 = _ci2.name,
                _r31 = _ci2.dynamic,
                _s17 = _n59[_o49] = oi("template", [], t);

            _s17.slotTarget = _o49, _s17.slotTargetDynamic = _r31, _s17.children = t.children.filter(function (t) {
              if (!t.slotScope) return t.parent = _s17, !0;
            }), _s17.slotScope = _e53.value || Ws, t.children = [], t.plain = !1;
          }
        }
      }(t), "slot" === (n = t).tag && (n.slotName = ko(n, "name")), function (t) {
        var e;
        (e = ko(t, "is")) && (t.component = e);
        null != Ao(t, "inline-template") && (t.inlineTemplate = !0);
      }(t);

      for (var _n60 = 0; _n60 < Xs.length; _n60++) {
        t = Xs[_n60](t, e) || t;
      }

      return function (t) {
        var e = t.attrsList;
        var n, o, r, s, i, a, c, l;

        for (n = 0, o = e.length; n < o; n++) {
          if (r = s = e[n].name, i = e[n].value, Is.test(r)) {
            if (t.hasBindings = !0, (a = li(r.replace(Is, ""))) && (r = r.replace(zs, "")), Us.test(r)) r = r.replace(Us, ""), i = mo(i), (l = Hs.test(r)) && (r = r.slice(1, -1)), a && (a.prop && !l && "innerHtml" === (r = _(r)) && (r = "innerHTML"), a.camel && !l && (r = _(r)), a.sync && (c = Eo(i, "$event"), l ? xo(t, "\"update:\"+(".concat(r, ")"), c, null, !1, 0, e[n], !0) : (xo(t, "update:".concat(_(r)), c, null, !1, 0, e[n]), C(r) !== _(r) && xo(t, "update:".concat(C(r)), c, null, !1, 0, e[n])))), a && a.prop || !t.component && ei(t.tag, t.attrsMap.type, r) ? $o(t, r, i, e[n], l) : _o(t, r, i, e[n], l);else if (Ms.test(r)) r = r.replace(Ms, ""), (l = Hs.test(r)) && (r = r.slice(1, -1)), xo(t, r, i, a, !1, 0, e[n], l);else {
              var _o50 = (r = r.replace(Is, "")).match(Bs);

              var _c6 = _o50 && _o50[1];

              l = !1, _c6 && (r = r.slice(0, -(_c6.length + 1)), Hs.test(_c6) && (_c6 = _c6.slice(1, -1), l = !0)), wo(t, r, s, i, _c6, l, a, e[n]);
            }
          } else _o(t, r, JSON.stringify(i), e[n]), !t.component && "muted" === r && ei(t.tag, t.attrsMap.type, r) && $o(t, r, "true", e[n]);
        }
      }(t), t;
    }

    function ii(t) {
      var e;

      if (e = Ao(t, "v-for")) {
        var _n61 = function (t) {
          var e = t.match(Fs);
          if (!e) return;
          var n = {};
          n.for = e[2].trim();
          var o = e[1].trim().replace(Rs, ""),
              r = o.match(Ps);
          r ? (n.alias = o.replace(Ps, "").trim(), n.iterator1 = r[1].trim(), r[2] && (n.iterator2 = r[2].trim())) : n.alias = o;
          return n;
        }(e);

        _n61 && A(t, _n61);
      }
    }

    function ai(t, e) {
      t.ifConditions || (t.ifConditions = []), t.ifConditions.push(e);
    }

    function ci(t) {
      var e = t.name.replace(Vs, "");
      return e || "#" !== t.name[0] && (e = "default"), Hs.test(e) ? {
        name: e.slice(1, -1),
        dynamic: !0
      } : {
        name: "\"".concat(e, "\""),
        dynamic: !1
      };
    }

    function li(t) {
      var e = t.match(zs);

      if (e) {
        var _t53 = {};
        return e.forEach(function (e) {
          _t53[e.slice(1)] = !0;
        }), _t53;
      }
    }

    function ui(t) {
      var e = {};

      for (var _n62 = 0, _o51 = t.length; _n62 < _o51; _n62++) {
        e[t[_n62].name] = t[_n62].value;
      }

      return e;
    }

    var fi = /^xmlns:NS\d+/,
        di = /^NS\d+:/;

    function pi(t) {
      return oi(t.tag, t.attrsList.slice(), t.parent);
    }

    var hi = [us, fs, {
      preTransformNode: function preTransformNode(t, e) {
        if ("input" === t.tag) {
          var _n63 = t.attrsMap;
          if (!_n63["v-model"]) return;

          var _o52;

          if ((_n63[":type"] || _n63["v-bind:type"]) && (_o52 = ko(t, "type")), _n63.type || _o52 || !_n63["v-bind"] || (_o52 = "(".concat(_n63["v-bind"], ").type")), _o52) {
            var _n64 = Ao(t, "v-if", !0),
                _r32 = _n64 ? "&&(".concat(_n64, ")") : "",
                _s18 = null != Ao(t, "v-else", !0),
                _i11 = Ao(t, "v-else-if", !0),
                _a5 = pi(t);

            ii(_a5), bo(_a5, "type", "checkbox"), si(_a5, e), _a5.processed = !0, _a5.if = "(".concat(_o52, ")==='checkbox'") + _r32, ai(_a5, {
              exp: _a5.if,
              block: _a5
            });

            var _c7 = pi(t);

            Ao(_c7, "v-for", !0), bo(_c7, "type", "radio"), si(_c7, e), ai(_a5, {
              exp: "(".concat(_o52, ")==='radio'") + _r32,
              block: _c7
            });

            var _l = pi(t);

            return Ao(_l, "v-for", !0), bo(_l, ":type", _o52), si(_l, e), ai(_a5, {
              exp: _n64,
              block: _l
            }), _s18 ? _a5.else = !0 : _i11 && (_a5.elseif = _i11), _a5;
          }
        }
      }
    }];
    var mi = {
      expectHTML: !0,
      modules: hi,
      directives: {
        model: function model(t, e, n) {
          var o = e.value,
              r = e.modifiers,
              s = t.tag,
              i = t.attrsMap.type;
          if (t.component) return To(t, o, r), !1;
          if ("select" === s) !function (t, e, n) {
            var o = "var $$selectedVal = ".concat('Array.prototype.filter.call($event.target.options,function(o){return o.selected}).map(function(o){var val = "_value" in o ? o._value : o.value;' + "return ".concat(n && n.number ? "_n(val)" : "val", "})"), ";");
            o = "".concat(o, " ").concat(Eo(e, "$event.target.multiple ? $$selectedVal : $$selectedVal[0]")), xo(t, "change", o, null, !0);
          }(t, o, r);else if ("input" === s && "checkbox" === i) !function (t, e, n) {
            var o = n && n.number,
                r = ko(t, "value") || "null",
                s = ko(t, "true-value") || "true",
                i = ko(t, "false-value") || "false";
            $o(t, "checked", "Array.isArray(".concat(e, ")") + "?_i(".concat(e, ",").concat(r, ")>-1") + ("true" === s ? ":(".concat(e, ")") : ":_q(".concat(e, ",").concat(s, ")"))), xo(t, "change", "var $$a=".concat(e, ",") + "$$el=$event.target," + "$$c=$$el.checked?(".concat(s, "):(").concat(i, ");") + "if(Array.isArray($$a)){" + "var $$v=".concat(o ? "_n(" + r + ")" : r, ",") + "$$i=_i($$a,$$v);" + "if($$el.checked){$$i<0&&(".concat(Eo(e, "$$a.concat([$$v])"), ")}") + "else{$$i>-1&&(".concat(Eo(e, "$$a.slice(0,$$i).concat($$a.slice($$i+1))"), ")}") + "}else{".concat(Eo(e, "$$c"), "}"), null, !0);
          }(t, o, r);else if ("input" === s && "radio" === i) !function (t, e, n) {
            var o = n && n.number;
            var r = ko(t, "value") || "null";
            $o(t, "checked", "_q(".concat(e, ",").concat(r = o ? "_n(".concat(r, ")") : r, ")")), xo(t, "change", Eo(e, r), null, !0);
          }(t, o, r);else if ("input" === s || "textarea" === s) !function (t, e, n) {
            var o = t.attrsMap.type,
                _ref4 = n || {},
                r = _ref4.lazy,
                s = _ref4.number,
                i = _ref4.trim,
                a = !r && "range" !== o,
                c = r ? "change" : "range" === o ? Uo : "input";

            var l = "$event.target.value";
            i && (l = "$event.target.value.trim()"), s && (l = "_n(".concat(l, ")"));
            var u = Eo(e, l);
            a && (u = "if($event.target.composing)return;".concat(u)), $o(t, "value", "(".concat(e, ")")), xo(t, c, u, null, !0), (i || s) && xo(t, "blur", "$forceUpdate()");
          }(t, o, r);else if (!F.isReservedTag(s)) return To(t, o, r), !1;
          return !0;
        },
        text: function text(t, e) {
          e.value && $o(t, "textContent", "_s(".concat(e.value, ")"), e);
        },
        html: function html(t, e) {
          e.value && $o(t, "innerHTML", "_s(".concat(e.value, ")"), e);
        }
      },
      isPreTag: function isPreTag(t) {
        return "pre" === t;
      },
      isUnaryTag: hs,
      mustUseProp: An,
      canBeLeftOpenTag: ms,
      isReservedTag: Un,
      getTagNamespace: zn,
      staticKeys: function (t) {
        return t.reduce(function (t, e) {
          return t.concat(e.staticKeys || []);
        }, []).join(",");
      }(hi)
    };
    var yi, gi;
    var vi = v(function (t) {
      return d("type,tag,attrsList,attrsMap,plain,parent,children,attrs,start,end,rawAttrsMap" + (t ? "," + t : ""));
    });

    function $i(t, e) {
      t && (yi = vi(e.staticKeys || ""), gi = e.isReservedTag || T, function t(e) {
        e.static = function (t) {
          if (2 === t.type) return !1;
          if (3 === t.type) return !0;
          return !(!t.pre && (t.hasBindings || t.if || t.for || p(t.tag) || !gi(t.tag) || function (t) {
            for (; t.parent;) {
              if ("template" !== (t = t.parent).tag) return !1;
              if (t.for) return !0;
            }

            return !1;
          }(t) || !Object.keys(t).every(yi)));
        }(e);

        if (1 === e.type) {
          if (!gi(e.tag) && "slot" !== e.tag && null == e.attrsMap["inline-template"]) return;

          for (var _n65 = 0, _o53 = e.children.length; _n65 < _o53; _n65++) {
            var _o54 = e.children[_n65];
            t(_o54), _o54.static || (e.static = !1);
          }

          if (e.ifConditions) for (var _n66 = 1, _o55 = e.ifConditions.length; _n66 < _o55; _n66++) {
            var _o56 = e.ifConditions[_n66].block;
            t(_o56), _o56.static || (e.static = !1);
          }
        }
      }(t), function t(e, n) {
        if (1 === e.type) {
          if ((e.static || e.once) && (e.staticInFor = n), e.static && e.children.length && (1 !== e.children.length || 3 !== e.children[0].type)) return void (e.staticRoot = !0);
          if (e.staticRoot = !1, e.children) for (var _o57 = 0, _r33 = e.children.length; _o57 < _r33; _o57++) {
            t(e.children[_o57], n || !!e.for);
          }
          if (e.ifConditions) for (var _o58 = 1, _r34 = e.ifConditions.length; _o58 < _r34; _o58++) {
            t(e.ifConditions[_o58].block, n);
          }
        }
      }(t, !1));
    }

    var _i = /^([\w$_]+|\([^)]*?\))\s*=>|^function\s*(?:[\w$]+)?\s*\(/,
        bi = /\([^)]*?\);*$/,
        wi = /^[A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*|\['[^']*?']|\["[^"]*?"]|\[\d+]|\[[A-Za-z_$][\w$]*])*$/,
        Ci = {
      esc: 27,
      tab: 9,
      enter: 13,
      space: 32,
      up: 38,
      left: 37,
      right: 39,
      down: 40,
      delete: [8, 46]
    },
        xi = {
      esc: ["Esc", "Escape"],
      tab: "Tab",
      enter: "Enter",
      space: [" ", "Spacebar"],
      up: ["Up", "ArrowUp"],
      left: ["Left", "ArrowLeft"],
      right: ["Right", "ArrowRight"],
      down: ["Down", "ArrowDown"],
      delete: ["Backspace", "Delete", "Del"]
    },
        ki = function ki(t) {
      return "if(".concat(t, ")return null;");
    },
        Ai = {
      stop: "$event.stopPropagation();",
      prevent: "$event.preventDefault();",
      self: ki("$event.target !== $event.currentTarget"),
      ctrl: ki("!$event.ctrlKey"),
      shift: ki("!$event.shiftKey"),
      alt: ki("!$event.altKey"),
      meta: ki("!$event.metaKey"),
      left: ki("'button' in $event && $event.button !== 0"),
      middle: ki("'button' in $event && $event.button !== 1"),
      right: ki("'button' in $event && $event.button !== 2")
    };

    function Oi(t, e) {
      var n = e ? "nativeOn:" : "on:";
      var o = "",
          r = "";

      for (var _e54 in t) {
        var _n67 = Si(t[_e54]);

        t[_e54] && t[_e54].dynamic ? r += "".concat(_e54, ",").concat(_n67, ",") : o += "\"".concat(_e54, "\":").concat(_n67, ",");
      }

      return o = "{".concat(o.slice(0, -1), "}"), r ? n + "_d(".concat(o, ",[").concat(r.slice(0, -1), "])") : n + o;
    }

    function Si(t) {
      if (!t) return "function(){}";
      if (Array.isArray(t)) return "[".concat(t.map(function (t) {
        return Si(t);
      }).join(","), "]");

      var e = wi.test(t.value),
          n = _i.test(t.value),
          o = wi.test(t.value.replace(bi, ""));

      if (t.modifiers) {
        var _r35 = "",
            _s19 = "";
        var _i12 = [];

        for (var _e55 in t.modifiers) {
          if (Ai[_e55]) _s19 += Ai[_e55], Ci[_e55] && _i12.push(_e55);else if ("exact" === _e55) {
            (function () {
              var e = t.modifiers;
              _s19 += ki(["ctrl", "shift", "alt", "meta"].filter(function (t) {
                return !e[t];
              }).map(function (t) {
                return "$event.".concat(t, "Key");
              }).join("||"));
            })();
          } else _i12.push(_e55);
        }

        return _i12.length && (_r35 += function (t) {
          return "if(!$event.type.indexOf('key')&&" + "".concat(t.map(Ti).join("&&"), ")return null;");
        }(_i12)), _s19 && (_r35 += _s19), "function($event){".concat(_r35).concat(e ? "return ".concat(t.value, "($event)") : n ? "return (".concat(t.value, ")($event)") : o ? "return ".concat(t.value) : t.value, "}");
      }

      return e || n ? t.value : "function($event){".concat(o ? "return ".concat(t.value) : t.value, "}");
    }

    function Ti(t) {
      var e = parseInt(t, 10);
      if (e) return "$event.keyCode!==".concat(e);
      var n = Ci[t],
          o = xi[t];
      return "_k($event.keyCode," + "".concat(JSON.stringify(t), ",") + "".concat(JSON.stringify(n), ",") + "$event.key," + "".concat(JSON.stringify(o)) + ")";
    }

    var Ei = {
      on: function on(t, e) {
        t.wrapListeners = function (t) {
          return "_g(".concat(t, ",").concat(e.value, ")");
        };
      },
      bind: function bind(t, e) {
        t.wrapData = function (n) {
          return "_b(".concat(n, ",'").concat(t.tag, "',").concat(e.value, ",").concat(e.modifiers && e.modifiers.prop ? "true" : "false").concat(e.modifiers && e.modifiers.sync ? ",true" : "", ")");
        };
      },
      cloak: S
    };

    var Ni = function Ni(t) {
      babelHelpers.classCallCheck(this, Ni);
      this.options = t, this.warn = t.warn || go, this.transforms = vo(t.modules, "transformCode"), this.dataGenFns = vo(t.modules, "genData"), this.directives = A(A({}, Ei), t.directives);
      var e = t.isReservedTag || T;
      this.maybeComponent = function (t) {
        return !!t.component || !e(t.tag);
      }, this.onceId = 0, this.staticRenderFns = [], this.pre = !1;
    };

    function ji(t, e) {
      var n = new Ni(e);
      return {
        render: "with(this){return ".concat(t ? Di(t, n) : '_c("div")', "}"),
        staticRenderFns: n.staticRenderFns
      };
    }

    function Di(t, e) {
      if (t.parent && (t.pre = t.pre || t.parent.pre), t.staticRoot && !t.staticProcessed) return Li(t, e);
      if (t.once && !t.onceProcessed) return Mi(t, e);
      if (t.for && !t.forProcessed) return Fi(t, e);
      if (t.if && !t.ifProcessed) return Ii(t, e);

      if ("template" !== t.tag || t.slotTarget || e.pre) {
        if ("slot" === t.tag) return function (t, e) {
          var n = t.slotName || '"default"',
              o = Bi(t, e);
          var r = "_t(".concat(n).concat(o ? ",".concat(o) : "");
          var s = t.attrs || t.dynamicAttrs ? Vi((t.attrs || []).concat(t.dynamicAttrs || []).map(function (t) {
            return {
              name: _(t.name),
              value: t.value,
              dynamic: t.dynamic
            };
          })) : null,
              i = t.attrsMap["v-bind"];
          !s && !i || o || (r += ",null");
          s && (r += ",".concat(s));
          i && (r += "".concat(s ? "" : ",null", ",").concat(i));
          return r + ")";
        }(t, e);
        {
          var _n68;

          if (t.component) _n68 = function (t, e, n) {
            var o = e.inlineTemplate ? null : Bi(e, n, !0);
            return "_c(".concat(t, ",").concat(Pi(e, n)).concat(o ? ",".concat(o) : "", ")");
          }(t.component, t, e);else {
            var _o59;

            (!t.plain || t.pre && e.maybeComponent(t)) && (_o59 = Pi(t, e));

            var _r36 = t.inlineTemplate ? null : Bi(t, e, !0);

            _n68 = "_c('".concat(t.tag, "'").concat(_o59 ? ",".concat(_o59) : "").concat(_r36 ? ",".concat(_r36) : "", ")");
          }

          for (var _o60 = 0; _o60 < e.transforms.length; _o60++) {
            _n68 = e.transforms[_o60](t, _n68);
          }

          return _n68;
        }
      }

      return Bi(t, e) || "void 0";
    }

    function Li(t, e) {
      t.staticProcessed = !0;
      var n = e.pre;
      return t.pre && (e.pre = t.pre), e.staticRenderFns.push("with(this){return ".concat(Di(t, e), "}")), e.pre = n, "_m(".concat(e.staticRenderFns.length - 1).concat(t.staticInFor ? ",true" : "", ")");
    }

    function Mi(t, e) {
      if (t.onceProcessed = !0, t.if && !t.ifProcessed) return Ii(t, e);

      if (t.staticInFor) {
        var _n69 = "",
            _o61 = t.parent;

        for (; _o61;) {
          if (_o61.for) {
            _n69 = _o61.key;
            break;
          }

          _o61 = _o61.parent;
        }

        return _n69 ? "_o(".concat(Di(t, e), ",").concat(e.onceId++, ",").concat(_n69, ")") : Di(t, e);
      }

      return Li(t, e);
    }

    function Ii(t, e, n, o) {
      return t.ifProcessed = !0, function t(e, n, o, r) {
        if (!e.length) return r || "_e()";
        var s = e.shift();
        return s.exp ? "(".concat(s.exp, ")?").concat(i(s.block), ":").concat(t(e, n, o, r)) : "".concat(i(s.block));

        function i(t) {
          return o ? o(t, n) : t.once ? Mi(t, n) : Di(t, n);
        }
      }(t.ifConditions.slice(), e, n, o);
    }

    function Fi(t, e, n, o) {
      var r = t.for,
          s = t.alias,
          i = t.iterator1 ? ",".concat(t.iterator1) : "",
          a = t.iterator2 ? ",".concat(t.iterator2) : "";
      return t.forProcessed = !0, "".concat(o || "_l", "((").concat(r, "),") + "function(".concat(s).concat(i).concat(a, "){") + "return ".concat((n || Di)(t, e)) + "})";
    }

    function Pi(t, e) {
      var n = "{";

      var o = function (t, e) {
        var n = t.directives;
        if (!n) return;
        var o,
            r,
            s,
            i,
            a = "directives:[",
            c = !1;

        for (o = 0, r = n.length; o < r; o++) {
          s = n[o], i = !0;
          var _r37 = e.directives[s.name];
          _r37 && (i = !!_r37(t, s, e.warn)), i && (c = !0, a += "{name:\"".concat(s.name, "\",rawName:\"").concat(s.rawName, "\"").concat(s.value ? ",value:(".concat(s.value, "),expression:").concat(JSON.stringify(s.value)) : "").concat(s.arg ? ",arg:".concat(s.isDynamicArg ? s.arg : "\"".concat(s.arg, "\"")) : "").concat(s.modifiers ? ",modifiers:".concat(JSON.stringify(s.modifiers)) : "", "},"));
        }

        if (c) return a.slice(0, -1) + "]";
      }(t, e);

      o && (n += o + ","), t.key && (n += "key:".concat(t.key, ",")), t.ref && (n += "ref:".concat(t.ref, ",")), t.refInFor && (n += "refInFor:true,"), t.pre && (n += "pre:true,"), t.component && (n += "tag:\"".concat(t.tag, "\","));

      for (var _o62 = 0; _o62 < e.dataGenFns.length; _o62++) {
        n += e.dataGenFns[_o62](t);
      }

      if (t.attrs && (n += "attrs:".concat(Vi(t.attrs), ",")), t.props && (n += "domProps:".concat(Vi(t.props), ",")), t.events && (n += "".concat(Oi(t.events, !1), ",")), t.nativeEvents && (n += "".concat(Oi(t.nativeEvents, !0), ",")), t.slotTarget && !t.slotScope && (n += "slot:".concat(t.slotTarget, ",")), t.scopedSlots && (n += "".concat(function (t, e, n) {
        var o = t.for || Object.keys(e).some(function (t) {
          var n = e[t];
          return n.slotTargetDynamic || n.if || n.for || Ri(n);
        }),
            r = !!t.if;

        if (!o) {
          var _e56 = t.parent;

          for (; _e56;) {
            if (_e56.slotScope && _e56.slotScope !== Ws || _e56.for) {
              o = !0;
              break;
            }

            _e56.if && (r = !0), _e56 = _e56.parent;
          }
        }

        var s = Object.keys(e).map(function (t) {
          return Hi(e[t], n);
        }).join(",");
        return "scopedSlots:_u([".concat(s, "]").concat(o ? ",null,true" : "").concat(!o && r ? ",null,false,".concat(function (t) {
          var e = 5381,
              n = t.length;

          for (; n;) {
            e = 33 * e ^ t.charCodeAt(--n);
          }

          return e >>> 0;
        }(s)) : "", ")");
      }(t, t.scopedSlots, e), ",")), t.model && (n += "model:{value:".concat(t.model.value, ",callback:").concat(t.model.callback, ",expression:").concat(t.model.expression, "},")), t.inlineTemplate) {
        var _o63 = function (t, e) {
          var n = t.children[0];

          if (n && 1 === n.type) {
            var _t54 = ji(n, e.options);

            return "inlineTemplate:{render:function(){".concat(_t54.render, "},staticRenderFns:[").concat(_t54.staticRenderFns.map(function (t) {
              return "function(){".concat(t, "}");
            }).join(","), "]}");
          }
        }(t, e);

        _o63 && (n += "".concat(_o63, ","));
      }

      return n = n.replace(/,$/, "") + "}", t.dynamicAttrs && (n = "_b(".concat(n, ",\"").concat(t.tag, "\",").concat(Vi(t.dynamicAttrs), ")")), t.wrapData && (n = t.wrapData(n)), t.wrapListeners && (n = t.wrapListeners(n)), n;
    }

    function Ri(t) {
      return 1 === t.type && ("slot" === t.tag || t.children.some(Ri));
    }

    function Hi(t, e) {
      var n = t.attrsMap["slot-scope"];
      if (t.if && !t.ifProcessed && !n) return Ii(t, e, Hi, "null");
      if (t.for && !t.forProcessed) return Fi(t, e, Hi);
      var o = t.slotScope === Ws ? "" : String(t.slotScope),
          r = "function(".concat(o, "){") + "return ".concat("template" === t.tag ? t.if && n ? "(".concat(t.if, ")?").concat(Bi(t, e) || "undefined", ":undefined") : Bi(t, e) || "undefined" : Di(t, e), "}"),
          s = o ? "" : ",proxy:true";
      return "{key:".concat(t.slotTarget || '"default"', ",fn:").concat(r).concat(s, "}");
    }

    function Bi(t, e, n, o, r) {
      var s = t.children;

      if (s.length) {
        var _t55 = s[0];

        if (1 === s.length && _t55.for && "template" !== _t55.tag && "slot" !== _t55.tag) {
          var _r38 = n ? e.maybeComponent(_t55) ? ",1" : ",0" : "";

          return "".concat((o || Di)(_t55, e)).concat(_r38);
        }

        var _i13 = n ? function (t, e) {
          var n = 0;

          for (var _o64 = 0; _o64 < t.length; _o64++) {
            var _r39 = t[_o64];

            if (1 === _r39.type) {
              if (Ui(_r39) || _r39.ifConditions && _r39.ifConditions.some(function (t) {
                return Ui(t.block);
              })) {
                n = 2;
                break;
              }

              (e(_r39) || _r39.ifConditions && _r39.ifConditions.some(function (t) {
                return e(t.block);
              })) && (n = 1);
            }
          }

          return n;
        }(s, e.maybeComponent) : 0,
            _a6 = r || zi;

        return "[".concat(s.map(function (t) {
          return _a6(t, e);
        }).join(","), "]").concat(_i13 ? ",".concat(_i13) : "");
      }
    }

    function Ui(t) {
      return void 0 !== t.for || "template" === t.tag || "slot" === t.tag;
    }

    function zi(t, e) {
      return 1 === t.type ? Di(t, e) : 3 === t.type && t.isComment ? (o = t, "_e(".concat(JSON.stringify(o.text), ")")) : "_v(".concat(2 === (n = t).type ? n.expression : Ki(JSON.stringify(n.text)), ")");
      var n, o;
    }

    function Vi(t) {
      var e = "",
          n = "";

      for (var _o65 = 0; _o65 < t.length; _o65++) {
        var _r40 = t[_o65],
            _s20 = Ki(_r40.value);

        _r40.dynamic ? n += "".concat(_r40.name, ",").concat(_s20, ",") : e += "\"".concat(_r40.name, "\":").concat(_s20, ",");
      }

      return e = "{".concat(e.slice(0, -1), "}"), n ? "_d(".concat(e, ",[").concat(n.slice(0, -1), "])") : e;
    }

    function Ki(t) {
      return t.replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029");
    }

    function Ji(t, e) {
      try {
        return new Function(t);
      } catch (n) {
        return e.push({
          err: n,
          code: t
        }), S;
      }
    }

    function qi(t) {
      var e = Object.create(null);
      return function (n, o, r) {
        (o = A({}, o)).warn;
        delete o.warn;
        var s = o.delimiters ? String(o.delimiters) + n : n;
        if (e[s]) return e[s];
        var i = t(n, o),
            a = {},
            c = [];
        return a.render = Ji(i.render, c), a.staticRenderFns = i.staticRenderFns.map(function (t) {
          return Ji(t, c);
        }), e[s] = a;
      };
    }

    var Wi = (Zi = function Zi(t, e) {
      var n = ri(t.trim(), e);
      !1 !== e.optimize && $i(n, e);
      var o = ji(n, e);
      return {
        ast: n,
        render: o.render,
        staticRenderFns: o.staticRenderFns
      };
    }, function (t) {
      function e(e, n) {
        var o = Object.create(t),
            r = [],
            s = [];

        if (n) {
          n.modules && (o.modules = (t.modules || []).concat(n.modules)), n.directives && (o.directives = A(Object.create(t.directives || null), n.directives));

          for (var _t56 in n) {
            "modules" !== _t56 && "directives" !== _t56 && (o[_t56] = n[_t56]);
          }
        }

        o.warn = function (t, e, n) {
          (n ? s : r).push(t);
        };

        var i = Zi(e.trim(), o);
        return i.errors = r, i.tips = s, i;
      }

      return {
        compile: e,
        compileToFunctions: qi(e)
      };
    });
    var Zi;

    var _Wi = Wi(mi),
        Gi = _Wi.compile,
        Xi = _Wi.compileToFunctions;

    var Yi;

    function Qi(t) {
      return (Yi = Yi || document.createElement("div")).innerHTML = t ? '<a href="\n"/>' : '<div a="\n"/>', Yi.innerHTML.indexOf("&#10;") > 0;
    }

    var ta = !!z && Qi(!1),
        ea = !!z && Qi(!0),
        na = v(function (t) {
      var e = Jn(t);
      return e && e.innerHTML;
    }),
        oa = yn.prototype.$mount;
    yn.prototype.$mount = function (t, e) {
      if ((t = t && Jn(t)) === document.body || t === document.documentElement) return this;
      var n = this.$options;

      if (!n.render) {
        var _e57 = n.template;
        if (_e57) {
          if ("string" == typeof _e57) "#" === _e57.charAt(0) && (_e57 = na(_e57));else {
            if (!_e57.nodeType) return this;
            _e57 = _e57.innerHTML;
          }
        } else t && (_e57 = function (t) {
          if (t.outerHTML) return t.outerHTML;
          {
            var _e58 = document.createElement("div");

            return _e58.appendChild(t.cloneNode(!0)), _e58.innerHTML;
          }
        }(t));

        if (_e57) {
          var _Xi = Xi(_e57, {
            outputSourceRange: !1,
            shouldDecodeNewlines: ta,
            shouldDecodeNewlinesForHref: ea,
            delimiters: n.delimiters,
            comments: n.comments
          }, this),
              _t57 = _Xi.render,
              _o66 = _Xi.staticRenderFns;

          n.render = _t57, n.staticRenderFns = _o66;
        }
      }

      return oa.call(this, t, e);
    }, yn.compile = Xi;

    var argumentAsArray = function argumentAsArray(argument) {
      return Array.isArray(argument) ? argument : [argument];
    };
    var isElement = function isElement(target) {
      return target instanceof Node;
    };
    var isElementList = function isElementList(nodeList) {
      return nodeList instanceof NodeList;
    };
    var eachNode = function eachNode(nodeList, callback) {
      if (nodeList && callback) {
        nodeList = isElementList(nodeList) ? nodeList : [nodeList];

        for (var i = 0; i < nodeList.length; i++) {
          if (callback(nodeList[i], i, nodeList.length) === true) {
            break;
          }
        }
      }
    };
    var throwError = function throwError(message) {
      return console.error("[scroll-lock] ".concat(message));
    };
    var arrayAsSelector = function arrayAsSelector(array) {
      if (Array.isArray(array)) {
        var selector = array.join(', ');
        return selector;
      }
    };
    var nodeListAsArray = function nodeListAsArray(nodeList) {
      var nodes = [];
      eachNode(nodeList, function (node) {
        return nodes.push(node);
      });
      return nodes;
    };
    var findParentBySelector = function findParentBySelector($el, selector) {
      var self = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var $root = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : document;

      if (self && nodeListAsArray($root.querySelectorAll(selector)).indexOf($el) !== -1) {
        return $el;
      }

      while (($el = $el.parentElement) && nodeListAsArray($root.querySelectorAll(selector)).indexOf($el) === -1) {
      }

      return $el;
    };
    var elementHasSelector = function elementHasSelector($el, selector) {
      var $root = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : document;
      var has = nodeListAsArray($root.querySelectorAll(selector)).indexOf($el) !== -1;
      return has;
    };
    var elementHasOverflowHidden = function elementHasOverflowHidden($el) {
      if ($el) {
        var computedStyle = getComputedStyle($el);
        var overflowIsHidden = computedStyle.overflow === 'hidden';
        return overflowIsHidden;
      }
    };
    var elementScrollTopOnStart = function elementScrollTopOnStart($el) {
      if ($el) {
        if (elementHasOverflowHidden($el)) {
          return true;
        }

        var scrollTop = $el.scrollTop;
        return scrollTop <= 0;
      }
    };
    var elementScrollTopOnEnd = function elementScrollTopOnEnd($el) {
      if ($el) {
        if (elementHasOverflowHidden($el)) {
          return true;
        }

        var scrollTop = $el.scrollTop;
        var scrollHeight = $el.scrollHeight;
        var scrollTopWithHeight = scrollTop + $el.offsetHeight;
        return scrollTopWithHeight >= scrollHeight;
      }
    };
    var elementScrollLeftOnStart = function elementScrollLeftOnStart($el) {
      if ($el) {
        if (elementHasOverflowHidden($el)) {
          return true;
        }

        var scrollLeft = $el.scrollLeft;
        return scrollLeft <= 0;
      }
    };
    var elementScrollLeftOnEnd = function elementScrollLeftOnEnd($el) {
      if ($el) {
        if (elementHasOverflowHidden($el)) {
          return true;
        }

        var scrollLeft = $el.scrollLeft;
        var scrollWidth = $el.scrollWidth;
        var scrollLeftWithWidth = scrollLeft + $el.offsetWidth;
        return scrollLeftWithWidth >= scrollWidth;
      }
    };
    var elementIsScrollableField = function elementIsScrollableField($el) {
      var selector = 'textarea, [contenteditable="true"]';
      return elementHasSelector($el, selector);
    };
    var elementIsInputRange = function elementIsInputRange($el) {
      var selector = 'input[type="range"]';
      return elementHasSelector($el, selector);
    };

    var FILL_GAP_AVAILABLE_METHODS = ['padding', 'margin', 'width', 'max-width', 'none'];
    var TOUCH_DIRECTION_DETECT_OFFSET = 3;
    var state = {
      scroll: true,
      queue: 0,
      scrollableSelectors: ['[data-scroll-lock-scrollable]'],
      lockableSelectors: ['body', '[data-scroll-lock-lockable]'],
      fillGapSelectors: ['body', '[data-scroll-lock-fill-gap]', '[data-scroll-lock-lockable]'],
      fillGapMethod: FILL_GAP_AVAILABLE_METHODS[0],
      //
      startTouchY: 0,
      startTouchX: 0
    };
    var disablePageScroll = function disablePageScroll(target) {
      if (state.queue <= 0) {
        state.scroll = false;
        hideLockableOverflow();
        fillGaps();
      }

      addScrollableTarget(target);
      state.queue++;
    };
    var enablePageScroll = function enablePageScroll(target) {
      state.queue > 0 && state.queue--;

      if (state.queue <= 0) {
        state.scroll = true;
        showLockableOverflow();
        unfillGaps();
      }

      removeScrollableTarget(target);
    };
    var getScrollState = function getScrollState() {
      return state.scroll;
    };
    var clearQueueScrollLocks = function clearQueueScrollLocks() {
      state.queue = 0;
    };
    var getTargetScrollBarWidth = function getTargetScrollBarWidth($target) {
      var onlyExists = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (isElement($target)) {
        var currentOverflowYProperty = $target.style.overflowY;

        if (onlyExists) {
          if (!getScrollState()) {
            $target.style.overflowY = $target.dataset.scrollLockSavedOverflowYProperty;
          }
        } else {
          $target.style.overflowY = 'scroll';
        }

        var width = getCurrentTargetScrollBarWidth($target);
        $target.style.overflowY = currentOverflowYProperty;
        return width;
      } else {
        return 0;
      }
    };
    var getCurrentTargetScrollBarWidth = function getCurrentTargetScrollBarWidth($target) {
      if (isElement($target)) {
        if ($target === document.body) {
          var documentWidth = document.documentElement.clientWidth;
          var windowWidth = window.innerWidth;
          var currentWidth = windowWidth - documentWidth;
          return currentWidth;
        } else {
          var borderLeftWidthCurrentProperty = $target.style.borderLeftWidth;
          var borderRightWidthCurrentProperty = $target.style.borderRightWidth;
          $target.style.borderLeftWidth = '0px';
          $target.style.borderRightWidth = '0px';

          var _currentWidth = $target.offsetWidth - $target.clientWidth;

          $target.style.borderLeftWidth = borderLeftWidthCurrentProperty;
          $target.style.borderRightWidth = borderRightWidthCurrentProperty;
          return _currentWidth;
        }
      } else {
        return 0;
      }
    };
    var getPageScrollBarWidth = function getPageScrollBarWidth() {
      var onlyExists = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      return getTargetScrollBarWidth(document.body, onlyExists);
    };
    var getCurrentPageScrollBarWidth = function getCurrentPageScrollBarWidth() {
      return getCurrentTargetScrollBarWidth(document.body);
    };
    var addScrollableTarget = function addScrollableTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              $target.dataset.scrollLockScrollable = '';
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });
      }
    };
    var removeScrollableTarget = function removeScrollableTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              delete $target.dataset.scrollLockScrollable;
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });
      }
    };
    var addScrollableSelector = function addScrollableSelector(selector) {
      if (selector) {
        var selectors = argumentAsArray(selector);
        selectors.map(function (selector) {
          state.scrollableSelectors.push(selector);
        });
      }
    };
    var removeScrollableSelector = function removeScrollableSelector(selector) {
      if (selector) {
        var selectors = argumentAsArray(selector);
        selectors.map(function (selector) {
          state.scrollableSelectors = state.scrollableSelectors.filter(function (sSelector) {
            return sSelector !== selector;
          });
        });
      }
    };
    var addLockableTarget = function addLockableTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              $target.dataset.scrollLockLockable = '';
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });

        if (!getScrollState()) {
          hideLockableOverflow();
        }
      }
    };
    var removeLockableTarget = function removeLockableTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              delete $target.dataset.scrollLockLockable;
              showLockableOverflowTarget($target);
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });
      }
    };
    var addLockableSelector = function addLockableSelector(selector) {
      if (selector) {
        var selectors = argumentAsArray(selector);
        selectors.map(function (selector) {
          state.lockableSelectors.push(selector);
        });

        if (!getScrollState()) {
          hideLockableOverflow();
        }

        addFillGapSelector(selector);
      }
    };
    var setFillGapMethod = function setFillGapMethod(method) {
      if (method) {
        if (FILL_GAP_AVAILABLE_METHODS.indexOf(method) !== -1) {
          state.fillGapMethod = method;
          refillGaps();
        } else {
          var methods = FILL_GAP_AVAILABLE_METHODS.join(', ');
          throwError("\"".concat(method, "\" method is not available!\nAvailable fill gap methods: ").concat(methods, "."));
        }
      }
    };
    var addFillGapTarget = function addFillGapTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              $target.dataset.scrollLockFillGap = '';

              if (!state.scroll) {
                fillGapTarget($target);
              }
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });
      }
    };
    var removeFillGapTarget = function removeFillGapTarget(target) {
      if (target) {
        var targets = argumentAsArray(target);
        targets.map(function ($targets) {
          eachNode($targets, function ($target) {
            if (isElement($target)) {
              delete $target.dataset.scrollLockFillGap;

              if (!state.scroll) {
                unfillGapTarget($target);
              }
            } else {
              throwError("\"".concat($target, "\" is not a Element."));
            }
          });
        });
      }
    };
    var addFillGapSelector = function addFillGapSelector(selector) {
      if (selector) {
        var selectors = argumentAsArray(selector);
        selectors.map(function (selector) {
          state.fillGapSelectors.push(selector);

          if (!state.scroll) {
            fillGapSelector(selector);
          }
        });
      }
    };
    var removeFillGapSelector = function removeFillGapSelector(selector) {
      if (selector) {
        var selectors = argumentAsArray(selector);
        selectors.map(function (selector) {
          state.fillGapSelectors = state.fillGapSelectors.filter(function (fSelector) {
            return fSelector !== selector;
          });

          if (!state.scroll) {
            unfillGapSelector(selector);
          }
        });
      }
    };
    var refillGaps = function refillGaps() {
      if (!state.scroll) {
        fillGaps();
      }
    };

    var hideLockableOverflow = function hideLockableOverflow() {
      var selector = arrayAsSelector(state.lockableSelectors);
      hideLockableOverflowSelector(selector);
    };

    var showLockableOverflow = function showLockableOverflow() {
      var selector = arrayAsSelector(state.lockableSelectors);
      showLockableOverflowSelector(selector);
    };

    var hideLockableOverflowSelector = function hideLockableOverflowSelector(selector) {
      var $targets = document.querySelectorAll(selector);
      eachNode($targets, function ($target) {
        hideLockableOverflowTarget($target);
      });
    };

    var showLockableOverflowSelector = function showLockableOverflowSelector(selector) {
      var $targets = document.querySelectorAll(selector);
      eachNode($targets, function ($target) {
        showLockableOverflowTarget($target);
      });
    };

    var hideLockableOverflowTarget = function hideLockableOverflowTarget($target) {
      if (isElement($target) && $target.dataset.scrollLockLocked !== 'true') {
        var computedStyle = window.getComputedStyle($target);
        $target.dataset.scrollLockSavedOverflowYProperty = computedStyle.overflowY;
        $target.dataset.scrollLockSavedInlineOverflowProperty = $target.style.overflow;
        $target.dataset.scrollLockSavedInlineOverflowYProperty = $target.style.overflowY;
        $target.style.overflow = 'hidden';
        $target.dataset.scrollLockLocked = 'true';
      }
    };

    var showLockableOverflowTarget = function showLockableOverflowTarget($target) {
      if (isElement($target) && $target.dataset.scrollLockLocked === 'true') {
        $target.style.overflow = $target.dataset.scrollLockSavedInlineOverflowProperty;
        $target.style.overflowY = $target.dataset.scrollLockSavedInlineOverflowYProperty;
        delete $target.dataset.scrollLockSavedOverflowYProperty;
        delete $target.dataset.scrollLockSavedInlineOverflowProperty;
        delete $target.dataset.scrollLockSavedInlineOverflowYProperty;
        delete $target.dataset.scrollLockLocked;
      }
    };

    var fillGaps = function fillGaps() {
      state.fillGapSelectors.map(function (selector) {
        fillGapSelector(selector);
      });
    };

    var unfillGaps = function unfillGaps() {
      state.fillGapSelectors.map(function (selector) {
        unfillGapSelector(selector);
      });
    };

    var fillGapSelector = function fillGapSelector(selector) {
      var $targets = document.querySelectorAll(selector);
      var isLockable = state.lockableSelectors.indexOf(selector) !== -1;
      eachNode($targets, function ($target) {
        fillGapTarget($target, isLockable);
      });
    };

    var fillGapTarget = function fillGapTarget($target) {
      var isLockable = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (isElement($target)) {
        var scrollBarWidth;

        if ($target.dataset.scrollLockLockable === '' || isLockable) {
          scrollBarWidth = getTargetScrollBarWidth($target, true);
        } else {
          var $lockableParent = findParentBySelector($target, arrayAsSelector(state.lockableSelectors));
          scrollBarWidth = getTargetScrollBarWidth($lockableParent, true);
        }

        if ($target.dataset.scrollLockFilledGap === 'true') {
          unfillGapTarget($target);
        }

        var computedStyle = window.getComputedStyle($target);
        $target.dataset.scrollLockFilledGap = 'true';
        $target.dataset.scrollLockCurrentFillGapMethod = state.fillGapMethod;

        if (state.fillGapMethod === 'margin') {
          var currentMargin = parseFloat(computedStyle.marginRight);
          $target.style.marginRight = "".concat(currentMargin + scrollBarWidth, "px");
        } else if (state.fillGapMethod === 'width') {
          $target.style.width = "calc(100% - ".concat(scrollBarWidth, "px)");
        } else if (state.fillGapMethod === 'max-width') {
          $target.style.maxWidth = "calc(100% - ".concat(scrollBarWidth, "px)");
        } else if (state.fillGapMethod === 'padding') {
          var currentPadding = parseFloat(computedStyle.paddingRight);
          $target.style.paddingRight = "".concat(currentPadding + scrollBarWidth, "px");
        }
      }
    };

    var unfillGapSelector = function unfillGapSelector(selector) {
      var $targets = document.querySelectorAll(selector);
      eachNode($targets, function ($target) {
        unfillGapTarget($target);
      });
    };

    var unfillGapTarget = function unfillGapTarget($target) {
      if (isElement($target)) {
        if ($target.dataset.scrollLockFilledGap === 'true') {
          var currentFillGapMethod = $target.dataset.scrollLockCurrentFillGapMethod;
          delete $target.dataset.scrollLockFilledGap;
          delete $target.dataset.scrollLockCurrentFillGapMethod;

          if (currentFillGapMethod === 'margin') {
            $target.style.marginRight = "";
          } else if (currentFillGapMethod === 'width') {
            $target.style.width = "";
          } else if (currentFillGapMethod === 'max-width') {
            $target.style.maxWidth = "";
          } else if (currentFillGapMethod === 'padding') {
            $target.style.paddingRight = "";
          }
        }
      }
    };

    var onResize = function onResize(e) {
      refillGaps();
    };

    var onTouchStart = function onTouchStart(e) {
      if (!state.scroll) {
        state.startTouchY = e.touches[0].clientY;
        state.startTouchX = e.touches[0].clientX;
      }
    };

    var onTouchMove = function onTouchMove(e) {
      if (!state.scroll) {
        var startTouchY = state.startTouchY,
            startTouchX = state.startTouchX;
        var currentClientY = e.touches[0].clientY;
        var currentClientX = e.touches[0].clientX;

        if (e.touches.length < 2) {
          var selector = arrayAsSelector(state.scrollableSelectors);
          var direction = {
            up: startTouchY < currentClientY,
            down: startTouchY > currentClientY,
            left: startTouchX < currentClientX,
            right: startTouchX > currentClientX
          };
          var directionWithOffset = {
            up: startTouchY + TOUCH_DIRECTION_DETECT_OFFSET < currentClientY,
            down: startTouchY - TOUCH_DIRECTION_DETECT_OFFSET > currentClientY,
            left: startTouchX + TOUCH_DIRECTION_DETECT_OFFSET < currentClientX,
            right: startTouchX - TOUCH_DIRECTION_DETECT_OFFSET > currentClientX
          };

          var handle = function handle($el) {
            var skip = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

            if ($el) {
              var parentScrollableEl = findParentBySelector($el, selector, false);

              if (elementIsInputRange($el)) {
                return false;
              }

              if (skip || elementIsScrollableField($el) && findParentBySelector($el, selector) || elementHasSelector($el, selector)) {
                var prevent = false;

                if (elementScrollLeftOnStart($el) && elementScrollLeftOnEnd($el)) {
                  if (direction.up && elementScrollTopOnStart($el) || direction.down && elementScrollTopOnEnd($el)) {
                    prevent = true;
                  }
                } else if (elementScrollTopOnStart($el) && elementScrollTopOnEnd($el)) {
                  if (direction.left && elementScrollLeftOnStart($el) || direction.right && elementScrollLeftOnEnd($el)) {
                    prevent = true;
                  }
                } else if (directionWithOffset.up && elementScrollTopOnStart($el) || directionWithOffset.down && elementScrollTopOnEnd($el) || directionWithOffset.left && elementScrollLeftOnStart($el) || directionWithOffset.right && elementScrollLeftOnEnd($el)) {
                  prevent = true;
                }

                if (prevent) {
                  if (parentScrollableEl) {
                    handle(parentScrollableEl, true);
                  } else {
                    e.preventDefault();
                  }
                }
              } else {
                handle(parentScrollableEl);
              }
            } else {
              e.preventDefault();
            }
          };

          handle(e.target);
        }
      }
    };

    var onTouchEnd = function onTouchEnd(e) {
      if (!state.scroll) {
        state.startTouchY = 0;
        state.startTouchX = 0;
      }
    };

    if (typeof window !== 'undefined') {
      window.addEventListener('resize', onResize);
    }

    if (typeof document !== 'undefined') {
      document.addEventListener('touchstart', onTouchStart);
      document.addEventListener('touchmove', onTouchMove, {
        passive: false
      });
      document.addEventListener('touchend', onTouchEnd);
    }

    var deprecatedMethods = {
      hide: function hide(target) {
        throwError('"hide" is deprecated! Use "disablePageScroll" instead. \n https://github.com/FL3NKEY/scroll-lock#disablepagescrollscrollabletarget');
        disablePageScroll(target);
      },
      show: function show(target) {
        throwError('"show" is deprecated! Use "enablePageScroll" instead. \n https://github.com/FL3NKEY/scroll-lock#enablepagescrollscrollabletarget');
        enablePageScroll(target);
      },
      toggle: function toggle(target) {
        throwError('"toggle" is deprecated! Do not use it.');

        if (getScrollState()) {
          disablePageScroll();
        } else {
          enablePageScroll(target);
        }
      },
      getState: function getState() {
        throwError('"getState" is deprecated! Use "getScrollState" instead. \n https://github.com/FL3NKEY/scroll-lock#getscrollstate');
        return getScrollState();
      },
      getWidth: function getWidth() {
        throwError('"getWidth" is deprecated! Use "getPageScrollBarWidth" instead. \n https://github.com/FL3NKEY/scroll-lock#getpagescrollbarwidth');
        return getPageScrollBarWidth();
      },
      getCurrentWidth: function getCurrentWidth() {
        throwError('"getCurrentWidth" is deprecated! Use "getCurrentPageScrollBarWidth" instead. \n https://github.com/FL3NKEY/scroll-lock#getcurrentpagescrollbarwidth');
        return getCurrentPageScrollBarWidth();
      },
      setScrollableTargets: function setScrollableTargets(target) {
        throwError('"setScrollableTargets" is deprecated! Use "addScrollableTarget" instead. \n https://github.com/FL3NKEY/scroll-lock#addscrollabletargetscrollabletarget');
        addScrollableTarget(target);
      },
      setFillGapSelectors: function setFillGapSelectors(selector) {
        throwError('"setFillGapSelectors" is deprecated! Use "addFillGapSelector" instead. \n https://github.com/FL3NKEY/scroll-lock#addfillgapselectorfillgapselector');
        addFillGapSelector(selector);
      },
      setFillGapTargets: function setFillGapTargets(target) {
        throwError('"setFillGapTargets" is deprecated! Use "addFillGapTarget" instead. \n https://github.com/FL3NKEY/scroll-lock#addfillgaptargetfillgaptarget');
        addFillGapTarget(target);
      },
      clearQueue: function clearQueue() {
        throwError('"clearQueue" is deprecated! Use "clearQueueScrollLocks" instead. \n https://github.com/FL3NKEY/scroll-lock#clearqueuescrolllocks');
        clearQueueScrollLocks();
      }
    };
    var scrollLock = babelHelpers.objectSpread({
      disablePageScroll: disablePageScroll,
      enablePageScroll: enablePageScroll,
      getScrollState: getScrollState,
      clearQueueScrollLocks: clearQueueScrollLocks,
      getTargetScrollBarWidth: getTargetScrollBarWidth,
      getCurrentTargetScrollBarWidth: getCurrentTargetScrollBarWidth,
      getPageScrollBarWidth: getPageScrollBarWidth,
      getCurrentPageScrollBarWidth: getCurrentPageScrollBarWidth,
      addScrollableSelector: addScrollableSelector,
      removeScrollableSelector: removeScrollableSelector,
      addScrollableTarget: addScrollableTarget,
      removeScrollableTarget: removeScrollableTarget,
      addLockableSelector: addLockableSelector,
      addLockableTarget: addLockableTarget,
      removeLockableTarget: removeLockableTarget,
      addFillGapSelector: addFillGapSelector,
      removeFillGapSelector: removeFillGapSelector,
      addFillGapTarget: addFillGapTarget,
      removeFillGapTarget: removeFillGapTarget,
      setFillGapMethod: setFillGapMethod,
      refillGaps: refillGaps,
      _state: state
    }, deprecatedMethods);

    var MoveObserver =
    /*#__PURE__*/
    function () {
      function MoveObserver(handler, element) {
        babelHelpers.classCallCheck(this, MoveObserver);
        babelHelpers.defineProperty(this, "detecting", false);
        babelHelpers.defineProperty(this, "x", 0);
        babelHelpers.defineProperty(this, "y", 0);
        babelHelpers.defineProperty(this, "deltaX", 0);
        babelHelpers.defineProperty(this, "deltaY", 0);
        this.element = element;
        this.handler = handler;
        this.listeners = {
          start: this.onTouchStart.bind(this),
          move: this.onTouchMove.bind(this),
          end: this.onTouchEnd.bind(this)
        };
      }

      babelHelpers.createClass(MoveObserver, [{
        key: "toggle",
        value: function toggle(mode, element) {
          if (element) {
            this.element = element;
          }

          mode ? this.run() : this.stop();
        }
      }, {
        key: "run",
        value: function run() {
          this.element.setAttribute('draggable', false);
          this.element.addEventListener('touchstart', this.listeners.start);
          this.element.addEventListener('touchmove', this.listeners.move);
          this.element.addEventListener('touchend', this.listeners.end);
          this.element.addEventListener('touchcancel', this.listeners.end);
        }
      }, {
        key: "stop",
        value: function stop() {
          this.element.removeAttribute('draggable');
          this.element.removeEventListener('touchstart', this.listeners.start);
          this.element.removeEventListener('touchmove', this.listeners.move);
          this.element.removeEventListener('touchend', this.listeners.end);
          this.element.removeEventListener('touchcancel', this.listeners.end);
        }
      }, {
        key: "onTouchStart",
        value: function onTouchStart(e) {
          if (e.touches.length !== 1 || this.detecting) {
            return;
          }

          var touch = e.changedTouches[0];
          this.detecting = true;
          this.x = touch.pageX;
          this.y = touch.pageY;
          this.deltaX = 0;
          this.deltaY = 0;
          this.touch = touch;
        }
      }, {
        key: "onTouchMove",
        value: function onTouchMove(e) {
          if (!this.detecting) {
            return;
          }

          var touch = e.changedTouches[0];
          var newX = touch.pageX;
          var newY = touch.pageY;

          if (!this.hasTouch(e.changedTouches, touch)) {
            return;
          }

          if (!this.detecting) {
            return;
          }

          e.preventDefault();
          this.deltaX = this.x - newX;
          this.deltaY = this.y - newY;
          this.handler(this, false);
        }
      }, {
        key: "onTouchEnd",
        value: function onTouchEnd(e) {
          if (!this.hasTouch(e.changedTouches, this.touch) || !this.detecting) {
            return;
          }

          if (this.deltaY > 2 && this.deltaX > 2) {
            e.preventDefault();
          }

          this.detecting = false;
          this.handler(this, true);
        }
      }, {
        key: "hasTouch",
        value: function hasTouch(list, item) {
          for (var i = 0; i < list.length; i++) {
            if (list.item(i).identifier === item.identifier) {
              return true;
            }
          }

          return false;
        }
      }]);
      return MoveObserver;
    }();

    var Scroll = {
      items: [],
      toggle: function toggle(element, mode) {
        mode ? this.enable(element) : this.disable(element);
      },
      getLastItem: function getLastItem() {
        return this.items.length > 0 ? this.items[this.items.length - 1] : null;
      },
      disable: function disable(element) {
        var prevElement = this.getLastItem();

        if (prevElement) {
          addLockableTarget(prevElement);
          addFillGapTarget(prevElement);
        }

        disablePageScroll(element);
        this.items.push(element);
      },
      enable: function enable() {
        var _this = this;

        setTimeout(function () {
          var element = _this.items.pop();

          enablePageScroll(element);

          var prevElement = _this.getLastItem();

          if (prevElement) {
            removeFillGapTarget(prevElement);
            removeLockableTarget(prevElement);
          }
        }, 300);
      }
    };
    var Type = {
      defined: function defined(val) {
        return typeof val !== 'undefined';
      },
      object: function object(val) {
        return babelHelpers.typeof(val) === 'object';
      },
      string: function string(val) {
        return typeof val === 'string';
      }
    };
    var Conv = {
      number: function number(value) {
        value = parseFloat(value);
        return isNaN(value) ? 0 : value;
      },
      string: function string() {},
      formatMoney: function formatMoney(val, format) {
        return (format || '').replace('#', val || 0);
      },
      replaceText: function replaceText(text, fields) {
        fields = fields || {};
        var holders = text.match(/{{[ -.a-zA-Z]+}}/g);

        if (!holders || holders.length === 0) {
          return text;
        }

        var result = holders.reduce(function (s, item) {
          var value = item.replace(/^{+/, '').replace(/}+$/, '').trim();
          value = fields[value] ? fields[value] : '';
          var parts = s.split(item);

          for (var i = 0; i < parts.length; i = i + 1) {
            if (i === parts.length - 1 && parts.length > 1) {
              continue;
            }

            var left = parts[i].replace(/[ \t]+$/, '');

            if (!value) {
              left = left.replace(/[,]+$/, '');
            }

            left += (value ? ' ' : '') + value;
            parts[i] = left;

            if (i + 1 >= parts.length) {
              continue;
            }

            var right = parts[i + 1].replace(/^[ \t]+/, '');

            if (!/^[<!?.\n]+/.test(right)) {
              var isLeftClosed = !left || /[<!?.\n]+$/.test(left);

              if (isLeftClosed) {
                right = right.replace(/^[ \t,]+/, '');
              }

              if (!/^[,]+/.test(right)) {
                if (isLeftClosed) {
                  right = right.charAt(0).toUpperCase() + right.slice(1);
                }

                right = ' ' + right;
              }
            }

            parts[i + 1] = right;
          }

          return parts.join('').trim();
        }, text);
        return result ? result : text;
      }
    };
    var Color = {
      parseHex: function parseHex(hex) {
        hex = this.fillHex(hex);
        var parts = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?$/i.exec(hex);

        if (!parts) {
          parts = [0, 0, 0, 1];
        } else {
          parts = [parseInt(parts[1], 16), parseInt(parts[2], 16), parseInt(parts[3], 16), parseInt(100 * (parseInt(parts[4] || 'ff', 16) / 255)) / 100];
        }

        return parts;
      },
      hexToRgba: function hexToRgba(hex) {
        return 'rgba(' + this.parseHex(hex).join(', ') + ')';
      },
      toRgba: function toRgba(numbers) {
        return 'rgba(' + numbers.join(', ') + ')';
      },
      fillHex: function fillHex(hex, fillAlpha) {
        if (hex.length === 4 || fillAlpha && hex.length === 5) {
          hex = hex.replace(/([a-f0-9])/gi, "$1$1");
        }

        if (fillAlpha && hex.length === 7) {
          hex += 'ff';
        }

        return hex;
      },
      isHexDark: function isHexDark(hex) {
        hex = this.parseHex(hex);
        var r = hex[0];
        var g = hex[1];
        var b = hex[2];
        var brightness = (r * 299 + g * 587 + b * 114) / 1000;
        return brightness < 155;
      }
    };
    var Browser = {
      isMobile: function isMobile() {
        return window.innerWidth <= 530;
      }
    };

    var Item = function Item(options) {
      babelHelpers.classCallCheck(this, Item);
      babelHelpers.defineProperty(this, "value", '');
      babelHelpers.defineProperty(this, "label", '');
      babelHelpers.defineProperty(this, "selected", false);
      this.selected = !!options.selected;

      if (Type.defined(options.label)) {
        this.label = options.label;
      }

      if (Type.defined(options.value)) {
        this.value = options.value;
      }
    };

    var Field = {
      props: ['field'],
      components: {},
      template: "\n\t\t<transition name=\"b24-form-field-a-slide\">\n\t\t\t<div class=\"b24-form-field\"\n\t\t\t\t:class=\"classes\"\n\t\t\t\tv-show=\"field.visible\"\n\t\t\t>\n\t\t\t\t<div v-if=\"field.isComponentDuplicable\">\n\t\t\t\t<transition-group name=\"b24-form-field-a-slide\" tag=\"div\">\n\t\t\t\t\t<component v-bind:is=\"field.getComponentName()\"\n\t\t\t\t\t\tv-for=\"(item, itemIndex) in field.items\"\n\t\t\t\t\t\tv-bind:key=\"field.id\"\n\t\t\t\t\t\tv-bind:field=\"field\"\n\t\t\t\t\t\tv-bind:itemIndex=\"itemIndex\"\n\t\t\t\t\t\tv-bind:item=\"item\"\n\t\t\t\t\t\t@input-blur=\"onBlur\"\n\t\t\t\t\t\t@input-focus=\"onFocus\"\n\t\t\t\t\t\t@input-key-down=\"onKeyDown\"\n\t\t\t\t\t></component>\n\t\t\t\t</transition-group>\t\n\t\t\t\t\t<a class=\"b24-form-control-add-btn\"\n\t\t\t\t\t\tv-if=\"field.multiple\"\n\t\t\t\t\t\t@click=\"addItem\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ field.messages.get('fieldAdd') }}\n\t\t\t\t\t</a>\t\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"!field.isComponentDuplicable\">\n\t\t\t\t\t<component v-bind:is=\"field.getComponentName()\"\n\t\t\t\t\t\tv-bind:key=\"field.id\"\n\t\t\t\t\t\tv-bind:field=\"field\"\n\t\t\t\t\t\t@input-blur=\"onBlur\"\n\t\t\t\t\t\t@input-focus=\"onFocus\"\n\t\t\t\t\t\t@input-key-down=\"onKeyDown\"\n\t\t\t\t\t></component>\t\t\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t",
      computed: {
        classes: function classes() {
          var list = ['b24-form-field-' + this.field.type, 'b24-form-control-' + this.field.getOriginalType()];
          /*
          if (this.field.design.dark)
          {
          	list.push('b24-form-field-dark');
          }
          */

          if (this.field.multiple) {
            list.push('b24-form-control-group');
          }

          if (this.hasErrors) {
            list.push('b24-form-control-alert');
          }

          return list;
        },
        hasErrors: function hasErrors() {
          return this.field.validated && !this.field.focused && !this.field.valid();
        }
      },
      methods: {
        addItem: function addItem() {
          this.field.addItem({});
        },
        onFocus: function onFocus() {
          this.field.focused = true;
        },
        onBlur: function onBlur() {
          this.field.focused = false;
          this.field.valid();
        },
        onKeyDown: function onKeyDown(e) {
          var value = e.key;

          if (this.field.filter(value)) {
            return;
          }

          if (e.key === 'Esc' || e.key === 'Delete' || e.key === 'Backspace') {
            return;
          }

          e.preventDefault();
        }
      }
    };

    var Storage =
    /*#__PURE__*/
    function () {
      function Storage() {
        babelHelpers.classCallCheck(this, Storage);
        babelHelpers.defineProperty(this, "language", 'en');
        babelHelpers.defineProperty(this, "messages", {});
      }

      babelHelpers.createClass(Storage, [{
        key: "setMessages",
        value: function setMessages(messages) {
          this.messages = messages;
        }
      }, {
        key: "setLanguage",
        value: function setLanguage(language) {
          this.language = language;
        }
      }, {
        key: "get",
        value: function get(code) {
          var mess = this.messages;
          var lang = this.language || 'en';

          if (mess[lang] && mess[lang][code]) {
            return mess[lang][code];
          }

          lang = 'en';

          if (mess[lang] && mess[lang][code]) {
            return mess[lang][code];
          }

          return '';
        }
      }]);
      return Storage;
    }();

    var Themes = {
      'modern-light': {
        dark: false,
        style: 'modern',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
          family: 'Open Sans'
        }
      },
      'modern-dark': {
        dark: true,
        style: 'modern',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
          family: 'Open Sans'
        }
      },
      'classic-light': {
        dark: false,
        style: 'classic',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
          family: 'PT Serif'
        }
      },
      'classic-dark': {
        dark: true,
        style: 'classic',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
          family: 'PT Serif'
        }
      },
      'fun-light': {
        dark: false,
        style: 'fun',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
          family: 'Pangolin'
        }
      },
      'fun-dark': {
        dark: true,
        style: 'fun',
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
          family: 'Pangolin'
        }
      },
      pixel: {
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Press+Start+2P&display=swap&subset=cyrillic',
          family: 'Press Start 2P'
        },
        dark: true,
        color: {
          text: '#90ee90'
        }
      },
      old: {
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Ruslan+Display&display=swap&subset=cyrillic',
          family: 'Ruslan Display'
        },
        color: {
          background: '#f1eddf'
        }
      },
      writing: {
        font: {
          uri: 'https://fonts.googleapis.com/css?family=Marck+Script&display=swap&subset=cyrillic',
          family: 'Marck Script'
        }
      }
    };

    var Model =
    /*#__PURE__*/
    function () {
      function Model(options) {
        babelHelpers.classCallCheck(this, Model);
        babelHelpers.defineProperty(this, "dark", null);
        babelHelpers.defineProperty(this, "font", {
          uri: '',
          family: ''
        });
        babelHelpers.defineProperty(this, "color", {
          primary: '',
          primaryText: '',
          text: '',
          background: '',
          fieldBorder: '',
          fieldBackground: '',
          fieldFocusBackground: ''
        });
        babelHelpers.defineProperty(this, "border", {
          top: false,
          left: false,
          bottom: true,
          right: false
        });
        babelHelpers.defineProperty(this, "shadow", false);
        babelHelpers.defineProperty(this, "style", null);
        babelHelpers.defineProperty(this, "backgroundImage", null);
        this.adjust(options);
      }

      babelHelpers.createClass(Model, [{
        key: "adjust",
        value: function adjust(options) {
          options = options || {};

          if (typeof options.theme !== 'undefined') {
            this.theme = options.theme;
            var theme = Themes[options.theme] || {};
            this.setStyle(theme.style || '');
            this.setDark(theme.dark || false);
            this.setFont(theme.font || {});
            this.setBorder(theme.border || {});
            this.setShadow(theme.shadow || false);
            this.setColor(Object.assign({
              primary: '',
              primaryText: '',
              text: '',
              background: '',
              fieldBorder: '',
              fieldBackground: '',
              fieldFocusBackground: ''
            }, theme.color));
            /*
            options.font = this.getEffectiveOption(options.font);
            options.dark = options.dark === 'auto'
            	? undefined
            	: this.getEffectiveOption(options.dark);
            options.style = this.getEffectiveOption(options.style);
            options.color = this.getEffectiveOption(options.color);
            */
          }

          if (typeof options.font === 'string' || babelHelpers.typeof(options.font) === 'object') {
            this.setFont(options.font);
          }

          if (typeof options.dark !== 'undefined') {
            this.setDark(options.dark);
          }

          if (babelHelpers.typeof(options.color) === 'object') {
            this.setColor(options.color);
          }

          if (typeof options.shadow !== 'undefined') {
            this.setShadow(options.shadow);
          }

          if (typeof options.border !== 'undefined') {
            this.setBorder(options.border);
          }

          if (typeof options.style !== 'undefined') {
            this.setStyle(options.style);
          }

          if (typeof options.backgroundImage !== 'undefined') {
            this.setBackgroundImage(options.backgroundImage);
          }
        }
      }, {
        key: "setFont",
        value: function setFont(family, uri) {
          if (babelHelpers.typeof(family) === 'object') {
            uri = family.uri;
            family = family.family;
          }

          this.font.family = family || '';
          this.font.uri = this.font.family ? uri || '' : '';
        }
      }, {
        key: "setShadow",
        value: function setShadow(shadow) {
          this.shadow = !!shadow;
        }
      }, {
        key: "setBackgroundImage",
        value: function setBackgroundImage(url) {
          this.backgroundImage = url;
        }
      }, {
        key: "setBorder",
        value: function setBorder(border) {
          if (babelHelpers.typeof(border) === 'object') {
            if (typeof border.top !== 'undefined') {
              this.border.top = !!border.top;
            }

            if (typeof border.right !== 'undefined') {
              this.border.right = !!border.right;
            }

            if (typeof border.bottom !== 'undefined') {
              this.border.bottom = !!border.bottom;
            }

            if (typeof border.left !== 'undefined') {
              this.border.left = !!border.left;
            }
          } else {
            border = !!border;
            this.border.top = border;
            this.border.right = border;
            this.border.bottom = border;
            this.border.left = border;
          }
        }
      }, {
        key: "setDark",
        value: function setDark(dark) {
          this.dark = typeof dark === 'boolean' ? dark : null;
        }
      }, {
        key: "setColor",
        value: function setColor(color) {
          if (typeof color.primary !== 'undefined') {
            this.color.primary = Color.fillHex(color.primary, true);
          }

          if (typeof color.primaryText !== 'undefined') {
            this.color.primaryText = Color.fillHex(color.primaryText, true);
          }

          if (typeof color.text !== 'undefined') {
            this.color.text = Color.fillHex(color.text, true);
          }

          if (typeof color.background !== 'undefined') {
            this.color.background = Color.fillHex(color.background, true);
          }

          if (typeof color.fieldBorder !== 'undefined') {
            this.color.fieldBorder = Color.fillHex(color.fieldBorder, true);
          }

          if (typeof color.fieldBackground !== 'undefined') {
            this.color.fieldBackground = Color.fillHex(color.fieldBackground, true);
          }

          if (typeof color.fieldFocusBackground !== 'undefined') {
            this.color.fieldFocusBackground = Color.fillHex(color.fieldFocusBackground, true);
          }
        }
      }, {
        key: "setStyle",
        value: function setStyle(style) {
          this.style = style;
        }
      }, {
        key: "getFontUri",
        value: function getFontUri() {
          return this.font.uri;
        }
      }, {
        key: "getFontFamily",
        value: function getFontFamily() {
          return this.font.family;
        }
      }, {
        key: "getEffectiveOption",
        value: function getEffectiveOption(option) {
          switch (babelHelpers.typeof(option)) {
            case "object":
              var result = undefined;

              for (var key in option) {
                if (option.hasOwnProperty(key)) {
                  continue;
                }

                var value = this.getEffectiveOption(option);

                if (value) {
                  result = result || {};
                  result[key] = option;
                }
              }

              return result;

            case "string":
              if (option) {
                return option;
              }

              break;
          }

          return undefined;
        }
      }, {
        key: "isDark",
        value: function isDark() {
          if (this.dark !== null) {
            return this.dark;
          }

          if (!this.color.background) {
            return false;
          }

          if (this.color.background.indexOf('#') !== 0) {
            return false;
          }

          return Color.isHexDark(this.color.background);
        }
      }, {
        key: "isAutoDark",
        value: function isAutoDark() {
          return this.dark === null;
        }
      }]);
      return Model;
    }();

    var DefaultOptions = {
      type: 'string',
      label: 'Default field name',
      multiple: false,
      visible: true,
      required: false
    };

    var Controller =
    /*#__PURE__*/
    function () {
      babelHelpers.createClass(Controller, [{
        key: "getComponentName",
        value: function getComponentName() {
          return 'field-' + this.getType();
        }
      }, {
        key: "getType",
        value: function getType() {
          return this.constructor.type();
        }
      }, {
        key: "isComponentDuplicable",
        get: function get() {
          return false;
        }
      }], [{
        key: "type",
        //#baseType: string;
        value: function type() {
          return '';
        }
      }, {
        key: "component",
        value: function component() {
          return Field;
        }
      }, {
        key: "createItem",
        value: function createItem(options) {
          return new Item(options);
        }
      }]);

      function Controller() {
        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DefaultOptions;
        babelHelpers.classCallCheck(this, Controller);
        babelHelpers.defineProperty(this, "options", DefaultOptions);
        babelHelpers.defineProperty(this, "items", []);
        babelHelpers.defineProperty(this, "validated", false);
        babelHelpers.defineProperty(this, "focused", false);
        babelHelpers.defineProperty(this, "validators", []);
        babelHelpers.defineProperty(this, "normalizers", []);
        babelHelpers.defineProperty(this, "formatters", []);
        babelHelpers.defineProperty(this, "filters", []);
        this.adjust(options);
      }

      babelHelpers.createClass(Controller, [{
        key: "selectedItems",
        value: function selectedItems() {
          return this.items.filter(function (item) {
            return item.selected;
          });
        }
      }, {
        key: "selectedItem",
        value: function selectedItem() {
          return this.selectedItems()[0];
        }
      }, {
        key: "unselectedItems",
        value: function unselectedItems() {
          return this.items.filter(function (item) {
            return !item.selected;
          });
        }
      }, {
        key: "unselectedItem",
        value: function unselectedItem() {
          return this.unselectedItems()[0];
        }
      }, {
        key: "item",
        value: function item() {
          return this.items[0];
        }
      }, {
        key: "value",
        value: function value() {
          return this.values()[0];
        }
      }, {
        key: "values",
        value: function values() {
          return this.selectedItems().map(function (item) {
            return item.value;
          });
        }
      }, {
        key: "normalize",
        value: function normalize(value) {
          return this.normalizers.reduce(function (v, f) {
            return f(v);
          }, value);
        }
      }, {
        key: "filter",
        value: function filter(value) {
          return this.filters.reduce(function (v, f) {
            return f(v);
          }, value);
        }
      }, {
        key: "format",
        value: function format(value) {
          return this.formatters.reduce(function (v, f) {
            return f(v);
          }, value);
        }
      }, {
        key: "validate",
        value: function validate(value) {
          var _this = this;

          if (value === '') {
            return true;
          }

          return !this.validators.some(function (validator) {
            return !validator.call(_this, value);
          });
        }
      }, {
        key: "isEmptyRequired",
        value: function isEmptyRequired() {
          var items = this.selectedItems();

          if (this.required) {
            if (items.length === 0 || !items[0].selected || items[0].value === '') {
              return true;
            }
          }

          return false;
        }
      }, {
        key: "valid",
        value: function valid() {
          var _this2 = this;

          this.validated = true;
          var items = this.selectedItems();

          if (this.isEmptyRequired()) {
            return false;
          }

          return !items.some(function (item) {
            return !_this2.validate(item.value);
          });
        }
      }, {
        key: "getOriginalType",
        value: function getOriginalType() {
          return this.type;
        }
      }, {
        key: "addItem",
        value: function addItem(options) {
          if (options.selected && !this.multiple && this.values().length > 0) {
            options.selected = false;
          }

          var item = this.constructor.createItem(options);
          this.items.push(item);
          return item;
        }
      }, {
        key: "addSingleEmptyItem",
        value: function addSingleEmptyItem() {
          if (this.items.length > this.values().length) {
            return;
          }

          if (this.items.length > 0 && !this.multiple) {
            return;
          }

          this.addItem({});
        }
      }, {
        key: "removeItem",
        value: function removeItem(itemIndex) {
          this.items.splice(itemIndex, 1);
          this.addSingleEmptyItem();
        }
      }, {
        key: "removeFirstEmptyItems",
        value: function removeFirstEmptyItems() {}
      }, {
        key: "adjust",
        value: function adjust() {
          var _this3 = this;

          var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DefaultOptions;
          this.options = Object.assign({}, this.options, options);
          this.id = this.options.id || '';
          this.name = this.options.name || '';
          this.type = this.options.type;
          this.label = this.options.label;
          this.multiple = !!this.options.multiple;
          this.visible = !!this.options.visible;
          this.required = !!this.options.required;

          if (options.messages || !this.messages) {
            if (options.messages instanceof Storage) {
              this.messages = options.messages;
            } else {
              this.messages = new Storage();
              this.messages.setMessages(options.messages || {});
            }
          }

          if (options.design || !this.design) {
            if (options.design instanceof Model) {
              this.design = options.design;
            } else {
              this.design = new Model();
              this.design.adjust(options.design || {});
            }
          }

          var values = this.options.values || [];
          var items = this.options.items || [];
          var selected = !this.multiple || values.length > 0;

          if (values.length === 0) {
            values.push('');
          } // empty single


          if (items.length === 0 && !this.multiple) {
            var value = this.options.value || values[0];

            if (typeof this.options.checked !== "undefined") {
              selected = !!this.options.checked;
            }

            items.push({
              value: value,
              selected: selected
            });
          } // empty multi


          if (items.length === 0 && this.multiple) {
            values.forEach(function (value) {
              return items.push({
                value: value,
                selected: selected
              });
            });
          }

          items.forEach(function (item) {
            return _this3.addItem(item);
          });
        }
      }]);
      return Controller;
    }();

    var Dropdown = {
      props: ['marginTop', 'maxHeight', 'width', 'visible', 'title'],
      template: "\n\t\t<div class=\"b24-form-dropdown\">\n\t\t\t<transition name=\"b24-form-dropdown-slide\" appear>\n\t\t\t<div class=\"b24-form-dropdown-container\" \n\t\t\t\t:style=\"{marginTop: marginTop, maxHeight: maxHeight, width: width, minWidth: width}\"\n\t\t\t\tv-if=\"visible\"\n\t\t\t>\n\t\t\t\t<div class=\"b24-form-dropdown-header\" ref=\"header\">\n\t\t\t\t\t<button @click=\"close()\" type=\"button\" class=\"b24-window-close\"></button>\n\t\t\t\t\t<div class=\"b24-form-dropdown-title\">{{ title }}</div>\n\t\t\t\t</div>\t\t\t\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t\t</transition>\n\t\t</div>\n\t",
      data: function data() {
        return {
          listenerBind: null,
          observers: {}
        };
      },
      created: function created() {
        this.listenerBind = this.listener.bind(this);
      },
      mounted: function mounted() {
        this.observers.move = new MoveObserver(this.observeMove.bind(this));
      },
      beforeDestroy: function beforeDestroy() {
        document.removeEventListener('mouseup', this.listenerBind);
      },
      watch: {
        visible: function visible(val) {
          var _this = this;

          if (val) {
            document.addEventListener('mouseup', this.listenerBind);
          } else {
            document.removeEventListener('mouseup', this.listenerBind);
          }

          if (window.innerWidth <= 530) {
            setTimeout(function () {
              Scroll.toggle(_this.$el.querySelector('.b24-form-dropdown-container'), !val);

              _this.observers.move.toggle(val, _this.$refs.header);
            }, 0);
          }
        }
      },
      methods: {
        close: function close() {
          this.$emit('close');
        },
        listener: function listener(e) {
          var el = e.target;

          if (this.$el !== el && !this.$el.contains(el)) {
            this.close();
          }
        },
        observeMove: function observeMove(observer, isEnd) {
          var target = observer.element.parentElement;

          if (!isEnd) {
            if (!target.dataset.height) {
              target.dataset.height = target.clientHeight;
            }

            target.style.height = target.style.minHeight = parseInt(target.dataset.height) + parseInt(observer.deltaY) + 'px';
          }

          if (isEnd) {
            if (observer.deltaY < 0 && Math.abs(observer.deltaY) > target.dataset.height / 2) {
              if (document.activeElement) {
                document.activeElement.blur();
              }

              this.close();
              setTimeout(function () {
                if (!target) {
                  return;
                }

                target.dataset.height = null;
                target.style.height = null;
                target.style.minHeight = null;
              }, 300);
            } else {
              target.style.transition = "all 0.4s ease 0s";
              target.style.height = target.style.minHeight = target.dataset.height + 'px';
              setTimeout(function () {
                return target.style.transition = null;
              }, 400);
            }
          }
        }
      }
    };
    var Alert = {
      props: ['field', 'item'],
      template: "\n\t\t<div class=\"b24-form-control-alert-message\"\n\t\t\tv-show=\"hasErrors\"\n\t\t>\n\t\t\t{{ message }}\n\t\t</div>\n\t",
      computed: {
        hasErrors: function hasErrors() {
          return this.field.validated && !this.field.focused && !this.field.valid();
        },
        message: function message() {
          if (this.field.isEmptyRequired()) {
            return this.field.messages.get('fieldErrorRequired');
          } else if (this.field.validated && !this.field.valid()) {
            var type = this.field.type;
            type = type.charAt(0).toUpperCase() + type.slice(1);
            return this.field.messages.get('fieldErrorInvalid' + type) || this.field.messages.get('fieldErrorInvalid');
          }
        }
      }
    };
    var Slider = {
      props: ['field', 'item'],
      data: function data() {
        return {
          index: 0,
          lastItem: null,
          minHeight: 100,
          indexHeight: 100,
          heights: {},
          touch: {
            started: false,
            detecting: false,
            x: 0,
            y: 0
          }
        };
      },
      template: "\n\t\t<div v-if=\"hasPics\" class=\"b24-from-slider\">\n\t\t\t<div class=\"b24-form-slider-wrapper\">\n\t\t\t\t<div class=\"b24-form-slider-container\" \n\t\t\t\t\t:style=\"{ height: height + 'px', width: width + '%', left: left + '%'}\"\n\t\t\t\t\tv-swipe=\"move\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"b24-form-slider-item\"\n\t\t\t\t\t\tv-for=\"(pic, picIndex) in getItem().pics\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<img class=\"b24-form-slider-item-image\" \n\t\t\t\t\t\t\t:src=\"pic\"\n\t\t\t\t\t\t\t@load=\"saveHeight($event, picIndex)\"\n\t\t\t\t\t\t>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t\t<div class=\"b24-form-slider-control-prev\"\n\t\t\t\t\t\t@click=\"prev\"\n\t\t\t\t\t\t:style=\"{ visibility: prevable() ? 'visible' : 'hidden'}\"\n\t\t\t\t\t><div class=\"b24-form-slider-control-prev-icon\"></div></div>\n\t\t\t\t\t<div class=\"b24-form-slider-control-next\"\n\t\t\t\t\t\t@click=\"next\"\n\t\t\t\t\t\t:style=\"{ visibility: nextable() ? 'visible' : 'hidden'}\"\n\t\t\t\t\t><div class=\"b24-form-slider-control-next-icon\"></div></div>\n\t\t\t</div>\n\t\t</div>\n\t",
      directives: {
        swipe: {
          inserted: function inserted(el, binding) {
            var data = {
              started: false,
              detecting: false,
              x: 0,
              y: 0,
              touch: null
            };

            var hasTouch = function hasTouch(list, item) {
              for (var i = 0; i < list.length; i++) {
                if (list.item(i).identifier === item.identifier) {
                  return true;
                }
              }

              return false;
            };

            el.addEventListener('touchstart', function (e) {
              if (e.touches.length !== 1 || data.started) {
                return;
              }

              var touch = e.changedTouches[0];
              data.detecting = true;
              data.x = touch.pageX;
              data.y = touch.pageY;
              data.touch = touch;
            });
            el.addEventListener('touchmove', function (e) {
              if (!data.started && !data.detecting) {
                return;
              }

              var touch = e.changedTouches[0];
              var newX = touch.pageX;
              var newY = touch.pageY;

              if (!hasTouch(e.changedTouches, touch)) {
                return;
              }

              if (data.detecting) {
                if (Math.abs(data.x - newX) >= Math.abs(data.y - newY)) {
                  e.preventDefault();
                  data.started = true;
                }

                data.detecting = false;
              }

              if (data.started) {
                e.preventDefault();
                data.delta = data.x - newX;
              }
            });

            var onEnd = function onEnd(e) {
              if (!hasTouch(e.changedTouches, data.touch) || !data.started) {
                return;
              }

              e.preventDefault();

              if (data.delta > 0) {
                binding.value(true);
              } else if (data.delta < 0) {
                binding.value(false);
              }

              data.started = false;
              data.detecting = false;
            };

            el.addEventListener('touchend', onEnd);
            el.addEventListener('touchcancel', onEnd);
          }
        }
      },
      computed: {
        height: function height() {
          if (this.indexHeight && this.indexHeight > this.minHeight) {
            return this.indexHeight;
          }

          return this.minHeight;
        },
        width: function width() {
          return this.getItem().pics.length * 100;
        },
        left: function left() {
          return this.index * -100;
        },
        hasPics: function hasPics() {
          return this.getItem() && this.getItem().pics && Array.isArray(this.getItem().pics) && this.getItem().pics.length > 0;
        }
      },
      methods: {
        saveHeight: function saveHeight(e, picIndex) {
          this.heights[picIndex] = e.target.clientHeight;
          this.applyIndexHeight();
        },
        applyIndexHeight: function applyIndexHeight() {
          this.indexHeight = this.heights[this.index];
        },
        getItem: function getItem() {
          var item = this.item || this.field.selectedItem();

          if (this.lastItem !== item) {
            this.lastItem = item;
            this.index = 0;
            this.heights = {};
          }

          return this.lastItem;
        },
        nextable: function nextable() {
          return this.index < this.getItem().pics.length - 1;
        },
        prevable: function prevable() {
          return this.index > 0;
        },
        next: function next() {
          if (this.nextable()) {
            this.index++;
            this.applyIndexHeight();
          }
        },
        prev: function prev() {
          if (this.prevable()) {
            this.index--;
            this.applyIndexHeight();
          }
        },
        move: function move(next) {
          next ? this.next() : this.prev();
        }
      }
    };
    var Definition = {
      'field-item-alert': Alert,
      'field-item-image-slider': Slider,
      'field-item-dropdown': Dropdown
    };

    var MixinField = {
      props: ['field'],
      components: Object.assign({}, Definition),
      computed: {
        selected: {
          get: function get() {
            return this.field.multiple ? this.field.values() : this.field.values()[0];
          },
          set: function set(newValue) {
            this.field.items.forEach(function (item) {
              item.selected = Array.isArray(newValue) ? newValue.includes(item.value) : newValue === item.value;
            });
          }
        }
      },
      methods: {
        controlClasses: function controlClasses() {//b24-form-control-checked
        }
      }
    };
    var MixinDropDown = {
      components: {
        'field-item-dropdown': Dropdown
      },
      data: function data() {
        return {
          dropDownOpened: false
        };
      },
      methods: {
        toggleDropDown: function toggleDropDown() {
          if (this.dropDownOpened) {
            this.closeDropDown();
          } else {
            this.dropDownOpened = true;
          }
        },
        closeDropDown: function closeDropDown() {
          var _this = this;

          setTimeout(function () {
            _this.dropDownOpened = false;
          }, 0);
        }
      }
    };

    var MixinString = {
      props: ['field', 'itemIndex', 'item', 'readonly', 'buttonClear'],
      mixins: [MixinField],
      computed: {
        label: function label() {
          return this.item.label ? this.item.label : this.field.label + (this.itemIndex > 0 ? ' (' + this.itemIndex + ')' : '');
        },
        value: {
          get: function get() {
            return this.item.value;
          },
          set: function set(newValue) {
            this.item.value = newValue;
            this.item.selected = !!this.item.value;
          }
        },
        inputClasses: function inputClasses() {
          var list = [];

          if (this.item.value) {
            list.push('b24-form-control-not-empty');
          }

          return list;
        }
      },
      methods: {
        deleteItem: function deleteItem() {
          this.field.items.splice(this.itemIndex, 1);
        },
        clearItem: function clearItem() {
          this.value = '';
        }
      },
      watch: {}
    };
    var FieldString = {
      mixins: [MixinString],
      template: "\n\t\t<div class=\"b24-form-control-container b24-form-control-icon-after\">\n\t\t\t<input class=\"b24-form-control\"\n\t\t\t\t:type=\"field.getInputType()\"\n\t\t\t\t:class=\"inputClasses\"\n\t\t\t\t:readonly=\"readonly\"\n\t\t\t\tv-model=\"value\"\n\t\t\t\t@blur=\"$emit('input-blur', $event)\"\n\t\t\t\t@focus=\"$emit('input-focus', $event)\"\n\t\t\t\t@click=\"$emit('input-click', $event)\"\n\t\t\t\t@input=\"onInput\"\n\t\t\t\t@keydown=\"$emit('input-key-down', $event)\"\n\t\t\t>\n\t\t\t<div class=\"b24-form-control-label\">\n\t\t\t\t{{ label }} \n\t\t\t\t<span class=\"b24-form-control-required\"\n\t\t\t\t\tv-show=\"field.required\"\n\t\t\t\t>*</span>\t\t\t\t\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-icon-after b24-form-icon-remove\"\n\t\t\t\t:title=\"field.messages.get('fieldRemove')\"\n\t\t\t\tv-if=\"itemIndex > 0\"\n\t\t\t\t@click=\"deleteItem\"\n\t\t\t></div>\n\t\t\t<div class=\"b24-form-icon-after b24-form-icon-remove\"\n\t\t\t\t:title=\"buttonClear\"\n\t\t\t\tv-if=\"buttonClear && itemIndex === 0 && value\"\n\t\t\t\t@click=\"clearItem\"\n\t\t\t></div>\n\t\t\t<field-item-alert\n\t\t\t\tv-bind:field=\"field\"\n\t\t\t\tv-bind:item=\"item\"\n\t\t\t></field-item-alert>\n\t\t</div>\n\t",
      methods: {
        onInput: function onInput() {
          var value = this.field.normalize(this.value);
          this.value = this.field.format(value);
        }
      }
    };

    var Controller$1 =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);

      function Controller$$1() {
        babelHelpers.classCallCheck(this, Controller$$1);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).apply(this, arguments));
      }

      babelHelpers.createClass(Controller$$1, [{
        key: "getOriginalType",
        value: function getOriginalType() {
          return 'string';
        }
      }, {
        key: "getInputType",
        value: function getInputType() {
          return 'string';
        }
      }, {
        key: "isComponentDuplicable",
        get: function get() {
          return true;
        }
      }], [{
        key: "type",
        value: function type() {
          return 'string';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldString;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var Filter = {
      Email: function Email(value) {
        return (value || '').replace(/[^\w.\d-_@]/g, '');
      },
      Double: function Double(value) {
        return (value || '').replace(/[^\-,.\d]/g, '');
      },
      Integer: function Integer(value) {
        return (value || '').replace(/[^-\d]/g, '');
      },
      Phone: function Phone(value) {
        return (value || '').replace(/[^+\d]/g, '');
      }
    };
    var Normalizer = {
      Email: Filter.Email,
      Double: function Double(value) {
        return Filter.Double(value).replace(/,/g, '.');
      },
      Integer: function Integer(value) {
        return Filter.Integer(value);
      },
      Phone: function Phone(value) {
        return value;
      }
    };
    var Validator = {
      Email: function Email(value) {
        return null !== (value || '').match(/^[\w.\d-_]+@[\w.\d-_]+\.\w{2,15}$/i);
      },
      Double: function Double(value) {
        value = (value || '').replace(/,/g, '.');
        var dotIndex = value.indexOf('.');

        if (dotIndex === 0) {
          value = '0' + value;
        } else if (dotIndex < 0) {
          value += '.0';
        }

        return value.match(/^\d+\.\d+$/);
      },
      Integer: function Integer(value) {
        return value && value.match(/^-?\d+$/);
      },
      Phone: function Phone(value) {
        return Filter.Phone(value).length > 5;
      }
    };
    var phoneDb = {
      list: null,
      findMask: function findMask(value) {
        var r = phoneDb.list.filter(function (item) {
          return value.indexOf(item.code) === 0;
        }).sort(function (a, b) {
          return b.code.length - a.code.length;
        })[0];
        return r ? r.mask : '_ ___ __ __ __';
      }
    };
    var Formatter = {
      Phone: function Phone(value) {
        value = value || '';
        var hasPlus = value.indexOf('+') === 0;
        value = value.replace(/[^\d]/g, '');

        if (!hasPlus && value.substr(0, 1) === '8') {
          value = '7' + value.substr(1);
        }

        if (!phoneDb.list) {
          phoneDb.list = "247,ac,___-____|376,ad,___-___-___|971,ae,___-_-___-____|93,af,__-__-___-____|1268,ag,_ (___) ___-____|1264,ai,_ (___) ___-____|355,al,___ (___) ___-___|374,am,___-__-___-___|599,bq,___-___-____|244,ao,___ (___) ___-___|6721,aq,___-___-___|54,ar,__ (___) ___-____|1684,as,_ (___) ___-____|43,at,__ (___) ___-____|61,au,__-_-____-____|297,aw,___-___-____|994,az,___ (__) ___-__-__|387,ba,___-__-____|1246,bb,_ (___) ___-____|880,bd,___-__-___-___|32,be,__ (___) ___-___|226,bf,___-__-__-____|359,bg,___ (___) ___-___|973,bh,___-____-____|257,bi,___-__-__-____|229,bj,___-__-__-____|1441,bm,_ (___) ___-____|673,bn,___-___-____|591,bo,___-_-___-____|55,br,__-__-____-____|1242,bs,_ (___) ___-____|975,bt,___-_-___-___|267,bw,___-__-___-___|375,by,___ (__) ___-__-__|501,bz,___-___-____|243,cd,___ (___) ___-___|236,cf,___-__-__-____|242,cg,___-__-___-____|41,ch,__-__-___-____|225,ci,___-__-___-___|682,ck,___-__-___|56,cl,__-_-____-____|237,cm,___-____-____|86,cn,__ (___) ____-___|57,co,__ (___) ___-____|506,cr,___-____-____|53,cu,__-_-___-____|238,cv,___ (___) __-__|357,cy,___-__-___-___|420,cz,___ (___) ___-___|49,de,__-___-___|253,dj,___-__-__-__-__|45,dk,__-__-__-__-__|1767,dm,_ (___) ___-____|1809,do,_ (___) ___-____|,do,_ (___) ___-____|213,dz,___-__-___-____|593,ec,___-_-___-____|372,ee,___-___-____|20,eg,__ (___) ___-____|291,er,___-_-___-___|34,es,__ (___) ___-___|251,et,___-__-___-____|358,fi,___ (___) ___-__-__|679,fj,___-__-_____|500,fk,___-_____|691,fm,___-___-____|298,fo,___-___-___|262,fr,___-_____-____|33,fr,__ (___) ___-___|508,fr,___-__-____|590,fr,___ (___) ___-___|241,ga,___-_-__-__-__|1473,gd,_ (___) ___-____|995,ge,___ (___) ___-___|594,gf,___-_____-____|233,gh,___ (___) ___-___|350,gi,___-___-_____|299,gl,___-__-__-__|220,gm,___ (___) __-__|224,gn,___-__-___-___|240,gq,___-__-___-____|30,gr,__ (___) ___-____|502,gt,___-_-___-____|1671,gu,_ (___) ___-____|245,gw,___-_-______|592,gy,___-___-____|852,hk,___-____-____|504,hn,___-____-____|385,hr,___-__-___-___|509,ht,___-__-__-____|36,hu,__ (___) ___-___|62,id,__-__-___-__|353,ie,___ (___) ___-___|972,il,___-_-___-____|91,in,__ (____) ___-___|246,io,___-___-____|964,iq,___ (___) ___-____|98,ir,__ (___) ___-____|354,is,___-___-____|39,it,__ (___) ____-___|1876,jm,_ (___) ___-____|962,jo,___-_-____-____|81,jp,__ (___) ___-___|254,ke,___-___-______|996,kg,___ (___) ___-___|855,kh,___ (__) ___-___|686,ki,___-__-___|269,km,___-__-_____|1869,kn,_ (___) ___-____|850,kp,___-___-___|82,kr,__-__-___-____|965,kw,___-____-____|1345,ky,_ (___) ___-____|77,kz,_ (___) ___-__-__|856,la,___-__-___-___|961,lb,___-_-___-___|1758,lc,_ (___) ___-____|423,li,___ (___) ___-____|94,lk,__-__-___-____|231,lr,___-__-___-___|266,ls,___-_-___-____|370,lt,___ (___) __-___|352,lu,___ (___) ___-___|371,lv,___-__-___-___|218,ly,___-__-___-___|212,ma,___-__-____-___|377,mc,___-__-___-___|373,md,___-____-____|382,me,___-__-___-___|261,mg,___-__-__-_____|692,mh,___-___-____|389,mk,___-__-___-___|223,ml,___-__-__-____|95,mm,__-___-___|976,mn,___-__-__-____|853,mo,___-____-____|1670,mp,_ (___) ___-____|596,mq,___ (___) __-__-__|222,mr,___ (__) __-____|1664,ms,_ (___) ___-____|356,mt,___-____-____|230,mu,___-___-____|960,mv,___-___-____|265,mw,___-_-____-____|52,mx,__-__-__-____|60,my,__-_-___-___|258,mz,___-__-___-___|264,na,___-__-___-____|687,nc,___-__-____|227,ne,___-__-__-____|6723,nf,___-___-___|234,ng,___-__-___-__|505,ni,___-____-____|31,nl,__-__-___-____|47,no,__ (___) __-___|977,np,___-__-___-___|674,nr,___-___-____|683,nu,___-____|64,nz,__-__-___-___|968,om,___-__-___-___|507,pa,___-___-____|51,pe,__ (___) ___-___|689,pf,___-__-__-__|675,pg,___ (___) __-___|63,ph,__ (___) ___-____|92,pk,__ (___) ___-____|48,pl,__ (___) ___-___|970,ps,___-__-___-____|351,pt,___-__-___-____|680,pw,___-___-____|595,py,___ (___) ___-___|974,qa,___-____-____|40,ro,__-__-___-____|381,rs,___-__-___-____|7,ru,_ (___) ___-__-__|250,rw,___ (___) ___-___|966,sa,___-_-___-____|677,sb,___-_____|248,sc,___-_-___-___|249,sd,___-__-___-____|46,se,__-__-___-____|65,sg,__-____-____|386,si,___-__-___-___|421,sk,___ (___) ___-___|232,sl,___-__-______|378,sm,___-____-______|221,sn,___-__-___-____|252,so,___-_-___-___|597,sr,___-___-___|211,ss,___-__-___-____|239,st,___-__-_____|503,sv,___-__-__-____|1721,sx,_ (___) ___-____|963,sy,___-__-____-___|268,sz,___ (__) __-____|1649,tc,_ (___) ___-____|235,td,___-__-__-__-__|228,tg,___-__-___-___|66,th,__-__-___-___|992,tj,___-__-___-____|690,tk,___-____|670,tl,___-___-____|993,tm,___-_-___-____|216,tn,___-__-___-___|676,to,___-_____|90,tr,__ (___) ___-____|1868,tt,_ (___) ___-____|688,tv,___-_____|886,tw,___-____-____|255,tz,___-__-___-____|380,ua,___ (__) ___-__-__|256,ug,___ (___) ___-___|44,gb,__-__-____-____|598,uy,___-_-___-__-__|998,uz,___-__-___-____|396698,va,__-_-___-_____|1784,vc,_ (___) ___-____|58,ve,__ (___) ___-____|1284,vg,_ (___) ___-____|1340,vi,_ (___) ___-____|84,vn,__-__-____-___|678,vu,___-_____|681,wf,___-__-____|685,ws,___-__-____|967,ye,___-_-___-___|27,za,__-__-___-____|260,zm,___ (__) ___-____|263,zw,___-_-______|1,us,_ (___) ___-____|".split('|').map(function (item) {
            item = item.split(',');
            return {
              code: item[0],
              id: item[1],
              mask: item[2]
            };
          });
        }

        if (value.length > 0) {
          var mask = phoneDb.findMask(value);
          mask += ((mask.indexOf('-') >= 0 ? '-' : ' ') + '__').repeat(10);

          for (var i = 0; i < value.length; i++) {
            mask = mask.replace('_', value.substr(i, 1));
          }

          value = mask.replace(/[^\d]+$/, '').replace(/_/g, '0');
        }

        if (hasPlus || value.length > 0) {
          value = '+' + value;
        }

        return value;
      }
    };

    var Controller$2 =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));

        _this.validators.push(Validator.Email);

        _this.normalizers.push(Normalizer.Email);

        _this.filters.push(Filter.Email);

        return _this;
      }

      babelHelpers.createClass(Controller, [{
        key: "getInputType",
        value: function getInputType() {
          return 'email';
        }
      }], [{
        key: "type",
        value: function type() {
          return 'email';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$3 =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));

        _this.formatters.push(Formatter.Phone);

        _this.validators.push(Validator.Phone);

        _this.normalizers.push(Normalizer.Phone);

        _this.filters.push(Filter.Phone);

        return _this;
      }

      babelHelpers.createClass(Controller, [{
        key: "getInputType",
        value: function getInputType() {
          return 'tel';
        }
      }], [{
        key: "type",
        value: function type() {
          return 'phone';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$4 =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));

        _this.validators.push(Validator.Integer);

        _this.normalizers.push(Normalizer.Integer);

        _this.filters.push(Normalizer.Integer);

        return _this;
      }

      babelHelpers.createClass(Controller, [{
        key: "getInputType",
        value: function getInputType() {
          return 'number';
        }
      }], [{
        key: "type",
        value: function type() {
          return 'integer';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$5 =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));

        _this.validators.push(Validator.Double);

        _this.normalizers.push(Normalizer.Double);

        _this.filters.push(Normalizer.Double);

        return _this;
      }

      babelHelpers.createClass(Controller, [{
        key: "getInputType",
        value: function getInputType() {
          return 'number';
        }
      }], [{
        key: "type",
        value: function type() {
          return 'double';
        }
      }]);
      return Controller;
    }(Controller$1);

    var FieldText = {
      mixins: [MixinString],
      template: "\n\t\t<div class=\"b24-form-control-container b24-form-control-icon-after\">\n\t\t\t<textarea class=\"b24-form-control\"\n\t\t\t\t:class=\"inputClasses\"\n\t\t\t\tv-model=\"value\"\n\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t></textarea>\n\t\t\t<div class=\"b24-form-control-label\">\n\t\t\t\t{{ label }} \n\t\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\t\t\t\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-icon-after b24-form-icon-remove\"\n\t\t\t\t:title=\"field.messages.get('fieldRemove')\"\n\t\t\t\tv-if=\"itemIndex > 0\"\n\t\t\t\t@click=\"deleteItem\"\n\t\t\t></div>\n\t\t\t<field-item-alert\n\t\t\t\tv-bind:field=\"field\"\n\t\t\t\tv-bind:item=\"item\"\n\t\t\t></field-item-alert>\n\t\t</div>\n\t"
    };

    var Controller$6 =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);

      function Controller$$1() {
        babelHelpers.classCallCheck(this, Controller$$1);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).apply(this, arguments));
      }

      babelHelpers.createClass(Controller$$1, [{
        key: "isComponentDuplicable",
        get: function get() {
          return true;
        }
      }], [{
        key: "type",
        value: function type() {
          return 'text';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldText;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var FieldBool = {
      mixins: [MixinField],
      template: "\t\n\t\t<label class=\"b24-form-control-container\"\n\t\t\t@click.capture=\"$emit('input-click', $event)\"\n\t\t>\n\t\t\t<input type=\"checkbox\" \n\t\t\t\tv-model=\"field.item().selected\"\n\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t\tonclick=\"this.blur()\"\n\t\t\t>\n\t\t\t<span class=\"b24-form-control-desc\">{{ field.label }}</span>\n\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\n\t\t</label>\n\t"
    };

    var Controller$7 =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'bool';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldBool;
        }
      }]);

      function Controller$$1(options) {
        babelHelpers.classCallCheck(this, Controller$$1);
        options.multiple = false;
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
      }

      return Controller$$1;
    }(Controller);

    var FieldCheckbox = {
      mixins: [MixinField],
      template: "\n\t\t<div class=\"b24-form-control-container\">\n\t\t\t<span class=\"b24-form-control-label\">\n\t\t\t\t{{ field.label }} \n\t\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t</span>\n\n\t\t\t<label class=\"b24-form-control\"\n\t\t\t\tv-for=\"item in field.items\"\n\t\t\t\t:class=\"{'b24-form-control-checked': item.selected}\"\n\t\t\t>\n\t\t\t\t<input :type=\"field.type\" \n\t\t\t\t\t:value=\"item.value\"\n\t\t\t\t\tv-model=\"selected\"\n\t\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t\t\tonclick=\"this.blur()\"\n\t\t\t\t>\n\t\t\t\t<span class=\"b24-form-control-desc\">{{ item.label }}</span>\n\t\t\t</label>\n\t\t\t<field-item-image-slider v-bind:field=\"field\"></field-item-image-slider>\n\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\n\t\t</div>\n\t"
    };

    var Controller$8 =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'radio';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldCheckbox;
        }
      }]);

      function Controller$$1(options) {
        babelHelpers.classCallCheck(this, Controller$$1);
        options.multiple = false;
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
      }

      return Controller$$1;
    }(Controller);

    var Controller$9 =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'checkbox';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldCheckbox;
        }
      }]);

      function Controller$$1(options) {
        babelHelpers.classCallCheck(this, Controller$$1);
        options.multiple = true;
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
      }

      return Controller$$1;
    }(Controller);

    var FieldSelect = {
      mixins: [MixinField],
      template: "\n\t\t<div class=\"field-item\">\n\t\t\t<label>\n\t\t\t\t<div class=\"field-label\">\n\t\t\t\t\t{{ field.label }} \n\t\t\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t\t</div>\n\t\t\t\t<div>\n\t\t\t\t\t<select \n\t\t\t\t\t\tv-model=\"selected\"\n\t\t\t\t\t\tv-bind:multiple=\"field.multiple\"\n\t\t\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<option v-for=\"item in field.items\" \n\t\t\t\t\t\t\tv-bind:value=\"item.value\"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ item.label }}\n\t\t\t\t\t\t</option>\n\t\t\t\t\t</select>\n\t\t\t\t</div>\n\t\t\t</label>\n\t\t\t<field-item-image-slider v-bind:field=\"field\"></field-item-image-slider>\n\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\n\t\t</div>\n\t"
    };

    var Controller$a =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);

      function Controller$$1() {
        babelHelpers.classCallCheck(this, Controller$$1);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).apply(this, arguments));
      }

      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'select';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldSelect;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var Item$1 =
    /*#__PURE__*/
    function (_BaseItem) {
      babelHelpers.inherits(Item$$1, _BaseItem);

      function Item$$1(options) {
        babelHelpers.classCallCheck(this, Item$$1);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item$$1).call(this, options));
        /*
        let value;
        if (Util.Type.object(options.value))
        {
        	value = options.value;
        	value.quantity = value.quantity ? Util.Conv.number(value.quantity) : 0;
        }
        else
        {
        	value = {id: options.value};
        }
        this.value = {
        	id: value.id || '',
        	quantity: value.quantity || this.quantity.min || this.quantity.step,
        };
        */
      }

      babelHelpers.createClass(Item$$1, [{
        key: "getFileData",
        value: function getFileData() {}
      }, {
        key: "setFileData",
        value: function setFileData(data) {}
      }, {
        key: "clearFileData",
        value: function clearFileData() {
          this.value = null;
        }
      }]);
      return Item$$1;
    }(Item);

    var FieldFileItem = {
      props: ['field', 'itemIndex', 'item'],
      template: "\n\t\t<div>\n\t\t\t<div v-if=\"file.content\" class=\"b24-form-control-file-item\">\n\t\t\t\t<div class=\"b24-form-control-file-item-preview\">\n\t\t\t\t\t<img class=\"b24-form-control-file-item-preview-image\" \n\t\t\t\t\t\t:src=\"file.content\"\n\t\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"b24-form-control-file-item-name\">\n\t\t\t\t\t<span class=\"b24-form-control-file-item-name-text\">\n\t\t\t\t\t\t{{ file.name }}\n\t\t\t\t\t</span>\n\t\t\t\t\t<div style=\"display: none;\" class=\"b24-form-control-file-item-preview-image-popup\">\n\t\t\t\t\t\t<img>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div @click.prevent=\"removeFile\" class=\"b24-form-control-file-item-remove\"></div>\n\t\t\t</div>\n\t\t\t<div v-show=\"!file.content\" class=\"b24-form-control-file-item-empty\">\n\t\t\t\t<label class=\"b24-form-control\">\n\t\t\t\t\t{{ field.messages.get('fieldFileChoose') }}\n\t\t\t\t\t<input type=\"file\" style=\"display: none;\"\n\t\t\t\t\t\tref=\"inputFiles\"\n\t\t\t\t\t\t@change=\"setFiles\"\n\t\t\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t\t\t>\n\t\t\t\t</label>\n\t\t\t</div>\n\t\t</div>\n\t",
      computed: {
        value: {
          get: function get() {
            var value = this.item.value || {};

            if (value.content) {
              return JSON.stringify(this.item.value);
            }

            return '';
          },
          set: function set(newValue) {
            newValue = newValue || {};

            if (typeof newValue === 'string') {
              newValue = JSON.parse(newValue);
            }

            this.item.value = newValue;
            this.item.selected = !!newValue.content;
            this.field.addSingleEmptyItem();
          }
        },
        file: function file() {
          return this.item.value || {};
        }
      },
      methods: {
        setFiles: function setFiles() {
          var _this = this;

          var file = this.$refs.inputFiles.files[0];

          if (!file) {
            this.value = null;
          } else {
            var reader = new FileReader();

            reader.onloadend = function () {
              _this.value = {
                name: file.name,
                size: file.size,
                content: reader.result
              };
            };

            reader.readAsDataURL(file);
          }
        },
        removeFile: function removeFile() {
          this.value = null;
          this.field.removeItem(this.itemIndex);
          this.$refs.inputFiles.value = null;
        }
      }
    };
    var FieldFile = {
      mixins: [MixinField],
      components: {
        'field-file-item': FieldFileItem
      },
      template: "\n\t\t<div class=\"b24-form-control-container\">\n\t\t\t<div class=\"b24-form-control-label\">\n\t\t\t\t{{ field.label }}\n\t\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-control-filelist\">\n\t\t\t\t<field-file-item\n\t\t\t\t\tv-for=\"(item, itemIndex) in field.items\"\n\t\t\t\t\tv-bind:key=\"field.id\"\n\t\t\t\t\tv-bind:field=\"field\"\n\t\t\t\t\tv-bind:itemIndex=\"itemIndex\"\n\t\t\t\t\tv-bind:item=\"item\"\n\t\t\t\t></field-file-item>\n\t\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\n\t\t\t</div>\n\t\t</div>\n\t",
      created: function created() {
        if (this.field.multiple) {
          this.field.addSingleEmptyItem();
        }
      },
      computed: {},
      methods: {}
    };

    var Controller$b =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);

      function Controller$$1() {
        babelHelpers.classCallCheck(this, Controller$$1);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).apply(this, arguments));
      }

      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'file';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldFile;
        }
      }, {
        key: "createItem",
        value: function createItem(options) {
          return new Item$1(options);
        }
      }]);
      return Controller$$1;
    }(Controller);

    var ItemSelector = {
      props: ['field'],
      template: "\n\t\t<div>\n\t\t\t<div class=\"b24-form-control-list-selector-item\"\n\t\t\t\tv-for=\"(item, itemIndex) in field.unselectedItems()\"\n\t\t\t\t@click=\"selectItem(item)\"\n\t\t\t>\n\t\t\t\t<img class=\"b24-form-control-list-selector-item-image\"\n\t\t\t\t\tv-if=\"pic(item)\" \n\t\t\t\t\t:src=\"pic(item)\"\n\t\t\t\t>\n\t\t\t\t<div class=\"b24-form-control-list-selector-item-title\">\n\t\t\t\t\t<span >{{ item.label }}</span>\n\t\t\t\t</div>\n\t\n\t\t\t\t<div class=\"b24-form-control-list-selector-item-price\">\n\t\t\t\t\t<div class=\"b24-form-control-list-selector-item-price-old\"\n\t\t\t\t\t\tv-if=\"item.discount\"\n\t\t\t\t\t\tv-html=\"field.formatMoney(item.price + item.discount)\"\n\t\t\t\t\t></div>\n\t\t\t\t\t<div class=\"b24-form-control-list-selector-item-price-current\"\n\t\t\t\t\t\tv-if=\"item.price\"\n\t\t\t\t\t\tv-html=\"field.formatMoney(item.price)\"\n\t\t\t\t\t></div> \n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t",
      computed: {},
      methods: {
        pic: function pic(item) {
          return item && item.pics && item.pics.length > 0 ? item.pics[0] : '';
        },
        selectItem: function selectItem(item) {
          this.$emit('select', item);
        }
      }
    };
    var fieldListMixin = {
      props: ['field'],
      mixins: [MixinField, MixinDropDown],
      components: {
        'item-selector': ItemSelector
      },
      methods: {
        toggleSelector: function toggleSelector() {
          if (this.field.unselectedItem()) {
            this.toggleDropDown();
          }
        },
        select: function select(item) {
          var _this = this;

          this.closeDropDown();

          var select = function select() {
            if (_this.item) {
              _this.item.selected = false;
            }

            item.selected = true;
          };

          if (this.item && this.item.selected) {
            select();
          } else {
            setTimeout(select, 300);
          }
        },
        unselect: function unselect() {
          this.item.selected = false;
        }
      }
    };
    var FieldListItem = {
      mixins: [fieldListMixin],
      props: ['field', 'item', 'itemSubComponent'],
      template: "\n\t\t<div class=\"b24-form-control-container b24-form-control-icon-after\">\n\t\t\t<input readonly=\"\" type=\"text\" class=\"b24-form-control\"\n\t\t\t\t:value=\"itemLabel\"\n\t\t\t\t:class=\"classes\"\n\t\t\t\t@click.capture=\"toggleSelector\"\n\t\t\t>\n\t\t\t<div class=\"b24-form-control-label\">\n\t\t\t\t{{ field.label }}\n\t\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-icon-after b24-form-icon-remove\"\n\t\t\t\tv-if=\"item.selected\"\n\t\t\t\t@click.capture=\"unselect\"\n\t\t\t\t:title=\"field.messages.get('fieldListUnselect')\"\n\t\t\t></div>\n\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\n\t\t\t<field-item-dropdown \n\t\t\t\t:marginTop=\"0\" \n\t\t\t\t:visible=\"dropDownOpened\"\n\t\t\t\t:title=\"field.label\"\n\t\t\t\t@close=\"closeDropDown()\"\n\t\t\t>\n\t\t\t\t<item-selector\n\t\t\t\t\t:field=\"field\"\n\t\t\t\t\t@select=\"select\"\n\t\t\t\t></item-selector>\n\t\t\t</field-item-dropdown>\n\t\t\t<field-item-image-slider \n\t\t\t\tv-if=\"item.selected && field.bigPic\" \n\t\t\t\t:field=\"field\" \n\t\t\t\t:item=\"item\"\n\t\t\t></field-item-image-slider>\n\t\t\t<component v-if=\"item.selected && itemSubComponent\" :is=\"itemSubComponent\"\n\t\t\t\t:key=\"field.id\"\n\t\t\t\t:field=\"field\"\n\t\t\t\t:item=\"item\"\n\t\t\t></component>\n\t\t</div>\n\t",
      computed: {
        itemLabel: function itemLabel() {
          if (!this.item || !this.item.selected) {
            return '';
          }

          return this.item.label;
        },
        classes: function classes() {
          var list = [];

          if (this.itemLabel) {
            list.push('b24-form-control-not-empty');
          }

          return list;
        }
      },
      methods: {}
    };
    var FieldList = {
      mixins: [fieldListMixin],
      components: {
        'field-list-item': FieldListItem
      },
      template: "\n\t\t<div>\n\t\t\t<field-list-item\n\t\t\t\tv-for=\"(item, itemIndex) in getItems()\"\n\t\t\t\t:key=\"itemIndex\"\n\t\t\t\t:field=\"field\"\n\t\t\t\t:item=\"item\"\n\t\t\t\t:itemSubComponent=\"itemSubComponent\"\n\t\t\t></field-list-item>\n\t\t\t\t\t\t\n\t\t\t<a class=\"b24-form-control-add-btn\"\n\t\t\t\tv-if=\"isAddVisible()\"\n\t\t\t\t@click=\"toggleSelector\"\n\t\t\t>\n\t\t\t\t{{ field.messages.get('fieldAdd') }}\n\t\t\t</a>\n\t\t\t<field-item-dropdown \n\t\t\t\t:marginTop=\"0\" \n\t\t\t\t:visible=\"dropDownOpened\"\n\t\t\t\t:title=\"field.label\"\n\t\t\t\t@close=\"closeDropDown()\"\n\t\t\t>\n\t\t\t\t<item-selector\n\t\t\t\t\t:field=\"field\"\n\t\t\t\t\t@select=\"select\"\n\t\t\t\t></item-selector>\n\t\t\t</field-item-dropdown>\n\t\t</div>\n\t",
      computed: {
        itemSubComponent: function itemSubComponent() {
          return null;
        }
      },
      methods: {
        getItems: function getItems() {
          return this.field.selectedItem() ? this.field.selectedItems() : [this.field.item()];
        },
        isAddVisible: function isAddVisible() {
          return this.field.multiple && this.field.item() && this.field.selectedItem() && this.field.unselectedItem();
        }
      }
    };

    var DefaultOptions$2 = {
      type: 'string',
      label: 'Default field name',
      multiple: false,
      visible: true,
      required: false,
      bigPic: true
    };

    var Controller$c =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, [{
        key: "getOriginalType",
        value: function getOriginalType() {
          return 'list';
        }
      }], [{
        key: "type",
        value: function type() {
          return 'list';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldList;
        }
      }]);

      function Controller$$1() {
        var _this;

        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DefaultOptions$2;
        babelHelpers.classCallCheck(this, Controller$$1);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "bigPic", false);
        _this.bigPic = !!options.bigPic;
        return _this;
      }
      /*
      adjust(options: Options = DefaultOptions)
      {
      	super.adjust(options);
      }
      */


      return Controller$$1;
    }(Controller);

    var Item$2 =
    /*#__PURE__*/
    function (_BaseItem) {
      babelHelpers.inherits(Item$$1, _BaseItem);

      function Item$$1(options) {
        var _this;

        babelHelpers.classCallCheck(this, Item$$1);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Item$$1).call(this, options));
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "pics", []);
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "price", 0);
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "discount", 0);

        if (Array.isArray(options.pics)) {
          _this.pics = options.pics;
        }

        _this.price = Conv.number(options.price);
        _this.discount = Conv.number(options.discount);
        var quantity = Type.object(options.quantity) ? options.quantity : {};
        _this.quantity = {
          min: quantity.min ? Conv.number(quantity.min) : 0,
          max: quantity.max ? Conv.number(quantity.max) : 0,
          step: quantity.step ? Conv.number(quantity.step) : 1,
          unit: quantity.unit || ''
        };
        var value;

        if (Type.object(options.value)) {
          value = options.value;
          value.quantity = value.quantity ? Conv.number(value.quantity) : 0;
        } else {
          value = {
            id: options.value
          };
        }

        _this.value = {
          id: value.id || '',
          quantity: value.quantity || _this.quantity.min || _this.quantity.step
        };
        return _this;
      }

      babelHelpers.createClass(Item$$1, [{
        key: "getNextIncQuantity",
        value: function getNextIncQuantity() {
          var q = this.value.quantity + this.quantity.step;
          var max = this.quantity.max;
          return max <= 0 || max >= q ? q : 0;
        }
      }, {
        key: "getNextDecQuantity",
        value: function getNextDecQuantity() {
          var q = this.value.quantity - this.quantity.step;
          var min = this.quantity.min;
          return q > 0 && (min <= 0 || min <= q) ? q : 0;
        }
      }, {
        key: "incQuantity",
        value: function incQuantity() {
          this.value.quantity = this.getNextIncQuantity();
        }
      }, {
        key: "decQuantity",
        value: function decQuantity() {
          this.value.quantity = this.getNextDecQuantity();
        }
      }, {
        key: "getSummary",
        value: function getSummary() {
          return (this.price + this.discount) * this.value.quantity;
        }
      }, {
        key: "getTotal",
        value: function getTotal() {
          return this.price * this.value.quantity;
        }
      }, {
        key: "getDiscounts",
        value: function getDiscounts() {
          return this.discount * this.value.quantity;
        }
      }]);
      return Item$$1;
    }(Item);

    var FieldProductSubItem = {
      props: ['field', 'item'],
      template: "\n\t\t<div class=\"b24-form-control-product-info\">\n\t\t\t<input type=\"hidden\" \n\t\t\t\tv-model=\"item.value.quantity\"\n\t\t\t>\n\t\t\t<div class=\"b24-form-control-product-icon\">\n\t\t\t\t<svg v-if=\"!pic\" width=\"28px\" height=\"24px\" viewBox=\"0 0 28 24\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n\t\t\t\t\t<g transform=\"translate(-14, -17)\" fill=\"#333\" stroke=\"none\" stroke-width=\"1\" fill-rule=\"evenodd\" opacity=\"0.2\">\n\t\t\t\t\t\t<path d=\"M29,38.5006415 C29,39.8807379 27.8807708,41 26.4993585,41 C25.1192621,41 24,39.8807708 24,38.5006415 C24,37.1192621 25.1192292,36 26.4993585,36 C27.8807379,36 29,37.1192292 29,38.5006415 Z M39,38.5006415 C39,39.8807379 37.8807708,41 36.4993585,41 C35.1192621,41 34,39.8807708 34,38.5006415 C34,37.1192621 35.1192292,36 36.4993585,36 C37.8807379,36 39,37.1192292 39,38.5006415 Z M20.9307332,21.110867 L40.9173504,21.0753348 C41.2504348,21.0766934 41.5636721,21.2250055 41.767768,21.4753856 C41.97328,21.7271418 42.046982,22.0537176 41.9704452,22.3639694 L39.9379768,33.1985049 C39.8217601,33.6666139 39.3866458,33.9972787 38.8863297,34 L22.7805131,34 C22.280197,33.9972828 21.8450864,33.6666243 21.728866,33.1985049 L18.2096362,19.0901297 L15,19.0901297 C14.4477153,19.0901297 14,18.6424144 14,18.0901297 L14,18 C14,17.4477153 14.4477153,17 15,17 L19.0797196,17 C19.5814508,17.0027172 20.0151428,17.3333757 20.1327818,17.8014951 L20.9307332,21.110867 Z\" id=\"Icon\"></path>\n\t\t\t\t\t</g>\n\t\t\t\t</svg>\n\t\t\t\t<img v-if=\"pic\" :src=\"pic\" style=\"height: 24px;\">\n\t\t\t</div>\n\t\t\t\n\t\t\t<div class=\"b24-form-control-product-quantity\"\n\t\t\t\tv-if=\"item.selected\"\n\t\t\t>\n\t\t\t\t<div class=\"b24-form-control-product-quantity-remove\"\n\t\t\t\t\t@click=\"item.decQuantity()\"\n\t\t\t\t\t:style=\"{visibility: item.getNextDecQuantity() ? 'visible' : 'hidden'}\"\n\t\t\t\t></div>\n\t\t\t\t<div class=\"b24-form-control-product-quantity-counter\">\n\t\t\t\t\t{{ item.value.quantity }}\n\t\t\t\t\t{{ item.quantity.unit }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"b24-form-control-product-quantity-add\"\n\t\t\t\t\t@click=\"item.incQuantity()\"\n\t\t\t\t\t:style=\"{visibility: item.getNextIncQuantity() ? 'visible' : 'hidden'}\"\n\t\t\t\t></div>\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-control-product-price\"\n\t\t\t\tv-if=\"item.price\"\n\t\t\t>\n\t\t\t\t<div>\n\t\t\t\t\t<div class=\"b24-form-control-product-price-old\"\n\t\t\t\t\t\tv-if=\"item.discount\"\n\t\t\t\t\t\tv-html=\"field.formatMoney(item.getSummary())\"\n\t\t\t\t\t></div>\n\t\t\t\t\t<div class=\"b24-form-control-product-price-current\"\n\t\t\t\t\t\tv-html=\"field.formatMoney(item.getTotal())\"\n\t\t\t\t\t></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t",
      computed: {
        pic: function pic() {
          return !this.field.bigPic && this.item && this.item.pics && this.item.pics.length > 0 ? this.item.pics[0] : '';
        }
      }
    };
    var FieldProductItem = {
      mixins: [FieldListItem],
      components: {
        'field-list-sub-item': FieldProductSubItem
      }
    };
    var FieldProduct = {
      mixins: [FieldList],
      components: {
        'field-list-item': FieldProductItem
      },
      computed: {
        itemSubComponent: function itemSubComponent() {
          return 'field-list-sub-item';
        }
      }
    };

    var Controller$d =
    /*#__PURE__*/
    function (_ListField$Controller) {
      babelHelpers.inherits(Controller, _ListField$Controller);
      babelHelpers.createClass(Controller, null, [{
        key: "type",
        value: function type() {
          return 'product';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldProduct;
        }
      }, {
        key: "createItem",
        value: function createItem(options) {
          return new Item$2(options);
        }
      }]);

      function Controller(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));
        _this.currency = options.currency;
        return _this;
      }

      babelHelpers.createClass(Controller, [{
        key: "getOriginalType",
        value: function getOriginalType() {
          return 'list';
        }
      }, {
        key: "formatMoney",
        value: function formatMoney(val) {
          return Conv.formatMoney(val, this.currency.format);
        }
      }]);
      return Controller;
    }(Controller$c);

    var Format = {
      re: /[,.\- :\/\\]/,
      year: 'YYYY',
      month: 'MM',
      day: 'DD',
      hours: 'HH',
      hours12: 'H',
      hoursZeroFree: 'GG',
      hoursZeroFree12: 'G',
      minutes: 'MI',
      seconds: 'SS',
      ampm: 'TT',
      ampmLower: 'T',
      format: function format(date, dateFormat) {
        var hours12 = date.getHours();

        if (hours12 === 0) {
          hours12 = 12;
        } else if (hours12 > 12) {
          hours12 -= 12;
        }

        var ampm = date.getHours() > 11 ? 'PM' : 'AM';
        return dateFormat.replace(this.year, function () {
          return date.getFullYear();
        }).replace(this.month, function (match) {
          return paddNum(date.getMonth() + 1, match.length);
        }).replace(this.day, function (match) {
          return paddNum(date.getDate(), match.length);
        }).replace(this.hours, function () {
          return paddNum(date.getHours(), 2);
        }).replace(this.hoursZeroFree, function () {
          return date.getHours();
        }).replace(this.hours12, function () {
          return paddNum(hours12, 2);
        }).replace(this.hoursZeroFree12, function () {
          return hours12;
        }).replace(this.minutes, function (match) {
          return paddNum(date.getMinutes(), match.length);
        }).replace(this.seconds, function (match) {
          return paddNum(date.getSeconds(), match.length);
        }).replace(this.ampm, function () {
          return ampm;
        }).replace(this.ampmLower, function () {
          return ampm.toLowerCase();
        });
      },
      parse: function parse(dateString, dateFormat) {
        var r = {
          day: 1,
          month: 1,
          year: 1970,
          hours: 0,
          minutes: 0,
          seconds: 0
        };
        var dateParts = dateString.split(this.re);
        var formatParts = dateFormat.split(this.re);
        var partsSize = formatParts.length;
        var isPm = false;

        for (var i = 0; i < partsSize; i++) {
          var part = dateParts[i];

          switch (formatParts[i]) {
            case this.ampm:
            case this.ampmLower:
              isPm = part.toUpperCase() === 'PM';
              break;
          }
        }

        for (var _i = 0; _i < partsSize; _i++) {
          var _part = dateParts[_i];
          var partInt = parseInt(_part);

          switch (formatParts[_i]) {
            case this.year:
              r.year = partInt;
              break;

            case this.month:
              r.month = partInt;
              break;

            case this.day:
              r.day = partInt;
              break;

            case this.hours:
            case this.hoursZeroFree:
              r.hours = partInt;
              break;

            case this.hours12:
            case this.hoursZeroFree12:
              r.hours = isPm ? (partInt > 11 ? 11 : partInt) + 12 : partInt > 11 ? 0 : partInt;
              break;

            case this.minutes:
              r.minutes = partInt;
              break;

            case this.seconds:
              r.seconds = partInt;
              break;
          }
        }

        return r;
      },
      isAmPm: function isAmPm(dateFormat) {
        return dateFormat.indexOf(this.ampm) >= 0 || dateFormat.indexOf(this.ampmLower) >= 0;
      },
      convertHoursToAmPm: function convertHoursToAmPm(hours, isPm) {
        return isPm ? (hours > 11 ? 11 : hours) + 12 : hours > 11 ? 0 : hours;
      }
    };
    var VueDatePick = {
      props: {
        show: {
          type: Boolean,
          default: true
        },
        value: {
          type: String,
          default: ''
        },
        format: {
          type: String,
          default: 'MM/DD/YYYY'
        },
        displayFormat: {
          type: String
        },
        editable: {
          type: Boolean,
          default: true
        },
        hasInputElement: {
          type: Boolean,
          default: true
        },
        inputAttributes: {
          type: Object
        },
        selectableYearRange: {
          type: Number,
          default: 40
        },
        parseDate: {
          type: Function
        },
        formatDate: {
          type: Function
        },
        pickTime: {
          type: Boolean,
          default: false
        },
        pickMinutes: {
          type: Boolean,
          default: true
        },
        pickSeconds: {
          type: Boolean,
          default: false
        },
        isDateDisabled: {
          type: Function,
          default: function _default() {
            return false;
          }
        },
        nextMonthCaption: {
          type: String,
          default: 'Next month'
        },
        prevMonthCaption: {
          type: String,
          default: 'Previous month'
        },
        setTimeCaption: {
          type: String,
          default: 'Set time:'
        },
        closeButtonCaption: {
          type: String,
          default: 'Close'
        },
        mobileBreakpointWidth: {
          type: Number,
          default: 530
        },
        weekdays: {
          type: Array,
          default: function _default() {
            return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
          }
        },
        months: {
          type: Array,
          default: function _default() {
            return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
          }
        },
        startWeekOnSunday: {
          type: Boolean,
          default: false
        }
      },
      data: function data() {
        return {
          inputValue: this.valueToInputFormat(this.value),
          currentPeriod: this.getPeriodFromValue(this.value, this.format),
          direction: undefined,
          positionClass: undefined,
          opened: !this.hasInputElement && this.show
        };
      },
      computed: {
        valueDate: function valueDate() {
          var value = this.value;
          var format = this.format;
          return value ? this.parseDateString(value, format) : undefined;
        },
        isReadOnly: function isReadOnly() {
          return !this.editable || this.inputAttributes && this.inputAttributes.readonly;
        },
        isValidValue: function isValidValue() {
          var valueDate = this.valueDate;
          return this.value ? Boolean(valueDate) : true;
        },
        currentPeriodDates: function currentPeriodDates() {
          var _this = this;

          var _this$currentPeriod = this.currentPeriod,
              year = _this$currentPeriod.year,
              month = _this$currentPeriod.month;
          var days = [];
          var date = new Date(year, month, 1);
          var today = new Date();
          var offset = this.startWeekOnSunday ? 1 : 0; // append prev month dates

          var startDay = date.getDay() || 7;

          if (startDay > 1 - offset) {
            for (var i = startDay - (2 - offset); i >= 0; i--) {
              var prevDate = new Date(date);
              prevDate.setDate(-i);
              days.push({
                outOfRange: true,
                date: prevDate
              });
            }
          }

          while (date.getMonth() === month) {
            days.push({
              date: new Date(date)
            });
            date.setDate(date.getDate() + 1);
          } // append next month dates


          var daysLeft = 7 - days.length % 7;

          for (var _i2 = 1; _i2 <= daysLeft; _i2++) {
            var nextDate = new Date(date);
            nextDate.setDate(_i2);
            days.push({
              outOfRange: true,
              date: nextDate
            });
          } // define day states


          days.forEach(function (day) {
            day.disabled = _this.isDateDisabled(day.date);
            day.today = areSameDates(day.date, today);
            day.dateKey = [day.date.getFullYear(), day.date.getMonth() + 1, day.date.getDate()].join('-');
            day.selected = _this.valueDate ? areSameDates(day.date, _this.valueDate) : false;
          });
          return chunkArray(days, 7);
        },
        yearRange: function yearRange() {
          var years = [];
          var currentYear = this.currentPeriod.year;
          var startYear = currentYear - this.selectableYearRange;
          var endYear = currentYear + this.selectableYearRange;

          for (var i = startYear; i <= endYear; i++) {
            years.push(i);
          }

          return years;
        },
        hasCurrentTime: function hasCurrentTime() {
          return !!this.valueDate;
        },
        currentTime: function currentTime() {
          var currentDate = this.valueDate;
          var hours = currentDate ? currentDate.getHours() : 12;
          var minutes = currentDate ? currentDate.getMinutes() : 0;
          var seconds = currentDate ? currentDate.getSeconds() : 0;
          return {
            hours: hours,
            minutes: minutes,
            seconds: seconds,
            hoursPadded: paddNum(hours, 1),
            minutesPadded: paddNum(minutes, 2),
            secondsPadded: paddNum(seconds, 2)
          };
        },
        directionClass: function directionClass() {
          return this.direction ? "vdp".concat(this.direction, "Direction") : undefined;
        },
        weekdaysSorted: function weekdaysSorted() {
          if (this.startWeekOnSunday) {
            var weekdays = this.weekdays.slice();
            weekdays.unshift(weekdays.pop());
            return weekdays;
          } else {
            return this.weekdays;
          }
        }
      },
      watch: {
        show: function show(value) {
          this.opened = value;
        },
        value: function value(_value) {
          if (this.isValidValue) {
            this.inputValue = this.valueToInputFormat(_value);
            this.currentPeriod = this.getPeriodFromValue(_value, this.format);
          }
        },
        currentPeriod: function currentPeriod(_currentPeriod, oldPeriod) {
          var currentDate = new Date(_currentPeriod.year, _currentPeriod.month).getTime();
          var oldDate = new Date(oldPeriod.year, oldPeriod.month).getTime();
          this.direction = currentDate !== oldDate ? currentDate > oldDate ? 'Next' : 'Prev' : undefined;
        }
      },
      beforeDestroy: function beforeDestroy() {
        this.removeCloseEvents();
        this.teardownPosition();
      },
      methods: {
        valueToInputFormat: function valueToInputFormat(value) {
          return !this.displayFormat ? value : this.formatDateToString(this.parseDateString(value, this.format), this.displayFormat) || value;
        },
        getPeriodFromValue: function getPeriodFromValue(dateString, format) {
          var date = this.parseDateString(dateString, format) || new Date();
          return {
            month: date.getMonth(),
            year: date.getFullYear()
          };
        },
        parseDateString: function parseDateString(dateString, dateFormat) {
          return !dateString ? undefined : this.parseDate ? this.parseDate(dateString, dateFormat) : this.parseSimpleDateString(dateString, dateFormat);
        },
        formatDateToString: function formatDateToString(date, dateFormat) {
          return !date ? '' : this.formatDate ? this.formatDate(date, dateFormat) : this.formatSimpleDateToString(date, dateFormat);
        },
        parseSimpleDateString: function parseSimpleDateString(dateString, dateFormat) {
          var r = Format.parse(dateString, dateFormat);
          var day = r.day,
              month = r.month,
              year = r.year,
              hours = r.hours,
              minutes = r.minutes,
              seconds = r.seconds;
          var resolvedDate = new Date([paddNum(year, 4), paddNum(month, 2), paddNum(day, 2)].join('-'));

          if (isNaN(resolvedDate)) {
            return undefined;
          } else {
            var date = new Date(year, month - 1, day);
            [[year, 'setFullYear'], [hours, 'setHours'], [minutes, 'setMinutes'], [seconds, 'setSeconds']].forEach(function (_ref) {
              var _ref2 = babelHelpers.slicedToArray(_ref, 2),
                  value = _ref2[0],
                  method = _ref2[1];

              typeof value !== 'undefined' && date[method](value);
            });
            return date;
          }
        },
        formatSimpleDateToString: function formatSimpleDateToString(date, dateFormat) {
          return Format.format(date, dateFormat);
        },
        getHourList: function getHourList() {
          var list = [];
          var isAmPm = Format.isAmPm(this.displayFormat || this.format);

          for (var hours = 0; hours < 24; hours++) {
            var hoursDisplay = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours;
            hoursDisplay += hours > 11 ? ' pm' : ' am';
            list.push({
              value: hours,
              name: isAmPm ? hoursDisplay : hours
            });
          }

          return list;
        },
        incrementMonth: function incrementMonth() {
          var increment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;
          var refDate = new Date(this.currentPeriod.year, this.currentPeriod.month);
          var incrementDate = new Date(refDate.getFullYear(), refDate.getMonth() + increment);
          this.currentPeriod = {
            month: incrementDate.getMonth(),
            year: incrementDate.getFullYear()
          };
        },
        processUserInput: function processUserInput(userText) {
          var userDate = this.parseDateString(userText, this.displayFormat || this.format);
          this.inputValue = userText;
          this.$emit('input', userDate ? this.formatDateToString(userDate, this.format) : userText);
        },
        open: function open() {
          if (!this.opened) {
            this.opened = true;
            this.currentPeriod = this.getPeriodFromValue(this.value, this.format);
            this.addCloseEvents();
            this.setupPosition();
          }

          this.direction = undefined;
        },
        close: function close() {
          if (this.opened) {
            this.opened = false;
            this.direction = undefined;
            this.removeCloseEvents();
            this.teardownPosition();
          }

          this.$emit('close');
        },
        closeViaOverlay: function closeViaOverlay(e) {
          if (this.hasInputElement && e.target === this.$refs.outerWrap) {
            this.close();
          }
        },
        addCloseEvents: function addCloseEvents() {
          var _this2 = this;

          if (!this.closeEventListener) {
            this.closeEventListener = function (e) {
              return _this2.inspectCloseEvent(e);
            };

            ['click', 'keyup', 'focusin'].forEach(function (eventName) {
              return document.addEventListener(eventName, _this2.closeEventListener);
            });
          }
        },
        inspectCloseEvent: function inspectCloseEvent(event) {
          if (event.keyCode) {
            event.keyCode === 27 && this.close();
          } else if (!(event.target === this.$el) && !this.$el.contains(event.target)) {
            this.close();
          }
        },
        removeCloseEvents: function removeCloseEvents() {
          var _this3 = this;

          if (this.closeEventListener) {
            ['click', 'keyup'].forEach(function (eventName) {
              return document.removeEventListener(eventName, _this3.closeEventListener);
            });
            delete this.closeEventListener;
          }
        },
        setupPosition: function setupPosition() {
          var _this4 = this;

          if (!this.positionEventListener) {
            this.positionEventListener = function () {
              return _this4.positionFloater();
            };

            window.addEventListener('resize', this.positionEventListener);
          }

          this.positionFloater();
        },
        positionFloater: function positionFloater() {
          var _this5 = this;

          var inputRect = this.$el.getBoundingClientRect();
          var verticalClass = 'vdpPositionTop';
          var horizontalClass = 'vdpPositionLeft';

          var calculate = function calculate() {
            var rect = _this5.$refs.outerWrap.getBoundingClientRect();

            var floaterHeight = rect.height;
            var floaterWidth = rect.width;

            if (window.innerWidth > _this5.mobileBreakpointWidth) {
              // vertical
              if (inputRect.top + inputRect.height + floaterHeight > window.innerHeight && inputRect.top - floaterHeight > 0) {
                verticalClass = 'vdpPositionBottom';
              } // horizontal


              if (inputRect.left + floaterWidth > window.innerWidth) {
                horizontalClass = 'vdpPositionRight';
              }

              _this5.positionClass = ['vdpPositionReady', verticalClass, horizontalClass].join(' ');
            } else {
              _this5.positionClass = 'vdpPositionFixed';
            }
          };

          this.$refs.outerWrap ? calculate() : this.$nextTick(calculate);
        },
        teardownPosition: function teardownPosition() {
          if (this.positionEventListener) {
            this.positionClass = undefined;
            window.removeEventListener('resize', this.positionEventListener);
            delete this.positionEventListener;
          }
        },
        clear: function clear() {
          this.$emit('input', '');
        },
        selectDateItem: function selectDateItem(item) {
          if (!item.disabled) {
            var newDate = new Date(item.date);

            if (this.hasCurrentTime) {
              newDate.setHours(this.currentTime.hours);
              newDate.setMinutes(this.currentTime.minutes);
              newDate.setSeconds(this.currentTime.seconds);
            }

            this.$emit('input', this.formatDateToString(newDate, this.format));

            if (this.hasInputElement && !this.pickTime) {
              this.close();
            }
          }
        },
        inputTime: function inputTime(method, event) {
          var currentDate = this.valueDate || new Date();
          var maxValues = {
            setHours: 23,
            setMinutes: 59,
            setSeconds: 59
          };
          var numValue = parseInt(event.target.value, 10) || 0;

          if (numValue > maxValues[method]) {
            numValue = maxValues[method];
          } else if (numValue < 0) {
            numValue = 0;
          }

          event.target.value = paddNum(numValue, method === 'setHours' ? 1 : 2);
          currentDate[method](numValue);
          this.$emit('input', this.formatDateToString(currentDate, this.format), true);
        }
      },
      template: "\n    <div class=\"vdpComponent\" v-bind:class=\"{vdpWithInput: hasInputElement}\">\n        <input\n            v-if=\"hasInputElement\"\n            type=\"text\"\n            v-bind=\"inputAttributes\"\n            v-bind:readonly=\"isReadOnly\"\n            v-bind:value=\"inputValue\"\n            v-on:input=\"editable && processUserInput($event.target.value)\"\n            v-on:focus=\"editable && open()\"\n            v-on:click=\"editable && open()\"\n        >\n        <button\n            v-if=\"editable && hasInputElement && inputValue\"\n            class=\"vdpClearInput\"\n            type=\"button\"\n            v-on:click=\"clear\"\n        ></button>\n            <div\n                v-if=\"opened\"\n                class=\"vdpOuterWrap\"\n                ref=\"outerWrap\"\n                v-on:click=\"closeViaOverlay\"\n                v-bind:class=\"[positionClass, {vdpFloating: hasInputElement}]\"\n            >\n                <div class=\"vdpInnerWrap\">\n                    <header class=\"vdpHeader\">\n                        <button\n                            class=\"vdpArrow vdpArrowPrev\"\n                            v-bind:title=\"prevMonthCaption\"\n                            type=\"button\"\n                            v-on:click=\"incrementMonth(-1)\"\n                        >{{ prevMonthCaption }}</button>\n                        <button\n                            class=\"vdpArrow vdpArrowNext\"\n                            type=\"button\"\n                            v-bind:title=\"nextMonthCaption\"\n                            v-on:click=\"incrementMonth(1)\"\n                        >{{ nextMonthCaption }}</button>\n                        <div class=\"vdpPeriodControls\">\n                            <div class=\"vdpPeriodControl\">\n                                <button v-bind:class=\"directionClass\" v-bind:key=\"currentPeriod.month\" type=\"button\">\n                                    {{ months[currentPeriod.month] }}\n                                </button>\n                                <select v-model=\"currentPeriod.month\">\n                                    <option v-for=\"(month, index) in months\" v-bind:value=\"index\" v-bind:key=\"month\">\n                                        {{ month }}\n                                    </option>\n                                </select>\n                            </div>\n                            <div class=\"vdpPeriodControl\">\n                                <button v-bind:class=\"directionClass\" v-bind:key=\"currentPeriod.year\" type=\"button\">\n                                    {{ currentPeriod.year }}\n                                </button>\n                                <select v-model=\"currentPeriod.year\">\n                                    <option v-for=\"year in yearRange\" v-bind:value=\"year\" v-bind:key=\"year\">\n                                        {{ year }}\n                                    </option>\n                                </select>\n                            </div>\n                        </div>\n                    </header>\n                    <table class=\"vdpTable\">\n                        <thead>\n                            <tr>\n                                <th class=\"vdpHeadCell\" v-for=\"weekday in weekdaysSorted\" v-bind:key=\"weekday\">\n                                    <span class=\"vdpHeadCellContent\">{{weekday}}</span>\n                                </th>\n                            </tr>\n                        </thead>\n                        <tbody\n                            v-bind:key=\"currentPeriod.year + '-' + currentPeriod.month\"\n                            v-bind:class=\"directionClass\"\n                        >\n                            <tr class=\"vdpRow\" v-for=\"(week, weekIndex) in currentPeriodDates\" v-bind:key=\"weekIndex\">\n                                <td\n                                    class=\"vdpCell\"\n                                    v-for=\"item in week\"\n                                    v-bind:class=\"{\n                                        selectable: !item.disabled,\n                                        selected: item.selected,\n                                        disabled: item.disabled,\n                                        today: item.today,\n                                        outOfRange: item.outOfRange\n                                    }\"\n                                    v-bind:data-id=\"item.dateKey\"\n                                    v-bind:key=\"item.dateKey\"\n                                    v-on:click=\"selectDateItem(item)\"\n                                >\n                                    <div\n                                        class=\"vdpCellContent\"\n                                    >{{ item.date.getDate() }}</div>\n                                </td>\n                            </tr>\n                        </tbody>\n                    </table>\n                    <div v-if=\"pickTime\" class=\"vdpTimeControls\">\n                        <span class=\"vdpTimeCaption\">{{ setTimeCaption }}</span>\n                        <div class=\"vdpTimeUnit\">\n                            <select class=\"vdpHoursInput\"\n                                v-if=\"pickMinutes\"\n                                v-on:input=\"inputTime('setHours', $event)\"\n                                v-on:change=\"inputTime('setHours', $event)\"\n                                v-bind:value=\"currentTime.hours\"\n                            >\n                                <option\n                                    v-for=\"item in getHourList()\"\n                                    :value=\"item.value\"\n                                >{{ item.name }}</option>\n                            </select>\n                        </div>\n                        <span v-if=\"pickMinutes\" class=\"vdpTimeSeparator\">:</span>\n                        <div v-if=\"pickMinutes\" class=\"vdpTimeUnit\">\n                            <pre><span>{{ currentTime.minutesPadded }}</span><br></pre>\n                            <input\n                                v-if=\"pickMinutes\"\n                                type=\"number\" pattern=\"\\d*\" class=\"vdpMinutesInput\"\n                                v-on:input=\"inputTime('setMinutes', $event)\"\n                                v-bind:value=\"currentTime.minutesPadded\"\n                            >\n                        </div>\n                        <span v-if=\"pickSeconds\" class=\"vdpTimeSeparator\">:</span>\n                        <div v-if=\"pickSeconds\" class=\"vdpTimeUnit\">\n                            <pre><span>{{ currentTime.secondsPadded }}</span><br></pre>\n                            <input\n                                v-if=\"pickSeconds\"\n                                type=\"number\" pattern=\"\\d*\" class=\"vdpSecondsInput\"\n                                v-on:input=\"inputTime('setSeconds', $event)\"\n                                v-bind:value=\"currentTime.secondsPadded\"\n                            >\n                        </div>\n                        <span class=\"vdpTimeCaption\">\n                            <button type=\"button\" @click=\"$emit('close');\">{{ closeButtonCaption }}</button>\n                        </span>\n                    </div>\n                </div>\n            </div>\n    </div>\n    "
    };

    function paddNum(num, padsize) {
      return typeof num !== 'undefined' ? num.toString().length > padsize ? num : new Array(padsize - num.toString().length + 1).join('0') + num : undefined;
    }

    function chunkArray(inputArray, chunkSize) {
      var results = [];

      while (inputArray.length) {
        results.push(inputArray.splice(0, chunkSize));
      }

      return results;
    }

    function areSameDates(date1, date2) {
      return date1.getDate() === date2.getDate() && date1.getMonth() === date2.getMonth() && date1.getFullYear() === date2.getFullYear();
    }

    var FieldDateTime = {
      mixins: [MixinString, MixinDropDown],
      components: {
        'date-pick': VueDatePick,
        'field-string': FieldString
      },
      data: function data() {
        return {
          format: null
        };
      },
      template: "\n\t\t<div>\n\t\t\t<field-string\n\t\t\t\t:field=\"field\"\n\t\t\t\t:item=\"item\"\n\t\t\t\t:itemIndex=\"itemIndex\"\n\t\t\t\t:readonly=\"true\"\n\t\t\t\t:buttonClear=\"field.messages.get('fieldListUnselect')\"\n\t\t\t\t@input-click=\"toggleDropDown()\"\n\t\t\t></field-string>\n\t\t\t<field-item-dropdown \n\t\t\t\t:marginTop=\"'-14px'\" \n\t\t\t\t:maxHeight=\"'none'\" \n\t\t\t\t:width=\"'auto'\" \n\t\t\t\t:visible=\"dropDownOpened\"\n\t\t\t\t:title=\"field.label\"\n\t\t\t\t@close=\"closeDropDown()\"\n\t\t\t>\n\t\t\t\t<date-pick \n\t\t\t\t\t:value=\"item.value\"\n\t\t\t\t\t:show=\"true\"\n\t\t\t\t\t:hasInputElement=\"false\"\n\t\t\t\t\t:pickTime=\"field.hasTime\"\n\t\t\t\t\t:startWeekOnSunday=\"field.sundayFirstly\"\n\t\t\t\t\t:format=\"field.format\"\n\t\t\t\t\t:weekdays=\"getWeekdays()\"\n\t\t\t\t\t:months=\"getMonths()\"\n\t\t\t\t\t:setTimeCaption=\"field.messages.get('fieldDateTime') + ':'\"\n\t\t\t\t\t:closeButtonCaption=\"field.messages.get('fieldDateClose')\"\n\t\t\t\t\t:selectableYearRange=\"120\"\n\t\t\t\t\t@input=\"setDate\"\n\t\t\t\t\t@close=\"closeDropDown()\"\n\t\t\t\t></date-pick>\n\t\t\t</field-item-dropdown>\n\t\t</div>\n\t",
      methods: {
        setDate: function setDate(value, stopClose) {
          this.value = value;

          if (!stopClose) {
            this.closeDropDown();
          }
        },
        getWeekdays: function getWeekdays() {
          var list = [];

          for (var n = 1; n <= 7; n++) {
            list.push(this.field.messages.get('fieldDateDay' + n));
          }

          return list;
        },
        getMonths: function getMonths() {
          var list = [];

          for (var n = 1; n <= 12; n++) {
            list.push(this.field.messages.get('fieldDateMonth' + n));
          }

          return list;
        }
      }
    };

    var Controller$e =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'datetime';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldDateTime;
        }
      }]);

      function Controller$$1(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller$$1);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
        _this.format = options.format;
        _this.sundayFirstly = !!options.sundayFirstly;
        return _this;
      }

      babelHelpers.createClass(Controller$$1, [{
        key: "getOriginalType",
        value: function getOriginalType() {
          return 'string';
        }
      }, {
        key: "getInputType",
        value: function getInputType() {
          return 'string';
        }
      }, {
        key: "isComponentDuplicable",
        get: function get() {
          return true;
        }
      }, {
        key: "hasTime",
        get: function get() {
          return true;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var Controller$f =
    /*#__PURE__*/
    function (_DateTimeField$Contro) {
      babelHelpers.inherits(Controller, _DateTimeField$Contro);

      function Controller() {
        babelHelpers.classCallCheck(this, Controller);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).apply(this, arguments));
      }

      babelHelpers.createClass(Controller, [{
        key: "hasTime",
        get: function get() {
          return false;
        }
      }], [{
        key: "type",
        value: function type() {
          return 'date';
        }
      }]);
      return Controller;
    }(Controller$e);

    var FieldAgreement = {
      mixins: [MixinField],
      template: "\t\n\t\t<label class=\"b24-form-control-container\">\n\t\t\t<input type=\"checkbox\" \n\t\t\t\tv-model=\"field.item().selected\"\n\t\t\t\t@blur=\"$emit('input-blur', this)\"\n\t\t\t\t@focus=\"$emit('input-focus', this)\"\n\t\t\t\t@click.capture=\"requestConsent\"\n\t\t\t\tonclick=\"this.blur()\"\n\t\t\t>\n\t\t\t<span class=\"b24-form-control-desc\">\n\t\t\t\t<a :href=\"href\" :target=\"target\"\n\t\t\t\t\t@click=\"requestConsent\" \n\t\t\t\t>{{ field.label }}</a>\n\t\t\t</span>\n\t\t\t<span v-show=\"field.required\" class=\"b24-form-control-required\">*</span>\n\t\t\t<field-item-alert v-bind:field=\"field\"></field-item-alert>\t\n\t\t</label>\n\t",
      computed: {
        target: function target() {
          return this.field.isLink() ? '_blank' : null;
        },
        href: function href() {
          return this.field.isLink() ? this.field.options.content : null;
        }
      },
      methods: {
        requestConsent: function requestConsent(e) {
          this.field.consentRequested = true;

          if (this.field.isLink()) {
            this.field.applyConsent();
            return true;
          }

          e ? e.preventDefault() : null;
          e ? e.stopPropagation() : null;
          this.$root.$emit('consent:request', this.field);
          return false;
        }
      }
    };

    var Controller$g =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);
      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'agreement';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldAgreement;
        }
      }]);

      function Controller$$1(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller$$1);
        options.type = 'agreement';
        options.visible = true;
        options.multiple = false;
        options.items = null;
        options.values = null;
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "consentRequested", false);
        return _this;
      }

      babelHelpers.createClass(Controller$$1, [{
        key: "isLink",
        value: function isLink() {
          return typeof this.options.content === 'string';
        }
      }, {
        key: "applyConsent",
        value: function applyConsent() {
          this.consentRequested = false;
          this.item().selected = true;
        }
      }, {
        key: "rejectConsent",
        value: function rejectConsent() {
          this.consentRequested = false;
          this.item().selected = false;
        }
      }, {
        key: "requestConsent",
        value: function requestConsent() {
          this.consentRequested = false;

          if (!this.required || this.valid()) {
            return true;
          }

          if (!this.isLink()) {
            this.consentRequested = true;
          }

          return false;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var Controller$h =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        babelHelpers.classCallCheck(this, Controller);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));
      }

      babelHelpers.createClass(Controller, null, [{
        key: "type",
        value: function type() {
          return 'name';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$i =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        babelHelpers.classCallCheck(this, Controller);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));
      }

      babelHelpers.createClass(Controller, null, [{
        key: "type",
        value: function type() {
          return 'second-name';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$j =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        babelHelpers.classCallCheck(this, Controller);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));
      }

      babelHelpers.createClass(Controller, null, [{
        key: "type",
        value: function type() {
          return 'last-name';
        }
      }]);
      return Controller;
    }(Controller$1);

    var Controller$k =
    /*#__PURE__*/
    function (_StringField$Controll) {
      babelHelpers.inherits(Controller, _StringField$Controll);

      function Controller(options) {
        babelHelpers.classCallCheck(this, Controller);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller).call(this, options));
      }

      babelHelpers.createClass(Controller, null, [{
        key: "type",
        value: function type() {
          return 'company-name';
        }
      }]);
      return Controller;
    }(Controller$1);

    var FieldLayout = {
      props: ['field'],
      template: "\n\t\t<hr v-if=\"field.content.type=='hr'\" class=\"b24-form-field-layout-hr\">\n\t\t<div v-else-if=\"field.content.type=='br'\" class=\"b24-form-field-layout-br\"></div>\n\t\t<div v-else-if=\"field.content.type=='section'\" class=\"b24-form-field-layout-section\">\n\t\t\t{{ field.label }}\n\t\t</div>\n\t\t<div v-else-if=\"field.content.html\" v-html=\"field.content.html\"></div>\n\t"
    };

    var Controller$l =
    /*#__PURE__*/
    function (_BaseField$Controller) {
      babelHelpers.inherits(Controller$$1, _BaseField$Controller);

      function Controller$$1(options) {
        var _this;

        babelHelpers.classCallCheck(this, Controller$$1);
        _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Controller$$1).call(this, options));
        babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "content", {
          type: '',
          html: ''
        });
        _this.multiple = false;
        _this.required = false;

        if (babelHelpers.typeof(options.content) === 'object') {
          if (options.content.type) {
            _this.content.type = options.content.type;
          }

          if (options.content.html) {
            _this.content.html = options.content.html;
          }
        }

        return _this;
      }

      babelHelpers.createClass(Controller$$1, null, [{
        key: "type",
        value: function type() {
          return 'layout';
        }
      }, {
        key: "component",
        value: function component() {
          return FieldLayout;
        }
      }]);
      return Controller$$1;
    }(Controller);

    var controllers = [Controller$1, Controller$3, Controller$2, Controller$4, Controller$5, Controller$6, Controller$7, Controller$8, Controller$a, Controller$9, Controller$b, Controller$c, Controller$d, Controller$e, Controller$f, Controller$g, Controller$h, Controller$i, Controller$j, Controller$k, Controller$l];
    var component = Controller.component();
    component.components = Object.assign({}, component.components || {}, controllers.reduce(function (accum, controller) {
      accum['field-' + controller.type()] = controller.component();
      return accum;
    }, {}));

    var Factory =
    /*#__PURE__*/
    function () {
      function Factory() {
        babelHelpers.classCallCheck(this, Factory);
      }

      babelHelpers.createClass(Factory, null, [{
        key: "create",
        value: function create(options) {
          var controller = controllers.filter(function (controller) {
            return options.type === controller.type();
          })[0];

          if (!controller) {
            throw new Error("Unknown field type '".concat(options.type, "'"));
          }

          return new controller(options);
        }
      }, {
        key: "getControllers",
        value: function getControllers() {
          return controllers;
        }
      }, {
        key: "getComponent",
        value: function getComponent() {
          return component;
        }
      }]);
      return Factory;
    }();

    var ViewTypes = ['inline', 'popup', 'panel', 'widget'];
    var ViewPositions = ['left', 'center', 'right'];
    var ViewVerticals = ['top', 'bottom'];

    var Navigation =
    /*#__PURE__*/
    function () {
      function Navigation() {
        babelHelpers.classCallCheck(this, Navigation);
        babelHelpers.defineProperty(this, "index", 1);
        babelHelpers.defineProperty(this, "pages", []);
      }

      babelHelpers.createClass(Navigation, [{
        key: "add",
        value: function add(page) {
          this.pages.push(page);
        }
      }, {
        key: "next",
        value: function next() {
          if (this.current().validate()) {
            this.index += this.index >= this.count() ? 0 : 1;
          }
        }
      }, {
        key: "prev",
        value: function prev() {
          this.index -= this.index > 1 ? 1 : 0;
        }
      }, {
        key: "current",
        value: function current() {
          return this.pages[this.index - 1];
        }
      }, {
        key: "iterable",
        value: function iterable() {
          return this.count() > 1;
        }
      }, {
        key: "ended",
        value: function ended() {
          return this.index >= this.count();
        }
      }, {
        key: "beginning",
        value: function beginning() {
          return this.index === 1;
        }
      }, {
        key: "count",
        value: function count() {
          return this.pages.length;
        }
      }, {
        key: "removeEmpty",
        value: function removeEmpty() {
          if (this.count() <= 1) {
            return;
          }

          this.pages = this.pages.filter(function (page) {
            return page.fields.length > 0;
          });
        }
      }, {
        key: "validate",
        value: function validate() {
          return this.pages.filter(function (page) {
            return !page.validate();
          }).length === 0;
        }
      }]);
      return Navigation;
    }();

    var Page =
    /*#__PURE__*/
    function () {
      function Page(title) {
        babelHelpers.classCallCheck(this, Page);
        babelHelpers.defineProperty(this, "fields", []);
        this.title = title;
      }

      babelHelpers.createClass(Page, [{
        key: "addField",
        value: function addField(field) {
          this.fields.push(field);
        }
      }, {
        key: "getTitle",
        value: function getTitle() {
          return this.title;
        }
      }, {
        key: "validate",
        value: function validate() {
          return this.fields.filter(function (field) {
            return !field.valid();
          }).length === 0;
        }
      }]);
      return Page;
    }();

    var Basket =
    /*#__PURE__*/
    function () {
      function Basket(fields, currency) {
        babelHelpers.classCallCheck(this, Basket);

        _currency.set(this, {
          writable: true,
          value: void 0
        });

        _fields.set(this, {
          writable: true,
          value: []
        });

        babelHelpers.classPrivateFieldSet(this, _currency, currency);
        babelHelpers.classPrivateFieldSet(this, _fields, fields.filter(function (field) {
          return field.type === 'product';
        }));
      }

      babelHelpers.createClass(Basket, [{
        key: "has",
        value: function has() {
          return babelHelpers.classPrivateFieldGet(this, _fields).length > 0;
        }
      }, {
        key: "items",
        value: function items() {
          return babelHelpers.classPrivateFieldGet(this, _fields).reduce(function (accumulator, field) {
            return accumulator.concat(field.selectedItems());
          }, []).filter(function (item) {
            return item.price;
          });
        }
      }, {
        key: "formatMoney",
        value: function formatMoney(val) {
          return Conv.formatMoney(val, babelHelpers.classPrivateFieldGet(this, _currency).format);
        }
      }, {
        key: "sum",
        value: function sum() {
          return this.items().reduce(function (sum, item) {
            return sum + item.getSummary();
          }, 0);
        }
      }, {
        key: "total",
        value: function total() {
          return this.items().reduce(function (sum, item) {
            return sum + item.getTotal();
          }, 0);
        }
      }, {
        key: "discount",
        value: function discount() {
          return this.items().reduce(function (sum, item) {
            return sum + item.getDiscounts();
          }, 0);
        }
      }, {
        key: "printSum",
        value: function printSum() {
          return this.formatMoney(this.sum());
        }
      }, {
        key: "printTotal",
        value: function printTotal() {
          return this.formatMoney(this.total());
        }
      }, {
        key: "printDiscount",
        value: function printDiscount() {
          return this.formatMoney(this.discount());
        }
      }]);
      return Basket;
    }();

    var _currency = new WeakMap();

    var _fields = new WeakMap();

    var Scrollable = {
      props: ['show', 'enabled', 'zIndex', 'text', 'topIntersected', 'bottomIntersected'],
      template: "\n\t\t<div>\n\t\t\t<transition name=\"b24-a-fade\">\n\t\t\t\t<div class=\"b24-window-scroll-arrow-up-box\"\n\t\t\t\t\tv-if=\"enabled && !text && !anchorTopIntersected\" \n\t\t\t\t\t:style=\"{ zIndex: zIndexComputed + 10}\"\n\t\t\t\t\t@click=\"scrollTo(false)\"\n\t\t\t\t>\n\t\t\t\t\t<button type=\"button\" class=\"b24-window-scroll-arrow-up\"></button>\n\t\t\t\t</div>\n\t\t\t</transition>\t\t\t\t\t\t\n\t\t\t<div class=\"b24-window-scrollable\" :style=\"{ zIndex: zIndexComputed }\">\n\t\t\t\t<div v-if=\"enabled\" class=\"b24-window-scroll-anchor\"></div>\n\t\t\t\t<slot></slot>\n\t\t\t\t<div v-if=\"enabled\" class=\"b24-window-scroll-anchor\"></div>\n\t\t\t</div>\n\t\t\t<transition name=\"b24-a-fade\">\n\t\t\t\t<div class=\"b24-window-scroll-arrow-down-box\"\n\t\t\t\t\tv-if=\"enabled && !text && !anchorBottomIntersected\"\n\t\t\t\t\t:style=\"{ zIndex: zIndexComputed + 10}\"\n\t\t\t\t\t@click=\"scrollTo(true)\"\n\t\t\t\t>\n\t\t\t\t\t<button type=\"button\" class=\"b24-window-scroll-arrow-down\"></button>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"b24-form-scroll-textable\"\n\t\t\t\t\tv-if=\"enabled && text && !anchorBottomIntersected\" \n\t\t\t\t\t:style=\"{ zIndex: zIndexComputed + 10}\"\n\t\t\t\t\t@click=\"scrollTo(true)\"\n\t\t\t\t>\n\t\t\t\t\t<p class=\"b24-form-scroll-textable-text\">{{ text }}</p>\n\t\t\t\t\t<div class=\"b24-form-scroll-textable-arrow\">\n\t\t\t\t\t\t<div class=\"b24-form-scroll-textable-arrow-item\"></div>\n\t\t\t\t\t\t<div class=\"b24-form-scroll-textable-arrow-item\"></div>\n\t\t\t\t\t\t<div class=\"b24-form-scroll-textable-arrow-item\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</transition>\n\t\t</div>\t\n\t",
      data: function data() {
        return {
          showed: false,
          anchorObserver: null,
          anchorTopIntersected: true,
          anchorBottomIntersected: true
        };
      },
      computed: {
        zIndexComputed: function zIndexComputed() {
          return this.zIndex || 200;
        }
      },
      methods: {
        getScrollNode: function getScrollNode() {
          return this.$el.querySelector('.b24-window-scrollable');
        },
        scrollTo: function scrollTo(toDown) {
          toDown = toDown || false;
          var el = this.getScrollNode();
          var interval = 10;
          var duration = 100;
          var diff = toDown ? el.scrollHeight - el.offsetHeight - el.scrollTop : el.scrollTop;
          var step = diff / (duration / interval);

          var scroller = function scroller() {
            diff -= step;
            el.scrollTop += toDown ? +step : -step;

            if (diff > 0) {
              setTimeout(scroller, interval);
            }
          };

          scroller();
        },
        toggleScroll: function toggleScroll() {
          Scroll.toggle(this.getScrollNode(), !this.show);
        },
        toggleObservingScrollHint: function toggleObservingScrollHint() {
          var _this = this;

          if (!window.IntersectionObserver) {
            return;
          }

          var scrollable = this.getScrollNode();

          if (!scrollable) {
            return;
          }

          var topAnchor = scrollable.firstElementChild;
          var bottomAnchor = scrollable.lastElementChild;

          if (!topAnchor && !bottomAnchor) {
            return;
          }

          if (this.anchorObserver) {
            topAnchor ? this.anchorObserver.unobserve(topAnchor) : null;
            bottomAnchor ? this.anchorObserver.unobserve(bottomAnchor) : null;
            this.anchorObserver = null;
            return;
          }

          this.anchorObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              //debugger;
              if (entry.target === topAnchor) {
                _this.anchorTopIntersected = !!entry.isIntersecting;
              } else if (entry.target === bottomAnchor) {
                _this.anchorBottomIntersected = !!entry.isIntersecting;
              }
            });
          }, {
            root: scrollable,
            rootMargin: this.scrollDownText ? '80px' : '60px',
            threshold: 0.1
          });
          topAnchor ? this.anchorObserver.observe(topAnchor) : null;
          bottomAnchor ? this.anchorObserver.observe(bottomAnchor) : null;
        }
      },
      mounted: function mounted() {
        if (this.show) {
          this.toggleScroll();
          this.toggleObservingScrollHint();
        }
      },
      watch: {
        show: function show(val) {
          if (val && !this.showed) {
            this.showed = true;
          }

          this.toggleScroll();
          this.toggleObservingScrollHint();
        }
      }
    };

    var Overlay = {
      props: ['show', 'background'],
      components: {},
      template: "\n\t\t<transition name=\"b24-a-fade\" appear>\n\t\t\t<div class=\"b24-window-overlay\"\n\t\t\t\t:style=\"{ backgroundColor: background }\" \n\t\t\t\t@click=\"$emit('click')\"\n\t\t\t\tv-show=\"show\"\n\t\t\t></div>\n\t\t</transition>\n\t"
    };
    var windowMixin = {
      props: ['show', 'title', 'position', 'vertical', 'maxWidth', 'zIndex', 'scrollDown', 'scrollDownText'],
      components: {
        'b24-overlay': Overlay,
        'b24-scrollable': Scrollable
      },
      data: function data() {
        return {
          escHandler: null
        };
      },
      methods: {
        hide: function hide() {
          this.show = false;
          this.$emit('hide');
        },
        listenEsc: function listenEsc() {
          var _this = this;

          if (!this.escHandler) {
            this.escHandler = function (e) {
              if (_this.show && e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();

                _this.hide();
              }
            };
          }

          this.show ? document.addEventListener('keydown', this.escHandler) : document.removeEventListener('keydown', this.escHandler);
        }
      },
      mounted: function mounted() {
        this.listenEsc();
      },
      watch: {
        show: function show() {
          this.listenEsc();
        }
      },
      computed: {
        zIndexComputed: function zIndexComputed() {
          return this.zIndex || 200;
        }
      }
    };
    var Popup = {
      mixins: [windowMixin],
      template: "\n\t\t<div class=\"b24-window\">\n\t\t\t<b24-overlay :show=\"show\" @click=\"hide()\"></b24-overlay>\n\t\t\t<transition :name=\"getTransitionName()\" appear>\n\t\t\t\t<div class=\"b24-window-popup\" \n\t\t\t\t\t:class=\"classes()\"\n\t\t\t\t\t@click.self.prevent=\"hide()\"\n\t\t\t\t\tv-show=\"show\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"b24-window-popup-wrapper\" \n\t\t\t\t\t\t:style=\"{ maxWidth: maxWidth + 'px' }\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<button @click=\"hide()\" type=\"button\" class=\"b24-window-close\" :style=\"{ zIndex: zIndexComputed + 20}\" ></button>\n\t\t\t\t\t\t<b24-scrollable\n\t\t\t\t\t\t\t:show=\"show\"\n\t\t\t\t\t\t\t:enabled=\"scrollDown\"\n\t\t\t\t\t\t\t:zIndex=\"zIndex\"\n\t\t\t\t\t\t\t:text=\"scrollDownText\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div v-if=\"title\" class=\"b24-window-popup-head\">\n\t\t\t\t\t\t\t\t<div class=\"b24-window-popup-title\">{{ title }}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"b24-window-popup-body\">\n\t\t\t\t\t\t\t\t<slot></slot>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</b24-scrollable>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</transition>\n\t\t</div>\n\t",
      methods: {
        getTransitionName: function getTransitionName() {
          return 'b24-a-slide-' + (this.vertical || 'bottom');
        },
        classes: function classes() {
          return ['b24-window-popup-p-' + (this.position || 'center')];
        }
      }
    };
    var Panel = {
      mixins: [windowMixin],
      template: "\n\t\t<div class=\"b24-window\">\n\t\t\t<b24-overlay :show=\"show\" @click=\"hide()\"></b24-overlay>\n\t\t\t<transition :name=\"getTransitionName()\" appear>\n\t\t\t\t<div class=\"b24-window-panel\"\n\t\t\t\t\t:class=\"classes()\"\n\t\t\t\t\tv-show=\"show\"\n\t\t\t\t>\n\t\t\t\t\t<button @click=\"hide()\" type=\"button\" class=\"b24-window-close\" :style=\"{ zIndex: zIndexComputed + 20}\" ></button>\n\t\t\t\t\t<b24-scrollable\n\t\t\t\t\t\t:show=\"show\"\n\t\t\t\t\t\t:enabled=\"scrollDown\"\n\t\t\t\t\t\t:zIndex=\"zIndex\"\n\t\t\t\t\t\t:text=\"scrollDownText\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<slot></slot>\n\t\t\t\t\t</b24-scrollable>\n\t\t\t\t</div>\n\t\t\t</transition>\n\t\t</div>\n\t",
      methods: {
        getTransitionName: function getTransitionName() {
          return 'b24-a-slide-' + (this.vertical || 'bottom');
        },
        classes: function classes() {
          return ['b24-window-panel-pos-' + (this.position || 'right')];
        }
      }
    };
    var Widget = {
      mixins: [windowMixin],
      template: "\n\t\t<div class=\"b24-window\">\n\t\t\t<b24-overlay :show=\"show\" @click=\"hide()\" :background=\"'transparent'\"></b24-overlay>\n\t\t\t<transition :name=\"getTransitionName()\" appear>\n\t\t\t\t<div class=\"b24-window-widget\" \n\t\t\t\t\t:class=\"classes()\" \n\t\t\t\t\tv-show=\"show\"\n\t\t\t\t>\n\t\t\t\t\t<button @click=\"hide()\" type=\"button\" class=\"b24-window-close\"></button>\n\t\t\t\t\t<div class=\"b24-window-widget-body\">\n\t\t\t\t\t\t<slot></slot>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</transition>\n\t\t</div>\n\t",
      methods: {
        getTransitionName: function getTransitionName() {
          return 'b24-a-slide-short-' + (this.vertical || 'bottom');
        },
        classes: function classes() {
          return ['b24-window-widget-p-' + (this.vertical || 'bottom') + '-' + (this.position || 'right')];
        }
      }
    };
    var Definition$1 = {
      'b24-overlay': Overlay,
      'b24-popup': Popup,
      'b24-panel': Panel,
      'b24-widget': Widget
    };

    //import {ScrollDown} from "./components/scrolldown";
    var Components = {
      //ScrollDown,
      Popup: Popup,
      Panel: Panel,
      Widget: Widget,
      Definition: Definition$1
    };

    var AgreementBlock = {
      mixins: [],
      props: ['messages', 'view', 'fields', 'visible', 'title', 'html', 'field'],
      components: Object.assign(Components.Definition, {
        'field': Factory.getComponent()
      }),
      data: function data() {
        return {
          field: null,
          visible: false,
          title: '',
          html: '',
          maxWidth: 600
        };
      },
      template: "\n\t\t<div>\n\t\t\t<component v-bind:is=\"'field'\"\n\t\t\t\tv-for=\"field in fields\"\n\t\t\t\tv-bind:key=\"field.id\"\n\t\t\t\tv-bind:field=\"field\"\n\t\t\t></component>\n\n\t\t\t<b24-popup\n\t\t\t\t:show=\"visible\" \n\t\t\t\t:title=\"title\" \n\t\t\t\t:maxWidth=\"maxWidth\" \n\t\t\t\t:zIndex=\"199999\"\n\t\t\t\t:scrollDown=\"true\"\n\t\t\t\t:scrollDownText=\"messages.get('consentReadAll')\"\n\t\t\t\t@hide=\"reject\"\n\t\t\t>\n\t\t\t\t<div style=\"padding: 0 12px 12px;\">\n\t\t\t\t\t<div v-html=\"html\"></div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"b24-form-btn-container\" style=\"padding: 12px 0 0;\">\n\t\t\t\t\t\t<div class=\"b24-form-btn-block\"\n\t\t\t\t\t\t\t@click.prevent=\"reject\"\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<button type=\"button\" class=\"b24-form-btn b24-form-btn-white b24-form-btn-border\">\n\t\t\t\t\t\t\t\t{{ messages.get('consentReject') }}\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"b24-form-btn-block\"\n\t\t\t\t\t\t\t@click.prevent=\"apply\"\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<button type=\"button\" class=\"b24-form-btn\">\n\t\t\t\t\t\t\t\t{{ messages.get('consentAccept') }}\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</b24-popup>\n\t\t</div>\n\t",
      mounted: function mounted() {
        this.$root.$on('consent:request', this.showPopup);
      },
      computed: {
        position: function position() {
          return this.view.position;
        }
      },
      methods: {
        apply: function apply() {
          this.field.applyConsent();
          this.field = null;
          this.hidePopup();
        },
        reject: function reject() {
          this.field.rejectConsent();
          this.field = null;
          this.hidePopup();
        },
        hidePopup: function hidePopup() {
          this.visible = false;
        },
        showPopup: function showPopup(field) {
          var _this = this;

          var text = field.options.content.text || '';
          var div = document.createElement('div');
          div.textContent = text;
          text = div.innerHTML.replace(/[\n]/g, '<br>');
          this.field = field;
          this.title = field.options.content.title;
          this.html = text || field.options.content.html;
          this.visible = true;
          setTimeout(function () {
            _this.$root.$emit('resize');
          }, 0);
        }
      }
    };

    var StateBlock = {
      props: ['form'],
      template: "\n\t\t<div class=\"b24-form-state-container\">\n\t\t\t\t<transition name=\"b24-a-fade\">\n\t\t\t\t\t<div v-show=\"form.loading\" class=\"b24-form-loader\">\n\t\t\t\t\t\t<div class=\"b24-form-loader-icon\">\n\t\t\t\t\t\t\t<svg xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns=\"http://www.w3.org/2000/svg\"  viewBox=\"0 0 263 174\">\n\t\t\t\t\t\t\t\t<defs>\n\t\t\t\t\t\t\t\t\t   <svg width=\"158px\" height=\"158px\" viewBox=\"0 0 158 158\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n\t\t\t\t\t\t\t\t\t\t   <path id=\"bxSunLines\" class=\"bx-sun-lines-animate\" d=\"M79,0 C80.6568542,0 82,1.34314575 82,3 L82,22 C82,23.6568542 80.6568542,25 79,25 C77.3431458,25 76,23.6568542 76,22 L76,3 C76,1.34314575 77.3431458,0 79,0 Z M134.861,23.139 C136.032146,24.3104996 136.032146,26.2095004 134.861,27.381 L121.426,40.816 C120.248863,41.9529166 118.377746,41.9366571 117.220544,40.7794557 C116.063343,39.6222543 116.047083,37.7511367 117.184,36.574 L130.619,23.139 C131.7905,21.9678542 133.6895,21.9678542 134.861,23.139 L134.861,23.139 Z M158,79 C158,80.6568542 156.656854,82 155,82 L136,82 C134.343146,82 133,80.6568542 133,79 C133,77.3431458 134.343146,76 136,76 L155,76 C156.656854,76 158,77.3431458 158,79 Z M134.861,134.861 C133.6895,136.032146 131.7905,136.032146 130.619,134.861 L117.184,121.426 C116.40413,120.672777 116.091362,119.557366 116.365909,118.508478 C116.640455,117.45959 117.45959,116.640455 118.508478,116.365909 C119.557366,116.091362 120.672777,116.40413 121.426,117.184 L134.861,130.619 C136.032146,131.7905 136.032146,133.6895 134.861,134.861 Z M79,158 C77.3431458,158 76,156.656854 76,155 L76,136 C76,134.343146 77.3431458,133 79,133 C80.6568542,133 82,134.343146 82,136 L82,155 C82,156.656854 80.6568542,158 79,158 Z M23.139,134.861 C21.9678542,133.6895 21.9678542,131.7905 23.139,130.619 L36.574,117.184 C37.3272234,116.40413 38.4426337,116.091362 39.491522,116.365909 C40.5404103,116.640455 41.3595451,117.45959 41.6340915,118.508478 C41.9086378,119.557366 41.5958698,120.672777 40.816,121.426 L27.381,134.861 C26.2095004,136.032146 24.3104996,136.032146 23.139,134.861 Z M0,79 C0,77.3431458 1.34314575,76 3,76 L22,76 C23.6568542,76 25,77.3431458 25,79 C25,80.6568542 23.6568542,82 22,82 L3,82 C1.34314575,82 0,80.6568542 0,79 L0,79 Z M23.139,23.139 C24.3104996,21.9678542 26.2095004,21.9678542 27.381,23.139 L40.816,36.574 C41.5958698,37.3272234 41.9086378,38.4426337 41.6340915,39.491522 C41.3595451,40.5404103 40.5404103,41.3595451 39.491522,41.6340915 C38.4426337,41.9086378 37.3272234,41.5958698 36.574,40.816 L23.139,27.381 C21.9678542,26.2095004 21.9678542,24.3104996 23.139,23.139 Z\" fill=\"#FFD110\" />\n\t\t\t\t\t\t\t\t\t   </svg>\n\t\t\t\t\t\t\t   </defs>\n\t\t\t\t\t\t\t   <g fill=\"none\" fill-rule=\"evenodd\">\n\t\t\t\t\t\t\t\t   <path d=\"M65.745 160.5l.245-.005c13.047-.261 23.51-10.923 23.51-23.995 0-13.255-10.745-24-24-24-3.404 0-6.706.709-9.748 2.062l-.47.21-.196-.477A19.004 19.004 0 0 0 37.5 102.5c-10.493 0-19 8.507-19 19 0 1.154.103 2.295.306 3.413l.108.6-.609-.01A17.856 17.856 0 0 0 18 125.5C8.335 125.5.5 133.335.5 143s7.835 17.5 17.5 17.5h47.745zM166.5 85.5h69v-.316l.422-.066C251.14 82.73 262.5 69.564 262.5 54c0-17.397-14.103-31.5-31.5-31.5-.347 0-.694.006-1.04.017l-.395.013-.103-.382C226.025 9.455 214.63.5 201.5.5c-15.014 0-27.512 11.658-28.877 26.765l-.047.515-.512-.063a29.296 29.296 0 0 0-3.564-.217c-16.016 0-29 12.984-29 29 0 15.101 11.59 27.643 26.542 28.897l.458.039v.064z\" stroke-opacity=\".05\" stroke=\"#000\" fill=\"#000\"/>\n\t\t\t\t\t\t\t\t   <circle class=\"b24-form-loader-icon-sun-ring\" stroke=\"#FFD110\" stroke-width=\"6\" cx=\"131.5\" cy=\"95.5\" r=\"44.5\"/>\n\t\t\t\t\t\t\t   </g>\n\t\t\t\t\t\t\t   <use xlink:href=\"#bxSunLines\" class=\"b24-form-loader-icon-sun-line\" y=\"16.5\" x=\"52.5\" width=\"158\" height=\"158\"/>\n\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</transition>\n\t\t\t\t\n\t\t\t\t<div v-show=\"form.sent\" class=\"b24-form-success\">\n\t\t\t\t\t<div class=\"b24-form-success-inner\">\n\t\t\t\t\t\t<div class=\"b24-form-success-icon\"></div>\n\t\t\t\t\t\t<div class=\"b24-form-success-text\">\n\t\t\t\t\t\t\t<p>{{ form.messages.get('stateSuccessTitle') }}</p>\n\t\t\t\t\t\t\t<p>{{ form.stateText }}</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<button class=\"b24-form-btn b24-form-btn-border b24-form-btn-tight\"\n\t\t\t\t\t\t\tv-if=\"form.stateButton.text\" \n\t\t\t\t\t\t\t@click=\"form.stateButton.handler\" \n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ form.stateButton.text }}\t\t\t\t\t\t\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\n\t\t\t\t<div v-show=\"form.error\" class=\"b24-form-error\">\n\t\t\t\t\t<div class=\"b24-form-error-inner\">\n\t\t\t\t\t\t<div class=\"b24-form-error-icon\"></div>\n\t\t\t\t\t\t<div class=\"b24-form-error-text\">\n\t\t\t\t\t\t\t<p>{{ form.stateText }}</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\n\t\t\t\t\t\t<button class=\"b24-form-btn b24-form-btn-border b24-form-btn-tight\"\n\t\t\t\t\t\t\t@click=\"form.submit()\" \n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{ form.messages.get('stateButtonResend') }}\t\t\t\t\t\t\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div v-show=\"form.disabled\" class=\"b24-form-warning\">\n\t\t\t\t\t<div class=\"b24-form-warning-inner\">\n\t\t\t\t\t\t<div class=\"b24-form-warning-icon\">\n\t\t\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 169 169\"><defs><circle id=\"a\" cx=\"84.5\" cy=\"84.5\" r=\"65.5\"/><filter x=\"-.8%\" y=\"-.8%\" width=\"101.5%\" height=\"101.5%\" filterUnits=\"objectBoundingBox\" id=\"b\"><feGaussianBlur stdDeviation=\".5\" in=\"SourceAlpha\" result=\"shadowBlurInner1\"/><feOffset dx=\"-1\" dy=\"-1\" in=\"shadowBlurInner1\" result=\"shadowOffsetInner1\"/><feComposite in=\"shadowOffsetInner1\" in2=\"SourceAlpha\" operator=\"arithmetic\" k2=\"-1\" k3=\"1\" result=\"shadowInnerInner1\"/><feColorMatrix values=\"0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.0886691434 0\" in=\"shadowInnerInner1\" result=\"shadowMatrixInner1\"/><feGaussianBlur stdDeviation=\".5\" in=\"SourceAlpha\" result=\"shadowBlurInner2\"/><feOffset dx=\"1\" dy=\"1\" in=\"shadowBlurInner2\" result=\"shadowOffsetInner2\"/><feComposite in=\"shadowOffsetInner2\" in2=\"SourceAlpha\" operator=\"arithmetic\" k2=\"-1\" k3=\"1\" result=\"shadowInnerInner2\"/><feColorMatrix values=\"0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 0.292285839 0\" in=\"shadowInnerInner2\" result=\"shadowMatrixInner2\"/><feMerge><feMergeNode in=\"shadowMatrixInner1\"/><feMergeNode in=\"shadowMatrixInner2\"/></feMerge></filter></defs><g fill=\"none\" fill-rule=\"evenodd\"><circle stroke-opacity=\".05\" stroke=\"#000\" fill-opacity=\".07\" fill=\"#000\" cx=\"84.5\" cy=\"84.5\" r=\"84\"/><use fill=\"#FFF\" xlink:href=\"#a\"/><use fill=\"#000\" filter=\"url(#b)\" xlink:href=\"#a\"/><path d=\"M114.29 99.648L89.214 58.376c-1.932-3.168-6.536-3.168-8.427 0L55.709 99.648c-1.974 3.25.41 7.352 4.234 7.352h50.155c3.782 0 6.166-4.103 4.193-7.352zM81.404 72.756c0-1.828 1.48-3.29 3.33-3.29h.452c1.85 0 3.33 1.462 3.33 3.29v12.309c0 1.827-1.48 3.29-3.33 3.29h-.453c-1.85 0-3.33-1.463-3.33-3.29V72.756zm7.77 23.886c0 2.274-1.892 4.143-4.194 4.143s-4.193-1.869-4.193-4.143c0-2.275 1.891-4.144 4.193-4.144 2.302 0 4.193 1.869 4.193 4.144z\" fill=\"#000\" opacity=\".4\"/></g></svg>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"b24-form-warning-text\">\n\t\t\t\t\t\t\t<p>{{ form.messages.get('stateDisabled') }}</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t</div>\n\t",
      computed: {},
      methods: {}
    };

    var PagerBlock = {
      props: {
        pager: {
          type: Object,
          required: true
        },
        diameter: {
          type: Number,
          default: 44
        },
        border: {
          type: Number,
          default: 4
        }
      },
      template: "\n\t\t<div class=\"b24-form-progress-container\"\n\t\t\tv-if=\"pager.iterable()\"\n\t\t>\n\t\t\t<div class=\"b24-form-progress-bar-container\">\n\t\t\t\t<svg class=\"b24-form-progress\" \n\t\t\t\t\t:viewport=\"'0 0 ' + diameter + ' ' + diameter\" \n\t\t\t\t\t:width=\"diameter\" :height=\"diameter\"\n\t\t\t\t>\n\t\t\t\t\t<circle class=\"b24-form-progress-track\"\n\t\t\t\t\t\t:r=\"(diameter - border) / 2\" \n\t\t\t\t\t\t:cx=\"diameter / 2\" :cy=\"diameter / 2\" \n\t\t\t\t\t\t:stroke-width=\"border\" \n\t\t\t\t\t></circle>\n\t\t\t\t\t<circle class=\"b24-form-progress-bar\"\n\t\t\t\t\t\t:r=\"(diameter - border) / 2\"\n\t\t\t\t\t\t:cx=\"diameter / 2\" :cy=\"diameter / 2\"\n\t\t\t\t\t\t:stroke-width=\"border\"\n\t\t\t\t\t\t:stroke-dasharray=\"strokeDasharray\" \n\t\t\t\t\t\t:stroke-dashoffset=\"strokeDashoffset\"\n\t\t\t\t\t></circle>\n\t\t\t\t</svg>\n\t\t\t\t<div class=\"b24-form-progress-bar-counter\">\n\t\t\t\t\t<strong>{{ pager.index}}</strong>/{{ pager.count() }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"b24-form-progress-bar-title\">\n\t\t\t\t{{ pager.current().getTitle() }}\n\t\t\t</div>\n\n\t\t</div>\n\t",
      computed: {
        strokeDasharray: function strokeDasharray() {
          return this.getCircuit();
        },
        strokeDashoffset: function strokeDashoffset() {
          return this.getCircuit() - this.getCircuit() / this.pager.count() * this.pager.index;
        }
      },
      methods: {
        getCircuit: function getCircuit() {
          return (this.diameter - this.border) * 3.14;
        }
      }
    };

    var BasketBlock = {
      props: ['basket', 'messages'],
      template: "\n\t\t<div v-if=\"basket.has()\" class=\"b24-form-basket\">\n\t\t\t<table>\n\t\t\t\t<tbody>\n\t\t\t\t\t<tr v-if=\"basket.discount()\" class=\"b24-form-basket-sum\">\n\t\t\t\t\t\t<td class=\"b24-form-basket-label\">\n\t\t\t\t\t\t\t{{ messages.get('basketSum') }}:\n\t\t\t\t\t\t</td>\n\t\t\t\t\t\t<td class=\"b24-form-basket-value\" v-html=\"basket.printSum()\"></td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr v-if=\"basket.discount()\" class=\"b24-form-basket-discount\">\n\t\t\t\t\t\t<td class=\"b24-form-basket-label\">\n\t\t\t\t\t\t\t{{ messages.get('basketDiscount') }}:\n\t\t\t\t\t\t</td>\n\t\t\t\t\t\t<td class=\"b24-form-basket-value\" v-html=\"basket.printDiscount()\"></td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr class=\"b24-form-basket-pay\">\n\t\t\t\t\t\t<td class=\"b24-form-basket-label\">\n\t\t\t\t\t\t\t{{ messages.get('basketTotal') }}:\n\t\t\t\t\t\t</td>\n\t\t\t\t\t\t<td class=\"b24-form-basket-value\" v-html=\"basket.printTotal()\"></td>\n\t\t\t\t\t</tr>\n\t\t\t\t</tbody>\n\t\t\t</table>\n\t\t</div>\n\t",
      computed: {},
      methods: {}
    };

    var Form = {
      props: {
        form: {
          type: Controller$m
        }
      },
      components: {
        'field': Factory.getComponent(),
        'agreement-block': AgreementBlock,
        'state-block': StateBlock,
        'pager-block': PagerBlock,
        'basket-block': BasketBlock
      },
      template: "\n\t\t<div class=\"b24-form-wrapper\"\n\t\t\t:class=\"classes()\"\n\t\t>\n\t\t\t<div v-if=\"form.title || form.desc\" class=\"b24-form-header b24-form-padding-side\">\n\t\t\t\t<div v-if=\"form.title\" class=\"b24-form-header-title\">{{ form.title }}</div>\n\t\t\t\t<div class=\"b24-form-header-description\"\n\t\t\t\t\tv-if=\"form.desc\"\n\t\t\t\t\tv-html=\"form.desc\"\n\t\t\t\t></div>\n\t\t\t</div>\n\t\t\t<div v-else class=\"b24-form-header-padding\"></div>\n\n\t\t\t<div class=\"b24-form-content b24-form-padding-side\">\n\t\t\t\t<form \n\t\t\t\t\tmethod=\"post\"\n\t\t\t\t\tnovalidate\n\t\t\t\t\t@submit=\"submit\"\n\t\t\t\t\tv-if=\"form.pager\"\n\t\t\t\t>\n\t\t\t\t\t<component v-bind:is=\"'pager-block'\"\n\t\t\t\t\t\tv-bind:key=\"form.id\"\n\t\t\t\t\t\tv-bind:pager=\"form.pager\"\n\t\t\t\t\t\tv-if=\"form.pager.iterable()\"\n\t\t\t\t\t></component>\n\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div>\t\t\n\t\t\t\t\t\t<component v-bind:is=\"'field'\"\n\t\t\t\t\t\t\tv-for=\"field in form.pager.current().fields\"\n\t\t\t\t\t\t\tv-bind:key=\"field.id\"\n\t\t\t\t\t\t\tv-bind:field=\"field\"\n\t\t\t\t\t\t></component>\n\t\t\t\t\t</div>\t\n\t\t\t\t\t\n\t\t\t\t\t<component v-bind:is=\"'agreement-block'\"\n\t\t\t\t\t\tv-bind:key=\"form.id\"\n\t\t\t\t\t\tv-bind:fields=\"form.agreements\"\n\t\t\t\t\t\tv-bind:view=\"form.view\"\n\t\t\t\t\t\tv-bind:messages=\"form.messages\"\n\t\t\t\t\t\tv-if=\"form.pager.ended()\"\n\t\t\t\t\t></component>\n\t\t\t\t\t\n\t\t\t\t\t<component v-bind:is=\"'basket-block'\"\n\t\t\t\t\t\tv-bind:key=\"form.id\"\n\t\t\t\t\t\tv-bind:basket=\"form.basket\"\n\t\t\t\t\t\tv-bind:messages=\"form.messages\"\n\t\t\t\t\t></component>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"b24-form-btn-container\">\n\t\t\t\t\t\t<div class=\"b24-form-btn-block\"\n\t\t\t\t\t\t\tv-if=\"!form.pager.beginning()\" \n\t\t\t\t\t\t\t@click.prevent=\"prevPage()\"\t\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<button type=\"button\" class=\"b24-form-btn b24-form-btn-white b24-form-btn-border\">\n\t\t\t\t\t\t\t\t{{ form.messages.get('navBack') }}\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\n\t\t\t\t\t\t<div class=\"b24-form-btn-block\"\n\t\t\t\t\t\t\tv-if=\"!form.pager.ended()\"\n\t\t\t\t\t\t\t@click.prevent=\"nextPage()\"\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<button type=\"button\" class=\"b24-form-btn\">\n\t\t\t\t\t\t\t\t{{ form.messages.get('navNext') }}\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"b24-form-btn-block\"\n\t\t\t\t\t\t\tv-if=\"form.pager.ended()\"\t\t\t\t\t\t\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<button type=\"submit\" class=\"b24-form-btn\">\n\t\t\t\t\t\t\t\t{{ form.buttonCaption }}\n\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<span style=\"color: red;\" v-show=\"false && hasErrors\">\n\t\t\t\t\t\tDebug: fill fields\n\t\t\t\t\t</span>\n\t\t\t\t</form>\n\t\t\t</div>\n\t\t\t\n\t\t\t<state-block v-bind:key=\"form.id\" v-bind:form=\"form\"></state-block>\n\t\t\t<div class=\"b24-form-sign\" v-if=\"form.useSign\">\n\t\t\t\t<select v-show=\"false\" v-model=\"form.messages.language\">\n\t\t\t\t\t<option v-for=\"language in form.languages\" \n\t\t\t\t\t\tv-bind:value=\"language\"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ language }}\n\t\t\t\t\t</option>\t\t\t\t\n\t\t\t\t</select>\n\t\t\t\n\t\t\t\t<span class=\"b24-form-sign-text\">{{ form.messages.get('sign') }}</span>\n\t\t\t\t<span class=\"b24-form-sign-bx\">{{ getSignBy() }}</span>\n\t\t\t\t<span class=\"b24-form-sign-24\">24</span>\t\t\t\n\t\t\t</div>\t\t\t\n\t\t</div>\n\t",
      computed: {
        hasErrors: function hasErrors() {
          return this.form.validated && !this.form.valid();
        }
      },
      methods: {
        prevPage: function prevPage() {
          var _this = this;

          this.form.loading = true;
          setTimeout(function () {
            _this.form.loading = false;

            _this.form.pager.prev();
          }, 300);
        },
        nextPage: function nextPage() {
          var _this2 = this;

          if (this.form.pager.current().validate()) {
            this.form.loading = true;
          }

          setTimeout(function () {
            _this2.form.loading = false;

            _this2.form.pager.next();
          }, 300);
        },
        getSignBy: function getSignBy() {
          return this.form.messages.get('signBy').replace('24', '');
        },
        submit: function submit(e) {
          if (!this.form.submit()) {
            e.preventDefault();
          }
        },
        classes: function classes() {
          var list = [];

          if (this.form.view.type === 'inline' && this.form.design.shadow) {
            list.push('b24-form-shadow');
          }

          var border = this.form.design.border;

          for (var pos in border) {
            if (!border.hasOwnProperty(pos) || !border[pos]) {
              continue;
            }

            list.push('b24-form-border-' + pos);
          }

          if (this.form.loading || this.form.sent || this.form.error || this.form.disabled) {
            list.push('b24-from-state-on');
          }

          return list;
        }
      }
    };

    var Wrapper = {
      props: ['form'],
      data: function data() {
        return {
          designStyleNode: null
        };
      },
      methods: {
        classes: function classes() {
          var list = [];

          if (this.form.design.isDark()) {
            list.push('b24-form-dark');
          } else if (this.form.design.isAutoDark()) ;

          if (this.form.design.style) {
            list.push('b24-form-style-' + this.form.design.style);
          }

          return list;
        },
        isDesignStylesApplied: function isDesignStylesApplied() {
          var color = this.form.design.color;
          var css = [];
          var fontFamily = this.form.design.getFontFamily();

          if (fontFamily) {
            fontFamily = fontFamily.trim();
            fontFamily = fontFamily.indexOf(' ') > 0 ? "\"".concat(fontFamily, "\"") : fontFamily;
            css.push('--b24-font-family: ' + fontFamily + ', var(--b24-font-family-default);');
          }

          var fontUri = this.form.design.getFontUri();

          if (fontUri) {
            var link = document.createElement('LINK');
            link.setAttribute('href', fontUri);
            link.setAttribute('rel', 'stylesheet');
            document.head.appendChild(link);
          }

          var colorMap = {
            style: '--b24-font-family',
            primary: '--b24-primary-color',
            primaryText: '--b24-primary-text-color',
            primaryHover: '--b24-primary-hover-color',
            text: '--b24-text-color',
            background: '--b24-background-color',
            fieldBorder: '--b24-field-border-color',
            fieldBackground: '--b24-field-background-color',
            fieldFocusBackground: '--b24-field-focus-background-color'
          };

          for (var key in color) {
            if (!color.hasOwnProperty(key) || !color[key]) {
              continue;
            }

            if (!colorMap.hasOwnProperty(key) || !colorMap[key]) {
              continue;
            }

            var rgba = Color.hexToRgba(color[key]);
            css.push(colorMap[key] + ': ' + rgba + ';');
          }

          var primaryHover = Color.parseHex(color.primary);
          primaryHover[3] -= 0.3;
          primaryHover = Color.toRgba(primaryHover);
          css.push(colorMap.primaryHover + ': ' + primaryHover + ';');

          if (this.form.design.backgroundImage) {
            css.push("background-image: url(".concat(this.form.design.backgroundImage, ");"));
            css.push("background-size: cover;");
            css.push("background-position: center;"); //css.push(`padding: 20px 0;`);
          }
          /*
          if (this.form.view.type === 'inline' && this.form.design.shadow)
          {
          	(document.documentElement.clientWidth <= 530)
          		? css.push('padding: 3px;')
          		: css.push('padding: 20px;')
          }
          */


          css = css.join("\n");

          if (!this.designStyleNode) {
            this.designStyleNode = document.createElement('STYLE');
            this.designStyleNode.setAttribute('type', 'text/css');
          }

          if (css) {
            css = ".b24-form #b24-".concat(this.form.getId(), ", .b24-form #b24-").concat(this.form.getId(), ".b24-form-dark\n\t\t\t\t {\n\t\t\t\t\t").concat(css, "\n\t\t\t\t}");
            this.designStyleNode.textContent = '';
            this.designStyleNode.appendChild(document.createTextNode(css));
            document.head.appendChild(this.designStyleNode);
            return true;
          }

          if (!css) {
            if (this.designStyleNode && this.designStyleNode.parentElement) {
              this.designStyleNode.parentElement.removeChild(this.designStyleNode);
            }

            return false;
          }
        }
      },
      template: "\n\t\t<div class=\"b24-form\">\n\t\t\t<div\n\t\t\t \t:class=\"classes()\"\n\t\t\t\t:id=\"'b24-' + form.getId()\"\n\t\t\t\t:data-styles-apllied=\"isDesignStylesApplied()\"\n\t\t\t>\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t</div>\n\t"
    };
    var viewMixin = {
      props: ['form'],
      components: Object.assign(Components.Definition, {
        'b24-form-container': Wrapper
      }),
      computed: {
        scrollDownText: function scrollDownText() {
          return Browser.isMobile() ? this.form.messages.get('moreFieldsYet') : null;
        }
      }
    };
    var Inline = {
      mixins: [viewMixin],
      template: "\n\t\t<b24-form-container :form=\"form\" v-show=\"form.visible\">\n\t\t\t<slot></slot>\n\t\t</b24-form-container>\n\t"
    };
    var Popup$1 = {
      mixins: [viewMixin],
      template: "\n\t\t<b24-form-container :form=\"form\">\n\t\t\t<b24-popup v-bind:key=\"form.id\" \n\t\t\t\t:show=\"form.visible\"\n\t\t\t\t:position=\"form.view.position\"  \n\t\t\t\t:scrollDown=\"!form.isOnState()\"  \n\t\t\t\t:scrollDownText=\"scrollDownText\"\n\t\t\t\t@hide=\"form.hide()\"\n\t\t\t>\n\t\t\t\t<div v-if=\"form.view.title\" class=\"b24-window-header\">\n\t\t\t\t\t<div class=\"b24-window-header-title\">{{ form.view.title }}</div>\n\t\t\t\t</div>\n\t\t\t\t<slot></slot>\n\t\t\t</b24-popup>\n\t\t</b24-form-container>\n\t"
    };
    var Panel$1 = {
      mixins: [viewMixin],
      template: "\n\t\t<b24-form-container :form=\"form\">\n\t\t\t<b24-panel v-bind:key=\"form.id\" \n\t\t\t\t:show=\"form.visible\"\n\t\t\t\t:position=\"form.view.position\"\n\t\t\t\t:vertical=\"form.view.vertical\"\n\t\t\t\t:scrollDown=\"!form.isOnState()\"\n\t\t\t\t:scrollDownText=\"scrollDownText\"\n\t\t\t\t@hide=\"form.hide()\"\n\t\t\t>\n\t\t\t\t<div v-if=\"form.view.title\" class=\"b24-window-header\">\n\t\t\t\t\t<div class=\"b24-window-header-title\">{{ form.view.title }}</div>\n\t\t\t\t</div>\n\t\t\t\t<slot></slot>\n\t\t\t</b24-panel>\n\t\t</b24-form-container>\n\t"
    };
    var Widget$1 = {
      mixins: [viewMixin],
      template: "\n\t\t<b24-form-container :form=\"form\">\n\t\t\t<b24-widget v-bind:key=\"form.id\" \n\t\t\t\tv-bind:show=\"form.visible\" \n\t\t\t\tv-bind:position=\"form.view.position\" \n\t\t\t\tv-bind:vertical=\"form.view.vertical\" \n\t\t\t\t@hide=\"form.hide()\"\n\t\t\t>\n\t\t\t\t<slot></slot>\n\t\t\t</b24-widget>\n\t\t</b24-form-container>\n\t"
    };
    var Definition$2 = {
      'b24-form': Form,
      'b24-form-inline': Inline,
      'b24-form-panel': Panel$1,
      'b24-form-popup': Popup$1,
      'b24-form-widget': Widget$1
    };

    var DefaultOptions$4 = {
      view: 'inline'
    };

    var Controller$m =
    /*#__PURE__*/
    function () {
      function Controller$$1() {
        var _this = this;

        var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DefaultOptions$4;
        babelHelpers.classCallCheck(this, Controller$$1);

        _id.set(this, {
          writable: true,
          value: void 0
        });

        babelHelpers.defineProperty(this, "view", {
          type: 'inline'
        });
        babelHelpers.defineProperty(this, "provider", {});
        babelHelpers.defineProperty(this, "languages", []);
        babelHelpers.defineProperty(this, "language", 'en');

        _handlers.set(this, {
          writable: true,
          value: {
            hide: [],
            show: []
          }
        });

        _fields$1.set(this, {
          writable: true,
          value: []
        });

        babelHelpers.defineProperty(this, "agreements", []);
        babelHelpers.defineProperty(this, "useSign", false);
        babelHelpers.defineProperty(this, "date", {
          dateFormat: 'DD.MM.YYYY',
          dateTimeFormat: 'DD.MM.YYYY HH:mm:ss',
          sundayFirstly: false
        });
        babelHelpers.defineProperty(this, "currency", {
          code: 'USD',
          title: '$',
          format: '$#'
        });

        _personalisation.set(this, {
          writable: true,
          value: {
            title: '',
            desc: ''
          }
        });

        babelHelpers.defineProperty(this, "validated", false);
        babelHelpers.defineProperty(this, "visible", true);
        babelHelpers.defineProperty(this, "loading", false);
        babelHelpers.defineProperty(this, "disabled", false);
        babelHelpers.defineProperty(this, "sent", false);
        babelHelpers.defineProperty(this, "error", false);
        babelHelpers.defineProperty(this, "stateText", '');
        babelHelpers.defineProperty(this, "stateButton", {
          text: '',
          handler: null
        });

        _vue.set(this, {
          writable: true,
          value: void 0
        });

        this.messages = new Storage();
        this.design = new Model();
        options = this.adjust(options);
        babelHelpers.classPrivateFieldSet(this, _id, options.id || Math.random().toString().split('.')[1] + Math.random().toString().split('.')[1]);
        this.provider = options.provider || {};

        if (this.provider.form) {
          this.loading = true;

          if (this.provider.form) {
            if (typeof this.provider.form === 'string') ; else if (typeof this.provider.form === 'function') {
              this.provider.form().then(function (options) {
                _this.adjust(options);

                _this.load();
              }).catch(function (e) {
                if (window.console && console.log) {
                  console.log('b24form get `user` error:', e.message);
                }
              });
            }
          }
        } else {
          this.load();

          if (this.provider.user) {
            if (typeof this.provider.user === 'string') ; else if (this.provider.user instanceof Promise) {
              this.provider.user.then(function (user) {
                _this.setValues(user);

                return user;
              }).catch(function (e) {
                if (window.console && console.log) {
                  console.log('b24form get `user` error:', e.message);
                }
              });
            } else if (babelHelpers.typeof(this.provider.user) === 'object') {
              this.setValues(this.provider.user);
            }
          }
        }

        this.render();
      }

      babelHelpers.createClass(Controller$$1, [{
        key: "load",
        value: function load() {
          if (babelHelpers.classPrivateFieldGet(this, _fields$1).length === 0) {
            this.disabled = true;
          }
        }
      }, {
        key: "show",
        value: function show() {
          var _this2 = this;

          this.visible = true;
          babelHelpers.classPrivateFieldGet(this, _handlers).show.forEach(function (handler) {
            return handler(_this2);
          });
        }
      }, {
        key: "hide",
        value: function hide() {
          var _this3 = this;

          this.visible = false;
          babelHelpers.classPrivateFieldGet(this, _handlers).hide.forEach(function (handler) {
            return handler(_this3);
          });
        }
      }, {
        key: "submit",
        value: function submit() {
          var _this4 = this;

          this.error = false;
          this.sent = false;

          if (!this.valid()) {
            return false;
          }

          if (!this.provider.submit) {
            return true;
          }

          var consents = this.agreements.reduce(function (acc, field) {
            acc[field.name] = field.value();
            return acc;
          }, {});
          this.loading = true;
          var formData = new FormData();
          formData.set('values', JSON.stringify(this.values()));
          formData.set('consents', JSON.stringify(consents));
          var promise;

          if (typeof this.provider.submit === 'string') {
            promise = window.fetch(this.provider.submit, {
              method: 'POST',
              mode: 'cors',
              cache: 'no-cache',
              headers: {
                'Origin': window.location.origin
              },
              body: formData
            });
          } else if (typeof this.provider.submit === 'function') {
            promise = this.provider.submit(this, formData);
          }

          promise.then(function (data) {
            _this4.sent = true;
            _this4.loading = false;
            _this4.stateText = data.message || _this4.messages.get('stateSuccess');
            var redirect = data.redirect || {};

            if (redirect.url) {
              var handler = function handler() {
                return window.location = redirect.url;
              };

              if (data.pay) {
                _this4.stateButton.text = _this4.messages.get('stateButtonPay');
                _this4.stateButton.handler = handler;
              }

              setTimeout(handler, (redirect.delay || 0) * 1000);
            }
          }).catch(function (e) {
            _this4.error = true;
            _this4.loading = false;
            _this4.stateText = _this4.messages.get('stateError');
          });
          return false;
        }
      }, {
        key: "setValues",
        value: function setValues(values) {
          if (!values || babelHelpers.typeof(values) !== 'object') {
            return;
          }

          if (babelHelpers.classPrivateFieldGet(this, _personalisation).title) {
            this.title = Conv.replaceText(babelHelpers.classPrivateFieldGet(this, _personalisation).title, values);
          }

          if (babelHelpers.classPrivateFieldGet(this, _personalisation).desc) {
            this.desc = Conv.replaceText(babelHelpers.classPrivateFieldGet(this, _personalisation).desc, values);
          }

          babelHelpers.classPrivateFieldGet(this, _fields$1).forEach(function (field) {
            if (!values[field.type] || !field.item()) {
              return;
            }

            field.item().value = field.format(values[field.type]);
          });
        }
      }, {
        key: "adjust",
        value: function adjust() {
          var _this5 = this;

          var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DefaultOptions$4;
          options = Object.assign({}, DefaultOptions$4, options);

          if (options.messages) {
            this.messages.setMessages(options.messages || {});
          }

          if (options.language) {
            this.language = options.language;
            this.messages.setLanguage(this.language);
          }

          if (options.languages) {
            this.languages = options.languages;
          } ////////////////////////////////////////


          if (options.handlers && babelHelpers.typeof(options.handlers) === 'object') {
            if (typeof options.handlers.hide === 'function') {
              babelHelpers.classPrivateFieldGet(this, _handlers).hide.push(options.handlers.hide);
            }

            if (typeof options.handlers.show === 'function') {
              babelHelpers.classPrivateFieldGet(this, _handlers).show(options.handlers.show);
            }
          }

          if (typeof options.title !== 'undefined') {
            babelHelpers.classPrivateFieldGet(this, _personalisation).title = options.title;
            this.title = Conv.replaceText(options.title, {});
          }

          if (typeof options.desc !== 'undefined') {
            babelHelpers.classPrivateFieldGet(this, _personalisation).desc = options.desc;
            this.desc = Conv.replaceText(options.desc, {});
          }

          if (typeof options.useSign !== 'undefined') {
            this.useSign = !!options.useSign;
          }

          if (babelHelpers.typeof(options.date) === 'object') {
            this.setDate(options.date);
          }

          if (babelHelpers.typeof(options.currency) === 'object') {
            this.setCurrency(options.currency);
          }

          if (Array.isArray(options.fields)) {
            this.setFields(options.fields);
          }

          if (Array.isArray(options.agreements)) {
            options.agreements.forEach(function (fieldOptions) {
              fieldOptions.messages = _this5.messages;
              fieldOptions.design = _this5.design;

              _this5.agreements.push(new Controller$g(fieldOptions));
            });
          }

          this.setView(options.view);
          this.buttonCaption = options.buttonCaption || this.messages.get('defButton');

          if (typeof options.visible !== 'undefined') {
            this.visible = !!options.visible;
          }

          if (typeof options.design !== 'undefined') {
            this.design.adjust(options.design);
          }

          if (options.node) {
            this.node = options.node;
          }

          if (!this.node) {
            this.node = document.createElement('div');
            document.body.appendChild(this.node);
          }

          return options;
        }
      }, {
        key: "setView",
        value: function setView(options) {
          var view = typeof (options || '') === 'string' ? {
            type: options
          } : options;

          if (typeof view.type !== 'undefined') {
            this.view.type = ViewTypes.includes(view.type) ? view.type : 'inline';
          }

          if (typeof view.position !== 'undefined') {
            this.view.position = ViewPositions.includes(view.position) ? view.position : null;
          }

          if (typeof view.vertical !== 'undefined') {
            this.view.vertical = ViewVerticals.includes(view.vertical) ? view.vertical : null;
          }

          if (typeof view.title !== 'undefined') {
            this.view.title = view.title;
          }

          if (typeof view.delay !== 'undefined') {
            this.view.delay = parseInt(view.delay);
            this.view.delay = isNaN(this.view.delay) ? 0 : this.view.delay;
          }
        }
      }, {
        key: "setDate",
        value: function setDate(date) {
          if (babelHelpers.typeof(date) !== 'object') {
            return;
          }

          if (date.dateFormat) {
            this.date.dateFormat = date.dateFormat;
          }

          if (date.dateTimeFormat) {
            this.date.dateTimeFormat = date.dateTimeFormat;
          }

          if (typeof date.sundayFirstly !== 'undefined') {
            this.date.sundayFirstly = date.sundayFirstly;
          }
        }
      }, {
        key: "setCurrency",
        value: function setCurrency(currency) {
          if (babelHelpers.typeof(currency) !== 'object') {
            return;
          }

          if (currency.code) {
            this.currency.code = currency.code;
          }

          if (currency.title) {
            this.currency.title = currency.title;
          }

          if (currency.format) {
            this.currency.format = currency.format;
          }
        }
      }, {
        key: "setFields",
        value: function setFields(fieldOptionsList) {
          var _this6 = this;

          babelHelpers.classPrivateFieldSet(this, _fields$1, []);
          var page = new Page(this.title);
          this.pager = new Navigation();
          this.pager.add(page);
          fieldOptionsList.forEach(function (options) {
            switch (options.type) {
              case 'page':
                page = new Page(options.label || _this6.title);

                _this6.pager.add(page);

                return;

              case 'date':
              case 'datetime':
                options.format = options.type === 'date' ? _this6.date.dateFormat : _this6.date.dateTimeFormat;
                options.sundayFirstly = _this6.date.sundayFirstly;
                break;

              case 'product':
                options.currency = _this6.currency;
                break;
            }

            options.messages = _this6.messages;
            options.design = _this6.design;
            var field = Factory.create(options);
            page.fields.push(field);
            babelHelpers.classPrivateFieldGet(_this6, _fields$1).push(field);
          });
          this.pager.removeEmpty();
          this.basket = new Basket(babelHelpers.classPrivateFieldGet(this, _fields$1), this.currency);
        }
      }, {
        key: "getId",
        value: function getId() {
          return babelHelpers.classPrivateFieldGet(this, _id);
        }
      }, {
        key: "delete",
        value: function _delete() {
          return null;
        }
      }, {
        key: "valid",
        value: function valid() {
          this.validated = true;
          return babelHelpers.classPrivateFieldGet(this, _fields$1).filter(function (field) {
            return !field.valid();
          }).length === 0 && this.agreements.every(function (field) {
            return field.requestConsent();
          });
        }
      }, {
        key: "values",
        value: function values() {
          return babelHelpers.classPrivateFieldGet(this, _fields$1).reduce(function (acc, field) {
            acc[field.name] = field.values();
            return acc;
          }, {});
        }
      }, {
        key: "isOnState",
        value: function isOnState() {
          return this.disabled || this.error || this.sent || this.loading;
        }
      }, {
        key: "render",
        value: function render() {
          //this.node.innerHTML = '';
          babelHelpers.classPrivateFieldSet(this, _vue, new yn({
            el: this.node,
            components: Definition$2,
            data: {
              form: this
            },
            template: "\n\t\t\t\t<component v-bind:is=\"'b24-form-' + form.view.type\"\n\t\t\t\t\t:key=\"form.id\"\n\t\t\t\t\t:form=\"form\"\n\t\t\t\t>\n\t\t\t\t\t<b24-form\n\t\t\t\t\t\tv-bind:key=\"form.id\"\n\t\t\t\t\t\tv-bind:form=\"form\"\n\t\t\t\t\t></b24-form>\n\t\t\t\t</component>\t\t\t\n\t\t\t"
          }));
        }
      }]);
      return Controller$$1;
    }();

    var _id = new WeakMap();

    var _handlers = new WeakMap();

    var _fields$1 = new WeakMap();

    var _personalisation = new WeakMap();

    var _vue = new WeakMap();

    /** @requires module:webpacker */

    /** @var {Object} module Current module.*/

    var Application =
    /*#__PURE__*/
    function () {
      function Application() {
        babelHelpers.classCallCheck(this, Application);

        _forms.set(this, {
          writable: true,
          value: []
        });

        _userProviderPromise.set(this, {
          writable: true,
          value: void 0
        });
      }

      babelHelpers.createClass(Application, [{
        key: "list",
        value: function list() {
          return babelHelpers.classPrivateFieldGet(this, _forms);
        }
      }, {
        key: "get",
        value: function get(id) {
          return babelHelpers.classPrivateFieldGet(this, _forms).filter(function (form) {
            return form.getId() === id;
          })[0];
        }
      }, {
        key: "create",
        value: function create(options) {
          var form = new Controller$m(options);
          babelHelpers.classPrivateFieldGet(this, _forms).push(form);
          return form;
        }
      }, {
        key: "remove",
        value: function remove(id) {}
      }, {
        key: "post",
        value: function post(uri, body, headers) {
          return window.fetch(uri, {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            headers: Object.assign(headers || {}, {
              'Origin': window.location.origin
            }),
            body: body
          });
        }
      }, {
        key: "createForm24",
        value: function createForm24(b24options, options) {
          options.provider = options.provider || {};

          if (!options.provider.user) {
            options.provider.user = this.getUserProvider24(b24options, options);
          }

          if (!options.provider.entities) {
            var entities = webPacker.url.parameter.get('b24form_entities');

            if (entities) {
              entities = JSON.parse(entities);

              if (babelHelpers.typeof(entities) === 'object') {
                options.provider.entities = entities;
              }
            }
          }

          options.provider.submit = this.getSubmitProvider24(b24options);

          if (b24options.lang) {
            options.language = b24options.lang;
          }

          options.languages = module.languages || [];
          options.messages = options.messages || {};
          options.messages = Object.assign(module.messages, options.messages || {});
          return this.create(options);
        }
      }, {
        key: "createWidgetForm24",
        value: function createWidgetForm24(b24options, options) {
          var pos = parseInt(BX.SiteButton.config.location) || 4;
          var positions = {
            1: ['left', 'top'],
            2: ['center', 'top'],
            3: ['right', 'top'],
            4: ['right', 'bottom'],
            5: ['center', 'bottom'],
            6: ['left', 'bottom']
          };
          options.view = {
            type: (options.fields || []).length <= 1 && (options.agreements || []).length <= 1 ? 'widget' : 'panel',
            position: positions[pos][0],
            vertical: positions[pos][1]
          };
          options.handlers = {
            hide: function hide() {
              BX.SiteButton.onWidgetClose();
            }
          };
          return b24form.App.createForm24(b24options, options);
        }
      }, {
        key: "getUserProvider24",
        value: function getUserProvider24(b24options) {
          var signTtl = 3600 * 24;
          var sign = webPacker.url.parameter.get('b24form_user');

          if (sign) {
            b24options.sign = sign;

            if (webPacker.ls.getItem('b24-form-sign', sign, signTtl)) {
              sign = null;
            }
          }

          var ttl = 3600 * 24 * 28;

          if (!sign) {
            if (b24form.user && babelHelpers.typeof(b24form.user) === 'object') {
              b24options.entities = b24options.entities || b24form.user.entities || [];
              return b24form.user.fields || {};
            }

            var user = webPacker.ls.getItem('b24-form-user', ttl);

            if (user !== null && babelHelpers.typeof(user) === 'object') {
              return user.fields || {};
            }
          }

          if (babelHelpers.classPrivateFieldGet(this, _userProviderPromise)) {
            return babelHelpers.classPrivateFieldGet(this, _userProviderPromise);
          }

          if (!sign) {
            return null;
          }

          webPacker.ls.setItem('b24-form-sign', sign, signTtl);
          var formData = new FormData();
          formData.set('security_sign', sign);
          formData.set('id', b24options.id);
          formData.set('sec', b24options.sec);
          babelHelpers.classPrivateFieldSet(this, _userProviderPromise, this.post(b24options.address + '/bitrix/services/main/ajax.php?action=crm.site.user.get', formData).then(function (response) {
            return response.json();
          }).then(function (data) {
            if (data.error) {
              throw new Error(data.error_description || data.error);
            }

            var user = data.result;
            user = user && babelHelpers.typeof(user) === 'object' ? user : {};
            user.fields = user && babelHelpers.typeof(user.fields) === 'object' ? user.fields : {};
            webPacker.ls.setItem('b24-form-user', user, ttl);
            return user.fields;
          }));
          return babelHelpers.classPrivateFieldGet(this, _userProviderPromise);
        }
      }, {
        key: "getSubmitProvider24",
        value: function getSubmitProvider24(b24options) {
          var _this = this;

          return function (form, formData) {
            var trace = b24options.usedBySiteButton && BX.SiteButton ? BX.SiteButton.getTrace() : window.b24Tracker && b24Tracker.guest ? b24Tracker.guest.getTrace() : null;
            formData.set('id', b24options.id);
            formData.set('sec', b24options.sec);
            formData.set('lang', form.language);
            formData.set('trace', trace);
            formData.set('entities', JSON.stringify(b24options.entities || []));
            formData.set('security_sign', b24options.sign);
            return _this.post(b24options.address + '/bitrix/services/main/ajax.php?action=crm.site.form.fill', formData).then(function (response) {
              return response.json();
            }).then(function (data) {
              if (data.error) {
                throw new Error(data.error_description || data.error);
              }

              data = data.result;
              return new Promise(function (resolve) {
                resolve(data);
              });
            });
          };
        }
      }, {
        key: "initFormScript24",
        value: function initFormScript24(b24options) {
          var _this2 = this;

          var options = b24options.data; // noinspection JSUnresolvedVariable

          if (b24options.usedBySiteButton) {
            this.createWidgetForm24(b24options, options);
            return;
          }

          var nodes = document.querySelectorAll('script[data-b24-form]');
          nodes = Array.prototype.slice.call(nodes);
          nodes.forEach(function (node) {
            if (node.hasAttribute('data-b24-loaded')) {
              return;
            }

            node.setAttribute('data-b24-loaded', true);
            var attributes = node.getAttribute('data-b24-form').split('/');

            if (attributes[1] !== b24options.id || attributes[2] !== b24options.sec) {
              return;
            }

            switch (attributes[0]) {
              case 'auto':
                setTimeout(function () {
                  _this2.createForm24(b24options, Object.assign({}, options, {
                    view: b24options.views.auto
                  })).show();
                }, (b24options.views.auto.delay || 1) * 1000);
                break;

              case 'click':
                var clickElement = node.nextElementSibling;

                if (clickElement) {
                  var form;
                  clickElement.addEventListener('click', function () {
                    if (!form) {
                      form = _this2.createForm24(b24options, Object.assign({}, options, {
                        view: b24options.views.click
                      }));
                    }

                    form.show();
                  });
                }

                break;

              default:
                var target = document.createElement('div');
                node.parentElement.insertBefore(target, node);

                _this2.createForm24(b24options, Object.assign({}, options, {
                  node: target
                }));

                break;
            }
          });
        }
      }]);
      return Application;
    }();

    var _forms = new WeakMap();

    var _userProviderPromise = new WeakMap();

    var App = new Application();

    exports.App = App;

}((this.b24form = this.b24form || {})));
//# sourceMappingURL=app.bundle.js.map
