<?php
$config = include("config.php");

$dsn = "mysql:host=".$config['db_host'].";dbname=".$config['db_name'].";charset=".$config['db_charset'];
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $config['db_options']);

$debugLines = array();
function debug($line){
	global $debugLines;
	$debugLines[] = $line;
}
function debugLines(){
	global $debugLines;
	$ret = "";
	foreach($debugLines as $linenumber=>$line){
		$ret .= '<div class="alert alert-danger" role="alert">'.preg_replace("`\n+`","<br>",$line).'</div>';
	}
	return $ret;
}
function runSql($sql,$params = array()){
	global $pdo;
	try{
		$statement = $pdo->prepare($sql);
		$statement->execute($params);
		return $statement;
	}
	catch(PDOException $e){
		debug($sql . "\n" . $e->getMessage());
	}
}


//CREATE THE TABLES
runSql('CREATE TABLE IF NOT EXISTS `USER` (
	`U_id` INT AUTO_INCREMENT NOT NULL,
	`U_name` TEXT,
	`U_email` TEXT,
	`U_token` TEXT,
	`U_created` TIMESTAMP DEFAULT NOW(),
	PRIMARY KEY (`U_id`));');

if(isset($_COOKIE['helpbuttonemail']) && isset($_COOKIE['helpbuttontoken'])){
	$utest = array();
	$utest['email'] = $_COOKIE['helpbuttonemail'];
	$utest['token'] = $_COOKIE['helpbuttontoken'];
}
if(isset($_GET['email']) && isset($_GET['pass']) && strlen($_GET['email']) && strlen($_GET['token'])){
	$utest = array();
	$utest['email'] = $_GET['email'];
	$utest['token'] = $_GET['token'];
}

if(isset($utest)){
	$query = runSql('SELECT * FROM `USER` WHERE `U_email` LIKE :email AND `U_token` = :token ;',
		array(":email"=>$utest['email'],":token"=>$utest['token']));
	if($query != null && $sth->rowCount() > 0){
		$user = $query->fetch(PDO::FETCH_ASSOC);
		setcookie('helpbutttonemail', $user['email'], time()+60*60*24*365, '/', $config['url']);
		setcookie('helpbuttontoken', $user['pass'], time()+60*60*24*365, '/', $config['url']);
	}
}

if(isset($_GET['newToken'])){
	$query = runSql('SELECT * FROM `USER` WHERE `U_email` LIKE :email ;',
		array(":email"=>$_GET['newToken']));
	$token = md5(rand());
        if($query == null || $query->rowCount() <= 0){
		debug("User Created");
		$result = runSql('INSERT INTO `USER`(`U_email`,`U_token`) VALUES(:email,:token) ;',
                	array(":email"=>$_GET['newToken'],":token"=>$token));
	}else{
		debug("Login Sending");
                $resetee = $query->fetch(PDO::FETCH_ASSOC);
		$result = runSql('UPDATE `USER` SET `U_token` = :token WHERE `U_id`=:id ;',
			array(":token"=>$token,":id"=>$resetee['U_id']));
	}
	$url = $config['url'].'?email='.urlencode($_GET['newToken']).'&token='.urlencode($token);

	$message = '<p>Please click the following link to login to HelpButton<br><a href="'.$url.'">'.$url.'</a></p>';
	$headers = 'From: '.$config['email'] . "\r\n" .
		'Reply-To: '.$config['email'] . "\r\n" .
		'X-Mailer: PHP/' . phpversion();

	mail($_GET['newToken'], "HelpButton login",$message, $headers);

}
?>

<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta http-equiv="x-ua-compatible" content="ie=edge">

		<title>HelpButton</title>

		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/css/bootstrap.min.css" integrity="sha384-2hfp1SzUoho7/TsGGGDaFdsuuDL0LX2hnUp6VkX3CUQ2K4K+xjboZdsXyp4oUHZj" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/js/bootstrap.min.js" integrity="sha384-VjEeINv9OSwtWFLAtmc4JCtEJXXBub00gtSnszmspDLCtC0I4z4nqz7rEFbIZLLU" crossorigin="anonymous"></script>
	</head>
	<body>
	<?php
		//echo json_encode($config);

		if(isset($user)){
			echo '<button type="button" class="btn btn-primary btn-lg btn-block">Help</button>';
		}else{
			echo '<form method="get"><input type="text" name="newToken"><button type="submit" class="btn btn-primary">Get Login Email</button></form>';
		}
	?>

		<?php echo debugLines(); ?>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.0.0/jquery.min.js" integrity="sha384-THPy051/pYDQGanwU6poAc/hOdQxjnOEXzbT+OuUAFqNqFjL+4IGLBgCJC3ZOShY" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.2.0/js/tether.min.js" integrity="sha384-Plbmg8JY28KFelvJVai01l8WyZzrYWG825m+cZ0eDDS1f7d/js6ikvy1+X+guPIB" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/js/bootstrap.min.js" integrity="VjEeINv9OSwtWFLAtmc4JCtEJXXBub00gtSnszmspDLCtC0I4z4nqz7rEFbIZLLU" crossorigin="anonymous"></script>
	</body>
</html>
