	<!-- global scripts -->
	<script src="js/jquery.js"></script>
    <script src="js/bootstrap.js"></script>
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/jquery.nanoscroller.min.js"></script>
	<script src="js/footable.js"></script>
	<script src="js/footable.sort.js"></script>
	<script src="js/footable.paginate.js"></script>
	<script src="js/footable.filter.js"></script>

  <!-- theme scripts -->
	<script src="js/scripts.js"></script>
	<script src="js/pace.min.js"></script>

    <!-- Specific Page-->
    <?
    foreach($config->template as $template) {
        foreach($template->js as $item) {?>
        <script src="js/<?echo $item;?>"></script>
    <?}}?>

    <script src="js/basic.php?pageID=<?echo $pageID;?>"></script>
    <script src="js/specific.php?pageID=<?echo $pageID;?>"></script>

<?if ($fullpage) {?>
    <div id="clipboard" style="display: none"></div>
    <script type="text/javascript" src="https://sso.gov2.web.id/js/iframeResizer.min.js"></script>
    <script type="text/javascript">
        iFrameResize({
            log                     : false,
            enablePublicMethods     : true,
            scrollCallback          : function(messageData){
              // alert('scrolling');
            },
            resizedCallback         : function(messageData){
                if ($("#clipboard").html()=="newest_bottom") {
                    $('#panelbody').animate({scrollTop: messageData.height}, 1000);
                }
            },
            messageCallback         : function(messageData){
                if (messageData.message=='"toppanel"') {
                    $('#panelbody').animate({scrollTop: 0}, 1000);
                } else if (messageData.message=='"topcloud"') {
                    $('html, body').animate({scrollTop: 0}, 1000);
                } else if (messageData.message=='"bottom"') {
                    $('#panelbody').animate({scrollTop: $('#panel_frame').height()}, 1000);
                } else {
                    var response=messageData.message;
                    var trimmed=response.replace('"', '');
                    trimmed=trimmed.replace('"', '');
                    var decoded = trimmed.split(",");
                    if (decoded[0]=='copy') {
                        $.get( "api.php?cmd="+decoded[1], function( html ) {
                            $( "#clipboard" ).html( html );
                            if (decoded[2]) {window[decoded[2]]();}
                        });
                    } else if (decoded[0]=='click') {
                        $('#'+decoded[1]).click();
                        if (decoded[2]) {window[decoded[2]]();}
                    } else if (decoded[0]=='clipboard') {
                        var deskboard_url='http://'+$('#panel_domain').val();
                        var clipboard_url=deskboard_url+decoded[1]+'&cmd=panel';
                        if (decoded[2]) {window[decoded[2]](clipboard_url);}
                    }
                }
            }
        });

        function receiveMessage(event)
        {
          // Do we trust the sender of this message?
      //    if (event.origin !== "http://example.com:8080")
      //      return;

          // event.source is window.opener
          // event.data is "hello there!"

          // Assuming you've verified the origin of the received message (which
          // you must do in any case), a convenient idiom for replying to a
          // message is to call postMessage on event.source and provide
          // event.origin as the targetOrigin.
            if (event.data=='service') {
                $('#service_frame').get(0).contentWindow.postMessage("balas ke:"+event.origin,"http://standar.gov2.web.id/");
            }
         // event.source.postMessage("balas ke:"+event.origin, event.origin);
        }

        window.addEventListener("message", receiveMessage, false);

        $(window).scroll(function () {
            $('#service_frame').contents().find('#menu').css('top',$('body').scrollTop());
            $('#service_frame').contents().find('#styleEditor').css('top',$('body').scrollTop());
            $('#service_frame').contents().find('#header').css('top',$('body').scrollTop());
        });

    </script>
<?} else {?>
    <script type="text/javascript" src="https://sso.gov2.web.id/js/iframeResizer.contentWindow.min.js"></script>
<?}?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', '<?echo $config->googleanalytic;?>', 'auto');
  ga('send', 'pageview');

 </script>
