<?php
/*
Plugin Name: Language Specific Anchor Links on Images
Plugin URI: 
Description: The name says everything
Version: 1.0.0
Author: Malte Schulze
License: Proprietary
Text Domain: lsali
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$languages = ["de_DE" => "Deutsch", "en_EN" => "Englisch", "de_CH" => "Schweizer Deutsch", "nl_NL" => "Holländisch"];

// backend functionality

add_filter('attachment_fields_to_edit', 'attachment_fields_to_edit', null, 2);
function attachment_fields_to_edit($form_fields, $post): array
{
    global $languages;
    foreach ($languages as $index => $name) {
        $form_fields['lsali_' . $index] = array(
            'label' => 'Link für: ' . $name,
            'input' => 'text',
            'value' => get_post_meta($post->ID, '_' . 'lsali_' . $index, true)
        );
    }

    return $form_fields;
}

add_filter('attachment_fields_to_save', 'attachment_fields_to_save', null, 2);
function attachment_fields_to_save($post, $attachment)
{
    global $languages;
    foreach ($languages as $index => $name) {
        if (!empty($attachment['lsali_' . $index])) {
            update_post_meta($post['ID'], '_' . 'lsali_' . $index, $attachment['lsali_' . $index]);
        } else {
            delete_post_meta($post['ID'], '_' . 'lsali_' . $index);
        }
    }
    return $post;
}

add_filter('render_block', 'lsali_wrap_images', 10, 2);
function lsali_wrap_images($block_content, $block)
{

    if ('core/image' !== $block['blockName']) {
        return $block_content;
    }

    $currentNeededLanguage = "en_EN";
    if (strpos($block_content, 'data-id')) {
        // find id out of html
        $findIdRelativeToString = "data-id";
        $locationOfBeginnFromDataIdAttribute = strpos($block_content, $findIdRelativeToString);
        $dataId = $block_content[$locationOfBeginnFromDataIdAttribute + strlen($findIdRelativeToString) + 2]; // +2 because next chars are '="'
        // TODO dataId can be more than just one digit, so fetch everything in between them double-quotes

        // get complete image attachment by id
        $wpImageObject = get_post_meta($dataId);
        $supportedLink = "";
        if (!empty($wpImageObject["_lsali_" . $currentNeededLanguage][0])) {
            $supportedLink = $wpImageObject["_lsali_" . $currentNeededLanguage][0];
        }
    }

    $return = "";
    if (!empty($supportedLink)) {
        $return .= '<a href="' . $supportedLink . '" class="lsali_anchor">';
        $return .= $block_content;
        $return .= '</a>';
    } else {
        $return .= $block_content;
    }


    return $return;
}



// möglichkeit b: mit template_redirect hook über das komplette template gehen und nach diesen images suchen und wrappen -> js?
// -> wie daten dahin? -> daten in script tag in die seite einbetten und dann nimmt die js file die var
// wrapping anchor with right language link around img tag
//add_filter('wp_get_attachment_image', 'lsali_wp_get_attachment_image', null, 2);
//function lsali_wp_get_attachment_image($html): string
//{
//    return '<a href="' . "!EINDEUTIG!" . '">' . $html . '</a>';
//}

// putting needed var in website so js can use it "later"
add_action('template_redirect', function () {
    echo '
<script>
let lsali_images = 
{
    
};
</script>';
});