<?php
//Captcha configuration file
$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz"; //Do not change without changing font files!

//Symbols used to draw captcha
$allowed_symbols = "23456789abcdeghkmnpqsuvxyz"; //Alphabet without similar symbols (o=0, 1=l, i=j, t=f)

//Folder with fonts
$fontsdir = 'fonts';	

//Captcha string length
$length = mt_rand(3,4);

//Captcha image size (you do not need to change it, whis parameters is optimal)
$width = 120;
$height = 50;

//Symbol's vertical fluctuation amplitude divided by 2
$fluctuation_amplitude = 10;

//Increases safety by prevention of spaces between symbols
$no_spaces = true;

//Show credits
$show_credits = false;

//Captcha image colors (RGB, 0-255)
$foreground_color = array(mt_rand(0,100), mt_rand(0,100), mt_rand(0,100));
$background_color = array(255,255,255); //array(mt_rand(200,255), mt_rand(200,255), mt_rand(200,255));

//Captcha image JPEG quality (bigger is better quality, but larger file size)
$jpeg_quality = 90;
?>