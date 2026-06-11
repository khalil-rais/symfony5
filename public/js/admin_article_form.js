import $ from 'jquery';
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css'
import Sortable from 'sortablejs';

/*
    We could fix this in the controller
    but we can also configure Dropzone to use the reference key.
    We're going to do that because, in general,
    as cool as it is that we can just add a "dropzone" class to our form and it mostly works,
    to really get this system working,
    we're going to need to customize a bunch of things on Dropzone.
    Open up admin_article_form.js.
    First, at the very top, add Dropzone.autoDiscover = false.
    That tells Dropzone to not automatically configure itself on any form
    that has the dropzone class: we're going to do it manually.
 */
Dropzone.autoDiscover = false;

$(document).ready(function() {
    initializeDropzone();
    const $autoComplete = $('.js-user-autocomplete');
    if (!$autoComplete.is(':disabled')) {
        import('./components/algolia-autocomplete').then((autocomplete) => {
            autocomplete.default($autoComplete, 'users', 'email');
        });
    }
    const $referenceList = $('.js-reference-list');
    if ($referenceList[0]) {
        var referenceList = new ReferenceList($('.js-reference-list'));
        initializeDropzone(referenceList);
    }
    var $locationSelect = $('.js-article-form-location');
    var $specificLocationTarget = $('.js-specific-location-target');

    $locationSelect.on('change', function(e) {
        $.ajax({
            url: $locationSelect.data('specific-location-url'),
            data: {
                location: $locationSelect.val()
            },
            success: function (html) {
                if (!html) {
                    $specificLocationTarget.find('select').remove();
                    $specificLocationTarget.addClass('d-none');

                    return;
                }

                // Replace the current field and show
                $specificLocationTarget
                    .html(html)
                    .removeClass('d-none')
            }
        });
    });
});

// todo - use Webpack Encore so ES6 syntax is transpiled to ES5
class ReferenceList
{
    constructor($element) {
        var stuff = new WeakSet([]);

        this.$element = $element;
        this.sortable = Sortable.create(this.$element[0], {
            handle: '.drag-handle',
            animation: 150,
            onEnd: () => {
                $.ajax({
                    url: this.$element.data('url')+'/reorder',
                    method: 'POST',
                    data: JSON.stringify(this.sortable.toArray())
                });
            }
        });
        this.references = [];
        this.render();

        this.$element.on('click', '.js-reference-delete', (event) => {
            this.handleReferenceDelete(event);
        });

        this.$element.on('blur', '.js-edit-filename', (event) => {
            this.handleReferenceEditFilename(event);
        });

        $.ajax({
            url: this.$element.data('url')
        }).then(data => {
            this.references = data;
            this.render();
        })
    }

    addReference(reference) {
        this.references.push(reference);
        this.render();
    }

    handleReferenceDelete(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        $li.addClass('disabled');

        $.ajax({
            url: '/admin/article/references/'+id,
            method: 'DELETE'
        }).then(() => {
            this.references = this.references.filter(reference => {
                return reference.id !== id;
            });
            this.render();
        });
    }

    handleReferenceEditFilename(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        const reference = this.references.find(reference => {
            return reference.id === id;
        });
        reference.originalFilename = $(event.currentTarget).val();

        $.ajax({
            url: '/admin/article/references/'+id,
            method: 'PUT',
            data: JSON.stringify(reference)
        });
    }

