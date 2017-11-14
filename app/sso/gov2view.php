<?
/********************************************************************
*	Date		: 25 Mar 2015
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: e-Gov Lab Univ of Indonesia 
*********************************************************************/


#------------------------configuration
    if ($gov2->error && !$view) {$view=$gov2->error;}
    elseif (!$gov2->error) {$view="profile";}

#------------------------view
switch ($view) {
    case "escape":
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Gov 2.0 Login iFrame</title>
        </head>
        <body>
        <script>
            <?if ($config->secure) {$protocol="https";} else {$protocol="http";}?>
            window.<?if (!$servicepage) {echo "parent.";}?>location.href = "<?echo $protocol;?>://<?echo $_SERVER["SERVER_NAME"];?>/gov2login.php?cmd=authorize&token=<?echo $token;?>"; 
        </script>
        <a target="_parent" href="<?echo $protocol;?>://<?echo $_SERVER["SERVER_NAME"];?>/gov2login.php?cmd=authorize"><img src="images/ajax-loader.gif" border="0"></a>
        </body>
        </html>
        <?
    break;
    case "NotExist":
    case "NotMember":
    case "UnauthorizedPage":
    case "UnauthorizedCase":
    case "InvalidToken":
        echo "Err:".$gov2->error;
    break;
    case "signup":
        ?>
        <style>
        .saas-frame {
        position: relative;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        }
        </style>
        <div class="saas-frame">
        <iframe src="<?echo account_url."/ssignup.php";?>?client=<?echo $_SERVER["SERVER_NAME"];?>" width="100%" frameborder="0"></iframe>		
        </div>	
        <script type="text/javascript" src="https://standar.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
        </script>
        <?
    break;
    case "fbconnect":
        ?>
        <style>
        .saas-frame {
        position: relative;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        }
        </style>
        <div class="saas-frame">
        <iframe src="<?echo account_url."/slogin.php";?>?cmd=connect_fb&secure=<?echo $config->secure;?>" width="100%" frameborder="0"></iframe>		
        </div>	
        <script type="text/javascript" src="https://standar.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
        </script>
        <?
    break;
    case "NotLogin":
        ?>
        <style>
        .saas-frame {
        position: relative;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        }
        </style>
        <div class="saas-frame">
        <iframe src="<?echo account_url?>/slogin.php?client=<?echo $_SERVER["SERVER_NAME"];?>&secure=<?echo $config->secure;?>" width="100%" frameborder="0"></iframe>		
        </div>	
        <script type="text/javascript" src="https://standar.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
        </script>
        <?
    break;
    case "profile":
        ?>
        <style>
        .saas-frame {
        position: relative;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        }
        </style>
        <div class="saas-frame">
        <iframe src="<?echo account_url;?>/sprofile.php?client=<?echo $_SERVER["SERVER_NAME"];?>&tab=<?echo $_GET['tab'];?>" width="100%" frameborder="0"></iframe>		
        </div>	
        <script type="text/javascript" src="https://standar.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
        </script>
        <?
    break;
    case "activation": 
        ?>
        <style>
        .saas-frame {
        position: relative;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        }
        </style>
        <div class="saas-frame">
        <iframe src="<?echo account_url."/ssignup.php";?>?cmd=activation&client=<?echo $_SERVER["SERVER_NAME"];?>" width="100%" frameborder="0"></iframe>		
        </div>	
        <script type="text/javascript" src="https://geo.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
        </script>
        <?
    break;
    default:
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Gov 2.0 Login iFrame</title>
        </head>
        <body>
        <script>
            window.location.href = "<?echo $protocol;?>://<?echo $_SERVER["SERVER_NAME"];?>";
        </script>
        </body>
        </html>
        <?
}
?>