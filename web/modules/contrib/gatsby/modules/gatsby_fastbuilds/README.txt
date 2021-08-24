Speeds up build times for gatsby develop and build commands by only downloading
content that has changed since the last build.

Gatsby Fastbuilds logs entity changes on your Drupal site and allows Gatsby to
only pull content that has changed since the last sync. This can dramatically
speed up build times for larger sites and can help speed up the development
process as well.

# Installation

1. Install the gatsby_fastbuilds module on your Drupal website.
2. Configure the entities and admin options for your site (see below).
3. Enable the `fastBuilds` option in your gatsby-config.js file as documented
in the gatsby-source-drupal Gatsby plugin.

# Configuration Options

There are two admin pages for the Gatsby Fastbuilds module. The first is the
log of all the entity changes. The JSON for any entity changes are stored and
then synced to the Gatsby site (running gatsby-source-drupal) based on a cached
timestamp. The entites that are logged are the ones selected on the main Gatsby
module admin page (at the bottom of the form).

The second admin page is the configuration form. The first option allwos you to
only sync published content changes. This is recommended if you are using
Fastbuilds for your production builds (to prevent unpublished content from
syncing to your live Gatsby site). You may want this to be unchecked in
development so you can see unpublished content on your Gatsby site.

The last checkbox on the configuration page allows you to specify how long to
keep log entries around. If a Gatsby site tries to sync and it's timestamp is
older than the last log entry, it will perform a full rebuild of the Gatsby
site.

# How to Use

Once configured, Gatsby Fastbuilds will work in the background without any
changes to your development or build workflow. If you want to run a full build
you will need to run `gatsby clean` before your develop or build command.
