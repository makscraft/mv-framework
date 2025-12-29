<?php
/**
 * Template for email messages, contains body and css styles of the message.
 * 
 * Variables for body:
 *		{message} - message body,
 *		{subject} - email subject,
 *		{domain} - current domain with project subfolder
 * 		{signature} - common signature from the 'EmailSignature' setting in config/settings.php file
 * 
 * Examples of use:
 * Email::setTemplate("name");
 * Email::send($recipient, $subject, $message);
 * Email::send($recipient, $subject, $message, $headers, $attachments);
 */
$email_template = [
	'body' => "<body style=\"margin:0; padding:5px 0; font-family:Arial, sans-serif;\" bgcolor=\"#fff\">
			   <div style=\"background:#fff; padding:10px 3%; max-width:600px;\">
			   <img src=\"https://mv-framework.com/media/images/logo-mv-framework.jpg\" alt=\"MV logo\" border=\"0\" width=\"40px\" style=\"display:block;\"/>
			   <p style=\"font-family:Arial, sans-serif; font-size:17px; line-height:17px; margin-bottom:15px; padding-top:18px;\">{subject}</p>
			   {message}
			   <p style=\"padding:10px 0 0 0; border-bottom:1px solid #ddd;\"></p>
			   {signature}
			   </div>
			   </body>
	",

	'css' => [
		'*'      => 'font-family:Arial; font-size:14px; color:#333',
		'a'      => 'color:#0057c2;', 
		'h1'     => 'font-weight:normal; margin:0 0 20px 0; font-size:24px; line-height:25px; padding:0',
		'p'      => 'margin:0 0 15px 0; line-height:16px',
		'ul'     => 'margin:0 0 9px 12px; padding:0; list-style:square outside;',
		'li'     => 'padding:0 0 5px 0; margin:0 0 0 12px;',
		'table'  => 'margin:15px 0; border:none; border-collapse:collapse; border-spacing:0;',
		'th'     => 'text-align:left; background:#eee; font-size:13px; font-weight:bold; padding:10px 20px; vertical-align:top',
		'td'     => 'text-align:left; padding:10px 20px; text-align:left; border-bottom:1px solid #d6d6ce; vertical-align:top'
	]
];