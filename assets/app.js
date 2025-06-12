import './bootstrap.js';
// Import Bootstrap's JavaScript
import 'bootstrap';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
import './styles/app.css';
import $ from 'jquery';
// any CSS you import will output into a single css file (app.css in this case)
var $container = $('.js-vote-arrows');
$container.find('a').on('click', function (e) {
    e.preventDefault();
    var $link = $(e.currentTarget);

    $.ajax({
        url: '/comments/10/vote/' + $link.data('direction'),
        method: 'POST'
    }).then(function(data) {
        $container.find('.js-vote-total').text(data.votes);
    });
})
