<?
#$overview=$doc->readxml("overview");

if (isset($overview)) {
    foreach ($overview->section as $item) {
        if ($item->section_id) {?>
        <h3><span><?echo $item->caption;?></span></h3>
        <div class="row">
            <div class="col-xs-12">
                <?echo $item->content;?>
            </div>
        </div>            
        <?}
    }
}?>