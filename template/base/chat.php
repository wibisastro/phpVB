<?while (list($key,$val)=each($data)) {?>
<div class="conversation-item <?if ($val->sender_id==$_GET['account_id']) {echo "item-right";} else {echo "item-left";}?> clearfix">
    <div class="conversation-body">
        <div class="name">
            <?echo $val->sender_id.$val->chat_id;?>
        </div>
        <div class="time">
            <?echo $val->date_inserted;?>
        </div>
        <div class="text">
            <?echo $val->message;?>
        </div>
    </div>
</div>
<?}?>