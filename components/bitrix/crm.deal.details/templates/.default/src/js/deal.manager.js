//@flow
import {Cache, Loc} from 'main.core';
import {DealOnboardingManager, OnboardingData} from "./deal.onboarding.manager";


type Params = {
	guid: string;
};



export class DealManager
{
	#dealGuid: string;
	#dealDetailManager: BX.Crm.DealDetailManager|null;
	#dealOnboardingManager: DealOnboardingManager|null = null;
	#cache = new Cache.MemoryCache();

	constructor(params: Params)
	{
		this.#dealGuid = params.guid;
		this.#dealDetailManager = BX.Crm.EntityDetailManager.get(this.#dealGuid);
	}

	getContainer(): HTMLElement
	{
		return this.#cache.remember('container', () => {
			return document.getElementById(this.#dealGuid + '_container');
		});
	}

	getDealDetailManager(): BX.Crm.DealDetailManager|null
	{
		return this.#dealDetailManager;
	}

	enableOnboardingChain(onboardingData: OnboardingData, serviceUrl: string)
	{
		if (this.#dealOnboardingManager === null && this.getDealDetailManager() !== null)
		{
			this.#dealOnboardingManager  = new DealOnboardingManager({
				onboardingData: onboardingData,
				contentContainer: this.getContainer(),
				serviceUrl: serviceUrl,
				dealDetailManager: this.getDealDetailManager()
			});
			this.#dealOnboardingManager.processOnboarding();
		}
	}
}