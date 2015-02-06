/**
 * S**tty function to lazy load images
 *
 * Iterates over tags with "data-original", like:
 *      <img data-original="http://example.com/picture.jpg" />
 *
 * and set the attribute "src" as the value of data-original
 *
 * @param $container
 */
function lazyLoadImagesFromDataOriginal($container) {
    $container.find("img").each(function() {
        if ($(this).hasClass('image-lazy-loaded') == false) {
            $(this).attr('src', $(this).data('original'))
                .addClass('image-lazy-loaded');
        }
    });
}