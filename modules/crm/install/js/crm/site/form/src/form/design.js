import * as Util from '../util/registry';
import {type Proxy, type ProxyItem} from './types';

const Themes = {
	'modern-light': {
		dark: false,
		style: 'modern',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
			family: 'Open Sans',
		},
	},
	'modern-dark': {
		dark: true,
		style: 'modern',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap&subset=cyrillic',
			family: 'Open Sans',
		},
	},
	'classic-light': {
		dark: false,
		style: 'classic',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
			family: 'PT Serif',
		},
	},
	'classic-dark': {
		dark: true,
		style: 'classic',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&display=swap&subset=cyrillic',
			family: 'PT Serif',
		},
	},
	'fun-light': {
		dark: false,
		style: 'fun',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
			family: 'Pangolin',
		},
	},
	'fun-dark': {
		dark: true,
		style: 'fun',
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Pangolin&display=swap&subset=cyrillic',
			family: 'Pangolin',
		},
	},
	pixel: {
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Press+Start+2P&display=swap&subset=cyrillic',
			family: 'Press Start 2P',
		},
		dark: true,
		color: {
			text: '#90ee90'
		}
	},
	old: {
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Ruslan+Display&display=swap&subset=cyrillic',
			family: 'Ruslan Display',
		},
		color: {
			background: '#f1eddf'
		}
	},
	writing: {
		font: {
			uri: 'https://fonts.googleapis.com/css?family=Marck+Script&display=swap&subset=cyrillic',
			family: 'Marck Script',
		},
	},
};

type Style = String;
type UrlString = String;
type Font = {
	uri: ?UrlString|string,
	family: string;
};

type Border = {
	top: ?boolean;
	left: ?boolean;
	bottom: ?boolean;
	right: ?boolean;
};

type Color = {
	primary: ?string;
	primaryText: ?string;
	text: ?string;
	background: ?string;
	fieldBorder: ?string;
	fieldBackground: ?string;
	fieldFocusBackground: ?string;
	popupBackground: ?string;
};

type Options = {
	theme: ?string;
	dark: ?boolean;
	font: ?Font|string;
	color: ?Color;
	style: ?Style;
	border: ?boolean|Border;
	shadow: ?boolean;
	compact: ?boolean;
	backgroundImage: ?UrlString;
	proxy: ?Proxy,
};

class Model
{
	theme: string;
	dark: boolean = null;
	font: Font = {uri: '', family: ''};
	color: Color = {
		primary: '',
		primaryText: '',
		text: '',
		background: '',
		fieldBorder: '',
		fieldBackground: '',
		fieldFocusBackground: '',
		popupBackground: '',
	};
	border: Border = {
		top: false,
		left: false,
		bottom: true,
		right: false
	};
	shadow: boolean = false;
	compact: boolean = false;
	style: Style = null;
	backgroundImage: UrlString = null;
	proxy: Proxy = {
		fonts: [],
	};

	constructor(options: Options)
	{
		this.adjust(options);
	}

	adjust(options: Options)
	{
		options = options || {};
		if (typeof options.proxy === 'object')
		{
			this.setProxy(options.proxy);
		}

		if (typeof options.theme !== 'undefined')
		{
			this.theme = options.theme;
			let theme = Themes[options.theme] || {};
			this.setStyle(theme.style || '');
			this.setDark(theme.dark || false);
			this.setFont(theme.font || {});
			this.setBorder(theme.border || {});
			this.setShadow(theme.shadow || false);
			this.setCompact(theme.compact || false);
			this.setColor(Object.assign(
				{
					primary: '',
					primaryText: '',
					text: '',
					background: '',
					fieldBorder: '',
					fieldBackground: '',
					fieldFocusBackground: '',
					popupBackground: '',
				},
				theme.color
			));

			/*
			options.font = this.getEffectiveOption(options.font);
			options.dark = options.dark === 'auto'
				? undefined
				: this.getEffectiveOption(options.dark);
			options.style = this.getEffectiveOption(options.style);
			options.color = this.getEffectiveOption(options.color);
			*/
		}

		if (typeof options.font === 'string' || typeof options.font === 'object')
		{
			this.setFont(options.font);
		}

		if (typeof options.dark !== 'undefined')
		{
			this.setDark(options.dark);
		}

		if (typeof options.color === 'object')
		{
			this.setColor(options.color);
		}

		if (typeof options.shadow !== 'undefined')
		{
			this.setShadow(options.shadow);
		}

		if (typeof options.compact !== 'undefined')
		{
			this.setCompact(options.compact);
		}

		if (typeof options.border !== 'undefined')
		{
			this.setBorder(options.border);
		}

		if (typeof options.style !== 'undefined')
		{
			this.setStyle(options.style);
		}

		if (typeof options.backgroundImage !== 'undefined')
		{
			this.setBackgroundImage(options.backgroundImage);
		}
	}

