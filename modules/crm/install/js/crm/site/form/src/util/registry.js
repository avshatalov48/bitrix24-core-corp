import * as ScrollLock from './scroll-lock/scroll-lock';
import {MoveObserver} from './moveobserver';
import {ViewObserver} from './viewobserver';

const Scroll = {
	items: [],
	toggle (element, mode)
	{
		mode ? this.enable(element) : this.disable(element);
	},
	getLastItem ()
	{
		return this.items.length > 0 ? this.items[this.items.length - 1] : null;
	},
	disable (element)
	{
		let prevElement = this.getLastItem();
		if (prevElement)
		{
			ScrollLock.addLockableTarget(prevElement);
			ScrollLock.addFillGapTarget(prevElement);
		}
		ScrollLock.disablePageScroll(element);
		this.items.push(element);
	},
	enable ()
	{
		setTimeout(() => {
			let element = this.items.pop();
			ScrollLock.enablePageScroll(element);
			let prevElement = this.getLastItem();
			if (prevElement)
			{
				ScrollLock.removeFillGapTarget(prevElement);
				ScrollLock.removeLockableTarget(prevElement);
			}
		}, 300);
	},
};

const Type = {
	defined(val): Boolean
	{
		return typeof val !== 'undefined';
	},
	object(val): Boolean
	{
		if (!val || typeof val !== 'object' || Object.prototype.toString.call(val) !== '[object Object]')
		{
			return false;
		}

		const proto = Object.getPrototypeOf(val);
		if (proto === null)
		{
			return true;
		}

		const objectCtorString = Function.prototype.toString.call(Object);
		const ctor = proto.hasOwnProperty('constructor') && proto.constructor;

		return (
			typeof ctor === 'function' &&
			Function.prototype.toString.call(ctor) === objectCtorString
		);
	},
	string(val): Boolean
	{
		return typeof val === 'string';
	},
};

const Conv = {
	number (value): Number
	{
		value = parseFloat(value);
		return isNaN(value) ? 0 : value;
	},
	string (): String
	{

	},
	formatMoney(val: Number, format): String
	{
		val = this.number(val).toFixed(2) || 0;
		return (format || '#')
			.replace('&#', '|||||')
			.replace('&amp;#', '|-|||-|')
			.replace('#', val)
			.replace('|-|||-|', '&amp;#')
			.replace('|||||', '&#')
		;
	},
	replaceText (text, fields)
	{
		text = text + '';
		fields = fields || {};
		let holders = text.match(/{{[ -.a-zA-Z]+}}/g);
		if (!holders || holders.length === 0)
		{
			return text;
		}
		let result = holders.reduce(function (s, item){
			let value = item.replace(/^{+/, '').replace(/}+$/, '').trim();
			value = fields[value] ? fields[value] : '';

			let parts = s.split(item);
			for (let i = 0; i < parts.length; i = i + 1)
			{
				if (i === parts.length - 1 && parts.length > 1)
				{
					continue;
				}

				let left = parts[i].replace(/[ \t]+$/, '');
				if (!value)
				{
					left = left.replace(/[,]+$/, '');
				}

				left += (value ? ' ' : '') + value;
				parts[i] = left;

				if ((i + 1 >= parts.length))
				{
					continue;
				}

				let right = parts[i+1].replace(/^[ \t]+/, '');
				if (!/^[<!?.\n]+/.test(right))
				{
					let isLeftClosed = !left || /[<!?.\n]+$/.test(left);
					if(isLeftClosed)
					{
						right = right.replace(/^[ \t,]+/, '');
					}

					if (!/^[,]+/.test(right))
					{
						if (isLeftClosed)
						{
							right = right.charAt(0).toUpperCase() + right.slice(1)
						}

						right = ' ' + right;
					}
				}

				parts[i+1] = right;
			}

			return parts.join('').trim();
		}, text);

		return result ? result : text ;
	},
	cloneDeep (object: Object): Object
	{
		return JSON.parse(JSON.stringify(object));
	}
};

const Color = {
	parseHex (hex)
	{
		hex = this.fillHex(hex);
		let parts = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?$/i.exec(hex);
		if (!parts)
		{
			parts = [0,0,0,1];
		}
		else
		{
			parts = [
				parseInt(parts[1], 16),
				parseInt(parts[2], 16),
				parseInt(parts[3], 16),
				parseInt(100 * (parseInt(parts[4] || 'ff', 16) / 255)) / 100,
			];
		}

		return parts;
	},
	hexToRgba (hex)
	{
		return 'rgba(' + this.parseHex(hex).join(', ') + ')';
	},
	toRgba (numbers)
	{
		return 'rgba(' + numbers.join(', ') + ')';
	},
	fillHex (hex: string, fillAlpha: boolean = false, alpha: string = null)
	{
		if (hex.length === 4 || (fillAlpha && hex.length === 5))
		{
			hex = hex.replace(/([a-f0-9])/gi, "$1$1");
		}

		if (fillAlpha && hex.length === 7)
		{
			hex += 'ff';
		}

		if (alpha)
		{
			hex = hex.substr(0, 7) + (alpha.toLowerCase() + 'ff').substr(0, 2);
		}

		return hex;
	},
	isHexDark (hex)
	{
		hex = this.parseHex(hex);
		const r = hex[0]; const g = hex[1]; const b = hex[2];
		const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
		return brightness < 155;
	},
};

const Browser = {
	isMobile()
	{
		return window.innerWidth <= 530;
	}
};

const Render = {
	component()
	{

	}
};

const fontsLoaded = [];

const Font = {
	load(font)
	{
		if (fontsLoaded.includes(font)) {
			return;
		}

		let fontUrl = null;
		switch (font)
		{
			case 'lobster':
				fontUrl = 'https://fonts.googleapis.com/css2?family=Lobster:wght@100;200;300;400;500;600;700;800;900&subset=cyrillic-ext,latin-ext';
				break;
			default:
			case 'opensans':
				fontUrl = 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@100;200;300;400;500;600;700;800;900&subset=cyrillic';
				break;
		}

		if (fontUrl)
		{
			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = fontUrl;
			document.getElementsByTagName('head')[0].appendChild(link);
			fontsLoaded.push(font);
		}
	}
}

export {
	Type,
	Conv,
	Color,
	Render,
	Scroll,
	Browser,
	MoveObserver,
	ViewObserver,
	Font,
}