    render() {
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    <span class="drag-handle fa fa-reorder"></span>
    <input type="text" value="${reference.originalFilename}" class="form-control js-edit-filename" style="width: auto;">

    <span>
        <a href="/admin/article/references/${reference.id}/download" class="btn btn-link btn-sm"><span class="fa fa-download" style="vertical-align: middle"></span></a>
        <button class="js-reference-delete btn btn-link btn-sm"><span class="fa fa-trash"></span></button>
    </span>
</li>
`
        });

        this.$element.html(itemsHtml.join(''));
    }
}

/*
    Copy that name, and, below, add it: function initializeDropzone().
    If I were using Webpack Encore,
    I'd probably organize this function into its own file and import it.
 */
function initializeDropzone() {
    /*
        Copy that, and back inside our JavaScript,
        say var formElement = document.querySelector() with .js-reference-dropzone.
     */
    var formElement = document.querySelector('.js-reference-dropzone');
    /*
        Yes, yes, I'm using straight JavaScript here instead of jQuery
        to be a bit more hipster - no big reason for that.
        There's also a jQuery plugin for Dropzone.
        Next, to avoid an error on the "new" form that doesn't have this element, if !formElement, return.
     */
    if (!formElement) {
        return;
    }
    /*
        Finally, initialize things with var dropzone = new Dropzone(formElement).
        And now we can pass an array of options.
        The one we need now is paramName.
        Set it to reference.
     */
    var dropzone = new Dropzone(formElement, {
        paramName: 'reference',
        /*
            Ok, let's look back at what happened with stars.
            This failed validation
            and so the server returned a 400 status code.
            Dropzone did notice that - it knows it failed.
            But, by default, Dropzone expects the Response to be just a string with the error message,
            not a nice JSON structure with a detail key like we have.
            No worries: we just need a little extra JavaScript to help this along.
            Back in admin_article_form.js,
            add another option called init and set that to a function.
         */
        init: function() {
            /*
                Dropzone calls this when it's setting itself up,
                and it's a great place to add extra behavior via events.
                For example, want to do something whenever there's an error?
                Call this.on('error')
                and pass that a callback with two arguments:
                a file object that holds details about the file that was uploaded and data -
                the data sent back from the server.
             */
            this.on('error', function(file, data) {
                /*
                    Because the real validation message lives on the detail key,
                    we can say: if data.detail, this.emit('error') passing file and the actual error message string: data.detail.
                 */
                if (data.detail) {
                    this.emit('error', file, data.detail);
                }
            });
        }
        /*
            That's it! Refresh the whole thing
            and upload the stars file again.
            It failed but when we hover on it!
            Nice!
            There's our validation error.
            Next: now that our files are automatically uploaded via AJAX,
            the reference list should also automatically update when each upload finishes.
            Let's render that whole section with JavaScript.
         */
    });
    /*
        That should do it!
        Head over and select another file - how about earth.jpeg.
        And... cool!
        It looks like it worked.
        Click to open the profiler for the AJAX request.
        Oh... careful - once again, we got redirected!
        So this is the profiler for the edit page.
        Click the link to go back to the profiler for the POST request
        and go back to the Debug tab.
        Yes! Now we're getting the normal UploadedFile object:
        POST Parameters:
        Uploaded Files:
        Key 	Value
        file

Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▼
  -test: false
  -originalName: "598-fuzzy-logic.jpg"
  -mimeType: "image/jpeg"
  -error: 0
  path: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T"
  filename: "phpgpe0a0mur3154rIAXQr"
  basename: "phpgpe0a0mur3154rIAXQr"
  pathname: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/phpgpe0a0mur3154rIAXQr"
  extension: ""
  realPath: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/phpgpe0a0mur3154rIAXQr"
  aTime: 2026-06-08 16:03:58
  mTime: 2026-06-08 16:03:58
  cTime: 2026-06-08 16:03:58
  inode: 100446500
  size: 13211
  perms: 0100600
  owner: 501
  group: 20
  type: "file"
  writable: true
  readable: true
  executable: false
  file: true
  dir: false
  link: false
}

        Close this and refresh. Look at the list! There is earth.jpeg! It worked!
Dumped Contents
In ArticleReferenceAdminController.php line 86:

Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▼
  -test: false
  -originalName: "image (1).png"
  -mimeType: "image/png"
  -error: 0
  path: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T"
  filename: "php6b8fg489ra5n4wS4PzJ"
  basename: "php6b8fg489ra5n4wS4PzJ"
  pathname: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php6b8fg489ra5n4wS4PzJ"
  extension: ""
  realPath: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php6b8fg489ra5n4wS4PzJ"
  aTime: 2026-06-08 16:21:35
  mTime: 2026-06-08 16:21:35
  cTime: 2026-06-08 16:21:35
  inode: 100453414
  size: 565088
  perms: 0100600
  owner: 501
  group: 20
  type: "file"
  writable: true
  readable: true
  executable: false
  file: true
  dir: false
  link: false
}

        Of course, it's a little weird that it redirected after success
        and if there were a validation error
        that would also cause a redirect
        and so it would look successful to Dropzone.
        The problem is that our endpoint isn't set up to be an API endpoint.
        Let's fix that next and make Dropzone read our validation errors.
     */

}