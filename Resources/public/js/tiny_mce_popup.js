var ecommitFileBrowserDialogue = {

    init : function () {
    },
    
    submit : function (url) {
        var win = tinyMCEPopup.getWindowArg("window");
        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = url;
        if (typeof(win.ImageDialog) != "undefined") {
            if (win.ImageDialog.getImageData)
                win.ImageDialog.getImageData();
            if (win.ImageDialog.showPreviewImage)
                win.ImageDialog.showPreviewImage(url);
        }
        tinyMCEPopup.close();
    }
};

tinyMCEPopup.onInit.add(ecommitFileBrowserDialogue.init, ecommitFileBrowserDialogue);