<?php
//CRM_DYNAMIC_TYPE table descriptions
$MESS['CRM_SMART_PROC_TABLE'] = "Смарт-процессы";
$MESS['CRM_SMART_PROC_FIELD_ENTITY_TYPE_ID'] = "Идентификатор типа";
$MESS['CRM_SMART_PROC_FIELD_ENTITY_TYPE_ID_FULL'] = "Идентификатор типа (EntityTypeId) смарт-процесса";
$MESS['CRM_SMART_PROC_FIELD_TITLE'] = "Название";
$MESS['CRM_SMART_PROC_FIELD_TITLE_FULL'] = "Название смарт-процесса";
$MESS['CRM_SMART_PROC_FIELD_DATASET_NAME'] = "Имя датасета смарт-процесса";
$MESS['CRM_SMART_PROC_FIELD_DATASET_NAME_FULL'] = "Имя датасета смарт-процесса";
$MESS['CRM_SMART_PROC_FIELD_AUTOMATED_SOLUTION_DATASET_NAME'] = "Имя датасета цифрового рабочего места";
$MESS['CRM_SMART_PROC_FIELD_AUTOMATED_SOLUTION_DATASET_NAME_FULL'] = "Имя датасета цифрового рабочего места если связано с процессом, иначе CRM";
$MESS['CRM_SMART_PROC_FIELD_CUSTOM_SECTION_ID'] = "Идентификатор цифрового рабочего места";
$MESS['CRM_SMART_PROC_FIELD_CUSTOM_SECTION_TITLE'] = "Название рабочего места";
$MESS['CRM_SMART_PROC_FIELD_PRODUCT_DATASET_NAME'] = "Имя датасета товаров смарт-процесса";
$MESS['CRM_SMART_PROC_FIELD_USER_FIELDS'] = "ID пользовательских полей";

//CRM_STAGES fields description
$MESS['CRM_STAGES_TABLE'] = "Стадии CRM";
$MESS['CRM_STAGES_FIELD_ID'] = "Уникальный ключ";
$MESS['CRM_STAGES_FIELD_ENTITY_TYPE_ID'] = "Идентификатор типа";
$MESS['CRM_STAGES_FIELD_STATUS_ID'] = "Идентификатор стадии";
$MESS['CRM_STAGES_FIELD_NAME'] = "Название стадии";
$MESS['CRM_STAGES_FIELD_CATEGORY_ID'] = "Идентификатор воронки";
$MESS['CRM_STAGES_FIELD_CATEGORY_NAME'] = "Название воронки";
$MESS['CRM_STAGES_FIELD_SORT'] = "Сортировка";
$MESS['CRM_STAGES_FIELD_SEMANTICS'] = "Тип стадии";

//CRM_ENTITY_RELATION table/field descriptions
$MESS['CRM_ENTITY_RELATION_TABLE'] = "Связи между элементами crm";
$MESS['CRM_ENTITY_RELATION_FIELD_SRC_ENTITY_TYPE_ID'] = "Идентификатор типа элемента, который связан";
$MESS['CRM_ENTITY_RELATION_FIELD_SRC_ENTITY_ID'] = "Идентификатор элемента, который связан";
$MESS['CRM_ENTITY_RELATION_FIELD_SRC_ENTITY_DATASET_NAME'] = "Название датасета элемента, который связан";
$MESS['CRM_ENTITY_RELATION_FIELD_DST_ENTITY_TYPE_ID'] = "Идентификатор типа элемента, с которым связан";
$MESS['CRM_ENTITY_RELATION_FIELD_DST_ENTITY_ID'] = "Идентификатор элемента, с которым связан";
$MESS['CRM_ENTITY_RELATION_FIELD_DST_ENTITY_DATASET_NAME'] = "Название датасета элемента, с которым связан";

//CRM_AUTOMATED_SOLUTION table/field descriptions
$MESS['CRM_AUTOMATED_SOLUTION_TABLE'] = "Цифровое рабочее место: #TITLE#";

//CRM_PRODUCT_ROW table/field for smart processes descriptions
$MESS['CRM_DYNAMIC_ITEMS_PROD_TABLE'] = "Смарт-процесс #TITLE#: товары";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_ID'] = "Уникальный идентификатор";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_ITEM_ID'] = "Идентификатор элемента смарт-процесса";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT'] = "Товар";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT_ID'] = "Идентификатор товара";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRODUCT_NAME'] = "Название товара";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE'] = "Цена";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_EXCLUSIVE'] = "Цена без налога со скидкой";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_NETTO'] = "Цена без скидок и налогов";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PRICE_BRUTTO'] = "Цена без скидок, с налогом";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_QUANTITY'] = "Количество";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE'] = "Скидка";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE_ID'] = "Идентификатор скидки";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_TYPE_NAME'] = "Название скидки";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_RATE'] = "Величина скидки";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_DISCOUNT_SUM'] = "Сумма скидки";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_TAX_RATE'] = "Налог";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_TAX_INCLUDED'] = "Налог включен в цену";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_CUSTOMIZED'] = "Изменена";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_CUSTOMIZED_FULL'] = "Товарная позиция была изменена вручную после добавления в сделку. Y - да, N - нет";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE'] = "Единица измерения";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE_CODE'] = "Идентификатор единицы измерения";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_MEASURE_NAME'] = "Название единицы измерения";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_SORT'] = "Порядок сортировки";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_PARENT'] = "Раздел товара";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_SUPERPARENT'] = "Раздел товара на уровень выше";
$MESS['CRM_DYNAMIC_ITEMS_PROD_FIELD_SUPERSUPERPARENT'] = "Раздел товара на два уровня выше";

