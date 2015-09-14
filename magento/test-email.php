<?php
$to = "dharmesh.php@gmail.com, danny@vaporin.com, danny@vpco.com, dmozlin@gmail.com, rohan@quicknetsoft.com, akshay@quicknetsoft.com, akshay5477@gmail.com, dharmesh.makwana1313@gmail.com";
$subject = "Test email";
$message = "This is a test email.";
$from = "sales@thevapestoreonline.com";
$headers = "From:" . $from;
if (mail($to, $subject, $message, $headers)) {
	echo("Your message has been sent successfully");
	} else {
	echo("Sorry, your message could not be sent");
}
?>