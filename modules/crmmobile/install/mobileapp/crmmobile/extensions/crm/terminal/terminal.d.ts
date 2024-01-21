type Payment = {
	id: number,
	name?: string,
	date: number,
	accountNumber?: string,
	sum?: number,
	currency?: string,
	hasEntityBinding?: boolean,
	productsCnt?: number,
	phoneNumber?: string,
	companyId?: number,
	contactIds?: number[],
	isPaid?: boolean,
	datePaid?: number,
	paymentSystemId?: number,
	paymentSystemName?: string,
	accessCode?: string,
	slipLink?: string,
	permissions: object,
	isTerminalPayment?: boolean,
	terminalPaymentSystems: TerminalPaymentSystem[],
	fields: [],
};

type Check = {
	id: number,
	name?: string,
	date: number,
	url?: string,
};

type TerminalCreatePaymentProps = {
	sum: number,
	currency: string,
	phoneNumber?: string,
	client?: TerminalClientProps,
	clientName?: string,
};

type TerminalEntityCreatePaymentProps = {
	entityTypeId: number,
	entityId: number,
	products: Object[],
	responsibleId: number,
};

type TerminalClientProps = {
	id: number,
	entityTypeId: number,
};

type TerminalPaymentSystem = {
	handler: string,
	type: string,
	connected: boolean,
	id: number,
	title?: string,
};

type TerminalCreatePaymentSystemProps = {
	handler: string,
	type: string,
};

type TerminalInitiatesPaymentProps = {
	paymentId: number,
	paymentSystemId: number,
	accessCode: string,
};

type TerminalPaymentMethod = {
	type: string,
	paymentSystem?: TerminalPaymentSystem,
};
