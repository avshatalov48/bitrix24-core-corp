<?
$MESS["INTR_MAIL_DOMAIN_TITLE"] = "Если ваш домен настроен для работы в Яндекс.Почте для домена, просто укажите доменное имя и токен в форме ниже";
$MESS["INTR_MAIL_DOMAIN_TITLE2"] = "К вашему порталу подключен домен";
$MESS["INTR_MAIL_DOMAIN_TITLE3"] = "Домен для вашей почты";
$MESS["INTR_MAIL_DOMAIN_INSTR_TITLE"] = "Для подключения своего домена к Битрикс24 вам необходимо выполнить несколько шагов.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1"] = "Шаг&nbsp;1.&nbsp;&nbsp;Подтвердить владение доменом";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2"] = "Шаг&nbsp;2.&nbsp;&nbsp;Настроить MX-записи";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_PROMPT"] = "Если вы являетесь владельцем указанного домена, подтвердите это любым из следующих способов:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_OR"] = "или";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_A"] = "Загрузите в корневой каталог вашего сайта файл с именем <b>#SECRET_N#.html</b> и содержащий текст <b>#SECRET_C#</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B"] = "Для настройки CNAME-записи у вас должен быть доступ к редактированию DNS-записей вашего домена у вашего регистратора или хостинг-провайдера. Обычно такой доступ предоставляется через веб-интерфейс.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_PROMPT"] = "Необходимо указать следующие настройки:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_TYPE"] = "Тип записи:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAME"] = "Имя записи:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAMEV"] = "<b>yamail-#SECRET_N#</b> (или <b>yamail-#SECRET_N#.#DOMAIN#.</b> с точкой на конце, в зависимости от интерфейса)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUE"] = "Значение:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUEV"] = "<b>mail.yandex.ru.</b> (точка на конце адреса существенна)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C"] = "Укажите адрес <b>#SECRET_N#@yandex.ru</b> в качестве контактного почтового адреса в регистрационных данных вашего домена. Эта операция производится при помощи инструментов вашего регистратора доменов.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C_HINT"] = "Сразу после подтверждения домена необходимо поменять этот адрес на ваш настоящий e-mail.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_HINT"] = "Если у вас возникли вопросы или проблемы, связанные с подтверждением домена, <a href=\"https://dev.1c-bitrix.ru/support/\" target=\"_blank\">обращайтесь в службу поддержки</a>.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PROMPT"] = "После того, как вы подтвердите владение доменом, от вас потребуется изменить MX-записи, которые ему соответствуют. Эта операция производится с помощью инструментов хостинг-провайдера, обслуживающего ваш домен.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TITLE"] = "Настройка MX-записи";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_MXPROMPT"] = "Заведите новую MX-запись со следующими параметрами:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TYPE"] = "Тип записи:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAME"] = "Имя записи:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAMEV"] = "<b>@</b> (или <b>#DOMAIN#.</b> с точкой на конце, в зависимости от интерфейса)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUE"] = "Значение:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUEV"] = "<b>mx.yandex.net.</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PRIORITY"] = "Приоритет:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_HINT"] = "Удалите все прежние MX-записи и TXT-записи, которые не указывают на серверы Яндекса. Процесс распространения информации об изменении MX-записей может занять от нескольких часов до двух-трех суток.";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE"] = "Статус подключения домена";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE2"] = "Домен подтвержден";
$MESS["INTR_MAIL_DOMAIN_STATUS_CONFIRM"] = "Подтвержден";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOCONFIRM"] = "Не подтвержден";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOMX"] = "MX-записи не настроены";
$MESS["INTR_MAIL_DOMAIN_HELP"] = "Если ваш домен еще не настроен для работы в Яндекс.Почте для домена, выполните следующие действия:
<br/><br/>
- <a href=\"https://passport.yandex.ru/registration/\" target=\"_blank\">Заведите аккаунт</a> в Яндекс.Почте либо используйте уже имеющийся<br/>
- <a href=\"https://pdd.yandex.ru/domains_add/\" target=\"_blank\">Подключите домен</a> к Яндекс.Почте для домена <sup>(<a href=\"http://help.yandex.ru/pdd/add-domain/add-exist.xml\" target=\"_blank\" title=\"Как подключить?\">?</a>)</sup><br/>
- Подтвердите владение доменом <sup>(<a href=\"http://help.yandex.ru/pdd/confirm-domain.xml\" target=\"_blank\" title=\"Как подтвердить?\">?</a>)</sup><br/>
- Настройте MX-записи <sup>(<a href=\"http://help.yandex.ru/pdd/records.xml#mx\" target=\"_blank\" title=\"Как настроить MX-записи?\">?</a>)</sup> или делегируйте свой домен на Яндекс <sup>(<a href=\"http://help.yandex.ru/pdd/hosting.xml#delegate\" target=\"_blank\" title=\"Как делегировать домен на Яндекс?\">?</a>)</sup>
<br/><br/>
После того как все настройки на стороне Яндекс.Почты для домена выполнены, подключите домен к своему порталу:
<br/><br/>
- <a href=\"https://pddimp.yandex.ru/api2/admin/get_token\" target=\"_blank\" onclick=\"window.open(this.href, '_blank', 'height=480,width=720,top='+parseInt(screen.height/2-240)+',left='+parseInt(screen.width/2-360)); return false; \">Получите токен</a> (в открывшемся окне заполните форму и нажмите кнопку \"Get token\", скопируйте полученный токен)<br/>
- Укажите домен и токен в форме";
$MESS["INTR_MAIL_INP_CANCEL"] = "Отмена";
$MESS["INTR_MAIL_INP_DOMAIN"] = "Доменное имя";
$MESS["INTR_MAIL_INP_TOKEN"] = "Токен";
$MESS["INTR_MAIL_GET_TOKEN"] = "получить";
$MESS["INTR_MAIL_INP_PUBLIC_DOMAIN"] = "Разрешить сотрудникам регистрировать ящики в новом домене";
$MESS["INTR_MAIL_DOMAIN_SAVE"] = "Сохранить";
$MESS["INTR_MAIL_DOMAIN_SAVE2"] = "Подключить";
$MESS["INTR_MAIL_DOMAIN_WHOIS"] = "Проверить";
$MESS["INTR_MAIL_DOMAIN_REMOVE"] = "Отключить";
$MESS["INTR_MAIL_DOMAIN_CHECK"] = "Проверить";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM"] = "Отключить домен?";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM_TEXT"] = "Вы действительно хотите отключить домен?<br>Все подключенные к порталу ящики тоже будут отключены!";
$MESS["INTR_MAIL_CHECK_TEXT"] = "Последняя проверка #DATE#";
$MESS["INTR_MAIL_CHECK_JUST_NOW"] = "только что";
$MESS["INTR_MAIL_CHECK_TEXT_NA"] = "Нет данных о состоянии домена";
$MESS["INTR_MAIL_CHECK_TEXT_NEXT"] = "Следующая проверка через #DATE#";
$MESS["INTR_MAIL_MANAGE"] = "Настроить ящики сотрудникам";
$MESS["INTR_MAIL_DOMAIN_NOCONFIRM"] = "Домен не подтвержден";
$MESS["INTR_MAIL_DOMAIN_NOMX"] = "MX-записи не настроены";
$MESS["INTR_MAIL_DOMAIN_WAITCONFIRM"] = "Ожидает подтверждения";
$MESS["INTR_MAIL_DOMAIN_WAITMX"] = "MX-записи не настроены";
$MESS["INTR_MAIL_AJAX_ERROR"] = "Ошибка при выполнении запроса";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_TITLE"] = "Подобрать домен";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_HINT"] = "Выберите имя в зоне .ru";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_WAIT"] = "Поиск вариантов...";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_TITLE"] = "Придумайте другое имя или выберите";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_MORE"] = "Показать другие варианты";
$MESS["INTR_MAIL_DOMAIN_EULA_CONFIRM"] = "Я принимаю условия <a href=\"https://www.bitrix24.ru/about/domain.php\" target=\"_blank\">Пользовательского соглашения</a>";
$MESS["INTR_MAIL_DOMAIN_EMPTY_NAME"] = "введите имя";
$MESS["INTR_MAIL_DOMAIN_SHORT_NAME"] = "минимум 2 символа перед .ru";
$MESS["INTR_MAIL_DOMAIN_LONG_NAME"] = "максимум 63 символа перед .ru";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME"] = "недопустимое имя";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME_HINT"] = "Имя домена может состоять из латинских букв, цифр и дефисов (не может начинаться или заканчиваться дефисом, не может содержать дефис на 3 и 4 позициях одновременно). После имени должна быть указана зона <b>.ru<b>";
$MESS["INTR_MAIL_DOMAIN_NAME_OCCUPIED"] = "имя занято";
$MESS["INTR_MAIL_DOMAIN_NAME_FREE"] = "имя свободно";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TITLE"] = "Проверьте, пожалуйста, корректность указанного имени домена.";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TEXT"] = "После подключения вы не сможете изменить имя текущего домена<br>или получить новый, так как вы можете зарегистрировать<br>только один домен для вашего Битрикс24.<br><br>Если выбранное имя <b>#DOMAIN#</b> является корректным, подтвердите подключение домена.";
$MESS["INTR_MAIL_DOMAIN_SETUP_HINT"] = "Подтверждение домена может занять от 1 часа до нескольких суток.";
?>