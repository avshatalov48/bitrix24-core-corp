/**
 * Bitrix Im mobile
 * Core application
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2020 Bitrix
 */
import {Controller} from "im.controller";
import {ApplicationLauncher} from "./launcher";

import {PULL as Pull, PullClient} from "mobile.pull.client";
import {VuexBuilder} from "ui.vue.vuex";

class CoreApplication
{
	constructor()
	{
		this.inited = false;
		this.initPromise = new BX.Promise;

		this.loadParams();
	}

	loadParams()
	{
		if (typeof BX.componentParameters === 'undefined')
		{
			setTimeout(this.loadParams.bind(this), 10);
			return false;
		}

		BX.componentParameters.init().then(params => {
			this.controller = new Controller({
				host: currentDomain,
				userId: params.USER_ID,
				siteDir: params.SITE_DIR,
				siteId: params.SITE_ID,
				languageId: params.LANGUAGE_ID,
				pull: {
					instance: PullClient,
					client: Pull,
				},
				vuexBuilder: {
					database: true,
					databaseName: 'mobile/im',
					databaseType: VuexBuilder.DatabaseType.jnSharedStorage,
				}
			});

			this.controller.ready().then(core => {
				this.inited = true;
				this.initPromise.resolve(core);
			});
		});
	}

	ready()
	{
		if (this.inited)
		{
			let promise = new BX.Promise;
			promise.resolve(this.controller);

			return promise;
		}

		return this.initPromise;
	}
}

let Core = new CoreApplication();
export {Core, ApplicationLauncher as Launch};