<?php

require_once '../config.php';

// Configurações SMTP do Gmail
define('SMTP_HOST', defined('EMAIL_HOST') ? EMAIL_HOST : 'localhost');
define('SMTP_PORT', defined('EMAIL_PORT') ? EMAIL_PORT : 25);
define('SMTP_USERNAME', defined('EMAIL_USERNAME') ? EMAIL_USERNAME : '');
define('SMTP_PASSWORD', defined('EMAIL_PASSWORD') ? EMAIL_PASSWORD : '');
define('SMTP_SECURE', (SMTP_PORT == 587) ? 'tls' : ((SMTP_PORT == 465) ? 'ssl' : ''));

// ✅ Usar EMAIL_FROM_ADDRESS se existir, senão EMAIL_USERNAME
define('SMTP_FROM_EMAIL', defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : (defined('EMAIL_USERNAME') ? EMAIL_USERNAME : 'noreply@esan-tesp-ds-paw.web.ua.pt'));
define('SMTP_FROM_NAME', defined('EMAIL_FROM') ? EMAIL_FROM : 'E-Commerce Vinhos');
