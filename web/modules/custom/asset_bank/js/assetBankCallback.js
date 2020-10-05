(function ($) {
    try {
        opener.assetbankInsertImage(drupalSettings.asset_bank.assetBankCallback);
    }
    catch(err) {
        opener.console.log(err.message);
    }
    finally {
        window.close();
    }
})(jQuery);
