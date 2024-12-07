import { Runtime, Tag, Type } from 'main.core';

export async function showNotification(content: string | HTMLElement): void
{
	Runtime.loadExtension('ui.notification')
		.then(({ BX }) => {
			BX.UI.Notification.Center.notify({
				content,
			});
		})
		.catch(() => {
			if (Type.isElementNode(content))
			{
				// eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs,no-alert
				alert(content.innerText);
			}
			else
			{
				// eslint-disable-next-line @bitrix24/bitrix24-rules/no-native-dialogs,no-alert
				alert(content);
			}
		});
}

export function highlightText(text: string, searchTerm: string): string
{
	if (!searchTerm || !text)
	{
		return text;
	}

	const lowerSearchTerm = searchTerm.toLowerCase();

	const regex = new RegExp(lowerSearchTerm, 'gi');

	return text.replace(regex, (match) => `<mark>${match}</mark>`);
}

export function wrapTextToHtmlWithWordBreak(text: string): HTMLElement
{
	return Tag.render`<span style="word-break: break-word;">${text}</span>`;
}
