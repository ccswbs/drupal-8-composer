Adds additional features for JSON:API integration.

# Menus

To enable Gatsby menus you will need to install the Gatsby JSON:API Extras
module until the point that the issue in JSON:API Extras is resolved
https://www.drupal.org/project/jsonapi_extras/issues/2982133.
The menu functionality relies on the overriding the menu_link_content parent
field to use our "Alias link" formatter.

To expose the menu_link_content endpoint in the JSON:API you will need
the Gatsby user to have the "Administer menus and menu items" permission.
This can be done using basic_auth with the gatsby-source-drupal plugin
or by using the key_auth module with Gatsby to an account with that permission.
