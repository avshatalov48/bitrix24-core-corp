import {ajax} from 'main.core';

class Backend
{
	static disableExternalLink(objectId): Promise
	{
		return ajax.runAction('disk.api.commonActions.disableExternalLink', {
			data: {
				objectId: objectId
			}
		});
	}

	static generateExternalLink(objectId): Promise
	{
		return ajax.runAction('disk.api.commonActions.generateExternalLink', {
			data: {
				objectId: objectId
			}
		})
	}

	static getExternalLink(objectId): Promise
	{
		return ajax.runAction('disk.api.commonActions.getExternalLink', {
			data: {
				objectId: objectId
			}
		})
	}

	static setDeathTime(externalLinkId: number, deathTimeTimestamp: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.setDeathTime', {
				data: {
					externalLinkId: externalLinkId,
					deathTime: deathTimeTimestamp
				}
			}).then(resolve, reject)
		});
	}

	static revokeDeathTime(externalLinkId: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.revokeDeathTime', {
				data: {
					externalLinkId: externalLinkId
				}
			}).then(resolve, reject)
		});
	}

	static setPassword(externalLinkId: number, newPassword: string): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.setPassword', {
				data: {
					externalLinkId: externalLinkId,
					newPassword: newPassword
				}
			}).then(resolve, reject)
		});
	}

	static revokePassword(externalLinkId: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.revokePassword', {
				data: {
					externalLinkId: externalLinkId
				}
			}).then(resolve, reject)
		});
	}

	static allowEditDocument(externalLinkId: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.allowEditDocument', {
				data: {
					externalLinkId: externalLinkId
				}
			}).then(resolve, reject)
		});
	}

	static disallowEditDocument(externalLinkId: number): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('disk.api.externalLink.disallowEditDocument', {
				data: {
					externalLinkId: externalLinkId
				}
			}).then(resolve, reject)
		});
	}
}

class BackendForTrackedObject extends Backend
{
	static disableExternalLink(objectId): Promise
	{
		BX.ajax.runAction('disk.api.trackedObject.disableExternalLink', {
			data: {
				objectId: objectId
			}
		});
	}

	static generateExternalLink(objectId): Promise
	{
		return ajax.runAction('disk.api.trackedObject.generateExternalLink', {
			data: {
				objectId: objectId
			}
		})
	}

	static getExternalLink(objectId): Promise
	{
		return ajax.runAction('disk.api.trackedObject.getExternalLink', {
			data: {
				objectId: objectId
			}
		})
	}
}

export {
	Backend, BackendForTrackedObject
}