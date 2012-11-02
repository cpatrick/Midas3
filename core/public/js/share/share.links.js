$(document).ready(function () {
    $('#selectViewUrl').click(function () {
        $('#resourceViewDisplay').focus();
        $('#resourceViewDisplay').select();
    });
    $('#selectDownloadUrl').click(function () {
        $('#resourceDownloadDisplay').focus();
        $('#resourceDownloadDisplay').select();
    });
    $('#selectViewUrl,#selectDownloadUrl').qtip({
        content: {
            text: 'Select this text'
        },
        style: {
            classes: 'dialogQtip'
        }
    });
    $('input.linksDone').click(function () {
        $('div.MainDialog').dialog('close');
    });
});