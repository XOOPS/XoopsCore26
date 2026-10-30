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

    $scPattern = '/(<textarea[\S\s]*\/textarea\s*>|<select[\S\s]*\/select\s*>|<script[\S\s]*\/script\s*>'
        . '|<style[\S\s]*\/style\s*>|<!--[\S\s]*-->|<[\/!]?[a-z](?:[^>"\']|"[^"]*"|\'[^\']*\')*>)/Ui';

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

    $text = $shortcodes->process($text);

    return strtr($text, $held);
}
