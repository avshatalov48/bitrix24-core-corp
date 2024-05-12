<?
$MESS["INTR_MAIL_DOMAIN_TITLE"] = "Wenn Ihre Domain für die Arbeit mit Yandex Hosted E-Mail bereits konfiguriert ist, geben Sie den Domainnamen und das Token im untenstehenden Formular an.";
$MESS["INTR_MAIL_DOMAIN_TITLE2"] = "Für Ihr Portal wurde ein Domain aktiviert";
$MESS["INTR_MAIL_DOMAIN_TITLE3"] = "Domain für Ihre E-Mails";
$MESS["INTR_MAIL_DOMAIN_INSTR_TITLE"] = "Um Ihre eigene Domain in Bitrix24 zu aktivieren, müssen Sie einige Schritte machen.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1"] = "Schritt&nbsp;1.&nbsp;&nbsp; Bestätigen Sie, dass die Domain in Ihrem Besitz ist";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2"] = "Schritt&nbsp;2.&nbsp;&nbsp; Konfigurieren Sie MX-Einträge";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_PROMPT"] = "Wenn die angegebene Domain in Ihrem Besitz ist, müssen Sie das mit einer der folgenden Methoden bestätigen:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_OR"] = "oder";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_A"] = "Laden Sie eine Datei <b>#SECRET_N#.html</b> in das Root-Verzeichnis Ihrer Website hoch. Die Daten muss den folgende Text enthalten: <b>#SECRET_C#</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B"] = "Um den CNAME-Eintrag zu bearbeiten, müssen Sie über Schreibrechte für DNS-Einträge Ihrer Domain beim Registrator oder Hosting-Anbieter, bei dem Sie die Domain registriert haben, verfügen. Diese Rechte werden meistens über die Webschnittstelle gewährt.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_PROMPT"] = "Folgende Einstellungen müssen vorgenommen werden:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_TYPE"] = "Eintragstyp:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAME"] = "Eintragsname:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAMEV"] = "<b>yamail-#SECRET_N#</b> (oder <b>yamail-#SECRET_N#.#DOMAIN#.</b>, was von der Schnittstelle abhängt.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUE"] = "Wert:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUEV"] = "<b>mail.yandex.ru.</b> (hier ist der Punkt wichtig)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C"] = "Geben Sie die E-Mail-Adresse <b>#SECRET_N#@yandex.ru</b> in der Registrierungsinformation Ihrer Domain an. Benutzen Sie das Control Panel des Registrators Ihrer Domain, um die E-Mail-Adresse anzugeben.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C_HINT"] = "Nachdem die Domain bestätigt wird, sollten Sie diese E-Mail-Adresse zu Ihrer wirklichen ändern.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_HINT"] = "Wenn Sie irgendwelche Fragen bezüglich Domainbestätigung haben, wenden Sie sich an den Technischen Support auf <a href=\"https://helpdesk.bitrix24.de/\" target=\"_blank\">helpdesk.bitrix24.de</a>.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PROMPT"] = "Nachdem Sie den Besitz der Domain bestätigen, müssen Sie die entsprechenden MX-Einträge bei Ihrem Hosting-Anbieter ändern.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TITLE"] = "Konfigurieren Sie die MX-Einträge";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_MXPROMPT"] = "Erstellen Sie einen neuen MX-Eintrag mit folgenden Parametern:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TYPE"] = "Eintragstyp:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAME"] = "Eintragsnamen:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAMEV"] = "<b>@</b> (oder <b>#DOMAIN#.</b> kann auch mit dem Punt am Ende sein, abhängig von der Schnittstelle.)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUE"] = "Wert:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUEV"] = "<b>mx.yandex.net.</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PRIORITY"] = "Priorität:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_HINT"] = "Löschen Sie alle anderen MX-Einträge und TXT-Einträge, die sich nicht auf Yandex beziehen. Änderungen, die an MX-Einträgen vorgenommen werden, können von ein paar Stunden bis zu 3 Tagen dauern.";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE"] = "Domainstatus";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE2"] = "Domain bestätigt";
$MESS["INTR_MAIL_DOMAIN_STATUS_CONFIRM"] = "Bestätigt";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOCONFIRM"] = "Nicht bestätigt";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOMX"] = "MX-Einträge sind nicht konfiguriert";
$MESS["INTR_MAIL_DOMAIN_HELP"] = "Wenn Sie Ihre Domain noch nicht für die Arbeit mit Yandex Hosted E-Mail konfiguriert haben, machen Sie das jetzt.
<br/><br/>
- <a href=\"https://passport.yandex.com/registration/\" target=\"_blank\">Erstellen Sie ein Yandex Hosted E-Mail-Konto</a>
oder benutzen Sie ein vorhandenes Mail-Konto, wenn Sie es haben.
- <a href=\"https://pdd.yandex.ru/domains_add/\" target=\"_blank\">Fügen Sie Ihre Domain </a> zu Yandex Hosted E-Mail<sup> hinzu (<a href=\"http://help.yandex.ru/pdd/add-domain/add-exist.xml\" target=\"_blank\" title=\"Wie mache ich das?\">?</a>)</sup><br/>
- Bestätigen Sie, dass die Domain in Ihrem Besitz ist <sup>(<a href=\"http://help.yandex.ru/pdd/confirm-domain.xml\" target=\"_blank\" title=\"Wie mache ich das?\">?</a>)</sup><br/>
- Konfigurieren Sie MX-Einträge <sup>(<a href=\"http://help.yandex.ru/pdd/records.xml#mx\" target=\"_blank\" title=\"Wie mache ich das?\">?</a>)</sup> 
oder delegieren Sie Ihre Domain an Yandex <sup>(<a href=\"http://help.yandex.ru/pdd/hosting.xml#delegate\" target=\"_blank\" title=\"Wie mache ich das?\">?</a>)</sup>
<br/><br/>
Wenn Ihr Yandex Hosted E-Mail-Konto konfiguriert ist, fügen Sie Ihre Domain zu Ihrem Bitrix24 hinzu:
<br/><br/>
- <a href=\"https://pddimp.yandex.ru/api2/admin/get_token\" target=\"_blank\" onclick=\"window.open(this.href, '_blank', 'height=480,width=720,top='+parseInt(screen.height/2-240)+',left='+parseInt(screen.width/2-360)); return false; \">Bekommen Sie ein Token</a> (füllen Sie Formularfelder aus und klicken auf \"Token bekommen&quot;. Wenn das Token erscheint, kopieren Sie es)<br/>
- Fügen Sie die Domain und das Token zu den Parametern hinzu.";
$MESS["INTR_MAIL_INP_CANCEL"] = "Abbrechen";
$MESS["INTR_MAIL_INP_DOMAIN"] = "Domainname";
$MESS["INTR_MAIL_INP_TOKEN"] = "Token";
$MESS["INTR_MAIL_GET_TOKEN"] = "bekommen";
$MESS["INTR_MAIL_INP_PUBLIC_DOMAIN"] = "Mitarbeiter können Mail-Konten in dieser Domain registrieren";
$MESS["INTR_MAIL_DOMAIN_SAVE"] = "Speichern";
$MESS["INTR_MAIL_DOMAIN_SAVE2"] = "Hinzufügen";
$MESS["INTR_MAIL_DOMAIN_WHOIS"] = "Überprüfen";
$MESS["INTR_MAIL_DOMAIN_REMOVE"] = "Deaktivieren";
$MESS["INTR_MAIL_DOMAIN_CHECK"] = "Prüfen";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM"] = "Möchten Sie die Domain deaktivieren?";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM_TEXT"] = "Möchten Sie die Domain deaktivieren?<br>Alle Mail-Konten, die ans Portal gebunden sind, werden somit auch deaktiviert.";
$MESS["INTR_MAIL_CHECK_TEXT"] = "Zuletzt geprüft am #DATE#";
$MESS["INTR_MAIL_CHECK_JUST_NOW"] = "Gerade eben";
$MESS["INTR_MAIL_CHECK_TEXT_NA"] = "Es gibt keine Daten über den Domainzustand";
$MESS["INTR_MAIL_CHECK_TEXT_NEXT"] = "Nächste Prüfung auf E-Mails in #DATE#";
$MESS["INTR_MAIL_MANAGE"] = "Mail-Konten der Mitarbeiter konfigurieren";
$MESS["INTR_MAIL_DOMAIN_NOCONFIRM"] = "Domain nicht bestätigt";
$MESS["INTR_MAIL_DOMAIN_NOMX"] = "MX-Einträge sind nicht konfiguriert";
$MESS["INTR_MAIL_DOMAIN_WAITCONFIRM"] = "Bestätigung wird erwartet";
$MESS["INTR_MAIL_DOMAIN_WAITMX"] = "MX-Einträge sind nicht konfiguriert";
$MESS["INTR_MAIL_AJAX_ERROR"] = "Fehler bei der Anfrage";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_TITLE"] = "Domain auswählen";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_HINT"] = "Einen Namen in .ru Domain auswählen";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_WAIT"] = "Nach möglichen Namen wird gesucht...";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_TITLE"] = "Geben Sie bitte einen anderen Namen an und wählen Sie diesen aus";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_MORE"] = "Andere Varianten anzeigen";
$MESS["INTR_MAIL_DOMAIN_EULA_CONFIRM"] = "Ich akzeptiere die <a href=\"http://www.bitrix24.ru/about/domain.php\" target=\"_blank\">Nutzungsbedingungen</a>";
$MESS["INTR_MAIL_DOMAIN_EMPTY_NAME"] = "Geben Sie den Namen ein";
$MESS["INTR_MAIL_DOMAIN_SHORT_NAME"] = "mindestens zwei Zeichen vor dem .ru";
$MESS["INTR_MAIL_DOMAIN_LONG_NAME"] = "max. 63 Zeichen vor dem .ru";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME"] = "ungültiger Name";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME_HINT"] = "Domainname kann lateinische Buchstaben, Zahlen oder Bindestriche enthalten; er kann nicht mit einem Bindestrich anfangen oder enden, der Bindestrich darf nicht auf Positionen 3 und 4 gleichzeitig wiederholt werden. Der Name muss mit dem <b>.ru<b> enden.";
$MESS["INTR_MAIL_DOMAIN_NAME_OCCUPIED"] = "der Name ist nicht verfügbar";
$MESS["INTR_MAIL_DOMAIN_NAME_FREE"] = "der Name ist verfügbar";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TITLE"] = "Stellen Sie bitte sicher, dass Sie den Namen der Domain korrekt eingegeben haben.";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TEXT"] = "Wenn einmal verbunden, können Sie den Namen der Domain nicht mehr ändern<br>oder einen anderen Namen beantragen, weil Sie<br>nur eine Domain für Ihr Bitrix24 registrieren können.<br><br>Wenn Sie feststellen, dass der Name <b>#DOMAIN#</b>korrekt ist, bestätigen Sie Ihre neue Domain.";
$MESS["INTR_MAIL_DOMAIN_SETUP_HINT"] = "Die Bestätigung des Domainnamens kann von 1 Stunde bis hin zu einigen Tagen Zeit in Anspruch nehmen.";
?>
