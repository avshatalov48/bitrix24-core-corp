import { Event, Loc, Tag, Dom, Type, Text } from 'main.core';
import type { SiteThemeOptions } from './site-theme-picker-field';
import type { SiteTitleInputType } from './site-title-field';

export function setPortalSettings(container: HTMLElement, portalSettings: SiteTitleInputType): void
{
	const logoNode = container.querySelector('[data-role="logo"]');
	const titleNode =  container.querySelector('[data-role="title"]');
	const logo24Node = container.querySelector('[data-role="logo24"]');

	if (!logoNode.hasAttribute('data-prev-display'))
	{
		logoNode.dataset.prevDisplay = logoNode.style.display;
		titleNode.dataset.prevDisplay = titleNode.style.display;
		logo24Node.dataset.prevDisplay = logo24Node.style.display;
	}

	if (Type.isUndefined(portalSettings.title) !== true)
	{
		titleNode.innerHTML = Text.encode(Type.isStringFilled(portalSettings.title) ?
			portalSettings.title : 'Bitrix')
		;
	}

	if (Type.isUndefined(portalSettings.logo24) !== true)
	{
		if (Type.isStringFilled(portalSettings.logo24))
		{
			delete logo24Node.dataset.visibility;
			if (logoNode.style.display === 'none')
			{
				logo24Node.style.removeProperty('display');
			}
		}
		else
		{
			logo24Node.dataset.visibility = 'hidden';
			logo24Node.style.display = 'none';
		}
	}

	if (Type.isUndefined(portalSettings.logo) !== true)
	{
		if (Type.isPlainObject(portalSettings.logo))
		{
			logoNode.style.backgroundImage = 'url("' + encodeURI(portalSettings.logo.src) + '")';
			logoNode.style.removeProperty('display');
			titleNode.style.display = 'none';
			logo24Node.style.display = 'none';
		}
		else
		{
			logoNode.style.display = 'none';
			titleNode.style.removeProperty('display');

			if (logo24Node.dataset.visibility !== 'hidden')
			{
				logo24Node.style.removeProperty('display');
			}
			else
			{
				logo24Node.style.display = 'none';
			}
		}
	}
}

export function setPortalThemeSettings(container: HTMLElement, themeSettings: SiteThemeOptions): void
{
	const theme = Type.isPlainObject(themeSettings) ? themeSettings : {};

	const lightning = String(theme.id).indexOf('dark:') === 0 ? 'dark' : 'light';

	Dom.removeClass(container, '--light --dark');
	Dom.addClass(container, '--' + lightning);

	if (Type.isStringFilled(theme.previewImage))
	{
		container.style.backgroundImage = 'url("' + theme.previewImage + '")';
		container.style.backgroundSize = 'cover';
	}
	else
	{
		container.style.removeProperty('backgroundImage');
		container.style.removeProperty('backgroundSize');
		container.style.background = 'none';
	}

	if (Type.isStringFilled(theme.previewColor))
	{
		container.style.backgroundColor = theme.previewColor;
	}
}
