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
    const $autoComplete = $('.js-user-autocomplete');
    if (!$autoComplete.is(':disabled')) {
        import('./components/algolia-autocomplete').then((autocomplete) => {
            autocomplete.default($autoComplete, 'users', 'email');
        });
    }
    const $referenceList = $('.js-reference-list');
    if ($referenceList[0]) {
        /*
            Before we dive into this class,
            let's start using it up on our document.ready() function.
            Say var referenceList = new ReferenceList() and
            pass it $('.js-reference-list')
            - that's the element we just added the attribute to.
         */
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

/*
    Next, in admin_article_form.js,
    I'm going to paste in a class that I've started:
    you can copy this from the code block on this page.
    This uses the newer "class" syntax from JavaScript
    which is compatible with most browsers,
    but not all of them.
    That's why I've added this note to use Webpack Encore,
    which will rewrite the new syntax
    so that it's compatible with whatever browsers you need.
 */
// todo - use Webpack Encore so ES6 syntax is transpiled to ES5
class ReferenceList
{
    /*

     */
    constructor($element) {
        var stuff = new WeakSet([]);
        /*
            And... yea! The class mostly takes care of the rest!
            In the constructor(), we take in the jQuery element and store it on this.$element.
            It also keeps track of all the references that it has,
            which starts empty and calls this.render(),
            whose job is to completely fill the ul element.
         */
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
        /*
            Finally, at the bottom, we take all that HTML
            and stick it into the element.
            This is a bit similar to what React does, but definitely less powerful.
            Back up in the constructor, the references array starts empty,
            but we immediately make an Ajax call by reading the data-url attribute off of our element.
            When it finishes, we set this.references to its data and once again call this.render().
            Phew! Let's see if it actually works!
            Refresh and... yes!
            If you watched closely, it was empty for a moment,
            then filled in once the AJAX call finished.
         */
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
        /*
            this.references.map is a fancy way to loop over the references array,
            which is empty at the start,
            but won't be forever.
            For each reference, it creates a string of HTML that is basically a copy of
            what we had in our template before.
            This uses a feature called template literals that allows us
            to create a multi-line string with variables inside -
            like reference.originalFilename and referenced.id.
            The data from the references will ultimately come from our new endpoint,
            so I'm using the same keys that our JSON has.
         */
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    ${reference.originalFilename}
    <span>
        <a href="/admin/article/references/${reference.id}/download">
            <span class="fa fa-download"></span>
        </a>
    </span>
</li>`
            /*
                I did hardcode the URL to the download endpoint instead of doing something fancier.
                You could generate that with FOSJsRoutingBundle if you want,
                but hardcoding it is also not a huge deal.
             */
        });

        this.$element.html(itemsHtml.join(''));
    }
}

/*
    Copy that name, and, below, add it: function initializeDropzone().
    If I were using Webpack Encore,
    I'd probably organize this function into its own file and import it.
 */
function initializeDropzone(referenceList) {
    var formElement = document.querySelector('.js-reference-dropzone');
    if (!formElement) {
        return;
    }

    var dropzone = new Dropzone(formElement, {
        paramName: 'reference',
        init: function() {
            this.on('success', function(file, data) {
                referenceList.addReference(data);
            });

            this.on('error', function(file, data) {
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