	setProxy ({fonts}: Proxy)
	{
		if (typeof fonts !== 'undefined')
		{
			this.proxy.fonts = Array.isArray(fonts) ? fonts : [];
		}

		return this;
	}

	#replaceFontUriByProxy(uri)
	{
		if (typeof uri !== 'string' || !uri)
		{
			return uri;
		}

		this.proxy.fonts.forEach((item: ProxyItem) => {
			if (!item.source || !item.target)
			{
				return;
			}

			uri = uri.replace('https://' + item.source, 'https://' + item.target);
		})

		return uri;
	}

	setFont (family: string|Font, uri)
	{
		if (typeof family === 'object')
		{
			uri = family.uri;
			family = family.family;
		}
		this.font.family = family || '';
		this.font.uri = this.font.family ? uri || '' : '';
	}

	setShadow (shadow: boolean)
	{
		this.shadow = !!shadow;
	}

	setCompact (compact: boolean)
	{
		this.compact = !!compact;
	}

	setBackgroundImage (url: UrlString)
	{
		this.backgroundImage = url;
	}

	setBorder (border: boolean|Border)
	{
		if (typeof border === 'object')
		{
			if (typeof border.top !== 'undefined')
			{
				this.border.top = !!border.top;
			}
			if (typeof border.right !== 'undefined')
			{
				this.border.right = !!border.right;
			}
			if (typeof border.bottom !== 'undefined')
			{
				this.border.bottom = !!border.bottom;
			}
			if (typeof border.left !== 'undefined')
			{
				this.border.left = !!border.left;
			}
		}
		else
		{
			border = !!border;
			this.border.top = border;
			this.border.right = border;
			this.border.bottom = border;
			this.border.left = border;
		}
	}

	setDark (dark: boolean)
	{
		this.dark = typeof dark === 'boolean' ? dark : null;
	}

	setColor (color: Color)
	{
		if (typeof color.primary !== 'undefined')
		{
			this.color.primary = Util.Color.fillHex(color.primary, true);
		}
		if (typeof color.primaryText !== 'undefined')
		{
			this.color.primaryText = Util.Color.fillHex(color.primaryText, true);
		}

		if (typeof color.text !== 'undefined')
		{
			this.color.text = Util.Color.fillHex(color.text, true);
		}
		if (typeof color.background !== 'undefined')
		{
			const isPopupColorDepend = this.color.popupBackground === this.color.background;
			this.color.background = Util.Color.fillHex(color.background, true);
			if (isPopupColorDepend || this.color.popupBackground.length === 0)
			{
				this.color.popupBackground = Util.Color.fillHex(color.background, true, 'ff');
			}
		}

		if (typeof color.fieldBorder !== 'undefined')
		{
			this.color.fieldBorder = Util.Color.fillHex(color.fieldBorder, true);
		}
		if (typeof color.fieldBackground !== 'undefined')
		{
			this.color.fieldBackground = Util.Color.fillHex(color.fieldBackground, true);
		}
		if (typeof color.fieldFocusBackground !== 'undefined')
		{
			this.color.fieldFocusBackground = Util.Color.fillHex(color.fieldFocusBackground, true);
		}
		if (typeof color.popupBackground !== 'undefined')
		{
			this.color.popupBackground = Util.Color.fillHex(color.popupBackground, true);
		}
	}

	setStyle (style: Style)
	{
		this.style = style;
	}

	getFontUri (): Array
	{
		return this.#replaceFontUriByProxy(this.font.uri);
	}

	getFontFamily (): Array
	{
		return this.font.family;
	}

	getEffectiveOption (option: Object|String|null)
	{
		switch (typeof option)
		{
			case "object":
				let result = undefined;
				for (let key in option)
				{
					if (option.hasOwnProperty(key))
					{
						continue;
					}

					let value = this.getEffectiveOption(option);
					if (value)
					{
						result = result || {};
						result[key] = option;
					}
				}

				return result;

			case "string":
				if (option)
				{
					return option;
				}
				break;
		}

		return undefined;
	}

	isDark ()
	{
		if (this.dark !== null)
		{
			return this.dark;
		}

		if (!this.color.background)
		{
			return false;
		}

		if (this.color.background.indexOf('#') !== 0)
		{
			return false;
		}

		return Util.Color.isHexDark(this.color.background);
	}

	isAutoDark ()
	{
		return this.dark === null;
	}
}

export {
	Options,
	Model
}