// eslint-disable-next-line no-unused-vars
type AnalyticsDTO = {
	event: string,
	tool: string,
	category: string,
	c_section?: string,
	c_sub_section?: string,
	c_element?: string,
	type?: string,
	status?: 'success' | 'error' | 'attempt',
	p1?: string,
	p2?: string,
	p3?: string,
	p4?: string,
	p5?: string,
};
