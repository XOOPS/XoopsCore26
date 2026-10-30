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
    $scPattern = '/(<textarea(?=[\s\/>])[\S\s]*<\/textarea\s*>|<select(?=[\s\/>])[\S\s]*<\/select\s*>'
        . '|<script(?=[\s\/>])[\S\s]*<\/script\s*>|<style(?=[\s\/>])[\S\s]*<\/style\s*>|<!--[\S\s]*-->'
        . '|' . $tagPattern . ')/Ui';

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

    // a placeholder that a shortcode copied into the attributes of a tag it
    // generated is dropped: escaping is not enough where the attribute is a
    // javascript: URL or an event handler, which decode entities before running
    $dropped = array_fill_keys(array_keys($held), '');
    $text = preg_replace_callback(
        '/' . $tagPattern . '/i',
        function ($tag) use ($dropped) {
            return strtr($tag[0], $dropped);
        },
        $text
    );

    return strtr($text, $held);
}
