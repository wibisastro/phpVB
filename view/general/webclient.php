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