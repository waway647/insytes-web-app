<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['protocol']  = 'smtp';
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_user'] = 'insytes.web@gmail.com';   // your Gmail address
$config['smtp_pass'] = 'gvhiqniragzhntcg';     // the 16-char app password
$config['smtp_crypto'] = 'tls';                 // use tls (not ssl)
$config['mailtype']  = 'html';
$config['charset']   = 'utf-8';
$config['newline']   = "\r\n";
$config['crlf']      = "\r\n";
