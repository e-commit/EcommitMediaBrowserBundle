var ecommitFileBrowserDialogue = {

    init : function () {
    },
    
    submit : function (url) {
        var args = top.tinymce.activeEditor.windowManager.getParams();
        var win = (args.window);
        var input = (args.input);
        win.document.getElementById(input).value = url;
        if (typeof(win.ImageDialog) != "undefined") {
            if (win.ImageDialog.getImageData)
                win.ImageDialog.getImageData();
            if (win.ImageDialog.showPreviewImage)
                win.ImageDialog.showPreviewImage(url);
        }
        top.tinymce.activeEditor.windowManager.close();
    }
};