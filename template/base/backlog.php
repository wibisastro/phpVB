<style>
.saas-frame {
position: relative;
top: 20px;
bottom: 0;
left: 0;
right: 0;
}
.leg1 { 
    display: block;
    font-size: 1.17em;
    font-weight: bold;
	text-align: center;
}
</style>
<!--div class="leg1">
<p>Legend</p>
<img src="images/legend.png" height="90%" width="90%">
</div-->

<div class="saas-frame">
<iframe src="<?echo $url_service;?>" width="100%" frameborder="0" name="service_frame" id="service_frame" style="min-height: 700px;"></iframe>
</div>	

		<script type="text/javascript" src="http://geo.gov2.web.id/js/iframeResizer.min.js"></script>
        <script type="text/javascript">

            iFrameResize({
                log                     : false,                  // Enable console logging
                enablePublicMethods     : true,                  // Enable methods within iframe hosted page
                resizedCallback         : function(messageData){ // Callback fn when message is received
                }
            });
			</script>
