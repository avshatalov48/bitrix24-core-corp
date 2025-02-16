/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,booking_const,booking_core) {
	'use strict';

	function mapDtoToModel(clientDto) {
	  if (!main_core.Type.isArrayFilled(Object.values(clientDto.data))) {
	    return null;
	  }
	  return {
	    id: clientDto.id,
	    name: clientDto.data.name,
	    image: clientDto.data.image,
	    type: clientDto.type,
	    phones: clientDto.data.phones,
	    emails: clientDto.data.emails,
	    isReturning: clientDto.isReturning
	  };
	}

	let _ = t => t,
	  _t;
	const VALUE_TYPE = 'WORK';
	const MethodName = Object.freeze({
	  AddFormattedName: 'crm.controller.integration.booking.contact.addFormattedName',
	  ParseFormattedName: 'crm.controller.integration.booking.contact.parseFormattedName',
	  CompanyAdd: 'crm.company.add',
	  ContactAdd: 'crm.contact.add',
	  CompanyGet: 'crm.company.get',
	  ContactGet: 'crm.contact.get',
	  GetCompanyContacts: 'crm.company.contact.items.get',
	  CompanyUpdate: 'crm.company.update',
	  ContactUpdate: 'crm.contact.update'
	});
	const RequestKey = Object.freeze({
	  AddFormattedName: 'add_formatted_name',
	  ParseName: 'parse_name_#id#',
	  CompanyAdd: 'company_add_#id#',
	  ContactAdd: 'contact_add_#id#',
	  CompanyGet: 'company_get_#id#',
	  ContactGet: 'contact_get_#id#',
	  GetCompanyContacts: 'get_company_contacts',
	  CompanyUpdate: 'company_update_#id#',
	  ContactUpdate: 'contact_update_#id#'
	});
	var _requestSaveMany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestSaveMany");
	var _isClientToUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isClientToUpdate");
	var _getParseNameMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getParseNameMethods");
	var _getCompanyAddMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyAddMethods");
	var _getContactAddMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContactAddMethods");
	var _getCompanyGetMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyGetMethods");
	var _getContactGetMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContactGetMethods");
	var _getCompanyUpdateMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyUpdateMethods");
	var _getContactUpdateMethods = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContactUpdateMethods");
	var _prepareContactNameFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareContactNameFields");
	var _prepareCommunicationsForUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareCommunicationsForUpdate");
	var _requestLinkedContactId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestLinkedContactId");
	var _getRequestKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRequestKey");
	class ClientService {
	  constructor() {
	    Object.defineProperty(this, _getRequestKey, {
	      value: _getRequestKey2
	    });
	    Object.defineProperty(this, _requestLinkedContactId, {
	      value: _requestLinkedContactId2
	    });
	    Object.defineProperty(this, _prepareCommunicationsForUpdate, {
	      value: _prepareCommunicationsForUpdate2
	    });
	    Object.defineProperty(this, _prepareContactNameFields, {
	      value: _prepareContactNameFields2
	    });
	    Object.defineProperty(this, _getContactUpdateMethods, {
	      value: _getContactUpdateMethods2
	    });
	    Object.defineProperty(this, _getCompanyUpdateMethods, {
	      value: _getCompanyUpdateMethods2
	    });
	    Object.defineProperty(this, _getContactGetMethods, {
	      value: _getContactGetMethods2
	    });
	    Object.defineProperty(this, _getCompanyGetMethods, {
	      value: _getCompanyGetMethods2
	    });
	    Object.defineProperty(this, _getContactAddMethods, {
	      value: _getContactAddMethods2
	    });
	    Object.defineProperty(this, _getCompanyAddMethods, {
	      value: _getCompanyAddMethods2
	    });
	    Object.defineProperty(this, _getParseNameMethods, {
	      value: _getParseNameMethods2
	    });
	    Object.defineProperty(this, _isClientToUpdate, {
	      value: _isClientToUpdate2
	    });
	    Object.defineProperty(this, _requestSaveMany, {
	      value: _requestSaveMany2
	    });
	  }
	  async saveMany(clients) {
	    try {
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _requestSaveMany)[_requestSaveMany](clients);
	      await booking_core.Core.getStore().dispatch('clients/upsertMany', data);
	      return {
	        clients: data.map(({
	          id,
	          type
	        }) => ({
	          id,
	          type
	        }))
	      };
	    } catch (error) {
	      console.error('ClientService: saveMany error', error);
	      return {
	        error
	      };
	    }
	  }
	  async getLinkedContactByCompany(companyData) {
	    var _company$contactId;
	    const company = booking_core.Core.getStore().getters['clients/getByClientData'](companyData);
	    (_company$contactId = company.contactId) != null ? _company$contactId : company.contactId = await babelHelpers.classPrivateFieldLooseBase(this, _requestLinkedContactId)[_requestLinkedContactId](companyData);
	    await booking_core.Core.getStore().dispatch('clients/update', {
	      id: company.id,
	      client: company
	    });
	    return booking_core.Core.getStore().getters['clients/getByClientData']({
	      id: company.contactId,
	      type: {
	        module: booking_const.Module.Crm,
	        code: booking_const.CrmEntity.Contact
	      }
	    });
	  }
	}
	async function _requestSaveMany2(clients) {
	  const companies = clients.filter(client => client.type.code === booking_const.CrmEntity.Company);
	  const contacts = clients.filter(client => client.type.code === booking_const.CrmEntity.Contact);
	  const companiesToAdd = companies.filter(client => !client.id);
	  const companiesToUpdate = companies.filter(client => babelHelpers.classPrivateFieldLooseBase(this, _isClientToUpdate)[_isClientToUpdate](client));
	  const contactsToAdd = contacts.filter(client => !client.id);
	  const contactsToUpdate = contacts.filter(client => babelHelpers.classPrivateFieldLooseBase(this, _isClientToUpdate)[_isClientToUpdate](client));
	  const clientsToRequest = [...companiesToAdd, ...companiesToUpdate, ...contactsToAdd, ...contactsToUpdate];
	  clientsToRequest.forEach((client, index) => {
	    client.index = index;
	  });
	  const restMethods = {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getParseNameMethods)[_getParseNameMethods]([...contactsToAdd, ...contactsToUpdate]),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getCompanyAddMethods)[_getCompanyAddMethods](companiesToAdd),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getContactAddMethods)[_getContactAddMethods](contactsToAdd, companies),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getCompanyGetMethods)[_getCompanyGetMethods](companiesToUpdate),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getCompanyUpdateMethods)[_getCompanyUpdateMethods](companiesToUpdate),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getContactGetMethods)[_getContactGetMethods](contactsToUpdate),
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getContactUpdateMethods)[_getContactUpdateMethods](contactsToUpdate)
	  };
	  const result = await new Promise(resolve => {
	    if (Object.keys(restMethods).length === 0) {
	      resolve([]);
	    }
	    BX.rest.callBatch(restMethods, batchResult => resolve(batchResult));
	  });
	  const errors = Object.values(result).map(ajaxResult => {
	    var _ajaxResult$answer$er;
	    return (_ajaxResult$answer$er = ajaxResult.answer.error) == null ? void 0 : _ajaxResult$answer$er.error_description;
	  }).filter(error => error);
	  if (main_core.Type.isArrayFilled(errors)) {
	    throw new Error(main_core.Tag.render(_t || (_t = _`<span>${0}</span>`), errors[0]).textContent);
	  }
	  companiesToAdd.forEach(client => {
	    client.id = result[babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyAdd, client.index)].data();
	  });
	  contactsToAdd.forEach(client => {
	    client.id = result[babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactAdd, client.index)].data();
	  });
	  return clients;
	}
	function _isClientToUpdate2(client) {
	  if (!client.id) {
	    return false;
	  }
	  const currentClient = booking_core.Core.getStore().getters['clients/getByClientData'](client);
	  return client.name !== currentClient.name || client.phones[0] !== currentClient.phones[0] || client.emails[0] !== currentClient.emails[0];
	}
	function _getParseNameMethods2(contacts) {
	  return contacts.reduce((methods, client) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ParseName, client.index)]: {
	      method: MethodName.ParseFormattedName,
	      params: {
	        fields: {
	          FORMATTED_NAME: client.name
	        }
	      }
	    }
	  }), {});
	}
	function _getCompanyAddMethods2(companiesToAdd) {
	  return companiesToAdd.reduce((methods, client) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyAdd, client.index)]: {
	      method: MethodName.CompanyAdd,
	      params: {
	        fields: {
	          TITLE: client.name,
	          PHONE: client.phones.map(VALUE => ({
	            VALUE,
	            VALUE_TYPE
	          })),
	          EMAIL: client.emails.map(VALUE => ({
	            VALUE,
	            VALUE_TYPE
	          }))
	        },
	        params: {
	          REGISTER_SONET_EVENT: 'Y'
	        }
	      }
	    }
	  }), {});
	}
	function _getContactAddMethods2(contactsToAdd, companies) {
	  return contactsToAdd.reduce((methods, client) => {
	    var _companies$0$id, _companies$;
	    const COMPANY_ID = (_companies$0$id = (_companies$ = companies[0]) == null ? void 0 : _companies$.id) != null ? _companies$0$id : `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyAdd)}]`;
	    return {
	      ...methods,
	      [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactAdd, client.index)]: {
	        method: MethodName.ContactAdd,
	        params: {
	          fields: {
	            COMPANY_ID: companies.length > 0 ? COMPANY_ID : undefined,
	            ...babelHelpers.classPrivateFieldLooseBase(this, _prepareContactNameFields)[_prepareContactNameFields](client.index),
	            PHONE: client.phones.map(VALUE => ({
	              VALUE,
	              VALUE_TYPE
	            })),
	            EMAIL: client.emails.map(VALUE => ({
	              VALUE,
	              VALUE_TYPE
	            }))
	          },
	          params: {
	            REGISTER_SONET_EVENT: 'Y'
	          }
	        }
	      }
	    };
	  }, {});
	}
	function _getCompanyGetMethods2(companies) {
	  return companies.reduce((methods, {
	    id
	  }) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyGet, id)]: [MethodName.CompanyGet, {
	      id
	    }]
	  }), {});
	}
	function _getContactGetMethods2(contacts) {
	  return contacts.reduce((methods, {
	    id
	  }) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactGet, id)]: [MethodName.ContactGet, {
	      id
	    }]
	  }), {});
	}
	function _getCompanyUpdateMethods2(companiesToUpdate) {
	  return companiesToUpdate.reduce((methods, client) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyUpdate, client.id)]: {
	      method: MethodName.CompanyUpdate,
	      params: {
	        id: client.id,
	        fields: {
	          TITLE: client.name,
	          ...babelHelpers.classPrivateFieldLooseBase(this, _prepareCommunicationsForUpdate)[_prepareCommunicationsForUpdate](client)
	        },
	        params: {
	          REGISTER_SONET_EVENT: 'Y'
	        }
	      }
	    }
	  }), {});
	}
	function _getContactUpdateMethods2(contactsToUpdate) {
	  return contactsToUpdate.reduce((methods, client) => ({
	    ...methods,
	    [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactUpdate, client.id)]: {
	      method: MethodName.ContactUpdate,
	      params: {
	        id: client.id,
	        fields: {
	          ...babelHelpers.classPrivateFieldLooseBase(this, _prepareContactNameFields)[_prepareContactNameFields](client.index),
	          ...babelHelpers.classPrivateFieldLooseBase(this, _prepareCommunicationsForUpdate)[_prepareCommunicationsForUpdate](client)
	        },
	        params: {
	          REGISTER_SONET_EVENT: 'Y'
	        }
	      }
	    }
	  }), {});
	}
	function _prepareContactNameFields2(index) {
	  return {
	    NAME: `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ParseName, index)}][NAME]`,
	    SECOND_NAME: `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ParseName, index)}][SECOND_NAME]`,
	    LAST_NAME: `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ParseName, index)}][LAST_NAME]`
	  };
	}
	function _prepareCommunicationsForUpdate2(client) {
	  const currentClient = booking_core.Core.getStore().getters['clients/getByClientData'](client);
	  const requestKey = client.type.code === booking_const.CrmEntity.Company ? babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.CompanyGet, client.id) : babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactGet, client.id);
	  const PHONE = [{
	    ID: currentClient.phones[0] ? `$result[${requestKey}][PHONE][0][ID]` : undefined,
	    VALUE: client.phones[0],
	    VALUE_TYPE
	  }];
	  const EMAIL = [{
	    ID: currentClient.emails[0] ? `$result[${requestKey}][EMAIL][0][ID]` : undefined,
	    VALUE: client.emails[0],
	    VALUE_TYPE
	  }];
	  return {
	    PHONE: client.phones.length > 0 ? PHONE : undefined,
	    EMAIL: client.emails.length > 0 ? EMAIL : undefined
	  };
	}
	async function _requestLinkedContactId2(company) {
	  try {
	    const id = company.id;
	    const client = await new Promise(resolve => {
	      BX.rest.callBatch({
	        [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.GetCompanyContacts)]: [MethodName.GetCompanyContacts, {
	          id
	        }],
	        [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactGet)]: {
	          method: MethodName.ContactGet,
	          params: {
	            id: `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.GetCompanyContacts)}][0][CONTACT_ID]`
	          }
	        },
	        [babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.AddFormattedName)]: {
	          method: MethodName.AddFormattedName,
	          params: {
	            fields: `$result[${babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.ContactGet)}]`
	          }
	        }
	      }, result => {
	        var _data$PHOTO, _data$PHONE$map, _data$PHONE, _data$EMAIL$map, _data$EMAIL;
	        const data = result[babelHelpers.classPrivateFieldLooseBase(this, _getRequestKey)[_getRequestKey](RequestKey.AddFormattedName)].data();
	        if (!(data != null && data.ID)) {
	          resolve(null);
	        }
	        resolve({
	          id: Number(data.ID),
	          name: data.FORMATTED_NAME,
	          image: (_data$PHOTO = data.PHOTO) == null ? void 0 : _data$PHOTO.showUrl,
	          type: {
	            module: booking_const.Module.Crm,
	            code: booking_const.CrmEntity.Contact
	          },
	          phones: (_data$PHONE$map = (_data$PHONE = data.PHONE) == null ? void 0 : _data$PHONE.map(({
	            VALUE
	          }) => VALUE)) != null ? _data$PHONE$map : [],
	          emails: (_data$EMAIL$map = (_data$EMAIL = data.EMAIL) == null ? void 0 : _data$EMAIL.map(({
	            VALUE
	          }) => VALUE)) != null ? _data$EMAIL$map : []
	        });
	      });
	    });
	    if (client === null) {
	      return 0;
	    }
	    await booking_core.Core.getStore().dispatch('clients/upsert', client);
	    return client.id;
	  } catch (error) {
	    console.error('ClientService: loadLinkedContactByCompany error', error);
	    return 0;
	  }
	}
	function _getRequestKey2(template, id = 0) {
	  return template.replace('#id#', id);
	}
	const clientService = new ClientService();

	const ClientMappers = {
	  mapDtoToModel
	};

	exports.ClientMappers = ClientMappers;
	exports.clientService = clientService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX,BX.Booking.Const,BX.Booking));
//# sourceMappingURL=client-service.bundle.js.map
