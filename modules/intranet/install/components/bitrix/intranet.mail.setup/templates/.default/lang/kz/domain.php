<?php
$MESS["INTR_MAIL_AJAX_ERROR"] = "Сұрауды орындау кезіндегі қате";
$MESS["INTR_MAIL_CHECK_JUST_NOW"] = "жаңа ғана";
$MESS["INTR_MAIL_CHECK_TEXT"] = "Соңғы тексеру #DATE#";
$MESS["INTR_MAIL_CHECK_TEXT_NA"] = "Домен күйі туралы деректер жоқ";
$MESS["INTR_MAIL_CHECK_TEXT_NEXT"] = "Келесі тексеру #DATE# кейін";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM"] = "Доменді өшіру керек пе?";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM_TEXT"] = "Сіз шынымен доменді өшіргіңіз келеді ме?<br>Порталға қосылған барлық жәшіктер де өшіріледі!";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME"] = "жарамсыз атау";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME_HINT"] = "Домен атауы латын әріптерінен, цифрлардан және сызықшалардан тұруы мүмкін (сызықшадан басталуы немесе аяқталуы мүмкін емес, бір уақытта 3 және 4 позицияларда сызықша болуы мүмкін емес). Атаудан кейін <b>.ru<b> аймағы көрсетілуі керек";
$MESS["INTR_MAIL_DOMAIN_CHECK"] = "Тексеру";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_HINT"] = ".ru аймағында атауды таңдау";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_TITLE"] = "Доменді таңдау";
$MESS["INTR_MAIL_DOMAIN_EMPTY_NAME"] = "атауды енгізіңіз";
$MESS["INTR_MAIL_DOMAIN_EULA_CONFIRM"] = "Мен <a href=\"https://www.bitrix24.kz/about/domain.php\" target=\"_blank\">Пайдаланушылық келісімнің шарттарын қабылдаймын</a>";
$MESS["INTR_MAIL_DOMAIN_HELP"] = "Егер сіздің доменіңіз домен үшін Яндекс.Поштада жұмыс істеу үшін бапталмаса, келесі әрекеттерді орындаңыз:
<br/><br/>
- <a href=\"https://passport.yandex.ru/registration/\" target=\"_blank\">Яндекс.Поштада аккаунт құрыңыз</a> немесе бұрыннан барын пайдаланыңыз<br/>
- <a href=\"https://pdd.yandex.ru/domains_add/\" target=\"_blank\">Домен үшін Яндекс.Пошта</a> қосыңыз <sup>(<a href=\"http://help.yandex.ru/pdd/add-domain/add-exist.xml\" target=\"_blank\" title=\"Қалай қосу керек?\">?</a>)</sup><br/>
- Доменге иелікті растаңыз <sup>(<a href=\"http://help.yandex.ru/pdd/confirm-domain.xml\" target=\"_blank\" title=\"Қалай растау керек?\">?</a>)</sup><br/>
- MX-жазбаларын баптаңыз <sup>(<a href=\"http://help.yandex.ru/pdd/records.xml#mx\" target=\"_blank\" title=\"MX-жазбаларын қалай баптауға болады?\">?</a>)</sup> немесе доменіңізді Яндекске табыстаңыз<sup>(<a href=\"http://help.yandex.ru/pdd/hosting.xml#delegate\" target=\"_blank\" title=\"Яндекске доменді қалай табыстауға болады?\">?</a>)</sup>
<br/><br/>
Доменге арналған Яндекс.Пошта жағындағы барлық баптаулар орындалғаннан кейін, доменді порталыңызға қосыңыз:
<br/><br/>
- <a href=\"https://pddimp.yandex.ru/api2/admin/get_token\" target=\"_blank\" onclick=\"window.open(this.href, '_blank', 'height=480,width=720,top='+parseInt(screen.height/2-240)+',left='+parseInt(screen.width/2-360)); return false; \">Токен алыңыз</a> (ашылған терезеде нысанды толтырыңыз және  \"Get token\" батырмасын басыңыз, алынған токенді көшіріңіз)<br/>
- Нысанда домен мен токенді көрсетіңіз";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1"] = "Қадам&nbsp;1.&nbsp;&nbsp;Доменге иелікті растау";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_A"] = "Сайтыңыздың түбірлік каталогына атауы бар <b>#SECRET_N#.html</b> және мәтін қамтылған<b>#SECRET_C#</b> файлды жүктеңіз";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B"] = "CNAME-жазбасын баптау үшін тіркеушіңізден немесе хостинг- провайдерінен доменнің DNS- жазбаларын редакциялауға қолжетімділік болуы керек. Әдетте мұндай қолжетімділік веб-интерфейс арқылы беріледі.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAME"] = "Жазба атауы:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAMEV"] = "<b>yamail-#SECRET_N#</b> (немесе <b>yamail-#SECRET_N#.#DOMAIN#.</b> интерфейске байланысты соңында нүктемен)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_PROMPT"] = "Келесі баптауларды көрсету керек:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_TYPE"] = "Жазба типі:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUE"] = "Мәні:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUEV"] = "<b>mail.yandex.ru.</b> (мекенжайдың соңындағы нүкте маңызды)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C"] = "<b>#SECRET_N#@yandex.ru</b> мекенжайын доменді тіркеу деректерінде байланыс пошта мекенжайы ретінде көрсетіңіз. Бұл операция доменді тіркеуші құралдарының көмегімен жасалады.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C_HINT"] = "Домен расталғаннан кейін бірден осы мекенжайды нақты e-mail-ге өзгерту керек.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_HINT"] = "Егер сізде доменді растауға қатысты сұрақтар немесе мәселелер туындаса, <a href=\"http://dev.1c-bitrix.ru/support/\" target=\"_blank\">қолдау қызметіне хабарласыңыз</a>.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_OR"] = "немесе";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_PROMPT"] = "Егер Сіз аталған доменнің иесі болсаңыз, оны келесі тәсілдердің кез келгенімен растаңыз:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2"] = "Қадам&nbsp;2.&nbsp;&nbsp;MX-жазбаларды баптау";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_HINT"] = "Яндекс серверлеріне нұсқамайтын барлық бұрынғы MX және TXT-жазбаларын жойыңыз. MX-жазбаларын өзгерту туралы ақпаратты тарату процесі бірнеше сағаттан екі-үш күнге дейін созылуы мүмкін.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_MXPROMPT"] = "Келесі параметрлермен жаңа MX-жазбасын бастаңыз:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAME"] = "Жазба атауы:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAMEV"] = "<b>@</b> (немесе <b>#DOMAIN#.</b> интерфейске байланысты соңында нүктемен)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PRIORITY"] = "Басымдық:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PROMPT"] = "Доменге иелік етуді растағаннан кейін сізге сәйкес келетін MX- жазбаларын өзгерту қажет болады. Бұл операция сіздің доменіңізге қызмет көрсететін хостинг- провайдерінің құралдары арқылы жасалады.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TITLE"] = "MX-жазбасын баптау";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TYPE"] = "Жазба типі:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUE"] = "Мәні:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUEV"] = "<b>mx.yandex.net.</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_TITLE"] = "Доменді Битрикс24-ке қосу үшін бірнеше қадамдарды орындау қажет.";
$MESS["INTR_MAIL_DOMAIN_LONG_NAME"] = ".ru алдында максимум 63 символ";
$MESS["INTR_MAIL_DOMAIN_NAME_FREE"] = "атау бос";
$MESS["INTR_MAIL_DOMAIN_NAME_OCCUPIED"] = "атау бос емес";
$MESS["INTR_MAIL_DOMAIN_NOCONFIRM"] = "Домен расталмаған";
$MESS["INTR_MAIL_DOMAIN_NOMX"] = "MX-жазбалар бапталмаған";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TEXT"] = "Қосылғаннан кейін сіз ағымдағы домен атауын өзгерте алмайсыз<br>немесе жаңасын ала алмайсыз, себебі сіздің<br>Битрикс24 үшін тек бір доменді тіркей аласыз.<br><br>Егер таңдалған атау <b>#DOMAIN#</b> дұрыс болып табылса, домен қосылымын растаңыз.";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TITLE"] = "Өтініш, көрсетілген домен атауының дұрыстығын тексеріңіз.";
$MESS["INTR_MAIL_DOMAIN_REMOVE"] = "Өшіру";
$MESS["INTR_MAIL_DOMAIN_SAVE"] = "Сақтау";
$MESS["INTR_MAIL_DOMAIN_SAVE2"] = "Қосу";
$MESS["INTR_MAIL_DOMAIN_SETUP_HINT"] = "Доменді растау 1 сағаттан бірнеше күнге дейін созылуы мүмкін.";
$MESS["INTR_MAIL_DOMAIN_SHORT_NAME"] = ".ru алдында минимум 2 символ";
$MESS["INTR_MAIL_DOMAIN_STATUS_CONFIRM"] = "Расталды";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOCONFIRM"] = "Расталмаған";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOMX"] = "MX-жазбалар бапталмаған";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE"] = "Домен қосылымының мәртебесі";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE2"] = "Домен расталды";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_MORE"] = "Басқа нұсқаларды көрсету";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_TITLE"] = "Басқа атау ойлап табыңыз немесе таңдаңыз";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_WAIT"] = "Нұсқаларды іздеу...";
$MESS["INTR_MAIL_DOMAIN_TITLE"] = "Егер сіздің доменіңіз доменге арналған Яндекс.Поштада жұмыс істеуге бапталса, төмендегі нысандағы домендік атау мен токенді көрсетіңіз";
$MESS["INTR_MAIL_DOMAIN_TITLE2"] = "Сіздің порталыңызға қосылған домен";
$MESS["INTR_MAIL_DOMAIN_TITLE3"] = "Сіздің поштаңызға арналған домен";
$MESS["INTR_MAIL_DOMAIN_WAITCONFIRM"] = "Растауды күтуде";
$MESS["INTR_MAIL_DOMAIN_WAITMX"] = "MX-жазбалар бапталмаған";
$MESS["INTR_MAIL_DOMAIN_WHOIS"] = "Тексеру";
$MESS["INTR_MAIL_GET_TOKEN"] = "алу";
$MESS["INTR_MAIL_INP_CANCEL"] = "Болдырмау";
$MESS["INTR_MAIL_INP_DOMAIN"] = "Домендік атау";
$MESS["INTR_MAIL_INP_PUBLIC_DOMAIN"] = "Қызметкерлерге жәшіктерді жаңа доменге тіркеуге рұқсат ету";
$MESS["INTR_MAIL_INP_TOKEN"] = "Токен";
$MESS["INTR_MAIL_MANAGE"] = "Қызметкерлерге жәшіктерді баптау";
