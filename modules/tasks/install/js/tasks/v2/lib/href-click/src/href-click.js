import { Dom, Tag } from 'main.core';

export const hrefClick = (href: string): void => {
	const a = Tag.render`
		<a href="${href}" target="_top"></a>
	`;
	Dom.append(a, document.body);
	a.click();
	a.remove();
};
