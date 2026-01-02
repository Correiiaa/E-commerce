<?php

spl_autoload_register(function ($class) {
    // ver qual classe está a tentar carregar
    error_log("Tentando carregar: $class");

    // Apenas classes do namespace PHPMailer\PHPMailer
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        // Remove o namespace base 
        $class_name = substr($class, 18);

        // Caminho para o ficheiro (relativo a vendor/)
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $class_name . '.php';


        if (file_exists($file)) {
            require $file;
            return true;
        }
    }

    return false;
});
