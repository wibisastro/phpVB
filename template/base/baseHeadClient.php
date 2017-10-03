<head>
	<meta charset="utf-8">
	<!--[if IE]>
		<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
	<![endif]-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">	
	<title><?echo $config->title;?></title>	
	<!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/admin.css" />
    <link rel="stylesheet" type="text/css" href="css/wizard.css" />
    <link rel="stylesheet" type="text/css" href="css/nanoscroller.css" />
    <link rel="stylesheet" type="text/css" href="css/custom.css" />

    <link rel="stylesheet" href="css/footable.core.css" type="text/css" />

    <!-- flickr galery page specific styles -->
    <link rel="stylesheet" type="text/css" href="css/gallery-component.css">

    <!-- product image galery page specific styles -->
    <link rel="stylesheet" type="text/css" href="css/image-picker.css">

    <!-- Google Web Fonts -->
    <link href="http://fonts.googleapis.com/css?family=Roboto+Condensed:300italic,400italic,700italic,400,300,700" rel="stylesheet" type="text/css">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>

    <!-- CSS Files -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <?if ($_SESSION['active_client']) {?>
    <script type="text/javascript">
        if (parent === window) {
            window.location.href = "http://<?echo $_SERVER['SERVER_NAME'];?>/gov2auth.php?cmd=escape";
        }
    </script>
    <?}?>
</head>