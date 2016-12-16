<?php

    define('LANG_AUTH_CONTROLLER',          'Authorization & Registration');

    define('LANG_AUTHORIZATION',            'Authorization');

    define('LANG_AUTH_BY',                  'Authorization by field');

    define('LANG_AUTH_RESTRICTIONS',            'Restrictions');
    define('LANG_AUTH_RESTRICTED_EMAILS',       "Restricted e-mails");
    define('LANG_AUTH_RESTRICTED_EMAILS_HINT',  'One address per line, you can use * as a wildcard');
    define('LANG_AUTH_RESTRICTED_EMAIL',        'E-mail <b>%s</b> is forbidden');

    define('LANG_AUTH_RESTRICTED_SLUGS',        'Restricted slugs in the profile addresses');
    define('LANG_AUTH_RESTRICTED_SLUGS_HINT',   'One word per line, you can use * as a wildcard');
    define('LANG_AUTH_RESTRICTED_SLUG',         'Address <b>%s</b> is forbidden');

    define('LANG_AUTH_RESTRICTED_NAMES',        'Restricted nicknames');
    define('LANG_AUTH_RESTRICTED_NAMES_HINT',   'One nickname per line, you can use * as a wildcard');
    define('LANG_AUTH_RESTRICTED_NAME',         'Nickname <b>%s</b> is forbidden');

    define('LANG_AUTH_RESTRICTED_IPS',          'IPs restricted for registration');
    define('LANG_AUTH_RESTRICTED_IPS_HINT',     'One address per line, you can use * as a wildcard');
    define('LANG_AUTH_RESTRICTED_IP',           'Registration from IP <b>%s</b> is forbidden');

    define('LANG_AUTH_INVITES',                 'Invites');
    define('LANG_AUTH_INVITES_AUTO',            'Issue invites to registered users');
    define('LANG_AUTH_INVITES_AUTO_HINT',       'Users will be able to send invites to their friends');
    define('LANG_AUTH_INVITES_STRICT',          'Bind invites to e-mail');
    define('LANG_AUTH_INVITES_STRICT_HINT',     'If enabled, a user will be able to register with an invite code received via e-mail only');
    define('LANG_AUTH_INVITES_PERIOD',          'Issue invites once in the period of');
    define('LANG_AUTH_INVITES_QTY',             'How many invites to issue');
    define('LANG_AUTH_INVITES_KARMA',           'Issue invites to users whose reputation is higher than');
    define('LANG_AUTH_INVITES_RATING',          'Issue invites to users whose rating is higher than');
    define('LANG_AUTH_INVITES_DATE',            'Issue invites to users registered on the site for at least');

    define('LANG_REG_INVITED_ONLY',             'Registration is allowed by invites only');
    define('LANG_REG_INVITE_CODE',              'Invite code');
    define('LANG_REG_WRONG_INVITE_CODE',        'Wrong invite code');
    define('LANG_REG_WRONG_INVITE_CODE_EMAIL',  'Invite code assigned to another e-mail');

    define('LANG_REG_CFG_IS_ENABLED',           'Registration is enabled');
    define('LANG_REG_CFG_DISABLED_NOTICE',      'Reason why registration is disabled');
    define('LANG_REG_CFG_IS_INVITES',           'Registration is by invitation only');

    define('LANG_REG_CFG_REG_CAPTCHA',          'Show CAPTCHA to protect from spam registrations');
    define('LANG_REG_CFG_AUTH_CAPTCHA',         'Show CAPTCHA after failed log in');
    define('LANG_REG_CFG_FIRST_AUTH_REDIRECT',  'After first login');
    define('LANG_REG_CFG_AUTH_REDIRECT',        'After consequent authorization');
    define('LANG_REG_CFG_AUTH_REDIRECT_NONE',        'Stay on page, at which the logged');
    define('LANG_REG_CFG_AUTH_REDIRECT_INDEX',       'Open homepage');
    define('LANG_REG_CFG_AUTH_REDIRECT_PROFILE',     'Open profile');
    define('LANG_REG_CFG_AUTH_REDIRECT_PROFILEEDIT', 'Open profile settings');

    define('LANG_REG_CFG_VERIFY_EMAIL',         'Send verification e-mail after registration');
    define('LANG_REG_CFG_VERIFY_EMAIL_HINT',    'New users will be blocked until they open URL from verification e-mail');
	define('LANG_REG_CFG_REG_AUTO_AUTH',        'Login after registration');
    define('LANG_REG_CFG_VERIFY_EXPIRATION',   'Delete unverified accounts after, hours');
    define('LANG_REG_CFG_VERIFY_LOCK_REASON',  'E-mail verification required');
    define('LANG_REG_CFG_DEF_GROUP_ID',		   'Add new users to groups');

    define('LANG_REG_CFG_REG_USER_SLUG',       'Request/substitute in the user URLs during registration');
    define('LANG_REG_CFG_REG_USER_SLUG_SLUG',  'Profile address (slug)');
    define('LANG_REG_CFG_RULES_URL',           'The link to the "Site rules"');
    define('LANG_REG_CFG_RULES_URL_HINT',      'Full address or the address from the root of the site with a slash "/" at the beginning. If the link is specified then the user will have to accept these rules during registration.');

    define('LANG_REG_INCORRECT_EMAIL',       'E-mail address is incorrect');
    define('LANG_REG_EMAIL_EXISTS',          'This e-mail address is already registered');
    define('LANG_REG_PASS_NOT_EQUAL',        'Passwords do not match');
    define('LANG_REG_PASS_EMPTY',            'Password is required');
    define('LANG_REG_SUCCESS',               'Registration was successful');
    define('LANG_REG_SUCCESS_NEED_VERIFY',   'Verification e-mail was sent to <b>%s</b>. Click on the link in the message to activate your account');
    define('LANG_REG_SUCCESS_VERIFIED',      'E-mail address has been successfully verified. You can login now.');
	define('LANG_REG_SUCCESS_VERIFIED_AND_AUTH', 'E-mail address has been successfully verified. Welcome!');

    define('LANG_PASS_RESTORE',              'Password Recovery');
    define('LANG_EMAIL_NOT_FOUND',           'This e-mail not found in our database');
    define('LANG_TOKEN_SENDED',              'We sent you an e-mail with further instructions on password recovery');
    define('LANG_RESTORE_NOTICE',            'Please enter the e-mail address with which you registered.<br/>We will send you further instructions on password recovery.');

    define('LANG_REG_RULES_SET',              'Acceptance of the "Site rules"');
    define('LANG_REG_RULES_AGREE',            'I confirm that I have read and accept the "<a href="%s" target="_blank">User Agreement</a>"');
