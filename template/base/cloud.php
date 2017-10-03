<div id="service_loading" class="saas-loading">
    <img src="images/ajax-loader.gif">
    <h2>Loading...</h2>
</div>

<div class="saas-frame">
    <iframe src="<?echo $data->link;?>" width="100%" frameborder="0" name="service_frame" id="service_frame" style="min-height: 700px;"></iframe>
</div>
<input type="hidden" id="panel_url" value="<?echo $data->link;?>">
<input type="hidden" id="panel_domain" value="<?echo $_SESSION['active_cloud'];?>">