import {Type} from 'main.core';
import "ui.feedback.form";

export function isHexDark(hex): boolean
{
	return Color.isHexDark(hex);
}

export function hexToRgba(hex): boolean
{
	return Color.hexToRgba(hex);
}

export function getHexFromOpacity(opacity: number): string
{
	if (Type.isString(opacity) && opacity.includes('%'))
	{
		opacity = Number.parseInt(opacity) / 100;
	}

	const hex = Math.round(opacity * 255).toString(16);
	return hex.length === 1 ? '0'+hex : hex;
}

export function getOpacityFromHex(hexA: string): number
{
	return Math.round((Number.parseInt(hexA, 16) / 255) * 100) / 100;
}

export function openFeedbackForm()
{
	BX.UI.Feedback.Form.open(
		{
			id: 'crm.webform.embed.feedback',
			forms: [
				{zones: ['en', 'eu', 'in', 'uk'], id: 372, lang: 'en', sec: 'qxzl3o'},
				{zones: ['by'], id: 362, lang: 'by', sec: 'gha9ge'},
				{zones: ['kz'], id: 362, lang: 'kz', sec: 'gha9ge'},
				{zones: ['ru'], id: 362, lang: 'ru', sec: 'gha9ge'},
				{zones: ['com.br'], id: 364, lang: 'br', sec: 'g649rj'},
				{zones: ['la', 'co', 'mx'], id: 366, lang: 'es', sec: 's80g9o'},
				{zones: ['de'], id: 368, lang: 'de', sec: 'bcmkrl'},
				{zones: ['ua'], id: 370, lang: 'ua', sec: 'ue05ne'},
				// {zones: ['pl'], id: 994, lang: 'pl', sec: 'qtxmku'},
			],
		}
	);
}

// copy from crm.site.form
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

