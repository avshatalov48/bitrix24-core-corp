import type { SignOptions } from 'sign.v2.sign-settings';
import { B2BSignSettings } from 'sign.v2.b2b.sign-settings';
import { B2ESignSettings } from 'sign.v2.b2e.sign-settings';

const settings = {
	b2b: B2BSignSettings,
	b2e: B2ESignSettings,
};

export function createSignSettings(
	containerId: string,
	options: SignOptions,
): void
{
	const { type, uid } = options;
	const SignSettingsConstructor = settings[type] ?? B2BSignSettings;
	const signSettings = new SignSettingsConstructor(containerId, options);
	signSettings.init(uid);
}
