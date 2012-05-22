var active_name = null;

$(document).ready(function() {
    $('td').bind('click', function(event) {
        $('#column-overview div').hide();
        $('td.active').removeClass('active');
        $(this).addClass('active');
        
        if($(this).hasClass('file'))
        {
            $('#overview-file-name').html($(this).attr('data-name'));
            $('#overview-file-size').html($(this).attr('data-size'));
            $('#overview-file-delete').attr('href', $(this).attr('data-delete'));
            $('#overview-file-rename').attr('href', $(this).attr('data-rename'));
            active_name = $(this).attr('data-name');
            if($(this).attr('data-image') == '1')
            {
                $('#overview-image img').attr('src', $(this).attr('data-url'));
                $('#overview-image').show();
            }
            $('#overview-file').show();
        }
        else
        {
            $('#overview-dir-name').html($(this).attr('data-name'));
            $('#overview-dir-open').attr('href', $(this).attr('data-url'));
            $('#overview-dir-delete').attr('href', $(this).attr('data-delete'));
            $('#overview-dir-rename').attr('href', $(this).attr('data-rename'));
            active_name = $(this).attr('data-name');
            $('#overview-dir').show();
        }
    });
    
    
    $('td').bind('dblclick', function(event) {
        var url = $(this).attr('data-url');
        if($(this).hasClass('file'))
        {
            ecommitFileBrowserDialogue.submit(url);
        }
        else
        {
            document.location.href = url;
        }
    });
});

function rename_element(link)
{
    var url = $(link).attr('href');
    var new_name = prompt(null, active_name);
    if(!new_name)
    {
        return false;
    }
    var reg = new RegExp("(^[A-Za-z0-9\._-]+$)", "g");
    if(new_name.match(reg))
    {
        url = url + '?new_name=' + new_name;
        document.location.href = url;
    }
    else
    {
        alert('Bad value');
    }
}