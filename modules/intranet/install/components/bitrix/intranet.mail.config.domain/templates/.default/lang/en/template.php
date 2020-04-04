<?
$MESS["INTR_MAIL_DOMAIN_TITLE"] = "If your domain is configured for work in Yandex.Mail for domains, just enter the domain name and token in the form below";
$MESS["INTR_MAIL_DOMAIN_TITLE2"] = "The domain is now linked to your portal";
$MESS["INTR_MAIL_DOMAIN_TITLE3"] = "Domain for your email";
$MESS["INTR_MAIL_DOMAIN_INSTR_TITLE"] = "To connect your domain to Bitrix24, there are a few steps. ";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1"] = "Step&nbsp;1.&nbsp;&nbsp;Confirm ownership of the domain";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2"] = "Step&nbsp;2.&nbsp;&nbsp;Configure MX records";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_PROMPT"] = "You need to confirm that you own the specified domain name using one of the following methods:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_OR"] = "or";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_A"] = "Upload a file named <b>#SECRET_N#.html</b> to your website root directory. The file must contain the text: <b>#SECRET_C#</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B"] = "To configure the CNAME record, you need to have write access to the DNS records of your domain at a registrar or web hosting service with which you registered your domain. You will find these settings in your account or control panel.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_PROMPT"] = "Specify these values:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_TYPE"] = "Record type: ";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAME"] = "Record name: ";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_NAMEV"] = "<b>yamail-#SECRET_N#</b> (or <b>yamail-#SECRET_N#.#DOMAIN#.</b> which depends on the interface. Notice the period at the end.)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUE"] = "Value: ";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_B_VALUEV"] = "<b>mail.yandex.ru.</b> (again, notice the period)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C"] = "Set the contact e-mail address in your domain registration info to <b>#SECRET_N#@yandex.ru</b>. Use your domain registrar's control panel to set the e-mail address.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_C_HINT"] = "Change this address to your real e-mail as soon as the domain is confirmed.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP1_HINT"] = "Should you have any question verifying your domain ownership, please contact the helpdesk at <a href=\"https://helpdesk.bitrix24.com/\" target=\"_blank\">helpdesk.bitrix24.com</a>.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PROMPT"] = "Once you have confirmed your domain ownership, you will have to change the corresponding MX records on your web hosting.";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TITLE"] = "Configure MX records";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_MXPROMPT"] = "Create a new MX record with the following parameters:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_TYPE"] = "Record type:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAME"] = "Record name:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_NAMEV"] = "<b>@</b> (or <b>#DOMAIN#.</b> - if required. Notice the period at the end)";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUE"] = "Value:";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_VALUEV"] = "<b>mx.yandex.net.</b>";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_PRIORITY"] = "Priority: ";
$MESS["INTR_MAIL_DOMAIN_INSTR_STEP2_HINT"] = "Delete all other MX and TXT records that are not related to Yandex. Changes made to MX records may take from a couple of hours to three days to be updated throughout the Internet.";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE"] = "Domain link status";
$MESS["INTR_MAIL_DOMAIN_STATUS_TITLE2"] = "Domain confirmed";
$MESS["INTR_MAIL_DOMAIN_STATUS_CONFIRM"] = "Confirmed";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOCONFIRM"] = "Not confirmed";
$MESS["INTR_MAIL_DOMAIN_STATUS_NOMX"] = "MX records not configured";
$MESS["INTR_MAIL_DOMAIN_HELP"] = "If you don't have your domain configured for use with Yandex Hosted E-Mail, do it now.
<br/><br/>
- <a href=\"https://passport.yandex.com/registration/\" target=\"_blank\">Create a Yandex Hosted E-Mail account</a> or use an existing mailbox if you have one.
- <a href=\"https://pdd.yandex.ru/domains_add/\" target=\"_blank\">Attach your domain</a> to Yandex Hosted E-Mail<sup> (<a href=\"http://help.yandex.ru/pdd/add-domain/add-exist.xml\" target=\"_blank\" title=\"How do I do it?\">?</a>)</sup><br/>
- Verify your domain ownership <sup>(<a href=\"http://help.yandex.ru/pdd/confirm-domain.xml\" target=\"_blank\" title=\"How do I do it?\">?</a>)</sup><br/>
- Configure MX records <sup>(<a href=\"http://help.yandex.ru/pdd/records.xml#mx\" target=\"_blank\" title=\"How do I do it?\">?</a>)</sup> or delegate your domain to Yandex <sup>(<a href=\"http://help.yandex.ru/pdd/hosting.xml#delegate\" target=\"_blank\" title=\"How do I do it?\">?</a>)</sup>
<br/><br/>
Once your Yandex Hosted E-Mail account has been configured, attach the domain to your Bitrix24:
<br/><br/>
- <a href=\"https://pddimp.yandex.ru/api2/admin/get_token\" target=\"_blank\" onclick=\"window.open(this.href, '_blank', 'height=480,width=720,top='+parseInt(screen.height/2-240)+',left='+parseInt(screen.width/2-360)); return false; \">Get a token</a> (fill in the form fields and click \"Get token&quot;. Once the token appears, copy it to the Clipboard)<br/>
- Add the domain and the token to the parameters.";
$MESS["INTR_MAIL_INP_CANCEL"] = "Cancel";
$MESS["INTR_MAIL_INP_DOMAIN"] = "Domain name";
$MESS["INTR_MAIL_INP_TOKEN"] = "Token";
$MESS["INTR_MAIL_GET_TOKEN"] = "get";
$MESS["INTR_MAIL_INP_PUBLIC_DOMAIN"] = "Employees can register mailboxes in this domain";
$MESS["INTR_MAIL_DOMAIN_SAVE"] = "Save";
$MESS["INTR_MAIL_DOMAIN_SAVE2"] = "Attach";
$MESS["INTR_MAIL_DOMAIN_WHOIS"] = "Check";
$MESS["INTR_MAIL_DOMAIN_REMOVE"] = "Detach";
$MESS["INTR_MAIL_DOMAIN_CHECK"] = "Verify";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM"] = "Do you want to disconnect the domain?";
$MESS["INTR_MAIL_DOMAINREMOVE_CONFIRM_TEXT"] = "Do you want to detach the domain?<br>All the mailboxes attached to the portal will be detached as well!";
$MESS["INTR_MAIL_CHECK_TEXT"] = "Last checked on #DATE#";
$MESS["INTR_MAIL_CHECK_JUST_NOW"] = "seconds ago";
$MESS["INTR_MAIL_CHECK_TEXT_NA"] = "No data for domain status";
$MESS["INTR_MAIL_CHECK_TEXT_NEXT"] = "Next mail check in #DATE#";
$MESS["INTR_MAIL_MANAGE"] = "Configure employee mailboxes";
$MESS["INTR_MAIL_DOMAIN_NOCONFIRM"] = "Domain not confirmed";
$MESS["INTR_MAIL_DOMAIN_NOMX"] = "MX records not configured";
$MESS["INTR_MAIL_DOMAIN_WAITCONFIRM"] = "Waiting confirmation";
$MESS["INTR_MAIL_DOMAIN_WAITMX"] = "MX records not configured";
$MESS["INTR_MAIL_AJAX_ERROR"] = "Error in executing query";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_TITLE"] = "Choose Domain";
$MESS["INTR_MAIL_DOMAIN_CHOOSE_HINT"] = "Choose a name in .ru domain";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_WAIT"] = "Searching for possible names...";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_TITLE"] = "Please come up with another name or pick one";
$MESS["INTR_MAIL_DOMAIN_SUGGEST_MORE"] = "Show other options";
$MESS["INTR_MAIL_DOMAIN_EULA_CONFIRM"] = "I accept the terms of the <a href=\"http://www.bitrix24.ru/about/domain.php\" target=\"_blank\">License Agreement</a>";
$MESS["INTR_MAIL_DOMAIN_EMPTY_NAME"] = "enter name";
$MESS["INTR_MAIL_DOMAIN_SHORT_NAME"] = "at least two characters before .ru";
$MESS["INTR_MAIL_DOMAIN_LONG_NAME"] = "max. 63 characters before .ru";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME"] = "invalid name";
$MESS["INTR_MAIL_DOMAIN_BAD_NAME_HINT"] = "Domain name can include Latin characters, digits and hyphens; cannot start or end with a hyphen, or repeat the hyphen at positions 3 and 4. End the name with the <b>.ru<b>.";
$MESS["INTR_MAIL_DOMAIN_NAME_OCCUPIED"] = "this name is not available";
$MESS["INTR_MAIL_DOMAIN_NAME_FREE"] = "this name is available";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TITLE"] = "Please check you have entered the domain name properly.";
$MESS["INTR_MAIL_DOMAIN_REG_CONFIRM_TEXT"] = "Once connected, you won't be able to change the domain name<br>or get another one because you can register<br>only one domain for your Bitrix24.<br><br>If you find the name <b>#DOMAIN#</b> is correct, confirm your new domain.";
$MESS["INTR_MAIL_DOMAIN_SETUP_HINT"] = "The domain name may take from 1 hour to several days to confirm.";
?>