import {Type} from "main.core";

class Utils
{
	constructor()
	{
	};

	getFileMimeType = (fileType) => {
		fileType = fileType.toString().toLowerCase();

		if (fileType.indexOf('/') !== -1) // iOS old form
		{
			return fileType;
		}

		let
			result = '';

		switch (fileType)
		{
			case 'png':
				result = 'image/png';
				break;
			case 'gif':
				result = 'image/gif';
				break;
			case 'jpg':
			case 'jpeg':
				result = 'image/jpeg';
				break;
			case 'heic':
				result = 'image/heic';
				break;
			case 'mp3':
				result = 'audio/mpeg';
				break;
			case 'mp4':
				result = 'video/mp4';
				break;
			case 'mpeg':
				result = 'video/mpeg';
				break;
			case 'ogg':
				result = 'video/ogg';
				break;
			case 'mov':
				result = 'video/quicktime';
				break;
			case 'zip':
				result = 'application/zip';
				break;
			case 'php':
				result = 'text/php';
				break;
			default:
				result = '';
		}

		return result;
	};

	getType = (mimeType) => {
		let result = mimeType.substring(0, mimeType.indexOf('/'));

		if (
			result !== 'image'
			&& result !== 'video'
			&& result !== 'audio'
		)
		{
			result = 'file';
		}

		return result;
	};

	getResizeOptions = (type) => {
		const
			mimeType = BX.MobileUtils.getFileMimeType(type),
			fileType = BX.MobileUtils.getType(mimeType);

		return (
			(
				fileType === 'image'
				&& mimeType !== 'image/gif'
			)
			|| fileType === 'video'
				? {
					quality: 80,
					width: 1920,
					height: 1080
				}
				: null
		);
	};

	getUploadFilename = (filename, type) => {
		const
			mimeType = BX.MobileUtils.getFileMimeType(type),
			fileType = BX.MobileUtils.getType(mimeType);

		if (
			fileType === 'image'
			|| fileType === 'video'
		)
		{
			let extension = filename.split('.').slice(-1)[0].toLowerCase();
			if (mimeType === 'image/heic')
			{
				extension = 'jpg';
			}
			filename = 'mobile_file_' + (new Date).toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.' + extension;
		}

		return filename;
	};

	static htmlWithInlineJS(node: HTMLElement, html, params = {}): Promise | string
	{
		const r = {
			script: /<script([^>]*)>/ig,
			script_end: /<\/script>/ig,
			script_src: /src=["\']([^"\']+)["\']/i,
			script_type: /type=["\']([^"\']+)["\']/i,
			space: /\s+/,
			ltrim: /^[\s\r\n]+/g,
			rtrim: /[\s\r\n]+$/g,
			style: /<link.*?(rel="stylesheet"|type="text\/css")[^>]*>/i,
			style_href: /href=["\']([^"\']+)["\']/i
		};

		type JsItem = {
			isInternal: boolean,
			JS: string,
		};

		function makeIterable(value: any): Array<any>
		{
			return Type.isArray(value) ? value : [value];
		}

		function externalStyles(acc: Array<string>, item: ?string)
		{
			if (Type.isString(item) && item !== '')
			{
				acc.push(item);
			}

			return acc;
		}

		function externalScripts(acc: Array<string>, item: JsItem)
		{
			if (!item.isInternal)
			{
				acc.push(item.JS);
			}

			return acc;
		}

		function inlineScripts(acc: Array<string>, item: JsItem)
		{
			if (item.isInternal)
			{
				acc.push(item.JS);
			}

			return acc;
		}

		function loadAll(items: Array<string>): Promise<void>
		{
			const itemsList = makeIterable(items);

			if (!itemsList.length)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				// eslint-disable-next-line
				BX.load(itemsList, resolve);
			});
		}

		if (Type.isNil(html) && Type.isDomNode(node))
		{
			return node.innerHTML;
		}

		// eslint-disable-next-line
		const parsedHtml = BX.processHTML(html);
		const externalCss = parsedHtml.STYLE.reduce(externalStyles, []);
		const externalJs = parsedHtml.SCRIPT.reduce(externalScripts, []);
		const inlineJs = parsedHtml.SCRIPT.reduce(inlineScripts, []);

		let
			inlineJsString = '',
			inlineJsStringNode = ''
		;

		// eslint-disable-next-line
		inlineJs.forEach(script => {
			inlineJsString += "\n" + script;
		});

		if (inlineJsString.length > 0)
		{
			inlineJsStringNode = `<span data-type="inline-script"><script>${inlineJsString}</script></span>`;
		}

		if (Type.isDomNode(node))
		{
			if (params.htmlFirst || (!externalJs.length && !externalCss.length))
			{
				node.innerHTML = parsedHtml.HTML + inlineJsStringNode;
			}
		}

		return Promise
			.all([
				loadAll(externalJs),
				loadAll(externalCss),
			])
			.then(() => {

				if (Type.isDomNode(node) && (externalJs.length > 0 || externalCss.length > 0))
				{
					node.innerHTML = parsedHtml.HTML + inlineJsStringNode;
				}

				BX.evalGlobal(inlineJsString);

				if (Type.isFunction(params.callback))
				{
					params.callback();
				}
			});
	}
}

let MobileUtils = new Utils;

export {
	Utils,
	MobileUtils
};
