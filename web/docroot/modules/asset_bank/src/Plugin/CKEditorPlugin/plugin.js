CKEDITOR.plugins.add( 'asset_bank', {
    icons: 'asset_bank',
    init: function( editor ) {

        var pluginDirectory = this.path;
        var isModuleConfigured = editor.config.asset_bank.url.trim() != '';

        var buttonLabel = isModuleConfigured ?
                          'Insert an asset from ' + editor.config.asset_bank.name :
                          'You need to configure the module in the Extend section';

        var buttonIcon = isModuleConfigured ?
                         pluginDirectory + '/icons/logo.png' :
                         pluginDirectory + '/icons/logo-disabled.png';

        var buttonCommand = isModuleConfigured ?
                            'insertAsset' :
                            'configureModule';

        assetBankEditor = undefined;
        editor.addCommand('insertAsset', {
            exec: function(editor) {
                assetBankEditor = editor;
                var ab_cms_mode_url = editor.config.asset_bank.url + '/action/selectImageForCms?returnurl=' + editor.config.asset_bank.callback_url;
                if (editor.config.asset_bank.repository_number) {
                    ab_cms_mode_url += '&repositoryNumber=' + editor.config.asset_bank.repository_number
                }
                if (editor.config.asset_bank.subrepository_name) {
                    ab_cms_mode_url += '&subrepositoryName=' + editor.config.asset_bank.subrepository_name
                }
                var assetbank_window = window.open(ab_cms_mode_url,'ABPopup','height=800,width=1024,resizable=yes,status=yes,scrollbars=yes');
                if (window.focus) { assetbank_window.focus(); }
            }
        });

        editor.addCommand('configureModule', new CKEDITOR.dialogCommand('configureAssetBankDialog'));
        CKEDITOR.dialog.add( 'configureAssetBankDialog', pluginDirectory + 'dialogs/configureAssetBankDialog.js' );

        editor.ui.addButton( 'AssetBank', {
            label: buttonLabel,
            command: buttonCommand,
            icon: buttonIcon
        });

    }
});


function assetbankInsertImage(callbackVars) {
    if(callbackVars.mimeType.startsWith('video')){
        // Instructions for video assets:
        // If you intend to embed video assets, and you have limited HTML tags,
        // please add `<video width controls> <source src type>` to the "Allowed HTML tags" section in "Configuration > Content Authoring > Text formats and editors"
        assetBankEditor.insertHtml('<video width="" controls><source src="' + callbackVars.imageUri + '" type="' + callbackVars.mimeType + '"> ' +
                                   'Your browser does not support the video tag.</video>');
    }else{
        assetBankEditor.insertHtml('<img src="' + callbackVars.imageUri + '" data-entity-type="media" data-entity-uuid="' + callbackVars.mediaUuid + '" />');
    }
}
