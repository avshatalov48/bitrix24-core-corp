type Analytics = {
	event: string,
	tool: string,
	category: string,
	c_element: string,
};

export const AnalyticsSourceType = Object.freeze({
	HEADER: 'header',
	CARD: 'card',
	DETAIL: 'dept_menu',
	NODE_DETAIL: 'details',
	PLUS: 'plus',
});

export type {
	Analytics,
};
