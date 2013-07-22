var FavoritesPage = function(){
    selector = '#favoritePage';
    posts = [];
    serviceUrl = window.WiziappAccessPoint + '/?wiziapp/content/list/favorites&output=html&androidapp=1&ap=1&pids=';

    // Get the favorites from the storage and save the list
    storedPosts = localStorage.getItem('WiziappFavoritePosts');
    if ( !storedPosts ) {
        //localStorage.setItem('WiziappFavoritePosts', posts.join(','));
        posts = [];
    } else {
        tmpPosts = storedPosts.split(',');
        for(p=0, total=tmpPosts.length; p<total; ++p){
            posts.push(parseInt(tmpPosts[p]));
        }
    }

    // Load the favorites from the server by using the post by ids web service

    if ( storedPosts ){
        // Fetch the posts
        jQuery.get(serviceUrl + storedPosts, function(data){
            var $data = jQuery(data);
            var $page = jQuery('#favorites');

            $page
                .find('.screen')
                .append('<ul class="editable" data-role="listview" data-theme="z"></ul>')
                    .find('ul')
                        .append($data.find('.content-primary ul').html());

            $page.trigger('pageshow');

            $page = $data = null;
        });
    }

    // Attach the screen callbacks


    return {
        refresh: function(){

        },

        havePost: function(post_id){
            return (jQuery.inArray(post_id, posts) != -1);
        },

        add: function(post_id){
            if ( this.havePost(post_id) == false ){
                posts.push(post_id);
                localStorage.setItem('WiziappFavoritePosts', posts.join(','));

                // Update the page html with the new post
                jQuery.get(serviceUrl + post_id, function(data){
                    var $data = jQuery(data);
                    var $page = jQuery('#favorites');

                    $page
                        .find('.screen ul')
                            .append($data.find('.content-primary ul').html());

                    $page.trigger('pageshow');

                    $page = $data = null;
                });
            }
        },

        remove: function(post_id){
            var pos = jQuery.inArray(post_id, posts);
            if ( pos != -1 ){
                posts.splice(pos, 1);
                localStorage.setItem('WiziappFavoritePosts', posts.join(','));

                var $page = jQuery('#favorites');

                $page.find('li[data-post-id=post_'+post_id+']').remove();

                // Check if the page is empty...
                if ( $page.find('.screen ul li').length == 0 ){
                    var cssClass = $page.attr('data-class');
                    $page.find('.screen').addClass(cssClass+'_empty');
                }
                $page = null;
            }
        }
    };
}();