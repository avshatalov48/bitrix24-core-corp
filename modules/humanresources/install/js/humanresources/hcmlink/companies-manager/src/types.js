export type CompanyData = {
	id: number,
	title: string,
	notMappedCount: number,
};

export type CompanyManagerOptions = {
	companies: Array<CompanyData>,
};