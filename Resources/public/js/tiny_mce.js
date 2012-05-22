ecommitFileBrowser = {

    url: null,

    init: function (url) {
        ecommitFileBrowser.url = url;
    },

    tinymceCallback: function (field_name, url, type, win) {

        tinyMCE.activeEditor.windowManager.open({
            file : ecommitFileBrowser.url,
            title : 'File Browser',
            width : 800,
            height : 500,
            resizable : "yes",
            inline : "yes",
            close_previous : "no"
            }, {
            window : win,
            input : field_name
            });
        return false;
    }
};