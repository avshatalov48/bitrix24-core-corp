/** Polyfill Fetch **/
!function(){var t={searchParams:"URLSearchParams"in self,iterable:"Symbol"in self&&"iterator"in Symbol,blob:"FileReader"in self&&"Blob"in self&&function(){try{return new Blob,!0}catch(t){return!1}}(),formData:"FormData"in self,arrayBuffer:"ArrayBuffer"in self};if(t.arrayBuffer)var e=["[object Int8Array]","[object Uint8Array]","[object Uint8ClampedArray]","[object Int16Array]","[object Uint16Array]","[object Int32Array]","[object Uint32Array]","[object Float32Array]","[object Float64Array]"],r=ArrayBuffer.isView||function(t){return t&&e.indexOf(Object.prototype.toString.call(t))>-1};function o(t){if("string"!=typeof t&&(t=String(t)),/[^a-z0-9\-#$%&'*+.^_`|~]/i.test(t)||""===t)throw new TypeError("Invalid character in header field name");return t.toLowerCase()}function n(t){return"string"!=typeof t&&(t=String(t)),t}function i(e){var r={next:function(){var t=e.shift();return{done:void 0===t,value:t}}};return t.iterable&&(r[Symbol.iterator]=function(){return r}),r}function s(t){this.map={},t instanceof s?t.forEach(function(t,e){this.append(e,t)},this):Array.isArray(t)?t.forEach(function(t){this.append(t[0],t[1])},this):t&&Object.getOwnPropertyNames(t).forEach(function(e){this.append(e,t[e])},this)}function a(t){if(t.bodyUsed)return Promise.reject(new TypeError("Already read"));t.bodyUsed=!0}function h(t){return new Promise(function(e,r){t.onload=function(){e(t.result)},t.onerror=function(){r(t.error)}})}function f(t){var e=new FileReader,r=h(e);return e.readAsArrayBuffer(t),r}function u(t){if(t.slice)return t.slice(0);var e=new Uint8Array(t.byteLength);return e.set(new Uint8Array(t)),e.buffer}function d(){return this.bodyUsed=!1,this._initBody=function(e){var o;this._bodyInit=e,e?"string"==typeof e?this._bodyText=e:t.blob&&Blob.prototype.isPrototypeOf(e)?this._bodyBlob=e:t.formData&&FormData.prototype.isPrototypeOf(e)?this._bodyFormData=e:t.searchParams&&URLSearchParams.prototype.isPrototypeOf(e)?this._bodyText=e.toString():t.arrayBuffer&&t.blob&&((o=e)&&DataView.prototype.isPrototypeOf(o))?(this._bodyArrayBuffer=u(e.buffer),this._bodyInit=new Blob([this._bodyArrayBuffer])):t.arrayBuffer&&(ArrayBuffer.prototype.isPrototypeOf(e)||r(e))?this._bodyArrayBuffer=u(e):this._bodyText=e=Object.prototype.toString.call(e):this._bodyText="",this.headers.get("content-type")||("string"==typeof e?this.headers.set("content-type","text/plain;charset=UTF-8"):this._bodyBlob&&this._bodyBlob.type?this.headers.set("content-type",this._bodyBlob.type):t.searchParams&&URLSearchParams.prototype.isPrototypeOf(e)&&this.headers.set("content-type","application/x-www-form-urlencoded;charset=UTF-8"))},t.blob&&(this.blob=function(){var t=a(this);if(t)return t;if(this._bodyBlob)return Promise.resolve(this._bodyBlob);if(this._bodyArrayBuffer)return Promise.resolve(new Blob([this._bodyArrayBuffer]));if(this._bodyFormData)throw new Error("could not read FormData body as blob");return Promise.resolve(new Blob([this._bodyText]))},this.arrayBuffer=function(){return this._bodyArrayBuffer?a(this)||Promise.resolve(this._bodyArrayBuffer):this.blob().then(f)}),this.text=function(){var t,e,r,o=a(this);if(o)return o;if(this._bodyBlob)return t=this._bodyBlob,e=new FileReader,r=h(e),e.readAsText(t),r;if(this._bodyArrayBuffer)return Promise.resolve(function(t){for(var e=new Uint8Array(t),r=new Array(e.length),o=0;o<e.length;o++)r[o]=String.fromCharCode(e[o]);return r.join("")}(this._bodyArrayBuffer));if(this._bodyFormData)throw new Error("could not read FormData body as text");return Promise.resolve(this._bodyText)},t.formData&&(this.formData=function(){return this.text().then(c)}),this.json=function(){return this.text().then(JSON.parse)},this}s.prototype.append=function(t,e){t=o(t),e=n(e);var r=this.map[t];this.map[t]=r?r+", "+e:e},s.prototype.delete=function(t){delete this.map[o(t)]},s.prototype.get=function(t){return t=o(t),this.has(t)?this.map[t]:null},s.prototype.has=function(t){return this.map.hasOwnProperty(o(t))},s.prototype.set=function(t,e){this.map[o(t)]=n(e)},s.prototype.forEach=function(t,e){for(var r in this.map)this.map.hasOwnProperty(r)&&t.call(e,this.map[r],r,this)},s.prototype.keys=function(){var t=[];return this.forEach(function(e,r){t.push(r)}),i(t)},s.prototype.values=function(){var t=[];return this.forEach(function(e){t.push(e)}),i(t)},s.prototype.entries=function(){var t=[];return this.forEach(function(e,r){t.push([r,e])}),i(t)},t.iterable&&(s.prototype[Symbol.iterator]=s.prototype.entries);var l=["DELETE","GET","HEAD","OPTIONS","POST","PUT"];function y(t,e){var r,o,n=(e=e||{}).body;if(t instanceof y){if(t.bodyUsed)throw new TypeError("Already read");this.url=t.url,this.credentials=t.credentials,e.headers||(this.headers=new s(t.headers)),this.method=t.method,this.mode=t.mode,this.signal=t.signal,n||null==t._bodyInit||(n=t._bodyInit,t.bodyUsed=!0)}else this.url=String(t);if(this.credentials=e.credentials||this.credentials||"same-origin",!e.headers&&this.headers||(this.headers=new s(e.headers)),this.method=(r=e.method||this.method||"GET",o=r.toUpperCase(),l.indexOf(o)>-1?o:r),this.mode=e.mode||this.mode||null,this.signal=e.signal||this.signal,this.referrer=null,("GET"===this.method||"HEAD"===this.method)&&n)throw new TypeError("Body not allowed for GET or HEAD requests");this._initBody(n)}function c(t){var e=new FormData;return t.trim().split("&").forEach(function(t){if(t){var r=t.split("="),o=r.shift().replace(/\+/g," "),n=r.join("=").replace(/\+/g," ");e.append(decodeURIComponent(o),decodeURIComponent(n))}}),e}function p(t,e){e||(e={}),this.type="default",this.status=void 0===e.status?200:e.status,this.ok=this.status>=200&&this.status<300,this.statusText="statusText"in e?e.statusText:"OK",this.headers=new s(e.headers),this.url=e.url||"",this._initBody(t)}y.prototype.clone=function(){return new y(this,{body:this._bodyInit})},d.call(y.prototype),d.call(p.prototype),p.prototype.clone=function(){return new p(this._bodyInit,{status:this.status,statusText:this.statusText,headers:new s(this.headers),url:this.url})},p.error=function(){var t=new p(null,{status:0,statusText:""});return t.type="error",t};var b=[301,302,303,307,308];p.redirect=function(t,e){if(-1===b.indexOf(e))throw new RangeError("Invalid status code");return new p(null,{status:e,headers:{location:t}})};var m=self.DOMException;try{new m}catch(t){(m=function(t,e){this.message=t,this.name=e;var r=Error(t);this.stack=r.stack}).prototype=Object.create(Error.prototype),m.prototype.constructor=m}function w(e,r){return new Promise(function(o,n){var i=new y(e,r);if(i.signal&&i.signal.aborted)return n(new m("Aborted","AbortError"));var a=new XMLHttpRequest;function h(){a.abort()}a.onload=function(){var t,e,r={status:a.status,statusText:a.statusText,headers:(t=a.getAllResponseHeaders()||"",e=new s,t.replace(/\r?\n[\t ]+/g," ").split(/\r?\n/).forEach(function(t){var r=t.split(":"),o=r.shift().trim();if(o){var n=r.join(":").trim();e.append(o,n)}}),e)};r.url="responseURL"in a?a.responseURL:r.headers.get("X-Request-URL");var n="response"in a?a.response:a.responseText;o(new p(n,r))},a.onerror=function(){n(new TypeError("Network request failed"))},a.ontimeout=function(){n(new TypeError("Network request failed"))},a.onabort=function(){n(new m("Aborted","AbortError"))},a.open(i.method,i.url,!0),"include"===i.credentials?a.withCredentials=!0:"omit"===i.credentials&&(a.withCredentials=!1),"responseType"in a&&t.blob&&(a.responseType="blob"),i.headers.forEach(function(t,e){a.setRequestHeader(e,t)}),i.signal&&(i.signal.addEventListener("abort",h),a.onreadystatechange=function(){4===a.readyState&&i.signal.removeEventListener("abort",h)}),a.send(void 0===i._bodyInit?null:i._bodyInit)})}w.polyfill=!0,self.fetch||(self.fetch=w,self.Headers=s,self.Request=y,self.Response=p)}();

/** Polyfill Promise **/
!function(n){"use strict";if(void 0===n.Promise||-1===n.Promise.toString().indexOf("[native code]")){var e="[[PromiseStatus]]",t="[[PromiseValue]]",o=function(n,o){"internal pending"===n[e]&&(n=n[t]),"pending"===n[e]?n.deferreds.push(o):(n.handled=!0,setTimeout(function(){var c="resolved"===n[e]?o.onFulfilled:o.onRejected;if(c)try{i(o.promise,c(n[t]))}catch(n){r(o.promise,n)}else"resolved"===n[e]?i(o.promise,n[t]):r(o.promise,n[t])},0))},i=function(n,o){if(o===n)throw new TypeError("A promise cannot be resolved with it promise.");try{if(o&&("object"==typeof o||"function"==typeof o)){if(o instanceof s)return n[e]="internal pending",n[t]=o,void c(n);if("function"==typeof o.then)return void f(o.then.bind(o),n)}n[e]="resolved",n[t]=o,c(n)}catch(e){r(n,e)}},r=function(n,o){n[e]="rejected",n[t]=o,c(n)},c=function(n){"rejected"===n[e]&&0===n.deferreds.length&&setTimeout(function(){n.handled||console.error("Unhandled Promise Rejection: "+n[t])},0),n.deferreds.forEach(function(e){o(n,e)}),n.deferreds=null},f=function(n,e){var t=!1;try{n(function(n){t||(t=!0,i(e,n))},function(n){t||(t=!0,r(e,n))})}catch(n){t||(t=!0,r(e,n))}},u=function(n,e,t){this.onFulfilled="function"==typeof n?n:null,this.onRejected="function"==typeof e?e:null,this.promise=t},s=function(n){this[e]="pending",this[t]=null,this.handled=!1,this.deferreds=[],f(n,this)};s.prototype.catch=function(n){return this.then(null,n)},s.prototype.then=function(n,e){var t=new s(function(){});return o(this,new u(n,e,t)),t},s.all=function(n){var e=[].slice.call(n);return new s(function(n,t){if(0===e.length)n(e);else for(var o=e.length,i=function(r,c){try{if(c&&("object"==typeof c||"function"==typeof c)&&"function"==typeof c.then)return void c.then.call(c,function(n){i(r,n)},t);e[r]=c,0==--o&&n(e)}catch(n){t(n)}},r=0;r<e.length;r++)i(r,e[r])})},s.resolve=function(n){return n&&"object"==typeof n&&n.constructor===s?n:new s(function(e){e(n)})},s.reject=function(n){return new s(function(e,t){t(n)})},s.race=function(n){return new s(function(e,t){for(var o=0,i=n.length;o<i;o++)n[o].then(e,t)})},n.Promise=s}}(window);


// PAYLOAD
(function(){

	function Warn(message)
	{
		if (window.console && console.warn)
		{
			// console.warn(message || '[DEPRECATED] This javascript-loader of CRM-forms is deprecated. Please, change to new javascript-loader.');
		}
	}
	Warn();

	function ParseHost(link)
	{
		return link.match(/((http|https):\/\/[^\/]+?)\//)[1];
	}

	var defaultHost = (function(){
		var scriptNode = document.querySelector('script[src*="/bitrix/js/crm/form_loader.js"]')
		if (scriptNode && scriptNode.src)
		{
			return ParseHost(scriptNode.src) ;
		}

		return null;
	})();

	var loaders = {};
	try
	{
		loaders = JSON.parse(window.sessionStorage.getItem('b24:form:compatible:loaders')) || {};
	}
	catch (e) {}

	var parametersList = [];
	function InvokeLoader(parameters, loaders)
	{
		parametersList.push(parameters);
		parameters.compatibility = {
			id: Math.random().toString().split('.')[1] + Math.random().toString().split('.')[1]
		};

		var anchorScript = document.createElement('div');
		anchorScript.innerHTML = loaders.form[parameters.type];
		anchorScript = anchorScript.children[0];
		anchorScript.dataset.b24Id = parameters.compatibility.id;
		parameters.compatibility.anchorScript = anchorScript;

		var execScript = document.createElement('script');
		execScript.type = 'text/javascript';
		execScript.appendChild(document.createTextNode(anchorScript.textContent))



		if(parameters.click && parameters.type === 'click')
		{
			parameters.click.forEach(function (node) {
				node.parentNode.insertBefore(anchorScript.cloneNode(true), node);
			});
		}
		else if(parameters.node)
		{
			parameters.node.appendChild(anchorScript);
		}
		else if (parameters.defaultNode)
		{
			if (parameters.defaultNode.nextElementSibling)
			{
				parameters.defaultNode.parentNode.insertBefore(
					anchorScript,
					parameters.defaultNode.nextElementSibling
				);
			}
			else if (parameters.defaultNode)
			{
				parameters.defaultNode.parentNode.appendChild(anchorScript);
			}
		}
		else
		{
			return;
		}

		if (parametersList.length === 1)
		{
			window.addEventListener('b24:form:init:before', function (event) {
				var options = event.detail.data;
				var form = event.detail.object;
				if (!options || !options.identification)
				{
					return;
				}

				var parameters = parametersList.filter(function (parameters) {
					if (parseInt(options.identification.id) !== parseInt(parameters.id))
					{
						return false;
					}

					if (options.identification.sec !== parameters.sec)
					{
						return false;
					}

					if (options.id && parameters.compatibility && parameters.compatibility.id)
					{
						return parseInt(options.id) === parseInt(parameters.compatibility.id);
					}

					return true;
				})[0];
				if (!parameters)
				{
					return;
				}

				if (parameters.compatibility && form)
				{
					parameters.compatibility.instance = form;
				}
				window.b24form.Compatibility.applyOldenLoaderData(options, parameters);
			});
		}

		document.head.appendChild(execScript);
	}

	var requestPromises = {};
	function LoadCompatible(parameters)
	{
		var host = ParseHost(parameters.ref) || defaultHost;
		if (!host)
		{
			throw new Error('Could not load form without parameter `ref`');
		}

		var cacheId = host + '|' + parameters.id;
		if (loaders[cacheId]) // check loaded
		{
			InvokeLoader(parameters, loaders[cacheId]);
			return;
		}

		if (!requestPromises[cacheId]) // check loading
		{
			requestPromises[cacheId] = new Promise(function (resolve, reject) {
				var uri = host + '/bitrix/services/main/ajax.php?action=crm.site.form.get'
					+ '&id=' + parameters.id
					+ '&sec=' + parameters.sec
					+ '&loaderOnly=y';

				window.fetch(
					uri,
					{
						method: 'GET',
						mode: 'cors',
						cache: 'no-cache',
						headers: {
							'Origin': window.location.origin
						}
					}
				)
					.then(function (response) {
						return response.json();
					})
					.then(function (data) {
						loaders[cacheId] = data.result.loader;
						try
						{
							window.sessionStorage.setItem('b24:form:compatible:loaders', JSON.stringify(loaders));
						}
						catch (e) {}
						resolve(parameters, data.result.loader);
					})
					.catch(reject);
			});
		}

		requestPromises[cacheId].then(function () {
			InvokeLoader(parameters, loaders[cacheId]);
		});
	}

	function UnLoadCompatible(parameters)
	{
		if (!parameters.compatibility || !parameters.compatibility.instance)
		{
			return;
		}

		parameters.compatibility.instance.destroy();
		parameters.compatibility.anchorScript.remove();
	}

	window.Bitrix24FormLoader = {
		init: function()
		{
			this.yaId = null;
			this.forms = {};
			this.eventHandlers = [];
			this.frameHeight = '200';
			this.defaultNodeId = 'bx24_form_';

			if(!window.Bitrix24FormObject || !window[window.Bitrix24FormObject])
				return;

			var b24form = window[window.Bitrix24FormObject];
			b24form.forms = b24form.forms || [];
			var forms = b24form.forms;
			forms.ntpush = forms.push;
			forms.push = function (params)
			{
				forms.ntpush(params);
				this.preLoad(params);
			}.bind(this);
			forms.forEach(this.preLoad, this);
		},
		preLoad: function(params)
		{
			var defaultNode = params.defaultNode = document.getElementById(this.defaultNodeId + params.type);
			if(!params.node && !params.defaultNode)
			{
				throw new Error('Could not load form: node not found.')
			}

			switch(params.type)
			{
				case 'click':
				case 'button':
				case 'link':
					var click = params.click || Array.prototype.slice.call(document.getElementsByClassName("b24-web-form-popup-btn-" + params.id));
					if(click && Object.prototype.toString.call(click) !== "[object Array]")
					{
						click = [click];
					}
					if(!click && defaultNode)
					{
						click = [defaultNode.nextElementSibling];
					}
					params.click = click;
					params.type = 'click';
					break;
				case 'delay':
					params.type = 'auto';
					break;
				case 'inline':
				default:
					params.type = 'inline';
					break;
			}

			this.load(params);
		},
		createPopup: function(params)
		{
			Warn();
		},
		resizePopup: function()
		{
			Warn();
		},
		showPopup: function(params)
		{
			Warn();
		},
		hidePopup: function(params)
		{
			Warn();
		},
		scrollToPopupMiddle: function(uniqueLoadId)
		{
			Warn();
		},
		util: {
			addClass: function(element, className)
			{
				if (element && typeof element.className == "string" && element.className.indexOf(className) === -1)
				{
					element.className += " " + className;
					element.className = element.className.replace('  ', ' ');
				}
			},
			removeClass: function(element, className)
			{
				if (!element || !element.className)
				{
					return;
				}

				element.className = element.className.replace(className, '').replace('  ', ' ');
			},
			hasClass: function(node, className)
			{
				var classList = this.nodeListToArray(node.classList);
				var filtered = classList.filter(function (name) { return name == className});
				return filtered.length > 0;
			},
			isIOS: function()
			{
				return (/(iPad;)|(iPhone;)/i.test(navigator.userAgent));
			},
			isMobile: function()
			{
				return (/(ipad|iphone|android|mobile|touch)/i.test(navigator.userAgent));
			}
		},
		createFrame: function(params)
		{
			Warn();
		},
		getUniqueLoadId: function(params)
		{
			var type = params.type;
			switch(type)
			{
				case 'click':
				case 'button':
				case 'link':
					type = 'button';
					break;
			}

			return type + '_' + params.id;
		},
		isFormExisted: function(params)
		{
			return !!this.forms[this.getUniqueLoadId(params)];
		},
		load: function(params)
		{
			params.loaded = false;
			params.handlers = params.handlers || {};
			params.options = params.options || {};

			LoadCompatible(params);
		},
		unload: function(params)
		{
			params = params || {};
			UnLoadCompatible(params);
			var uniqueLoadId = this.getUniqueLoadId(params);
			this.forms[uniqueLoadId] = null;
		},
		doFrameAction: function(dataString, uniqueLoadId)
		{
			Warn();
		},
		checkHash: function(uniqueLoadId)
		{
			Warn();
		},
		sendDataToFrame: function(uniqueLoadId, data)
		{
			Warn();
		},
		onFrameLoad: function(uniqueLoadId)
		{
			Warn();
		},

		isGuestLoaded: function()
		{
			return window.b24Tracker && window.b24Tracker.guest;
		},
		guestLoadedChecker: function()
		{
			Warn();
		},
		onGuestLoaded: function()
		{
			Warn();
		},

		addEventListener: function(el, eventName, handler)
		{
			el = el || window;
			if (window.addEventListener)
			{
				el.addEventListener(eventName, handler, false);
			}
			else
			{
				el.attachEvent('on' + eventName, handler);
			}
		},
		addEventHandler: function(target, eventName, handler)
		{
			Warn();
		},
		execEventHandler: function(target, eventName, params)
		{
			Warn();
		},

		setFrameHeight: function(uniqueLoadId, height)
		{
			Warn();
		}
	};

	window.Bitrix24FormLoader.init();
})();