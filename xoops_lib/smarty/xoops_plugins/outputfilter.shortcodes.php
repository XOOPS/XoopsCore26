<?php
/*
 * Smarty plugin to enable shortcodes in templates
 *
 * Shortcodes are expanded only in text between HTML tags. Tags (and so their
 * attributes) and the bodies of form, script and style elements are swapped out
 * for random placeholders first, because text there is already escaped for its
 * context and shortcode markup would break out of it.
 */
function smarty_outputfilter_shortcodes($output, Smarty_Internal_Template $template)
{
    $shortcodes = \Xoops\Core\Text\Sanitizer::getInstance()->getShortCodes();

    $tagPattern = '<[\/!]?[a-z](?:[^>"\']|"[^"]*"|\'[^\']*\')*>';
    // CDATA sections (inert text inside SVG and MathML) and the bogus comments a
    // browser makes of any other <!, <? or </ are held whole too: escaping their
    // opener would turn the markup they hide into live tags. An unclosed element
    // or comment is held to the end of the output and a comment ends where a
    // browser ends it (<!-->, <!--->, --!>), as a browser reads them. An unclosed
    // CDATA is held only to its first >, which ends it outside SVG and MathML;
    // inside them the rest stays inert text whatever the shortcodes make of it.
    $scPattern = '/(<textarea(?=[\s\/>])[\S\s]*(?:<\/textarea\s*>|\z)|<select(?=[\s\/>])[\S\s]*(?:<\/select\s*>|\z)'
        . '|<script(?=[\s\/>])[\S\s]*(?:<\/script\s*>|\z)|<style(?=[\s\/>])[\S\s]*(?:<\/style\s*>|\z)'
        . '|<!--(?:-?>|[\S\s]*(?:--!?>|\z))|<\?[\S\s]*\?>|<!\[CDATA\[[\S\s]*\]\]>'
        . '|' . $tagPattern . '|<[!?\/][^>]*>)/Ui';

    $marker = "\x1A" . bin2hex(random_bytes(8)) . '_';
    $held = array();
    $text = preg_replace_callback(
        $scPattern,
        function ($innerMatches) use ($marker, &$held) {
            $key = $marker . count($held) . "\x1A";
            $held[$key] = $innerMatches[1];
            return $key;
        },
        $output
    );
    if ($text===null) {
        trigger_error('preg_last_error=' . preg_last_error(), E_USER_WARNING);
        return $output;
    }

    // every remaining < is text: escape it, so no tag dropped below can join it
    // with the text that follows into new markup
    $text = $shortcodes->process(str_replace('<', '&lt;', $text));

    // a tag a shortcode generated with a placeholder copied into its attributes
    // is dropped whole: escaping the held markup is not enough where the
    // attribute is a URL or an event handler, which decode entities before
    // running, and removing just the placeholder joins its neighbours, so that
    // [url=java<b></b>script:...] would become a javascript: link
    $text = preg_replace_callback(
        '/' . $tagPattern . '/i',
        function ($tag) use ($marker) {
            return strpos($tag[0], $marker) === false ? $tag[0] : '';
        },
        $text
    );

    return strtr($text, $held);
}
