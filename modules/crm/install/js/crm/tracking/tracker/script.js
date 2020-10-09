;(function () {

	"use strict";

	/** @requires module:webpacker */
	/** @requires {Object} module */
	if(typeof webPacker === "undefined")
	{
		return;
	}

	function ElementEngine(params)
	{
		this.setTypes(params.types);
		this.setMap(params.map);
	}
	ElementEngine.prototype = {
		searched: null,
		setTypes: function (types)
		{
			this.types = types || [];
			return this;
		},
		setMap: function (map)
		{
			this.map = map || [];
			return this;
		},
		getFromHref: function (element, type)
		{
			var value = element.href || '';
			if (!value)
			{
				return null;
			}
			value = value.substr(element.protocol.length || 0).trim();

			return {
				'node': element,
				'values': [{
					'type': type,
					'value': value,
					'cleaned': Util.clean(type, value)
				}]
			};
		},
		isItemValid: function (item)
		{
			if (!item)
			{
				return false;
			}

			if (!item.values)
			{
				return false;
			}

			if (item.values.length === 0)
			{
				return false;
			}

			return !!item.node;
		},
		getElementsBySelector: function (context, selector)
		{
			return webPacker.type.toArray(context.querySelectorAll(selector));
		},
		replaceItem: function (item)
		{
			if (this.map.length === 0)
			{
				return;
			}

			var href = item.node.href;
			item.values.forEach(function (value) {
				var result = Util.replaceByMap(this.map, href, value.type, value.value, true);
				href = result.text;
				item.replaced = {from: result.from, to: result.to};
			}, this);

			if (href)
			{
				item.node.href = href;
			}

			this.textEngine.replace(item.node);
		},
		replace: function (context)
		{
			context = context || document.body;

			this.textEngine = new TextEngine({'types': this.types, 'map': this.map});
			this.search(context).forEach(this.replaceItem, this);
		},
		getSearched: function (context)
		{
			return this.searched || this.search(context);
		},
		search: function (context)
		{
			context = context || document.body;

			var items = [];
			this.types.forEach(function (type) {
				var elements = this.getElementsBySelector(context, type.selector);
				items = items.concat(elements.map(function (element) {
					return this.getFromHref(element, type.code);
				}, this));
			}, this);

			var searched = items.filter(this.isItemValid, this);
			if (!this.searched)
			{
				this.searched = searched;
			}

			return searched;
		}
	};

	function TextEngine(params)
	{
		this.setTypes(params.types);
		this.setMap(params.map);
		this.setEnrich(params.isEnrichTextual);
	}
	TextEngine.prototype = {
		searched: null,
		minTextLength: 6, // min length of phone (332211) or email (x@yy.zz)
		setTypes: function (types)
		{
			this.types = types || [];
			return this;
		},
		setMap: function (map)
		{
			this.map = map || [];
			return this;
		},
		setEnrich: function (isEnrichTextual)
		{
			this.isEnrichTextual = isEnrichTextual || false;
			return this;
		},
		getNodesByChildNodes: function (context)
		{
			var items = [];
			if (!context.hasChildNodes())
			{
				return items;
			}

			for (var i = 0; i < context.childNodes.length; i++)
			{
				var node = context.childNodes.item(i);
				if (node.nodeType === 3) //#text
				{
					if (this.checkTextNode(node))
					{
						items.push(node);
					}
				}
				else
				{
					var textNodes = this.getNodesByChildNodes(node, this.minTextLength);
					if (textNodes.length >  0)
					{
						items = items.concat(textNodes);
					}
				}
			}

			return items;
		},
		getNodesByXPath: function (context)
		{
			// all text nodes(not of script and style elements) with length > 6
			var nodes = [], node;
			var elementsXPath = 'descendant-or-self::*[not(self::script|style|noscript)]/text()[string-length(normalize-space()) >= ' + this.minTextLength + ']';
			var result = context.ownerDocument.evaluate(elementsXPath, context, null, XPathResult.ANY_TYPE, null);
			while(node = result.iterateNext())
			{
				nodes.push(node)
			}

			return nodes;
		},
		getNodesByTreeWalker: function (context)
		{
			var node, nodes = [];
			var walk = context.ownerDocument.createTreeWalker(
				context,
				NodeFilter.SHOW_TEXT,
				null,
				false
			);
			while (node = walk.nextNode())
			{
				if (this.checkTextNode(node))
				{
					nodes.push(node);
				}
			}

			return nodes;
		},
		getNodesByNodeIterator: function (context)
		{
			var node, nodes = [];
			var iterator = context.ownerDocument.createNodeIterator(context, NodeFilter.SHOW_TEXT, null);
			while (node = iterator.nextNode())
			{
				if (this.checkTextNode(node))
				{
					nodes.push(node);
				}
			}

			return nodes;
		},
		checkTextNode: function (node)
		{
			switch (node.parentNode.nodeName)
			{
				case 'NOSCRIPT':
				case 'SCRIPT':
				case 'STYLE':
					return false;
			}

			var text = (node.textContent || '').trim();
			return text.length >= this.minTextLength;
		},
		prepareTextItem: function (node)
		{
			var item = {
				'node': node,
				'values': []
			};

			var text = (node.nodeValue || '').trim();
			if (!text)
			{
				return item;
			}

			this.types.forEach(function (type) {
				item.values = this.parseValues(text, type.regexp)
					.map(function (value) {
						return {
							'type': type.code,
							'value': value,
							'cleaned': Util.clean(type.code, value)
						};
					})
					.concat(item.values);
			}, this);

			return item;
		},
		parseValues: function (text, pattern)
		{
			var matches = text.match(pattern);
			if (!matches || matches.length === 0)
			{
				return [];
			}

			return matches.map(Util.trim).filter(Util.hasLength);
		},

		replaceItem: function (item)
		{
			var initialText = item.node.nodeValue;
			var text = initialText;

			item.values.forEach(function (value) {
				var result = Util.replaceByMap(this.map, text, value.type, value.value);
				if (result.text)
				{
					text = result.text;
				}
				if (!item.replaced)
				{
					item.replaced = {from: result.from, to: result.to};
				}
			}, this);


			if (this.isEnrichTextual)
			{
				TextConverter.enrich(this.map, item, text);
			}
			else if (text !== initialText)
			{
				item.node.nodeValue = text;
			}
		},

		uniqueItemValues: function (values)
		{
			return Util.uniqueArray(values, function (a, b) {
				return a.value === b.value && a.type === b.type;
			});
		},

		replace: function (context)
		{
			context = context || document.body;
			if (!context)
			{
				return;
			}

			this.search(context).forEach(this.replaceItem, this);
		},

		getSearched: function (context, method)
		{
			return this.searched || this.search(context, method);
		},
		search: function (context, method)
		{
			context = context || document.body;
			method = method || null;

			var methodXpath = context.ownerDocument.evaluate ? this.getNodesByXPath : null;
			var methodTreeWalker = context.ownerDocument.createTreeWalker ? this.getNodesByTreeWalker : null;
			var methodNodeIterator = context.ownerDocument.createNodeIterator ? this.getNodesByNodeIterator : null;
			var methodChildNodes = this.getNodesByChildNodes;

			if (!method)
			{
				var defaultMethodScheme = 1;
				switch (defaultMethodScheme)
				{
					case 1:
						method = methodXpath || methodTreeWalker || methodNodeIterator || methodChildNodes;
						break;
					case 2:
						method = methodTreeWalker || methodNodeIterator || methodXpath || methodChildNodes;
						break;
					case 0:
					default:
						method = methodTreeWalker || methodChildNodes;
						break;
				}
			}

			var searched = (
				method
					.apply(this, [context])
					.map(this.prepareTextItem, this)
					.filter(function (item) {
						var hasValues = item.values.length > 0;
						if (hasValues)
						{
							item.values = this.uniqueItemValues(item.values);
						}
						return hasValues;
					}, this)
			);

			if (!this.searched)
			{
				this.searched = searched;
			}

			return searched;
		}
	};


	var TextConverter = {
		getMappedValues: function (map, values)
		{
			var finalFormattedValues = [];
			values.forEach(function (value) {
				finalFormattedValues = Util.filterMap(map, value.type, value.value)
					.map(function (mapItem) {
						return {
							'type': value.type,
							'cleaned': mapItem.final.cleaned,
							'formatted': mapItem.final.formatted
						};
					})
					.concat(finalFormattedValues);
			}, this);

			return finalFormattedValues;
		},
		getHtmlByType: function (type, formattedValue, cleanedValue)
		{
			var a = document.createElement('a');
			a.textContent = formattedValue;
			switch (type)
			{
				case 'phone':
					a.href = 'tel:' + cleanedValue;
					break;
				case 'email':
					a.href = 'mailto:' + cleanedValue;
					break;
			}

			return a.outerHTML;
		},
		split: function (text, type, formattedValue, cleanedValue)
		{
			var list = text.split(formattedValue).map(function(piece){
				return {
					'text': piece,
					'isHtml': false
				};
			});

			var valueHtml = this.getHtmlByType(type, formattedValue, cleanedValue);
			var result = [];
			for (var i = 0; i < list.length; i++)
			{
				result.push(list[i]);
				if (i !== (list.length - 1))
				{
					result.push({
						'text': valueHtml,
						'isHtml': true
					});
				}
			}

			return result;
		},
		enrichValue: function (pieces, type, formattedValue, cleanedValue)
		{
			var result = [];
			pieces.forEach(function (piece) {
				if (piece.isHtml)
				{
					result.push(piece);
					return;
				}

				result = result.concat(this.split(piece.text, type, formattedValue, cleanedValue));
			}, this);

			return result;
		},
		enrich: function (map, item, text)
		{
			var values = this.getMappedValues(map, item.values);
			var pieces = [{'text': text, 'isHtml': false}];
			values.forEach(function (value) {
				pieces = this.enrichValue(pieces, value.type, value.formatted, value.cleaned);
			}, this);

			if (pieces.length <= 1)
			{
				item.node.nodeValue = text;
			}
			else
			{
				var containerNode = document.createElement('DIV');
				var parentNode = item.node.parentNode;
				pieces.forEach(function (piece) {
					var nodeList = [];
					if (piece.isHtml)
					{
						containerNode.innerHTML = piece.text;
						nodeList = nodeList.concat(webPacker.type.toArray(containerNode.childNodes));
					}
					else
					{
						nodeList.push(document.createTextNode(piece.text));
					}

					nodeList.forEach(function (node) {
						parentNode.insertBefore(node, item.node);
					});
				});

				parentNode.removeChild(item.node);
				//item.node = parentNode;
			}
		}
	};


	var Util = {
		trim: function(v)
		{
			return (v || '').trim();
		},
		hasLength: function(v)
		{
			return (v || '').length > 0;
		},
		clean: function (type, value)
		{
			return (type === 'phone' ? Util.cleanPhone(value) : Util.trim(value));
		},
		cleanPhone: function (value)
		{
			return value.trim().replace(/[^\d+]/gim, '');
		},
		filterMap: function (map, type, formattedValue)
		{
			var cleanedValue = Util.clean(type, formattedValue);
			if (!cleanedValue)
			{
				return [];
			}

			return map.filter(function (item) {
				return item.origin.cleaned === cleanedValue;
			});
		},
		replaceByMap: function (map, text, type, formattedValue, replaceByCleanValue)
		{
			var result = {text: '', from: null, to: null};
			replaceByCleanValue = replaceByCleanValue || false;

			if (!text)
			{
				return result;
			}

			var escapedValue = formattedValue.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
			var finalText = text;
			Util.filterMap(map, type, formattedValue).forEach(function (item) {
				var finalValue = replaceByCleanValue ? item.final.cleaned : item.final.formatted;
				result.from = formattedValue;
				result.to = finalValue;
				finalText = finalText.replace(new RegExp(escapedValue, 'g'), finalValue);
			});

			result.text = finalText === text ? '' : finalText;
			return result;
		},
		uniqueArray: function (list, compareFunction)
		{
			compareFunction = compareFunction || function (a, b) {
				return a === b;
			};

			var result = [];
			list.forEach(function (value) {
				var isExists = result.some(function (resultValue) {
					return compareFunction.apply(this, [value, resultValue]);
				});
				if (!isExists)
				{
					result.push(value);
				}
			});

			return result;
		}
	};

	var Helper = {
		context: null,
		urlParameters: null,
		getUtmArray: function ()
		{
			var list = webPacker.url.parameter.getList().filter(function (item) {
				return item.name === 'utm_source';
			});

			var key = 'b24-tracker-utm';
			if (list.length > 0)
			{
				webPacker.ls.setItem(key, list);
			}

			return webPacker.ls.getItem(key) || [];
		},
		ready: function (handler)
		{
			/compl|loaded|intera/.test(document.readyState)
				? handler()
				: this.addEventListener(document, 'DOMContentLoaded', handler)
			;
		}
	};

	var Performance = new function ()
	{
		this.items = {};

		this.now = function ()
		{
			if (window.performance && window.performance.now)
			{
				return window.performance.now();
			}

			return null;
		};
		this.start = function (tag)
		{
			if (!this.items[tag])
			{
				this.items[tag] = {
					'start': null,
					'end': null,
					'time': null
				};
			}

			this.items[tag].start = this.now();
		};
		this.end = function (tag)
		{
			if (!this.items[tag])
			{
				return;
			}

			var item = this.items[tag];
			item.end = this.now();

			if (!item.start)
			{
				return;
			}

			if (!item.time)
			{
				item.time = 0;
			}
			item.time += item.end - item.start;
		};
		this.dump = function ()
		{
			for(var tag in this.items)
			{
				if (!this.items.hasOwnProperty(tag))
				{
					continue;
				}

				if (!this.items[tag])
				{
					return;
				}
				var v = Math.round(this.items[tag].time);
				(console || {}).log('Perf,', tag + ': ', v, 'ms');
			}
		};
	};

	var Duplicates = {
		log: [],
		resolve: function (list)
		{
			this.log = [];
			this.makeGroups(list).forEach(this.hideGroup, this);
		},
		makeGroups: function (list)
		{
			var parentNodes = list.map(function (node) {
				return node.parentNode;
			});
			parentNodes = Util.uniqueArray(parentNodes);

			return parentNodes.map(function (parentNode) {
				return list.filter(function (comparedNode) {
					return parentNode !== comparedNode && parentNode === comparedNode.parentNode;
				});
			}).filter(function (groupNodes) {
				return groupNodes.length > 1;
			});
		},
		hideGroup: function (group)
		{
			group.forEach(function (node, index) {
				if (index === 0)
				{
					return;
				}

				node.style.display = 'none';
				this.log.push(node);
			}, this);
		},
		dump: function ()
		{
			(console || {}).log('hidden nodes: ', this.log);
		}
	};

	var ClickTracker = {
		tracked: [],
		init: function ()
		{
			if (!window.b24Tracker || !window.b24Tracker.guest)
			{
				return;
			}

			if (webPacker.browser.isMobile())
			{
				var items = Manager.Instance.getElementEngine().search();
				items.filter(function (item) {
					return item.values[0].type === 'phone';
				}).forEach(function (item) {
					if (!item.node || item.node.isTrackingHandled)
					{
						return;
					}

					webPacker.addEventListener(item.node, 'click', this.storeTrace.bind(this, item.values[0].value));
					item.node.isTrackingHandled = true;
				}, this);
			}

			if (Manager.Instance.source && window.b24Tracker.guest.isUtmSourceDetected())
			{
				var source = Manager.Instance.source;
				if (source.replacement.phone[0])
				{
					this.storeTrace(source.replacement.phone[0], true);
				}
			}
		},
		storeTrace: function (value, skipMarking)
		{
			if (this.tracked.indexOf(value) >= 0)
			{
				return;
			}

			var trace = window.b24Tracker.guest.getTrace({
				channels: [{code: 'call', value: value}]
			});
			window.b24Tracker.guest.storeTrace(trace);
			if (!skipMarking)
			{
				this.tracked.push(value);
			}
		}
	};


	function Manager ()
	{
		this.types = [
			{
				'code': 'phone',
				'selector': 'a[href^="tel:"], a[href^="callto:"]',
				'regexp': /([+]?([\d][- ()\u00A0]{0,2}){5,15}[\d])/gi, //'regexp': /([\+]?([\d][- \(\)\u00A0]{0,2}){6,16})/gi,
				'cleaner': Util.cleanPhone
			},
			{
				'code': 'email',
				'selector': 'a[href^="mailto:"]',
				'regexp': /([-_.\w\d]+@[-_.\w\d]+\.\w{2,15})/gi, //'regexp': /([\w\.\d-_]+@[\w\.\d-_]+\.\w{2,15})/gi,
				'cleaner': Util.trim
			}
		];

		this.map = [];
		this.perf = Performance;
		this.duplicates = Duplicates;

		this.enrichText = false;
		this.replaceText = false;
		this.resolveDup = false;
		this.source = null;
		this.site = null;
		this.loaded = false;
	}
	Manager.Instance = null;
	Manager.prototype = {
		load: function (options)
		{
			if (webPacker.url.parameter.get('b24_tracker_debug_enabled') === 'y')
			{
				debugger;
			}

			if (this.loaded)
			{
				return;
			}
			this.loaded = true;

			options = options || {};
			options.editor = options.editor || {resources: []};

			options.b24SiteOnly = EditorStatus.init(options);

			if (["complete", "loaded", "interactive"].indexOf(document.readyState) > -1)
			{
				this.run(options);
			}
			else
			{
				webPacker.addEventListener(window, 'DOMContentLoaded', this.run.bind(this, options))
			}
		},
		run: function (options)
		{
			if (!options.enabled)
			{
				return;
			}

			Performance.start('Load');
			this.configure(options);
			Performance.end('Load');

			this.replace();
			if (this.map.length > 0 && this.source)
			{
				this.resolveDuplicates();
			}

			this.trackClicks();
		},
		configure: function (options)
		{
			this.map = [];

			var site = options.sites.filter(function (site) {
				if (site.host === 'all')
				{
					return true;
				}
				if (options.b24SiteOnly && !site.b24)
				{
					return false;
				}

				var a = document.createElement('a');
				var hosts = webPacker.type.isArray(site.host) ? site.host : [site.host];
				hosts = hosts.map(function (host) {
					a.href = 'http://' + host;
					return a.hostname;
				});

				return hosts.indexOf(window.location.hostname) > -1;
			})[0];
			if (!site)
			{
				return;
			}

			this.enrichText = site.enrichText || false;
			this.replaceText = site.replaceText || false;
			this.resolveDup = site.resolveDup || false;

			var utmSource;
			if (window.b24Tracker && window.b24Tracker.guest)
			{
				utmSource = window.b24Tracker.guest.getUtmSource();
			}
			else
			{
				utmSource = Helper.getUtmArray().filter(function(tag) {
					return tag.name === 'utm_source';
				});
				utmSource = utmSource[0] ? utmSource[0].value : '';
			}

			var source = options.sources.filter(function (source) {
				return source.utm.filter(function (sourceUtmSouce) {
					return sourceUtmSouce === utmSource;
				}).length > 0;
			})[0];

			if (site.replacement === 'all')
			{
				site.replacement = this.search();
			}

			var types = this.types;
			types = types.reduce(function (accumulator, item) {
				accumulator[item.code] = item;
				return accumulator;
			}, {});

			this.map = site.replacement
				.filter(function (item) {
					return !!types[item.type];
				})
				.map(function (item) {
					var type = types[item.type];
					var final = item.value;
					if (source && source.replacement[item.type])
					{
						var repl = source.replacement[item.type];
						var filtered = [];
						filtered = filtered.length > 0
							? filtered
							: repl.filter(function (replItem) {
								return typeof replItem === 'string' || replItem.host === site.host;
							});
						filtered = filtered.length > 0
							? filtered
							: repl.filter(function (replItem) {
								return !replItem.host;
							});
						filtered = filtered.length > 0
							? filtered
							: repl;

						final = filtered.length > 0
							? typeof filtered[0] === 'string'
								? filtered[0]
								: filtered[0].value
							: final
					}

					return {
						'origin': {
							'cleaned': type.cleaner(item.value),
							'formatted': [item.value]
						},
						'final': {
							'cleaned': type.cleaner(final),
							'formatted': final
						}
					};
				}, this);

			this.site = site;
			this.source = source;
		},
		getElementEngine: function ()
		{
			if (!this.elementEngine)
			{
				this.elementEngine = new ElementEngine({});
			}

			return this.elementEngine.setTypes(this.types).setMap(this.map);
		},
		getTextEngine: function ()
		{
			if (!this.textEngine)
			{
				this.textEngine = new TextEngine({});
			}

			return this.textEngine.setTypes(this.types).setMap(this.map).setEnrich(this.enrichText);
		},
		replace: function (context)
		{
			context = context || document.body;

			var tagElement = 'Element replace';
			Performance.start(tagElement);

			this.getElementEngine().replace(context);

			Performance.end(tagElement);

			if (this.replaceText)
			{
				var tagText = 'Global text replace';
				Performance.start(tagText);

				this.getTextEngine().replace(context);

				Performance.end(tagText);
			}
		},
		resolveDuplicates: function ()
		{
			if (!this.resolveDup)
			{
				return;
			}

			this.types.forEach(function (type) {
				var list = document.body.querySelectorAll(type.selector);
				list = webPacker.type.toArray(list);
				if (list.length === 0)
				{
					return;
				}

				this.duplicates.resolve(list);
			}, this);
		},
		trackClicks: function ()
		{
			ClickTracker.init();
		},
		searchNodes: function (context)
		{
			context = context || document.body;
			var items = this.getElementEngine().search(context);
			return this.getTextEngine().search(context).concat(items);
		},
		getSearchedNodes: function (context)
		{
			context = context || document.body;
			var elementItems = this.getElementEngine().getSearched(context);
			var textItems = this.getTextEngine().getSearched(context);

			textItems = textItems.filter(function(item) {
				var isChild = elementItems.some(function (elementItem) {
					return elementItem.node.contains(item.node);
				});
				if (isChild || !item.node.parentNode)
				{
					return false;
				}

				item.node = item.node.parentNode;
				return true;
			});

			return elementItems.concat(textItems);
		},
		search: function (context)
		{
			context = context || document.body;

			var result = [];
			this.searchNodes(context).forEach(function (item) {
				item.values.forEach(function (value) {
					var existed = result.some(function (resultValue) {
						return (
							resultValue.value === value.value
							&&
							resultValue.type === value.type
						);
					});
					if (!existed)
					{
						result.push(value);
					}
				});
			});

			return result;
		}
	};

	var EditorStatus = {
		checkingName: 'b24_tracker_checking_origin',
		debugName: 'bx_debug',
		debug: false,
		timeout: 600,
		options: {},
		fields: {
			list: null,
			name: 'b24_tracker_edit_enabled',
			get: function (key)
			{
				this.restore();
				return this.list[key] ? this.list[key] : null;
			},
			set: function (key, value)
			{
				this.restore();
				this.list[key] = value;
				webPacker.ls.setItem(this.name, this.list);
				return this;
			},
			clear: function ()
			{
				webPacker.ls.removeItem(this.name);
			},
			restore: function ()
			{
				if (this.list !== null)
				{
					return;
				}

				this.list = webPacker.ls.getItem(this.name) || {};
				var source = webPacker.url.parameter.get('utm_source');
				if (source !== null)
				{
					this.set('source', source);
				}
				this.set('timestamp', this.get('timestamp') || 0);
			}
		},
		prolong: function ()
		{
			this.fields.set('timestamp', Date.now());
		},
		stop: function ()
		{
			this.fields.clear();
		},
		isActivated: function ()
		{
			if (!window.opener)
			{
				return false;
			}

			if (webPacker.url.parameter.get(this.fields.name) === 'y')
			{
				return true;
			}

			return (Date.now() - this.fields.get('timestamp')) < this.timeout * 1000;
		},
		log: function (mess)
		{
			if (this.debug && window.console && 'log' in console)
			{
				console.log('b24Tracker[EditorStatus]:', mess);
			}
		},
		check: function ()
		{
			this.debug = webPacker.url.parameter.get(this.debugName) === 'y';

			if (!window.opener)
			{
				this.log('window.opener is empty');
				return;
			}

			var origin = webPacker.url.parameter.get(this.checkingName);
			if (!origin)
			{
				this.log('Origin parameter is empty');
				return;
			}
			if (origin !== webPacker.getAddress() && !this.debug)
			{
				this.log('Origin parameter not equal `' + webPacker.getAddress() + '`');
				return;
			}

			var data = JSON.stringify({
				source: 'b24Tracker',
				action: 'init',
				items: Manager.Instance.search()
			});
			window.opener.postMessage(data, origin);
			this.log('Send to `' + origin + '` data ' + data);
		},
		init: function (options)
		{
			this.check();

			if (!options.editor.force && !this.isActivated())
			{
				this.stop();
				return false;
			}

			options.editor.resources.forEach(function (resource) {
				webPacker.resource.load(resource);
			});

			this.prolong();
			return true;
		},
		onEditorInit: function ()
		{
			b24Tracker.Editor.Manager.init({
				status: this,
				items: Manager.Instance.getSearchedNodes()
			});
		}
	};

	if (!window.b24Tracker) window.b24Tracker = {};
	if (window.b24Tracker.Manager) return;
	b24Tracker.Manager = Manager;
	Manager.Instance = new Manager();

	if (!b24Tracker.Editor) b24Tracker.Editor = {};
	b24Tracker.Editor.Status = EditorStatus;
})();