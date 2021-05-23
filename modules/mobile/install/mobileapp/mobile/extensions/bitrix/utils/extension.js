(() =>
{

	function camelize(str) {
		return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(word, index) {
			return index == 0 ? word.toLowerCase() : word.toUpperCase();
		}).replace(/\s+/g, '');
	}

	console.color = ( ... arguments) =>
	{
		let template = "";
		for (let i = 0; i < arguments.length; i++)
		{
			if(typeof arguments[i] === "string")
				template = template+"%s";
			if(typeof arguments[i] === "object" || typeof arguments[i] === "Array")
				template = template+"%O";
			if(typeof arguments[i] === "number")
				template = template+"%d";
		}

		class colorLog
		{
			constructor(template, ... data)
			{
				this.template = template;
				this.data = data[0];
			}

			red()
			{
				this.data.unshift(`background:#ffffff; color:#fb0000; font-size: 14px; padding:0 2px;`);
				this.data.unshift(`💋%c${template}`);
				console.log( ... this.data);
			}

			green()
			{
				this.data.unshift(``);
				this.data.unshift(`background:#ffffff; color:green; font-size: 14x; padding:0 0; border-radius:3px;`);
				this.data.unshift(`---- %c🍀%c ----\n\n${template}\n\n-------------`);
				console.log( ... this.data);
			}
		}

		return new colorLog(template, arguments);
	};

	let Utils = {
		md5: function (any)
		{
			let string = null;
			if (typeof any === "object")
			{
				string = JSON.stringify(any)
			}
			else if (typeof any === "string")
			{
				string = any
			}

			if (string === null)
				return null;

			function RotateLeft(lValue, iShiftBits)
			{
				return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
			}

			function AddUnsigned(lX, lY)
			{
				var lX4, lY4, lX8, lY8, lResult;
				lX8 = (lX & 0x80000000);
				lY8 = (lY & 0x80000000);
				lX4 = (lX & 0x40000000);
				lY4 = (lY & 0x40000000);
				lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
				if (lX4 & lY4)
				{
					return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
				}
				if (lX4 | lY4)
				{
					if (lResult & 0x40000000)
					{
						return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
					}
					else
					{
						return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
					}
				}
				else
				{
					return (lResult ^ lX8 ^ lY8);
				}
			}

			function F(x, y, z)
			{
				return (x & y) | ((~x) & z);
			}

			function G(x, y, z)
			{
				return (x & z) | (y & (~z));
			}

			function H(x, y, z)
			{
				return (x ^ y ^ z);
			}

			function I(x, y, z)
			{
				return (y ^ (x | (~z)));
			}

			function FF(a, b, c, d, x, s, ac)
			{
				a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
				return AddUnsigned(RotateLeft(a, s), b);
			};

			function GG(a, b, c, d, x, s, ac)
			{
				a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
				return AddUnsigned(RotateLeft(a, s), b);
			};

			function HH(a, b, c, d, x, s, ac)
			{
				a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
				return AddUnsigned(RotateLeft(a, s), b);
			};

			function II(a, b, c, d, x, s, ac)
			{
				a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
				return AddUnsigned(RotateLeft(a, s), b);
			};

			function ConvertToWordArray(string)
			{
				var lWordCount;
				var lMessageLength = string.length;
				var lNumberOfWords_temp1 = lMessageLength + 8;
				var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
				var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
				var lWordArray = Array(lNumberOfWords - 1);
				var lBytePosition = 0;
				var lByteCount = 0;
				while (lByteCount < lMessageLength)
				{
					lWordCount = (lByteCount - (lByteCount % 4)) / 4;
					lBytePosition = (lByteCount % 4) * 8;
					lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(
						lByteCount) << lBytePosition));
					lByteCount++;
				}
				lWordCount = (lByteCount - (lByteCount % 4)) / 4;
				lBytePosition = (lByteCount % 4) * 8;
				lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
				lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
				lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
				return lWordArray;
			};

			function WordToHex(lValue)
			{
				var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
				for (lCount = 0; lCount <= 3; lCount++)
				{
					lByte = (lValue >>> (lCount * 8)) & 255;
					WordToHexValue_temp = "0" + lByte.toString(16);
					WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
				}
				return WordToHexValue;
			};

			function Utf8Encode(string)
			{
				string = string.replace(/\r\n/g, "\n");
				var utftext = "";

				for (var n = 0; n < string.length; n++)
				{

					var c = string.charCodeAt(n);

					if (c < 128)
					{
						utftext += String.fromCharCode(c);
					}
					else if ((c > 127) && (c < 2048))
					{
						utftext += String.fromCharCode((c >> 6) | 192);
						utftext += String.fromCharCode((c & 63) | 128);
					}
					else
					{
						utftext += String.fromCharCode((c >> 12) | 224);
						utftext += String.fromCharCode(((c >> 6) & 63) | 128);
						utftext += String.fromCharCode((c & 63) | 128);
					}

				}

				return utftext;
			};

			var x = Array();
			var k, AA, BB, CC, DD, a, b, c, d;
			var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
			var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
			var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
			var S41 = 6, S42 = 10, S43 = 15, S44 = 21;

			string = Utf8Encode(string);

			x = ConvertToWordArray(string);

			a = 0x67452301;
			b = 0xEFCDAB89;
			c = 0x98BADCFE;
			d = 0x10325476;

			for (k = 0; k < x.length; k += 16)
			{
				AA = a;
				BB = b;
				CC = c;
				DD = d;
				a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
				d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
				c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
				b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
				a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
				d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
				c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
				b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
				a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
				d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
				c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
				b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
				a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
				d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
				c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
				b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
				a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
				d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
				c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
				b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
				a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
				d = GG(d, a, b, c, x[k + 10], S22, 0x2441453);
				c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
				b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
				a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
				d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
				c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
				b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
				a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
				d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
				c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
				b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
				a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
				d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
				c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
				b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
				a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
				d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
				c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
				b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
				a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
				d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
				c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
				b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
				a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
				d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
				c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
				b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
				a = II(a, b, c, d, x[k + 0], S41, 0xF4292244);
				d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
				c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
				b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
				a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
				d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
				c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
				b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
				a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
				d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
				c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
				b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
				a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
				d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
				c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
				b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
				a = AddUnsigned(a, AA);
				b = AddUnsigned(b, BB);
				c = AddUnsigned(c, CC);
				d = AddUnsigned(d, DD);
			}

			var temp = WordToHex(a) + WordToHex(b) + WordToHex(c) + WordToHex(d);

			return temp.toLowerCase();
		},
		objectClone: function (properties)
		{
			let newProperties = {};
			if (properties === null)
			{
				return null;
			}

			if (typeof properties == 'object')
			{
				if (BX.type.isArray(properties))
				{
					newProperties = [];
					for (let i = 0, l = properties.length; i < l; i++)
					{
						if (typeof properties[i] == "object")
						{
							newProperties[i] = Utils.objectClone(properties[i]);
						}
						else
						{
							newProperties[i] = properties[i];
						}
					}
				}
				else
				{
					newProperties = {};
					if (properties.constructor)
					{
						if (BX.type.isDate(properties))
						{
							newProperties = new Date(properties);
						}
						else
						{
							newProperties = new properties.constructor();
						}
					}

					for (let i in properties)
					{
						if (!properties.hasOwnProperty(i))
						{
							continue;
						}
						if (typeof properties[i] == "object")
						{
							newProperties[i] = Utils.objectClone(properties[i]);
						}
						else
						{
							newProperties[i] = properties[i];
						}
					}
				}
			}
			else
			{
				newProperties = properties;
			}

			return newProperties;
		},
		objectMerge: function (currentProperties, newProperties)
		{
			for (let name in newProperties)
			{
				if (!newProperties.hasOwnProperty(name))
				{
					continue;
				}
				if (BX.type.isPlainObject(newProperties[name]))
				{
					if (!BX.type.isPlainObject(currentProperties[name]))
					{
						currentProperties[name] = {};
					}
					currentProperties[name] = this.objectMerge(currentProperties[name], newProperties[name]);
				}
				else
				{
					currentProperties[name] = newProperties[name];
				}
			}

			return currentProperties;
		},
		isObjectChanged: function (currentProperties, newProperties)
		{
			for (let name in newProperties)
			{
				if (!newProperties.hasOwnProperty(name))
				{
					continue;
				}

				if (typeof currentProperties[name] == 'undefined')
				{
					return true;
				}

				if (BX.type.isPlainObject(newProperties[name]))
				{
					if (!BX.type.isPlainObject(currentProperties[name]))
					{
						return true;
					}

					if (this.isObjectChanged(currentProperties[name], newProperties[name]) === true)
					{
						return true;
					}
				}
				else if (currentProperties[name] !== newProperties[name])
				{
					return true;
				}
			}

			return false;
		},
		isString(value)
		{
			return typeof value === 'string';
		},
		isFunction(value)
		{
			return typeof value === 'function';
		},
		isObjectLike(value)
		{
			return !!value && typeof value === 'object';
		},
		isNotEmptyString: function(value)
		{
			return this.isString(value) && value !== '';
		},
		isNotEmptyObject: function(value) {
			return this.isObjectLike(value) && Object.keys(value).length > 0;
		},
		getRandom(length = 8)
		{
			return [...Array(length)].map(() => (~~(Math.random() * 36)).toString(36)).join('');
		}
	};

	window.Utils = Utils;
	window.CommonUtils = Utils; //alias

	Object.toMD5 = function (object)
	{
		let result = null;
		try
		{
			let string = JSON.stringify(object);
			result = Utils.md5(string);
		}
		catch (e)
		{

		}

		return result;

	};

	Object.tryJSONParse = function (object)
	{
		let result = null;
		if (typeof object == "string")
		{
			try
			{
				result = JSON.parse(object);
			}
			catch (e)
			{
				console.error("Parse JSON error", e);
			}
		}

		return result;
	};

	class KeyValueStorage
	{
		constructor(id)
		{
			this.id = id;
			this.storageObject = Application.sharedStorage(id);
		}

		setObject (key, value)
		{
			let result = null;
			if (value && typeof value == "object")
			{
				try
				{
					result = JSON.stringify(value);
					this.storageObject.set(key, result);
				}
				catch (e)
				{
					//do nothing
				}
			}
		}

		updateObject (key, object = {}, handler = null)
		{
			let result = null;
			if (object && typeof object == "object")
			{

				let savedObject = this.getObject(key, {});
				if (handler)
				{
					let result = handler(savedObject, object);
					if (typeof result != "undefined")
					{
						savedObject = result;
					}
				}
				else
				{
					savedObject = Object.assign(savedObject, object);
				}
				try
				{
					result = JSON.stringify(savedObject);
					this.storageObject.set(key, result);
				}
				catch (e)
				{
					//do nothing
				}
			}
		}

		getObject (key, fallback = {})
		{
			let result = Object.tryJSONParse(this.storageObject.get(key));
			if (result == null)
			{
				return fallback;
			}

			if(typeof fallback === "object" && fallback !== null)
			{
				return Object.assign(fallback, result);
			}
			else
			{
				return result;
			}

		}

		setBoolean (key, value = false)
		{
			this.set(key, value == true ? "1" : "0");
		}

		getBoolean (key, fallback = false)
		{
			let fallbackString = (fallback == true ? "1" : "0");
			return Boolean(parseInt(this.get(key, fallbackString)));
		}

		setNumber (key, value)
		{
			value = value? value.toString(): "0";
			this.set(key, value);
		}

		getNumber (key, fallback = null)
		{
			const result = this.get(key, NaN);
			return Number.isNaN(result)? fallback: Number(result);
		}

		get (key, fallback = null)
		{
			let result = this.storageObject.get(key);
			if (result == null && fallback != null)
			{
				return fallback;
			}

			return result;

		}

		set (key, value)
		{
			return this.storageObject.set(key, value)
		}

	}

	let appStorages = {};
	Application.storage = new KeyValueStorage();
	Application.storageById = storageId =>{
		if(!appStorages[storageId])
		{
			appStorages[storageId] = new KeyValueStorage(storageId);
		}

		return appStorages[storageId];

	};

	let resolveArgs = function ()
	{
		let version = null;
		let func = null;
		let functionDetect = (arg) =>
		{
			if (typeof arg !== "function")
			{
				throw new Error("The argument must be \"function\" type" + arg);
			}

			return arg;
		};

		if (arguments.length === 0)
		{
			throw new Error("Arguments not passed");
		}

		if (arguments.length === 1)
		{
			func = functionDetect(arguments[0]);
		}
		else
		{
			func = functionDetect(arguments[1]);
			version = arguments[0];
		}

		return {func, version}
	};

	class apiExec
	{
		constructor(ver = null, func = null)
		{
			this.func = func;
			this.ver = ver;
			this.preventElse = false;
			this.executeFunction()
		}

		/**
		 * Calls function if the previous call in the chain was unsuccessful(if version is passed checking)
		 * @param ver
		 * @param func
		 * @returns {apiExec}
		 */
		else(ver = null, func = null)
		{
			if (this.preventElse === true)
			{
				return this;
			}

			return this.next.apply(this, arguments)
		}

		/**
		 * Calls function if the previous call in the chain was unsuccessful(if version is passed checking)
		 * @param condition
		 * @param func
		 * @returns {apiExec}
		 */
		elseIf(condition = null, func = null)
		{
			if (this.preventElse === true || Boolean(condition) === false)
			{
				return this;
			}

			return this.next.call(this, null, func)

		}

		/**
		 * Always calls function after previous call (if version is passed checking)
		 * @returns {apiExec}
		 */
		next(ver = null, func = null)
		{
			let resolvedArgs = resolveArgs.apply(null, arguments);
			this.ver = resolvedArgs["version"];
			this.func = resolvedArgs["func"];
			this.executeFunction();
			return this;
		}

		executeFunction()
		{
			if (this.ver !== false)
			{
				if (Application.getApiVersion() >= this.ver || this.ver == null)
				{
					this.preventElse = true;
					this.func.apply();
				}
			}
			else
			{
				this.preventElse = true;
			}
		}

		static call()
		{
			let resolvedArgs = resolveArgs.apply(null, arguments);
			return new apiExec(resolvedArgs["version"], resolvedArgs["func"]);
		}
	}

	window.ifApi = apiExec.call;

	window.StringUtils = class
	{
		static camelize(str) {
			return str
				.replace(/_/g, " ")
				.replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) =>
				{
					return index === 0
						? word.toLowerCase()
						: word.toUpperCase();
				}).replace(/\s+/g, '');
		}

	};

	window.reflectFunction = function (object, funcName, thisObject)
	{
		return function(){
			let context = thisObject || object;
			let targetFunction = StringUtils.camelize(funcName);
			if(object && typeof object[targetFunction] == "function")
			{
				return object[targetFunction].apply(context, arguments);
			}

			return function(){};
		}
	}


})();