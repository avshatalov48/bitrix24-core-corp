import '../../bootstrap-bx';
import '../../../../common';
import { extractReceivers } from '../../../src/internal/extract-receivers';
import { Receiver } from '../../../src/receiver';
import { ItemIdentifier } from 'crm.data-structures';
import { Runtime } from 'main.core';

const rootSource = new ItemIdentifier(2, 13840);

// was copied from a real browser event
const entityData = {
	"ID": "13840",
	"MULTIFIELD_DATA": {
		"PHONE": {
			"4_1010": [
				{
					"ID": "2018",
					"VALUE": "+79992221133",
					"VALUE_TYPE": "WORK",
					"VALUE_EXTRA": {
						"COUNTRY_CODE": "RU"
					},
					"VALUE_FORMATTED": "+7 999 222-11-33",
					"COMPLEX_ID": "PHONE_WORK",
					"COMPLEX_NAME": "Рабочий"
				},
				{
					"ID": "2019",
					"VALUE": "+79221115522",
					"VALUE_TYPE": "MAILING",
					"VALUE_EXTRA": {
						"COUNTRY_CODE": "RU"
					},
					"VALUE_FORMATTED": "+7 922 111-55-22",
					"COMPLEX_ID": "PHONE_MAILING",
					"COMPLEX_NAME": "Для рассылок"
				}
			],
			"3_3260": [
				{
					"ID": "2016",
					"VALUE": "+79991112233",
					"VALUE_TYPE": "WORK",
					"VALUE_EXTRA": {
						"COUNTRY_CODE": "RU"
					},
					"VALUE_FORMATTED": "+7 999 111-22-33",
					"COMPLEX_ID": "PHONE_WORK",
					"COMPLEX_NAME": "Рабочий"
				}
			]
		},
		"EMAIL": {
			"4_1010": [
				{
					"ID": "2020",
					"VALUE": "mail@example.com",
					"VALUE_TYPE": "WORK",
					"VALUE_EXTRA": {
						"COUNTRY_CODE": ""
					},
					"VALUE_FORMATTED": "mail@example.com",
					"COMPLEX_ID": "EMAIL_WORK",
					"COMPLEX_NAME": "Рабочий"
				}
			],
			"3_3260": [
				{
					"ID": "2017",
					"VALUE": "mail@example.com",
					"VALUE_TYPE": "WORK",
					"VALUE_EXTRA": {
						"COUNTRY_CODE": ""
					},
					"VALUE_FORMATTED": "mail@example.com",
					"COMPLEX_ID": "EMAIL_WORK",
					"COMPLEX_NAME": "Рабочий"
				}
			]
		}
	},
	"CLIENT_INFO": {
		"COMPANY_DATA": [
			{
				"id": "1010",
				"type": "company",
				"typeName": "COMPANY",
				"typeNameTitle": "Компания",
				"place": "company",
				"hidden": false,
				"title": "Компания #1010",
				"url": "/crm/company/details/1010/",
				"desc": "Клиент, Информационные технологии",
				"image": "",
				"permissions": {
					"canUpdate": true
				},
				"largeImage": "",
				"advancedInfo": {
					"multiFields": [
						{
							"ID": "2018",
							"ENTITY_ID": "1010",
							"ENTITY_TYPE_NAME": "COMPANY",
							"TYPE_ID": "PHONE",
							"VALUE_TYPE": "WORK",
							"VALUE": "+79992221133",
							"VALUE_EXTRA": {
								"COUNTRY_CODE": "RU"
							},
							"VALUE_FORMATTED": "+7 999 222-11-33",
							"COMPLEX_ID": "PHONE_WORK",
							"COMPLEX_NAME": "Рабочий"
						},
						{
							"ID": "2019",
							"ENTITY_ID": "1010",
							"ENTITY_TYPE_NAME": "COMPANY",
							"TYPE_ID": "PHONE",
							"VALUE_TYPE": "MAILING",
							"VALUE": "+79221115522",
							"VALUE_EXTRA": {
								"COUNTRY_CODE": "RU"
							},
							"VALUE_FORMATTED": "+7 922 111-55-22",
							"COMPLEX_ID": "PHONE_MAILING",
							"COMPLEX_NAME": "Для рассылок"
						},
						{
							"ID": "2020",
							"ENTITY_ID": "1010",
							"ENTITY_TYPE_NAME": "COMPANY",
							"TYPE_ID": "EMAIL",
							"VALUE_TYPE": "WORK",
							"VALUE": "mail@example.com",
							"VALUE_EXTRA": {
								"COUNTRY_CODE": ""
							},
							"VALUE_FORMATTED": "mail@example.com",
							"COMPLEX_ID": "EMAIL_WORK",
							"COMPLEX_NAME": "Рабочий"
						}
					],
					"requisiteData": [],
					"hasEditRequisiteData": true
				}
			}
		],
		"CONTACT_DATA": [
			{
				"id": "3260",
				"type": "contact",
				"typeName": "CONTACT",
				"typeNameTitle": "Контакт",
				"place": "contact",
				"hidden": false,
				"title": "Контакт #3260",
				"url": "/crm/contact/details/3260/",
				"desc": "",
				"image": "",
				"permissions": {
					"canUpdate": true
				},
				"largeImage": "",
				"advancedInfo": {
					"contactType": {
						"id": "CLIENT",
						"name": "Клиенты"
					},
					"multiFields": [
						{
							"ID": "2016",
							"ENTITY_ID": "3260",
							"ENTITY_TYPE_NAME": "CONTACT",
							"TYPE_ID": "PHONE",
							"VALUE_TYPE": "WORK",
							"VALUE": "+79991112233",
							"VALUE_EXTRA": {
								"COUNTRY_CODE": "RU"
							},
							"VALUE_FORMATTED": "+7 999 111-22-33",
							"COMPLEX_ID": "PHONE_WORK",
							"COMPLEX_NAME": "Рабочий"
						},
						{
							"ID": "2017",
							"ENTITY_ID": "3260",
							"ENTITY_TYPE_NAME": "CONTACT",
							"TYPE_ID": "EMAIL",
							"VALUE_TYPE": "WORK",
							"VALUE": "mail@example.com",
							"VALUE_EXTRA": {
								"COUNTRY_CODE": ""
							},
							"VALUE_FORMATTED": "mail@example.com",
							"COMPLEX_ID": "EMAIL_WORK",
							"COMPLEX_NAME": "Рабочий"
						}
					],
					"bindings": {
						"COMPANY": []
					},
					"requisiteData": [],
					"hasEditRequisiteData": true
				}
			}
		]
	},
};

