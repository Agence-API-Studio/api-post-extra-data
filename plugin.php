<?php
/*
 * Plugin Name:       API Post Extra Data
 * Plugin URI:        https://github.com/Agence-API-Studio/api-post-extra-data
 * Description:       Le module ajoute 3 données à la liste des articles : nombre de mots, nombre d'images et présence de lien externe
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            API Studio
 * Author URI:        https://www.api-studio.fr
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aped
 */

// Ajout de post_metas pour enregistrer le nombre de mots et le nombre d'images dans la base de données.
add_action('save_post', 'apiPostMeta');
function apiPostMeta($post_id) 
{
    $contenuArticle = get_post_field('post_content', $post_id);
    $nbMotsArticles = str_word_count(strip_tags($contenuArticle));
    $listImages = get_children(array(
    'post_parent' => $post_id,
    'post_type' => 'attachment',
    'post_mime_type' => 'image')
    );
    $totalImages = count($listImages);
    // Sauvegarde dans la base de données
    update_post_meta($post_id, '_nb_mots', $nbMotsArticles);
    update_post_meta($post_id, '_nb_images', $totalImages);
}


// Ajout des 3 colonnes supplémentaires : nombre de mots, nombre d'images, lien externe.
add_filter('manage_posts_columns', 'apiAjouterColonnes');
function apiAjouterColonnes($colonnes) 
{
    $colonnes['nb_mots'] = __('Mots', 'aped');
    $colonnes['nb_images'] = __('Images', 'aped');
    $colonnes['lien_externe'] = __('Lien Externe', 'aped');
    return $colonnes;
}

// Ajout des data dans les nouvelles colonnes.
add_action('manage_posts_custom_column', 'apiAjoutDataColonnes', 10, 2);
function apiAjoutDataColonnes($nomColonne, $post_id) 
{
    $urlSite = get_site_url();
    if ('nb_mots' == $nomColonne) 
    {
        // Récupération du contenu de l'article
        $contenuPost = get_post_field('post_content', $post_id);
        // Calcul du nombre de mots
        $nombreMots = str_word_count(strip_tags($contenuPost));
        // Affichage
        echo '<span style="color:grey; font-weight:bold;">'.$nombreMots.'</span>';
    }
    elseif ('nb_images' == $nomColonne) 
    {
        // Récupération des "attachments" / images
        $attachments = get_children(array(
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'post_mime_type' => 'image')
        );
        // Affichage
        echo '<span style="color:grey; font-weight:bold;">'.count($attachments).'</span>';
    }
    elseif ('lien_externe' == $nomColonne) 
    {
        // Récupération du contenu de l'article
        $contenuPost = get_post_field('post_content', $post_id);
        // Regexp pour détecter un lien externe
        $regexLien = '/http(s)?:\/\/[\w\-]+(\.[\w\-]+)+[\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#]/i';
        // Récupération de tous les liens de l'articles
        $liensArticles = preg_match_all($regexLien, $contenuPost, $externe);
        // On boucle dans le tableau contenant tous les liens et on vérifie s'il s'agit d'un lien externe ou non
        foreach($externe[0] as $url) 
        {
            // Si lien externe = Oui
            if(strpos($url, $urlSite) === false) 
            {
                echo '<span style="color:red; font-weight:bold;">'.__('Oui', 'aped').'</span>';
                return;
            }
        }
        // Par défaut = non
        echo '<span style="color:green; font-weight:bold;">'.__('Non', 'aped').'</span>';
    }
}

// Ajout de la capacité de tri dans le tableau des articles.
add_filter('manage_edit-post_sortable_columns', 'apiAjoutTriColonnes');
function apiAjoutTriColonnes($colonnes) {
    $colonnes['nb_mots'] = 'nb_mots';
    $colonnes['nb_images'] = 'nb_images';
    return $colonnes;
}

// Fonction pour trier dans l'ordre croissant / décroissant les data.
add_action('pre_get_posts', 'apiTriColonne');
function apiTriColonne($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if ('nb_mots' == $query->get('orderby')) {
        $query->set('meta_key', '_nb_mots');
        $query->set('orderby', 'meta_value_num');
    }
    if ('nb_images' == $query->get('orderby')) {
        $query->set('meta_key', '_nb_images');
        $query->set('orderby', 'meta_value_num');
    }
}

?>
