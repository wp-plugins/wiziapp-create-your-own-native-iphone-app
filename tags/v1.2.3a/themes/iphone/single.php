<?php ob_start();
    $processed = FALSE;
    if (have_posts()) : while (have_posts() && !$processed) :
        global $post;
        setup_postdata($post);
                            
        wiziapp_content_get_post_headers(true);
        echo ob_get_contents();
        ob_end_clean();
    
        include('_content.php');
        $processed = TRUE;
    ?>
<?php endwhile; else :
// No content???
endif; 
?>