const receivers = [
	new Receiver(
		rootSource,
		new ItemIdentifier(4, 1010),
		{
			id: 2018,
			typeId: 'PHONE',
			valueType: 'WORK',
			value: '+79992221133',
			valueFormatted: '+7 999 222-11-33',
		},
		{
			title: 'Компания #1010',
		}
	),
	new Receiver(
		rootSource,
		new ItemIdentifier(4, 1010),
		{
			id: 2019,
			typeId: 'PHONE',
			valueType: 'MAILING',
			value: '+79221115522',
			valueFormatted: '+7 922 111-55-22',
		},
		{
			title: 'Компания #1010',
		}
	),
	new Receiver(
		rootSource,
		new ItemIdentifier(3, 3260),
		{
			id: 2016,
			typeId: 'PHONE',
			valueType: 'WORK',
			value: '+79991112233',
			valueFormatted: '+7 999 111-22-33',
		},
		{
			title: 'Контакт #3260',
		},
	),
	new Receiver(
		rootSource,
		new ItemIdentifier(4, 1010),
		{
			id: 2020,
			typeId: 'EMAIL',
			valueType: 'WORK',
			value: 'mail@example.com',
			valueFormatted: 'mail@example.com',
		},
		{
			title: 'Компания #1010',
		}
	),
	new Receiver(
		rootSource,
		new ItemIdentifier(3, 3260),
		{
			id: 2017,
			typeId: 'EMAIL',
			valueType: 'WORK',
			value: 'mail@example.com',
			valueFormatted: 'mail@example.com',
		},
		{
			title: 'Контакт #3260',
		}
	)
];
receivers.sort();

describe('extractReceivers', function() {
	it('should extract receivers correctly', function() {
		const extracted = extractReceivers(rootSource, entityData);
		extracted.sort();

		assert.deepEqual(extracted, receivers);
	});

	it('should extract receivers correctly when there is only CLIENT_INFO', function() {
		const entityDataWithoutMultifieldData = Runtime.clone(entityData);
		delete entityDataWithoutMultifieldData.MULTIFIELD_DATA;

		const extracted = extractReceivers(rootSource, entityDataWithoutMultifieldData);
		extracted.sort();

		assert.deepEqual(extracted, receivers);
	});
});
