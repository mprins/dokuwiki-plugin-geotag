<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

/*
 * Copyright (c) 2011 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * DokuWiki Plugin geotag (Action Component).
 *
 * @license BSD license
 * @author  Mark C. Prins <mprins@users.sf.net>
 */
class action_plugin_geotag extends ActionPlugin
{
    /**
     * Register for events.
     *
     * @param EventHandler $controller
     *          DokuWiki's event controller object. Also available as global $EVENT_HANDLER
     */
    final public function register(EventHandler $controller): void
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handleMetaheaderOutput');
        if ($this->getConf('toolbar_icon')) {
            $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insertButton', []);
        }
        $controller->register_hook('PLUGIN_POPULARITY_DATA_SETUP', 'AFTER', $this, 'popularity');
    }

    /**
     * Retrieve metadata and add to the head of the page using appropriate meta tags.
     *
     * @param Event $event
     *          the DokuWiki event. $event->data is a two-dimensional
     *          array of all meta headers. The keys are meta, link and script.
     *
     * @see https://www.dokuwiki.org/devel:event:tpl_metaheader_output
     */
    final public function handleMetaheaderOutput(Event $event): void
    {
        global $ID;
        $title = p_get_metadata($ID, 'title', METADATA_RENDER_USING_SIMPLE_CACHE);
        $geotags = p_get_metadata($ID, 'geo', METADATA_RENDER_USING_SIMPLE_CACHE) ?? [];
        $region = $geotags ['region'] ?? null;
        $lat = $geotags ['lat'] ?? null;
        $lon = $geotags ['lon'] ?? null;
        $alt = $geotags ['alt'] ?? null;
        $country = $geotags ['country'] ?? null;
        $placename = $geotags ['placename'] ?? null;
        $geohash = $geotags ['geohash'] ?? null;

        if (!empty($region)) {
            $event->data ['meta'] [] = ['name' => 'geo.region', 'content' => $region];
        }
        if (!empty($placename)) {
            $event->data ['meta'] [] = ['name' => 'geo.placename', 'content' => $placename];
        }
        if (!(empty($lat) && empty($lon))) {
            if (!empty($alt)) {
                $event->data ['meta'] [] = ['name' => 'geo.position', 'content' => $lat . ';' . $lon . ';' . $alt];
            } else {
                $event->data ['meta'] [] = ['name' => 'geo.position', 'content' => $lat . ';' . $lon];
            }
        }
        if (!empty($country)) {
            $event->data ['meta'] [] = ['name' => 'geo.country', 'content' => $country];
        }
        if (!(empty($lat) && empty($lon))) {
            $event->data ['meta'] [] = ['name' => "ICBM", 'content' => $lat . ', ' . $lon];
            // icbm is generally useless without a DC.title,
            // so we copy that from title unless it's empty...
            if (!(empty($title))) {
                /*
                 * don't specify the DC namespace as this is incomplete; it should be done at the
                 * template level as it also needs a 'profile' attribute on the head/container,
                 * see: https://dublincore.org/documents/dc-html/#sect-3.1.1
                 * $event->data ['link'] [] = array ('rel' => 'schema.DC',
                 * 'href' => 'http://purl.org/dc/elements/1.1/');
                 */
                $event->data ['meta'] [] = ['name' => "DC.title", 'content' => $title];
            }
        }
        if (!empty($geohash)) {
            $event->data ['meta'] [] = ['name' => 'geo.geohash', 'content' => $geohash];
        }
    }

    /**
     * Inserts the toolbar button.
     *
     * @param Event $event
     *          the DokuWiki event
     */
    final public function insertButton(Event $event, array $param): void
    {
        $event->data [] = [
            'type' => 'format',
            'title' => $this->getLang('toolbar_desc'),
            'icon' => '../../plugins/geotag/images/geotag.png',
            'open' => '{{geotag>lat:', 'sample' => '52.2345',
            'close' => ', lon:7.521, alt: , placename: , country: , region: }}'
        ];
    }

    /**
     * Add geotag popularity data.
     *
     * @param Event $event
     *          the DokuWiki event
     */
    final public function popularity(Event $event): void
    {
        $versionInfo = getVersionData();
        $plugin_info = $this->getInfo();
        $event->data['geotag']['version'] = $plugin_info['date'];
        $event->data['geotag']['dwversion'] = $versionInfo['date'];
        $event->data['geotag']['combinedversion'] = $versionInfo['date'] . '_' . $plugin_info['date'];
    }
}
