/**
 * @file
 * Contains all javascript logic for Gatsby preview button.
 */

(function ($, Drupal) {

  Drupal.behaviors.gatsby_preview = {
    attach: function (context, settings) {
      // Get the alias on page load, not the alias that might be edited
      // and thus trigger a 404.
      if (context == document) {
        // Remove trailing slash.
        var gatsby_url = settings.gatsby_preview_url.replace(/\/$/, '');
        var alias = settings.gatsby_path;

        $("#edit-gatsby-preview").on("click", function(e) {
          e.preventDefault();

          // If the sidebar is already open, then close it.
          if ($(this).hasClass('sidebar-opened')) {
            $(this).removeClass('sidebar-opened');

            $(this).val(Drupal.t('Open Gatsby Preview'));
            $(".gatsby-iframe-sidebar").remove();
            $("body div.dialog-off-canvas-main-canvas").css("width", "100%");
          }
          // Open iframe sidebar if selected and the window is wide enough.
          else if (settings.gatsby_preview_target == 'sidebar' && window.innerWidth > 1024) {
            $(this).addClass("sidebar-opened");
            $(this).val(Drupal.t("Close Gatsby Preview"));

            // Calculate Iframe height.
            var iframeHeight = window.innerHeight - 100;
            var arrow = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"></path></svg>';

            var gatsbyIframe = '<div class="gatsby-iframe-sidebar">';
            gatsbyIframe += '<a class="gatsby-link" href="' + gatsby_url + alias + '" target="_blank">' + Drupal.t('Open in New Window ') + arrow + '</a>';
            gatsbyIframe += '<iframe width="100%" height=" ' + iframeHeight + '" class="gatsby-iframe" src="' + gatsby_url + alias + '" />';
            gatsbyIframe += '</div>';

            $('body div.dialog-off-canvas-main-canvas').css('width', '50%').css('float', 'left').after(gatsbyIframe);
          } else {
            // Open the full Gatsby page URL in a new window.
            window.open(gatsby_url + alias);
          }

        });
      }
    }
  };

})(jQuery, Drupal);
