/* Copyright (c) 2016 Eugene Rodionov

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE. */

/**
 * @module statemanager/redux/middleware/logger
 */
jn.define('statemanager/redux/middleware/logger', (require, exports, module) => {
	'use strict';

	function t(e, t)
	{
		e.super_ = t, e.prototype = Object.create(
			t.prototype,
			{ constructor: { value: e, enumerable: !1, writable: !0, configurable: !0 } },
		);
	}

	function r(e, t)
	{
		Object.defineProperty(this, 'kind', { value: e, enumerable: !0 }), t && t.length && Object.defineProperty(
			this,
			'path',
			{ value: t, enumerable: !0 },
		);
	}

	function n(e, t, r)
	{
		n.super_.call(this, 'E', e), Object.defineProperty(
			this,
			'lhs',
			{ value: t, enumerable: !0 },
		), Object.defineProperty(this, 'rhs', { value: r, enumerable: !0 });
	}

	function o(e, t)
	{
		o.super_.call(this, 'N', e), Object.defineProperty(this, 'rhs', { value: t, enumerable: !0 });
	}

	function i(e, t)
	{
		i.super_.call(this, 'D', e), Object.defineProperty(this, 'lhs', { value: t, enumerable: !0 });
	}

	function a(e, t, r)
	{
		a.super_.call(this, 'A', e), Object.defineProperty(
			this,
			'index',
			{ value: t, enumerable: !0 },
		), Object.defineProperty(this, 'item', { value: r, enumerable: !0 });
	}

	function f(e, t, r)
	{
		const n = e.slice((r || t) + 1 || e.length);

		return e.length = t < 0 ? e.length + t : t, e.push.apply(e, n), e;
	}

	function u(e)
	{
		const t = typeof e === 'undefined' ? 'undefined' : N(e);

		return t === 'object' ? (e === Math ? 'math' : e === null ? 'null' : Array.isArray(e) ? 'array' : Object.prototype.toString.call(
			e,
		) === '[object Date]' ? 'date' : typeof e.toString === 'function' && /^\/.*\//.test(e.toString()) ? 'regexp' : 'object') : t;
	}

	function l(e, t, r, c, s, d, p)
	{
		s = s || [], p = p || [];
		const g = [...s];
		if (typeof d !== 'undefined')
		{
			if (c)
			{
				if (typeof c === 'function' && c(g, d))
				{
					return;
				}

				if ((typeof c === 'undefined' ? 'undefined' : N(c)) === 'object')
				{
					if (c.prefilter && c.prefilter(g, d))
					{
						return;
					}

					if (c.normalize)
					{
						const h = c.normalize(g, d, e, t);
						h && (e = h[0], t = h[1]);
					}
				}
			}
			g.push(d);
		}
		u(e) === 'regexp' && u(t) === 'regexp' && (e = e.toString(), t = t.toString());
		const y = typeof e === 'undefined' ? 'undefined' : N(e);
		const v = typeof t === 'undefined' ? 'undefined' : N(t);
		const b = y !== 'undefined' || p && p[p.length - 1].lhs && p[p.length - 1].lhs.hasOwnProperty(d);
		const m = v !== 'undefined' || p && p[p.length - 1].rhs && p[p.length - 1].rhs.hasOwnProperty(d);
		if (!b && m)
		{
			r(new o(g, t));
		}
		else if (!m && b)
		{
			r(new i(g, e));
		}
		else if (u(e) !== u(t))
		{
			r(new n(
				g,
				e,
				t,
			));
		}
		else if (u(e) === 'date' && e - t !== 0)
		{
			r(new n(
				g,
				e,
				t,
			));
		}
		else if (y === 'object' && e !== null && t !== null)
		{
			if (p.some((t) => {
				return t.lhs === e;
			}))
			{
				e !== t && r(new n(g, e, t));
			}
			else
			{
				if (p.push({ lhs: e, rhs: t }), Array.isArray(e))
				{
					let w;
					e.length;
					for (w = 0; w < e.length; w++)
					{
						w >= t.length ? r(new a(g, w, new i(void 0, e[w]))) : l(
							e[w],
							t[w],
							r,
							c,
							g,
							w,
							p,
						);
					}

					while (w < t.length)
					{
						r(new a(g, w, new o(void 0, t[w++])));
					}
				}
				else
				{
					const x = Object.keys(e);
					let S = Object.keys(t);
					x.forEach((n, o) => {
						const i = S.indexOf(n);
						i >= 0 ? (l(e[n], t[n], r, c, g, n, p), S = f(S, i)) : l(e[n], void 0, r, c, g, n, p);
					}), S.forEach((e) => {
						l(void 0, t[e], r, c, g, e, p);
					});
				}
				p.length -= 1;
			}
		}
		else
		{
			e !== t && (y === 'number' && isNaN(e) && isNaN(t) || r(new n(g, e, t)));
		}
	}

	function c(e, t, r, n)
	{
		return n = n || [], l(e, t, (e) => {
			e && n.push(e);
		}, r), n.length > 0 ? n : void 0;
	}

	function s(e, t, r)
	{
		if (r.path && r.path.length > 0)
		{
			let n;
			let o = e[t];
			const i = r.path.length - 1;
			for (n = 0; n < i; n++)
			{
				o = o[r.path[n]];
			}

			switch (r.kind)
			{
				case 'A':
					s(o[r.path[n]], r.index, r.item);
					break;
				case 'D':
					delete o[r.path[n]];
					break;
				case 'E':
				case 'N':
					o[r.path[n]] = r.rhs;
			}
		}
		else
		{
			switch (r.kind)
			{
				case 'A':
					s(e[t], r.index, r.item);
					break;
				case 'D':
					e = f(e, t);
					break;
				case 'E':
				case 'N':
					e[t] = r.rhs;
			}
		}

		return e;
	}

	function d(e, t, r)
	{
		if (e && t && r && r.kind)
		{
			for (
				var n = e,
					o = -1,
					i = r.path ? r.path.length - 1 : 0; ++o < i;
			)
			{
				typeof n[r.path[o]] === 'undefined' && (n[r.path[o]] = typeof r.path[o] === 'number' ? [] : {}), n = n[r.path[o]];
			}

			switch (r.kind)
			{
				case 'A':
					s(r.path ? n[r.path[o]] : n, r.index, r.item);
					break;
				case 'D':
					delete n[r.path[o]];
					break;
				case 'E':
				case 'N':
					n[r.path[o]] = r.rhs;
			}
		}
	}

	function p(e, t, r)
	{
		if (r.path && r.path.length > 0)
		{
			let n;
			let o = e[t];
			const i = r.path.length - 1;
			for (n = 0; n < i; n++)
			{
				o = o[r.path[n]];
			}

			switch (r.kind)
			{
				case 'A':
					p(o[r.path[n]], r.index, r.item);
					break;
				case 'D':
					o[r.path[n]] = r.lhs;
					break;
				case 'E':
					o[r.path[n]] = r.lhs;
					break;
				case 'N':
					delete o[r.path[n]];
			}
		}
		else
		{
			switch (r.kind)
			{
				case 'A':
					p(e[t], r.index, r.item);
					break;
				case 'D':
					e[t] = r.lhs;
					break;
				case 'E':
					e[t] = r.lhs;
					break;
				case 'N':
					e = f(e, t);
			}
		}

		return e;
	}

	function g(e, t, r)
	{
		if (e && t && r && r.kind)
		{
			let n;
			let o;
			let i = e;
			for (
				o = r.path.length - 1, n = 0;
				n < o;
				n++
			)
			{
				typeof i[r.path[n]] === 'undefined' && (i[r.path[n]] = {}), i = i[r.path[n]];
			}

			switch (r.kind)
			{
				case 'A':
					p(i[r.path[n]], r.index, r.item);
					break;
				case 'D':
					i[r.path[n]] = r.lhs;
					break;
				case 'E':
					i[r.path[n]] = r.lhs;
					break;
				case 'N':
					delete i[r.path[n]];
			}
		}
	}

	function h(e, t, r)
	{
		if (e && t)
		{
			const n = function(n) {
				r && !r(e, t, n) || d(e, t, n);
			};
			l(e, t, n);
		}
	}

	function y(e)
	{
		return `color: ${F[e].color}; font-weight: bold`;
	}

	function v(e)
	{
		const t = e.kind;
		const r = e.path;
		const n = e.lhs;
		const o = e.rhs;
		const i = e.index;
		const a = e.item;
		switch (t)
		{
			case 'E':
				return [r.join('.'), n, '→', o];
			case 'N':
				return [r.join('.'), o];
			case 'D':
				return [r.join('.')];
			case 'A':
				return [`${r.join('.')}[${i}]`, a];
			default:
				return [];
		}
	}

	function b(e, t, r, n)
	{
		const o = c(e, t);
		try
		{
			n ? r.groupCollapsed('diff') : r.group('diff');
		}
		catch
		{
			r.log('diff');
		}
		o ? o.forEach((e) => {
			const t = e.kind;
			const n = v(e);
			r.log.apply(r, [`%c ${F[t].text}`, y(t)].concat(P(n)));
		}) : r.log('—— no diff ——');
		try
		{
			r.groupEnd();
		}
		catch
		{
			r.log('—— diff end —— ');
		}
	}

	function m(e, t, r, n)
	{
		switch (typeof e === 'undefined' ? 'undefined' : N(e))
		{
			case 'object':
				return typeof e[n] === 'function' ? e[n].apply(e, P(r)) : e[n];
			case 'function':
				return e(t);
			default:
				return e;
		}
	}

	function w(e)
	{
		const t = e.timestamp;
		const r = e.duration;

		return function(e, n, o) {
			const i = ['action'];

			return i.push(`%c${String(e.type)}`), t && i.push(`%c@ ${n}`), r && i.push(`%c(in ${o.toFixed(2)} ms)`), i.join(
				' ',
			);
		};
	}

	function x(e, t)
	{
		const r = t.logger;
		const n = t.actionTransformer;
		const o = t.titleFormatter;
		const i = void 0 === o ? w(t) : o;
		const a = t.collapsed;
		const f = t.colors;
		const u = t.level;
		const l = t.diff;
		const c = typeof t.titleFormatter === 'undefined';
		e.forEach((o, s) => {
			const d = o.started;
			const p = o.startedTime;
			const g = o.action;
			const h = o.prevState;
			const y = o.error;
			let v = o.took;
			let w = o.nextState;
			const x = e[s + 1];
			x && (w = x.prevState, v = x.started - d);
			const S = n(g);
			const k = typeof a === 'function' ? a(() => {
				return w;
			}, g, o) : a;
			const j = D(p);
			const E = f.title ? `color: ${f.title(S)};` : '';
			const A = ['color: gray; font-weight: lighter;'];
			A.push(E), t.timestamp && A.push('color: gray; font-weight: lighter;'), t.duration && A.push(
				'color: gray; font-weight: lighter;',
			);
			const O = i(S, j, v);
			try
			{
				k ? (f.title && c ? r.groupCollapsed.apply(
					r,
					[`%c ${O}`, ...A],
				) : r.groupCollapsed(O)) : (f.title && c ? r.group.apply(r, [`%c ${O}`, ...A]) : r.group(O));
			}
			catch
			{
				r.log(O);
			}
			const N = m(u, S, [h], 'prevState');
			const P = m(u, S, [S], 'action');
			const C = m(u, S, [y, h], 'error');
			const F = m(u, S, [w], 'nextState');
			if (N)
			{
				if (f.prevState)
				{
					const L = `color: ${f.prevState(h)}; font-weight: bold`;
					r[N]('%c prev state', L, h);
				}
				else
				{
					r[N]('prev state', h);
				}
			}

			if (P)
			{
				if (f.action)
				{
					const T = `color: ${f.action(S)}; font-weight: bold`;
					r[P]('%c action    ', T, S);
				}
				else
				{
					r[P]('action    ', S);
				}
			}

			if (y && C)
			{
				if (f.error)
				{
					const M = `color: ${f.error(y, h)}; font-weight: bold;`;
					r[C]('%c error     ', M, y);
				}
				else
				{
					r[C]('error     ', y);
				}
			}

			if (F)
			{
				if (f.nextState)
				{
					const _ = `color: ${f.nextState(w)}; font-weight: bold`;
					r[F]('%c next state', _, w);
				}
				else
				{
					r[F]('next state', w);
				}
			}
			l && b(h, w, r, k);
			try
			{
				r.groupEnd();
			}
			catch
			{
				r.log('—— log end ——');
			}
		});
	}

	function S()
	{
		const e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : {};
		const t = { ...L, ...e };
		const r = t.logger;
		const n = t.stateTransformer;
		const o = t.errorTransformer;
		const i = t.predicate;
		const a = t.logErrors;
		const f = t.diffPredicate;
		if (typeof r === 'undefined')
		{
			return function() {
				return function(e) {
					return function(t) {
						return e(t);
					};
				};
			};
		}

		if (e.getState && e.dispatch)
		{
			return console.error(
				'[redux-logger] redux-logger not installed. Make sure to pass logger instance as middleware:\n// Logger with default options\nimport { logger } from \'redux-logger\'\nconst store = createStore(\n  reducer,\n  applyMiddleware(logger)\n)\n// Or you can create your own logger with custom options http://bit.ly/redux-logger-options\nimport createLogger from \'redux-logger\'\nconst logger = createLogger({\n  // ...options\n});\nconst store = createStore(\n  reducer,\n  applyMiddleware(logger)\n)\n',
			), function() {
				return function(e) {
					return function(t) {
						return e(t);
					};
				};
			};
		}
		const u = [];

		return function(e) {
			const r = e.getState;

			return function(e) {
				return function(l) {
					if (typeof i === 'function' && !i(r, l))
					{
						return e(l);
					}
					const c = {};
					u.push(c), c.started = O.now(), c.startedTime = new Date(), c.prevState = n(r()), c.action = l;
					let s = void 0;
					if (a)
					{
						try
						{
							s = e(l);
						}
						catch (e)
						{
							c.error = o(e);
						}
					}
					else
					{
						s = e(l);
					}
					c.took = O.now() - c.started, c.nextState = n(r());
					const d = t.diff && typeof f === 'function' ? f(r, l) : t.diff;
					if (x(u, { ...t, diff: d }), u.length = 0, c.error)
					{
						throw c.error;
					}

					return s;
				};
			};
		};
	}

	let k;
	let j;
	const E = function(e, t) {
		return new Array(t + 1).join(e);
	};

	const A = function(e, t) {
		return E('0', t - e.toString().length) + e;
	};

	var D = function(e) {
		return `${A(e.getHours(), 2)}:${A(e.getMinutes(), 2)}:${A(
			e.getSeconds(),
			2,
		)}.${A(e.getMilliseconds(), 3)}`;
	};
	var O = typeof performance !== 'undefined' && performance !== null && typeof performance.now === 'function' ? performance : Date;
	var N = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol' ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol === 'function' && e.constructor === Symbol && e !== Symbol.prototype ? 'symbol' : typeof e;
	};

	var P = function(e) {
		if (Array.isArray(e))
		{
			for (
				var t = 0,
					r = Array.from({ length: e.length }); t < e.length; t++
			)
			{
				r[t] = e[t];
			}

			return r;
		}

		return [...e];
	};
	let C = [];
	k = (typeof global === 'undefined' ? 'undefined' : N(global)) === 'object' && global ? global : (typeof window === 'undefined' ? {} : window), j = k.DeepDiff, j && C.push(
		() => {
			typeof j !== 'undefined' && k.DeepDiff === c && (k.DeepDiff = j, j = void 0);
		},
	), t(n, r), t(o, r), t(i, r), t(a, r), Object.defineProperties(
		c,
		{
			diff: { value: c, enumerable: !0 },
			observableDiff: { value: l, enumerable: !0 },
			applyDiff: { value: h, enumerable: !0 },
			applyChange: { value: d, enumerable: !0 },
			revertChange: { value: g, enumerable: !0 },
			isConflict: {
				value()
				{
					return typeof j !== 'undefined';
				},
				enumerable: !0,
			},
			noConflict: {
				value()
				{
					return C && (C.forEach((e) => {
						e();
					}), C = null), c;
				},
				enumerable: !0,
			},
		},
	);
	var F = {
		E: { color: '#2196f3', text: 'CHANGED:' },
		N: { color: '#4caf50', text: 'ADDED:' },
		D: { color: '#f44336', text: 'DELETED:' },
		A: { color: '#2196f3', text: 'ARRAY:' },
	};
	var L = {
		level: 'log',
		logger: console,
		logErrors: !0,
		collapsed: void 0,
		predicate: void 0,
		duration: !1,
		timestamp: !0,
		stateTransformer(e)
		{
			return e;
		},
		actionTransformer(e)
		{
			return e;
		},
		errorTransformer(e)
		{
			return e;
		},
		colors: {
			title()
			{
				return 'inherit';
			},
			prevState()
			{
				return '#9e9e9e';
			},
			action()
			{
				return '#03a9f4';
			},
			nextState()
			{
				return '#4caf50';
			},
			error()
			{
				return '#f20404';
			},
		},
		diff: !1,
		diffPredicate: void 0,
		transformer: void 0,
	};

	const T = function() {
		const e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : {};
		const t = e.dispatch;
		const r = e.getState;

		return typeof t === 'function' || typeof r === 'function' ? S()({
			dispatch: t,
			getState: r,
		}) : void console.error(
			'\n[redux-logger v3] BREAKING CHANGE\n[redux-logger v3] Since 3.0.0 redux-logger exports by default logger with default settings.\n[redux-logger v3] Change\n[redux-logger v3] import createLogger from \'redux-logger\'\n[redux-logger v3] to\n[redux-logger v3] import { createLogger } from \'redux-logger\'\n',
		);
	};

	module.exports = {
		logger: T,
	};
});
