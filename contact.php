<?php


if ($_POST['submit']) {
	
	if (!$_POST['name']) {
		$error="<br/>- Please enter your name";
	}
	
	if (!$_POST['email']) {
		$error.="<br/>- Please enter your email";
	}
	
	if (!$_POST['message']) {
		$error.="<br/>- Please enter a message";
	}
	
	if (!$_POST['check']) {
		$error.="<br/>- Please confirm you are human";
	}
	
	if ($error) {
		$results='<div class="alert alert-danger" role="alert"><strong>Sorry, there is an error.</strong> Please correct the following: '.$error.' </div';
	} else {
		mail("mike@mikesdreamosophy.com", "Contact message", "Name: ".$_POST['name'].
			"Email: ".$_POST['email'].
			"Message: ".$_POST['message']);
		{
		$results='<div class="alert alert-success" role="alert"><stron>Thank you! We will get back in touch with you shortly.</div>';	
		}
	}
}
		   

?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us</title>
	<script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
    
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <script   src="https://code.jquery.com/jquery-3.1.1.js"   integrity="sha256-16cdPddA6VdVInumRGo6IbivbERE8p7CQR3HzTBuELA="   crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
	<link rel="stylesheet" type="text/css" href="css/contact.css">
	<link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet">
	
</head>

<body>
	<nav class="navbar navbar-inverse navbar-fixed-top">
		
		<div class="container">
			
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-nav-demo" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="index.html"><img src="images/white owl.png" class="logo" alt="owl icon"/></a>
      		</div>
			
			<div class="collapse navbar-collapse" id="bs-nav-demo">
				
				<ul class="nav navbar-nav">
					<li class="menu"><a href="about.html">ABOUT</a></li>
					<li class="dropdown menu">
          		    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">BLOGS<span class="caret"></span></a>
          			<ul class="dropdown-menu">
            			<li><a href="remember.html">How to Remember Dreams</a></li>
                        <li><a href="journal.html">How to Use a Dream Journal</a></li>
          			</ul>
        			</li>
					<li class="menu"><a href="shop.html">SHOP</a></li>
				</ul>
				
				<ul class="nav navbar-nav navbar-right">
        			<li><a href="contact.php"><i class="fa fa-envelope envelope" aria-hidden="true"></i> &nbsp;CONTACT</a></li>
        		</ul>
					
			</div>
			
		</div>
		
	</nav>
    
    <div class="ad">
	<!--Chitika -->
	<script type="text/javascript">
    ( function() {
        if (window.CHITIKA === undefined) { window.CHITIKA = { 'units' : [] }; };
        var unit = {"calltype":"async[2]","publisher":"mightymike105","width":728,"height":90,"sid":"Chitika Default"};
        var placement_id = window.CHITIKA.units.length;
        window.CHITIKA.units.push(unit);
        document.write('<div id="chitikaAdBlock-' + placement_id + '"></div>');
        }());
    </script>
    <script type="text/javascript" src="//cdn.chitika.net/getads.js" async></script>
	</div>
		
	<section id="contact">
		<div class="container">
		
			<div class="row">
				
				<h1 class="welcome">Contact Form</h1>
				
				<div class="col-md-6 col-md-offset-3">
					
					<?php echo $results;?>
					
					<center>
						<p><strong>Questions and comments are welcome. mike@mikesdreamosophy.com</strong></p>
					</center>
					
					<form method="post" role="form">
						
						<div class="form-group">
							<input type="text" name="name" class="form-control" placeholder="Your Name" value="<?php echo $_POST['name']; ?>">
						</div>
						
						<div class="form-group">
							<input type="email" name="email" class="form-control" placeholder="Your Email" value="<?php echo $_POST['email']; ?>">
						</div>
						
						<div class="form-group">
							<textarea name="message" rows="8" class="form-control" placeholder="Message..."><?php echo $_POST['message']; ?></textarea>
						</div>
						
						<div class="checkbox">
							<label>
								<input type="checkbox" name="check"> I am human
							</label>
						</div>
						
						<div align="center">
							<input type="submit" name="submit" class="btn btn-default" value="send message"/>
						</div>
						
					</form>
						  
				</div>
			</div>
		
		
		</div>
	</section>
	
	
	<div class="container">
		<center>
			<img class="social2" src="images/mikedreamlogo.png" width="200px" height="auto" alt="mikesdreamlogo"/>
		</center>
	</div>
	
	
	<div class="container footer">
		
		<h4><a href="https://www.facebook.com/mikesdreamosophy/"><img class="social2" src="images/facebook.png" width="50px" height="auto" alt="facebook"/></a>
	
		<a href="https://twitter.com/mikedreamosophy/"><img class="social2" src="images/twitter.png" width="50px" height="auto" alt="twitter"/></a></h4>
	
	<h5>© Copyright 2018, Sonicpress LLC - © Copyright 2018 Limnosophy LLC, used with permission. All Rights Reserved.</h5><br>
		<h6 class="sonic">Web Design by Sonicpress</h6><br>
	</div>
	
	
</body>
</html>



