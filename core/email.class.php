<?php
/**
 * Class for sending email messages.
 */
class Email
{
	/**
	 * Email css template settings.
	 * @var array
	 */
	static private $template = [];
	
	/**
	 * Sets email css template from file.
	 * @param string $name name of file, located in customs/emails/ folder.
	 */
	static public function setTemplate($name)
	{
		$registry = Registry :: instance();
		$file = $registry -> getSetting("IncludePath")."customs/emails/".$name.".php";
		
		unset($email_template);
		self :: $template = [];
		
		if(!is_file($file))
		{
			if($name != "default")
				Log :: add("Unable to load email template with file name '".$name.".php' from folder customs/emails/");
			
			return;
		}
		
		include_once $file;
		
		if(isset($email_template) && is_array($email_template))
		{
			if(isset($email_template["body"]) && strpos($email_template["body"], "{message}") !== false)
				self :: $template["body"] = $email_template["body"];
				
			if(isset($email_template["css"]) && is_array($email_template["css"]) && count($email_template["css"]))
				self :: $template["css"] = $email_template["css"];
		}
	}

	/**
	 * Sends the email message, using email framework.
	 */
	static public function send(string $recipient, 
								string $subject, 
								string $message, 
								array $headers = [], 
								array $attachments = [])
	{
		$registry = Registry :: instance();

		include_once $registry -> getSetting("IncludeAdminPath")."phpmailer/src/PHPMailer.php";
		include_once $registry -> getSetting("IncludeAdminPath")."phpmailer/src/SMTP.php";

		$from = $registry -> getSetting("EmailFrom");
		$from = $headers['From'] ?? $from;

   		if(!$from && isset($_SERVER["SERVER_NAME"]))
   			$from = "no-reply@".$_SERVER["SERVER_NAME"];
		
		if(!count(self :: $template))
			self :: setTemplate("default");
				
		//If template is set up we parse it
		if(isset(self :: $template["body"]) && self :: $template["body"])
		{
			$message = str_replace("{message}", $message, self :: $template["body"]);
			$message = str_replace("{subject}", $subject, $message);
			
			if($registry -> getSetting("EmailSignature"))
				$message = str_replace("{signature}", $registry -> getSetting("EmailSignature"), $message);
		}
		else if($registry -> getSetting("EmailSignature")) //Adds signature from config/settings.php if exists
			$message .= "\r\n".$registry -> getSetting("EmailSignature");
				
		//Add domain name into message
		$domain = Registry :: get('MainPath') == '/' ? 'DomainName' : 'HttpPath';
		$message = str_replace("{domain}", $registry -> getSetting($domain), $message);
		$message = preg_replace("/\s*([-a-z0-9_\.]+@[-a-z0-9_\.]+\.[a-z]{2,5})/i", ' <a href="mailto:$1">$1</a>', $message);

		//Starts mail process
   		$mail = new PHPMailer(true);
   		
   		$mail -> CharSet = "UTF-8";
   		$mail -> Encoding = "quoted-printable";

   		try
   		{
	   		if($registry -> getSetting("EmailMode") == "smtp")
	   		{
			    $mail -> isSMTP();
			    $mail -> Host       = $registry -> getSetting("SMTPHost");
			    $mail -> SMTPAuth   = true;
			    $mail -> Username   = $registry -> getSetting("SMTPUsername");
			    $mail -> Password   = $registry -> getSetting("SMTPPassword");
			    $mail -> SMTPSecure = PHPMailer :: ENCRYPTION_SMTPS;
			    $mail -> Port       = $registry -> getSetting("SMTPPort");
	   		}

	   		if($from)
	   		{
	   			if(preg_match("/^[^<]+<[^<>]+>$/", $from))
	   			{
	   				$from = explode("<", str_replace(">", "", $from));
	   				$mail -> setFrom(trim($from[1]), trim($from[0]));
	   			}
	   			else
			   		$mail -> setFrom($from);
		   	}

	   		foreach(self :: explodeEmailAddress($recipient) as $to)
			{
				if(is_array($to) && count($to) == 2)
					$mail -> addAddress($to[0], $to[1]);
				else
					$mail -> addAddress($to);
			}

			foreach($headers as $key => $value)
				$mail -> addCustomHeader($key, $value);
			
			foreach($attachments as $file)
				if(is_file($file))
					$mail -> addAttachment($file);

			$mail -> isHTML(true);
			$mail -> Subject = $subject;
			$mail -> Body = self :: addCssStyles($message);			

			$result = $mail -> send();
		}
		catch(Exception $error)
		{
			if(!isset($result) || !$result)
			{
				if(Registry :: onDevelopment())
					Debug :: displayError('PHPMailer error: '.$mail -> ErrorInfo);
				else
					Log :: add('PHPMailer error: '.$mail -> ErrorInfo);
			}
		}

		return isset($result) ? (bool) $result : false;
	}
	
	/**
	 * Divides string of email addresses.
	 * @return array
	 */
	static public function explodeEmailAddress(string $email)
	{
		$emails = (strpos($email, ",") === false) ? array($email) : explode(",", $email);
		$result = [];
		
		foreach($emails as $email)
		{   			
   			if(strpos($email, "<") !== false)
   			{
   				$address = trim(preg_replace("/.*<([^>]+)>.*/", "$1", $email));
   				$name = trim(preg_replace("/(.*)<[^>]+>.*/", "$1", $email));
			    $result[] = [$address, $name];
   			}
   			else
   				$result[] = trim($email);
		}

   		return $result;
	}

	/**
	 * Applies CSS styles of current template to html message.
	 * @return string $message styled with css
	 */
	static public function addCssStyles(string $message)
	{
		$registry = Registry :: instance();
		$css_templates = $registry -> getSetting('EmailCssStyles');
		
		if(!is_array($css_templates))
			$css_templates = [];
		
		if(isset(self :: $template["css"]) && count(self :: $template["css"]))
			foreach(self :: $template["css"] as $key => $value)
				$css_templates[$key] = $value;
		
		$common_styles = false;
		
		if(!$css_templates || !count($css_templates))
			return $message;
			
		$tags = [];
		
		foreach($css_templates as $key => $template)
		{
			$template = preg_replace("/;\s*$/", "", $template);

			if($key == "*" && $template)
			{
				$common_styles = $template;
				continue;
			}
			else if(strpos($key, ",") !== false)
			{
				$keys = preg_split("/\s*,\s*/", $key);
				
				foreach($keys as $i)
					if(isset($tags[$i]) && $tags[$i])
						$tags[$i] .= ";".$template;
					else
						$tags[$i] = $template;
			}
			else
			{
				if(isset($tags[$key]) && $tags[$key])
					$tags[$key]	.= "; ".$template;
				else
					$tags[$key]	= $template;
			}
		}
		
		if($common_styles)
			foreach($tags as $key => $style)
				$tags[$key] = $common_styles."; ".$style;
				
		foreach($tags as $key => $style)
		{
			//If style attribute exists we move it to the first position
			$message = preg_replace("/<(".$key.")([^>]*)\sstyle=.([^\"'>]+).(.*)>/", "<$1 style=\"$3\"$2$4>", $message);
			$message = preg_replace("/<(".$key.")([^>]*)>/", "<$1 style=\"".$style."\"$2>", $message); //Adds config styles
			
			$re = "/<(".$key.")\sstyle=.([^\"'>]+).\sstyle=.([^\"'>]+).(.*)>/";			
			$message = preg_replace($re, "<$1 style=\"$2; $3\"$4>", $message); //Deletes double attribute style
			$message = str_replace(";;", ";", $message); //Clean up
		}
			
		return $message;
	}
}
