import type { EngineAgreement } from 'ai.agreement';

export type EngineInfo = {
	agreement: EngineAgreement;
	code: string;
	expired: boolean;
	inTariff: boolean;
	partner: boolean;
	queue: boolean;
	selected: boolean;
	title: string;
}
