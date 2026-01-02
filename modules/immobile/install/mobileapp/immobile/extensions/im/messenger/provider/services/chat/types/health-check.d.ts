declare type HealthStatus = {
	result: 'error' | 'ok',
	info: string,
	id?: string,
	statusInfo?: string,
	statusTitle?: string,
};
