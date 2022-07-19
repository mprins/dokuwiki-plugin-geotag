<?php
/*
 * Copyright (c) 2011-2021 Mark C. Prins <mprins@users.sf.net>
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
class action_plugin_geotag extends DokuWiki_Action_Plugin
{

    /**
     * Register for events.
     *
     * @param Doku_Event_Handler $controller
     *          DokuWiki's event controller object. Also available as global $EVENT_HANDLER
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handleMetaheaderOutput');
        if ($this->getConf('toolbar_icon')) {
            $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insertButton', array());
        }
    }

    /**
     * Retrieve metadata and add to the head of the page using appropriate meta tags.
     *
     * @param Doku_Event $event
     *                          the DokuWiki event. $event->data is a two-dimensional
     *                          array of all meta headers. The keys are meta, link and script.
     * @param mixed      $param ignored
     *
     * @see http://www.dokuwiki.org/devel:event:tpl_metaheader_output
     */
    public function handleMetaheaderOutput(Doku_Event $event, $param)
    {
        global $ID;
        $title     = p_get_metadata($ID, 'title', true);
        $geotags   = p_get_metadata($ID, 'geo', true);
        $region    = $geotags ['region'];
        $lat       = $geotags ['lat'];
        $lon       = $geotags ['lon'];
        $alt       = $geotags ['alt'];
        $country   = $geotags ['country'];
        $placename = $geotags ['placename'];
        $geohash   = $geotags ['geohash'];

        if (!empty ($region)) {
            $event->data ['meta'] [] = array(
                'name'    => 'geo.region',
                'content' => $region
            );
        }
        if (!empty ($placename)) {
            $event->data ['meta'] [] = array(
                'name'    => 'geo.placename',
                'content' => $placename
            );
        }
        if (!(empty ($lat) && empty ($lon))) {
            if (!empty ($alt)) {
                $event->data ['meta'] [] = array(
                    'name'    => 'geo.position',
                    'content' => $lat . ';' . $lon . ';' . $alt
                );
            } else {
                $event->data ['meta'] [] = array(
                    'name'    => 'geo.position',
                    'content' => $lat . ';' . $lon
                );
            }
        }
        if (!empty ($country)) {
            $event->data ['meta'] [] = array(
                'name'    => 'geo.country',
                'content' => $country
            );
        }
        if (!(empty ($lat) && empty ($lon))) {
            $event->data ['meta'] [] = array(
                'name'    => "ICBM",
                'content' => $lat . ', ' . $lon
            );
            // icbm is generally useless without a DC.title,
            // so we copy that from title unless it's empty...
            if (!(empty ($title))) {
                /*
                 * don't specify the DC namespace as this is incomplete; it should be done at the
                 * template level as it also needs a 'profile' attribute on the head/container,
                 * see: http://dublincore.org/documents/dc-html/#sect-3.1.1
                 * $event->data ['link'] [] = array ('rel' => 'schema.DC',
                 * 'href' => 'http://purl.org/dc/elements/1.1/');
                 */
                $event->data ['meta'] [] = array(
                    'name'    => "DC.title",
                    'content' => $title
                );
            }
        }
        if (!empty ($geohash)) {
            $event->data ['meta'] [] = array(
                'name'    => 'geo.geohash',
                'content' => $geohash
            );
        }
    }

    /**
     * Inserts the toolbar button.
     *
     * @param Doku_Event $event
     *          the DokuWiki event
     */
    public function insertButton(Doku_Event $event, $param)
    {
        $event->data [] = array(
            'type'   => 'format',
            'title'  => $this->getLang('toolbar_desc'),
            'icon'   => '../../plugins/geotag/images/geotag.png',
            'open'   => '{{geotag>lat:',
            'sample' => '52.2345',
            'close'  => ', lon:7.521, alt: , placename: , country: , region: }}'
        );
    }
}
