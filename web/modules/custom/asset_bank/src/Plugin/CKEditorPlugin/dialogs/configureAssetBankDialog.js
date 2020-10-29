CKEDITOR.dialog.add( 'configureAssetBankDialog', function( editor ) {
    return {
		title: 'Asset Bank Module',
		minWidth: 400,
		minHeight: 50,
        contents: [
            {
                id: 'tab-1',
                label: '',
				elements: [
					{
						type: 'html',
						html: 'Please configure the Asset Bank Module in the <a class="link" target="_blank" href="/admin/config/media/asset_bank">Extend</a> section and refresh this page.'
					}
				]
            },
        ],
		buttons: [ CKEDITOR.dialog.okButton ]
    };
});
