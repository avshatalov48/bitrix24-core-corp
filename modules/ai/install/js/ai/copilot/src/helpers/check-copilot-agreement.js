import type { CopilotAgreement as CopilotAgreementClass, CopilotAgreementOptions } from 'ai.copilot-agreement';
import { Runtime } from 'main.core';

export async function checkCopilotAgreement(options: CopilotAgreementOptions): Promise<boolean>
{
	try
	{
		const copilotAgreement: CopilotAgreementClass = await initCopilotAgreement(options);

		return copilotAgreement.checkAgreement();
	}
	catch (e)
	{
		console.error(e);

		return true;
	}
}

async function initCopilotAgreement(options: CopilotAgreementOptions): CopilotAgreementClass
{
	try
	{
		const { CopilotAgreement } = await Runtime.loadExtension('ai.copilot-agreement');

		return new CopilotAgreement(options);
	}
	catch (e)
	{
		console.error(e);

		return null;
	}
}
