function editForm(data) {
    var obj = jQuery.parseJSON(data);
    jQuery.each(obj, function(key, val) {
        if ($('#form_'+key).is(':checkbox')) {
            $('#form_'+key).prop('checked', true);
        } else {
            $('#form_'+key).val(val);
        }
    });

    $('#button_<?echo $_GET['pageID'];?>').val("update"); 
    $('#button_<?echo $_GET['pageID'];?>').text("Save");  
    $('#editform_<?echo $_GET['pageID'];?>').modal('show');
}

function closeEditForm_<?echo $_GET['pageID'];?>() {
    $('#editform_<?echo $_GET['pageID'];?>').modal('hide');
    $(this).closest('form').find("input[type=text], textarea").val("");
}

function regenerateForm(data) {
    var obj = jQuery.parseJSON(data);
    jQuery.each(obj, function(key, val) {
        if ($('#form_'+key).is(':checkbox')) {
            $('#form_'+key).prop('checked', true);
        } else {
            $('#form_'+key).val(val);
        }
    });
    $('#form_regenerate_apikey_id').val(obj['apikey_id']);
    $('#button_<?echo $_GET['pageID'];?>').val("update"); 
    $('#button_<?echo $_GET['pageID'];?>').text("Save");  
    $('#regenerateform_<?echo $_GET['pageID'];?>').modal('show');
}

function closeRegenerateForm_<?echo $_GET['pageID'];?>() {
    $('#regenerateform_<?echo $_GET['pageID'];?>').modal('hide');
    $(this).closest('form').find("input[type=text], textarea").val("");
}