//CRM_QUOTE table/field for quote descriptions
$MESS['CRM_QUOTE_TABLE'] = "Предложения";
$MESS['CRM_QUOTE_FIELD_ID'] = "ID предложения";
$MESS['CRM_QUOTE_FIELD_DATE_CREATE'] = "Дата создания";
$MESS['CRM_QUOTE_FIELD_DATE_MODIFY'] = "Дата изменения";
$MESS['CRM_QUOTE_FIELD_CREATED_BY_ID'] = "ID создателя";
$MESS['CRM_QUOTE_FIELD_CREATED_BY_NAME'] = "Имя создателя";
$MESS['CRM_QUOTE_FIELD_CREATED_BY'] = "Создатель";
$MESS['CRM_QUOTE_FIELD_MODIFY_BY_ID'] = "ID редактора";
$MESS['CRM_QUOTE_FIELD_MODIFIED_BY_NAME'] = "Имя редактора";
$MESS['CRM_QUOTE_FIELD_MODIFIED_BY'] = "Редактор";
$MESS['CRM_QUOTE_FIELD_ASSIGNED_BY_ID'] = "ID ответственного";
$MESS['CRM_QUOTE_FIELD_ASSIGNED_BY_NAME'] = "Имя ответственного";
$MESS['CRM_QUOTE_FIELD_ASSIGNED_BY'] = "Ответственный";
$MESS['CRM_QUOTE_FIELD_ASSIGNED_BY_DEPARTMENT'] = "Отдел ответственного";
$MESS['CRM_QUOTE_FIELD_OPENED'] = "Доступно для всех";
$MESS['CRM_QUOTE_FIELD_LEAD_ID'] = "ID лида";
$MESS['CRM_QUOTE_FIELD_DEAL_ID'] = "ID сделки";
$MESS['CRM_QUOTE_FIELD_COMPANY_ID'] = "ID компании";
$MESS['CRM_QUOTE_FIELD_COMPANY_NAME'] = "Название компании";
$MESS['CRM_QUOTE_FIELD_COMPANY'] = "Компания";
$MESS['CRM_QUOTE_FIELD_CONTACT_ID'] = "ID контакта";
$MESS['CRM_QUOTE_FIELD_CONTACT_NAME'] = "Имя контакта";
$MESS['CRM_QUOTE_FIELD_CONTACT'] = "Контакт";
$MESS['CRM_QUOTE_FIELD_PERSON_TYPE_ID'] = "ID типа плательщика";
$MESS['CRM_QUOTE_FIELD_MYCOMPANY_ID'] = "ID компании, от которой делается предложение";
$MESS['CRM_QUOTE_FIELD_MYCOMPANY_NAME'] = "Название компании, от которой делается предложение";
$MESS['CRM_QUOTE_FIELD_MYCOMPANY'] = "Компания, от которой делается предложение";
$MESS['CRM_QUOTE_FIELD_TITLE'] = "Название";
$MESS['CRM_QUOTE_FIELD_STATUS_ID'] = "ID статуса";
$MESS['CRM_QUOTE_FIELD_STATUS_NAME'] = "Название статуса";
$MESS['CRM_QUOTE_FIELD_STATUS'] = "Статус";
$MESS['CRM_QUOTE_FIELD_CLOSED'] = "Завершено";
$MESS['CRM_QUOTE_FIELD_OPPORTUNITY'] = "Ожидаемая сумма";
$MESS['CRM_QUOTE_FIELD_TAX_VALUE'] = "Налог";
$MESS['CRM_QUOTE_FIELD_CURRENCY_ID'] = "Валюта";
$MESS['CRM_QUOTE_FIELD_COMMENTS'] = "Комментарии";
$MESS['CRM_QUOTE_FIELD_BEGINDATE'] = "Дата выставления";
$MESS['CRM_QUOTE_FIELD_CLOSEDATE'] = "Дата завершения";
$MESS['CRM_QUOTE_FIELD_QUOTE_NUMBER'] = "Номер предложения";
$MESS['CRM_QUOTE_FIELD_CONTENT'] = "Содержание предложения";
$MESS['CRM_QUOTE_FIELD_TERMS'] = "Условия";
$MESS['CRM_QUOTE_FIELD_LOCATION_ID'] = "Месторасположение";
$MESS['CRM_QUOTE_FIELD_CLIENT_TITLE'] = "Название клиента";
$MESS['CRM_QUOTE_FIELD_CLIENT_TITLE_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_CLIENT_ADDR'] = "Адрес контакта";
$MESS['CRM_QUOTE_FIELD_CLIENT_CONTACT'] = "Контакт";
$MESS['CRM_QUOTE_FIELD_CLIENT_CONTACT_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_CLIENT_EMAIL'] = "Адрес электронной почты контакта";
$MESS['CRM_QUOTE_FIELD_CLIENT_EMAIL_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_CLIENT_PHONE'] = "Проверка заполненности поля телефон";
$MESS['CRM_QUOTE_FIELD_CLIENT_PHONE_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_CLIENT_TP_ID'] = "ИНН клиента";
$MESS['CRM_QUOTE_FIELD_CLIENT_TP_ID_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_CLIENT_TPA_ID'] = "КПП клиента";
$MESS['CRM_QUOTE_FIELD_CLIENT_TPA_ID_FULL'] = "Устаревший. Сохраняется для совместимости.";
$MESS['CRM_QUOTE_FIELD_UTM_SOURCE'] = "Рекламный источник (utm_source)";
$MESS['CRM_QUOTE_FIELD_UTM_MEDIUM'] = "Рекламный носитель (utm_medium)";
$MESS['CRM_QUOTE_FIELD_UTM_CAMPAIGN'] = "Рекламная кампания (utm_campaign)";
$MESS['CRM_QUOTE_FIELD_UTM_CONTENT'] = "Рекламный контент (utm_content)";
$MESS['CRM_QUOTE_FIELD_UTM_TERM'] = "Рекламный термин (utm_term)";

