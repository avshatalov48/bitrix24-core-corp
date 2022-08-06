import {Type} from 'main.core';
import {Color} from "../../../site/form/src/util/registry";
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