//CRM_QUOTE_PRODUCT_ROW table/field for quote product descriptions
$MESS['CRM_QUOTE_PRODUCT_ROW_TABLE'] = "Предложение: товары";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_ID'] = "Уникальный ID";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_QUOTE_ID'] = "ID предложения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_QUOTE_DATE_CREATE'] = "Дата создания предложения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_QUOTE_CLOSEDATE'] = "Дата закрытия предложения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRODUCT'] = "Товар";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRODUCT_FULL'] = "ID и название товара";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRODUCT_ID'] = "ID товара";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRODUCT_NAME'] = "Название товара";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRICE'] = "Цена";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRICE_EXCLUSIVE'] = "Цена без налога со скидкой";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRICE_NETTO'] = "Цена без скидок и налогов";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PRICE_BRUTTO'] = "Цена без скидок, с налогом";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_QUANTITY'] = "Количество";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_TYPE'] = "Скидка";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_TYPE_FULL'] = "ID и название скидки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_TYPE_ID'] = "ID скидки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_TYPE_NAME'] = "Название скидки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_RATE'] = "Величина скидки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_DISCOUNT_SUM'] = "Сумма скидки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_TAX_RATE'] = "Налог";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_TAX_INCLUDED'] = "Налог включен в цену";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_TAX_INCLUDED_FULL'] = "Y - да, N - нет";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_CUSTOMIZED'] = "Изменена";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_CUSTOMIZED_FULL'] = "Товарная позиция была изменена вручную после добавления в сделку. Y - да, N - нет";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_MEASURE'] = "Единица измерения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_MEASURE_FULL'] = "ID и название единицы измерения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_MEASURE_CODE'] = "ID единицы измерения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_MEASURE_NAME'] = "Название единицы измерения";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_SORT'] = "Порядок сортировки";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_PARENT'] = "Раздел товара";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_SUPERPARENT'] = "Раздел товара на уровень выше";
$MESS['CRM_QUOTE_PRODUCT_ROW_FIELD_SUPERSUPERPARENT'] = "Раздел товара на два уровня выше";

//CRM_ACT_BIND table/field for quote product descriptions
$MESS['CRM_ACTIVITY_RELATION_TABLE'] = "Связи дел crm";
$MESS['CRM_ACTIVITY_RELATION_FIELD_ACTIVITY_ID'] = "Идентификатор дела";
$MESS['CRM_ACTIVITY_RELATION_FIELD_OWNER_ID'] = "Идентификатор элемента, к которому привязано дело";
$MESS['CRM_ACTIVITY_RELATION_FIELD_OWNER_TYPE_ID'] = "Идентификатор типа элемента, к которому привязано дело";

//CRM_AI_QUALITY_ASSESSMENT table/field for CoPilot quality assessment descriptions
$MESS['CRM_AI_QUALITY_ASSESSMENT_TABLE'] = "Оценки разговоров по скриптам";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_ID'] = "ID";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_CREATED_AT'] = "Дата создания";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_ACTIVITY_ID'] = "ID дела звонка";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_ASSESSMENT'] = "Оценка";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_USE_IN_RATING'] = "Участвует в общем рейтинге";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_USE_IN_RATING_FULL'] = "Оценка является основной для звонка. Y - да, N - нет";
$MESS['CRM_AI_QUALITY_ASSESSMENT_FIELD_RATED_USER_ID'] = "ID сотрудника";
