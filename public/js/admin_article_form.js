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
        /*
            Back on top, pass in the object - referenceList.
         */
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
        /*
            Next, open admin_article_form.js
            and scroll up to the constructor so we can start using this.
            Here's the plan:
            we're going to make each element - each "row" - sortable.
            And when we finish dragging,
            we'll send an AJAX request to save the new positions.
            Add this.sortable = Sortable.create().
            We're storing the instance of our new sortable object onto a property
            because we'll need it later.
            Pass this the parent of the elements that should be sortable.
            So in our case, we want to attach sortable to the <ul> element that's around everything.
            Fortunately, that's exactly what this.$element represents!
            So we can say this.$element,
            and, this actually wants a raw HTMLElement, not a jQuery object, so add [0].

            Give it a test! Refresh... and grab... sweet!
            When we finish ordering, nothing saves yet, but we'll get there.
         */
        /*
            Before we do, I think we can make this a bit nicer.
            Pass a second argument to create(): an array of options.
            Pass one called handle set to .drag-handle.
         */
        this.sortable = Sortable.create(this.$element[0],{
            handle: '.drag-handle',
            /*
                Oh, and while we're making this fancy, add animation:
                150... it just makes it look cooler. Try it!
                There's our drag handle and... nice - it's a bit smoother.
             */
            animation: 150,
        });
        this.references = [];
        this.render();
        /*
            Copy that class name and go back up to the constructor.
            Here say this.$element.on('click') and then pass .js-reference-delete.
            This is called a delegate event handler.
            It's handy because it allows us to attach a listener to any .js-reference-delete elements,
            even if they're added to the HTML after this line is executed.
            For the callback, I'll pass an ES6 arrow function so that
            the this variable inside is still my ReferenceList object.
            Call a new method: this.handleReferenceDelete() and pass it the event object.
         */
        this.$element.on('click', '.js-reference-delete', (event) => {
            this.handleReferenceDelete(event);
        });
        /*
            Next: copy the js- class name and head back up to the constructor.
            We're going to do the same thing we did with our delete link:
            this.$element.on('blur'),
            this time with .js-edit-filename and then our arrow function.
            Inside that, call a new function:
            this.handleReferenceEditFilename() and pass that the event.
         */
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

    /*
        Ok, refresh, select any file and... in the console... nice!
        We already did the work of returning the new ArticleReference JSON on success...
        even though we didn't need it before.
        Thanks past us!
        And now, we're dangerous.
        If we can somehow take that data,
        put it into the references property in our class and re-render, we'll be good!
        To help that, add a new function called addReference().
        This will take in a new reference and then push it onto this.references.
        Then call this.render().
     */
    /*
        For people that are used to React,
        I do want to mention two things.
        First, we're mutating, um, changing the this.references property
        when we say this.references.push().
        Changing "state", which is basically what this is,
        is a big "no no" in React.
        But in our simpler system, it's fine.
        Second, each time we call this.render(),
        it is completely emptying the ul and re-adding all the HTML from scratch.
        Front-end frameworks like React or Vue are way smarter than this
        and are able to update just the pieces that changed.
     */
    /*
        Anyways, inside of initializeDropzone(), add a referenceList argument:
        we're going to force this to get passed to us.
        I'll even document that this will be an instance of the ReferenceList class.
     */
    addReference(reference) {
        this.references.push(reference);
        this.render();
    }

    handleReferenceDelete(event) {
        /*
            Copy that name, head down, and paste to create that.
            Inside, we need to do two things:
            make the AJAX request to delete the item from the server
            and remove the reference from the references array and call this.render() so it disappears.
            Start with const $li =.
            I'm going to use the button that was just clicked to find the <li> element
            that's around everything -
            you'll see why in a second.
            So, const $li = $(event.currentTarget) to get the button that was clicked,
            then .closest('.list-group-item').
         */
        const $li = $(event.currentTarget).closest('.list-group-item');
        /*
            To create the URL for the DELETE request,
            I need the id of this specific article reference.
            To get that, add a new data-id attribute on the li set to ${reference.id}.
            I'm adding this here instead of directly on the button
            so that we could re-use it for other behaviors.
            Now we can say const id = $li.data('id') and $li.addClass('disabled')
            to make it look like we're doing something during the AJAX call.
         */
        const id = $li.data('id');
        $li.addClass('disabled');
        /*
            Make that with $.ajax() with url() set to '/admin/article/references/'+id and method "DELETE":
         */
        $.ajax({
            url: '/admin/article/references/'+id,
            method: 'DELETE'
        })
            /*
                To handle success, chain a .then() on this with another arrow function.
             */
            .then(() => {
            this.references = this.references.filter(reference => {
                /*
                    Now that the article reference has been deleted from the server,
                    let's remove it from this.references.
                    A nice way to do that is by saying:
                    this.references = this.references.filter() and passing this an arrow function with return reference.id !== id.
                 */
                return reference.id !== id;
            });
            /*
                This callback function will be called once for each item in the array.
                If the function returns true,
                that item will be put into the new references variable.
                If it returns false, it won't be.
                The end effect is that we get an identical array,
                except without the reference that was just deleted.
                After this, call this.render().
             */
            this.render();
            /*
                Let's try it! Refresh and... cool!
                There's our delete icon - it looks a little weird,
                but we'll fix that in a minute.
                Let's see, in var/uploads we have a rocket.jpeg file.
                Let's delete that one.
                Ha! It disappeared! The 204 status code looks good and the file is gone!
DELETE
	https://127.0.0.1:8000/admin/article/references/2
Status
204
VersionHTTP/2
Transferred296 B (0 B size)
Referrer Policystrict-origin-when-cross-origin
Request PriorityHighest
DNS ResolutionSystem
                It's strange when things work on the first try!
             */
        });
    }
    /*
        Keep going: copy the method name,
        scroll down a bit,
        and create that function,
        which will accept an event object.
        Let's also steal the first two lines from handleReferenceDelete():
        we're going to start the exact same way.
     */
    handleReferenceEditFilename(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        /*
            Heck, we're going to make an AJAX request to the same URL!
            Just with the PUT method insteadof DELETE.
            When we send that AJAX request,
            we're only going to send one piece of data:
            the originalFilename that's in the text box.
            But I want you to pretend that we're allowing multiple fields to be updated on the reference.
            So, more abstractly, what we were really want to do
            is find the reference that's being updated from inside this.references,
            change the originalFilename data on it, JSON-encode that entire object,
            and send it to the endpoint.
            If that doesn't make sense yet, don't worry.
            To find the reference object that's being updated right now,
            say const reference = this.references.find()
            and pass this an arrow function with a reference argument.
            Inside, return reference.id === id.
         */
        const reference = this.references.find(reference => {
            //return reference === id;
            return reference.id === id;
        });
        /*
            This loops over all the references
            and returns the first one it finds that matches the id...
            which should only be one.
            Now change the originalFilename property to $(event.currentTarget) -
            that will give us the input element - .val().
         */
        reference.originalFilename = $(event.currentTarget).val();
        /*
            Ok! We're ready to send the AJAX request!
            Copy the first-half of the AJAX call from the delete function,
            remove the .then() stuff,
            change the method to PUT and, for the data, just pass reference.
         */

        $.ajax({
            url: '/admin/article/references/'+id,
            method: 'PUT',
            /*
                When we set the data key to the reference object,
                jQuery doesn't send up that data as JSON,
                it uses the standard "form submit" format.
                We want JSON.stringify(reference).
             */
            // data: reference
            data: JSON.stringify(reference)
            /*
                I think we've got it this time. Refresh,
                tweak the filename, click off and... no errors!
                Check out the network tab. Yeah 200!
                The response returns the updated originalFilename and,
                if you scroll down to the request body... cool!
                You can see the raw JSON that was sent up.
                Request: {"{\"id\":3,\"filename\":\"cv-rais-de-260602-6a22edc59ceac.pdf\",\"originalFilename\":\"CV_Rais.pdf\",\"mimeType\":\"application/pdf\"}": ""
                Response: {"id":3,"filename":"cv-rais-de-260602-6a22edc59ceac.pdf","originalFilename":"CV_Rais.pdf","mimeType":"application\/pdf"}
}
             */
        });
        /*
            There is a small problem with this - so if you see it, hang on!
            But, the idea is cool: we're sending up all of the reference data.
            And yes, this will send more fields than we need, but that's ok!
            The deserializer just ignores that extra stuff.
            Testing time! Refresh the whole page.
            Oh wow - we have an extra < sign! As cool as that looks,
            let's scroll down to render and... there it is - remove that.
            Refresh again.
            Let's tweak the filename and then click off to trigger the "blur".
            Uh oh! “Cannot set property originalFilename of undefined.”
            Hmm. Look back at our code: for some reason it's not finding our reference.
            Oh, duh: return referenced.id === id.
         */
    }

    render() {
        /*
            With this, instead of being able to grab anywhere to start sorting,
            we'll only be able to grab elements with this class.
            Down in render, how about, before the text field,
            add <span class="drag-handle">, and fa and fa-reorder.
         */
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    <span class="drag-handle fa fa-reorder"></span>
    <input 
        type="text" 
        value="${reference.originalFilename}" 
        class="form-control js-edit-filename" 
        style="width: auto;"
    >
    <span>
        <a href="/admin/article/references/${reference.id}/download" class="btn btn-link btn-sm">
            <span class="fa fa-download"  style="vertical-align: middle"></span>
        </a>
        <button class="js-reference-delete btn btn-link btn-sm">
            <span class="fa fa-trash"></span>
        </button>
    </span>
</li>`
            /*
                Next: our users are begging for another feature:
                the ability to rename the file after it's been uploaded.
             */
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
                /*
                    Now that we're rendering this in JavaScript,
                    we have a clean way to add a new row whenever a file finishes uploading.
                    Back inside the init function for Dropzone,
                    add another event listener:
                    this.on('success') and pass a callback with the same file and data arguments.
                    To start, just console.log(data) so we can see what it looks like.
                    console.log(file, data);
                    15:37:25.440
                    File { upload: {…}, status: "success", previewElement: div.dz-preview.dz-processing.dz-image-preview.dz-success, previewTemplate: div.dz-preview.dz-processing.dz-image-preview.dz-success, accepted: true, processing: true, xhr: XMLHttpRequest, dataURL: "data:image/png;base64,iV...c1/rX", width: 316, height: 170 }

                    Object { id: 12, filename: "binarylogic-6a2aba155ea72.png", originalFilename: "BinaryLogic.png", mimeType: "image/png" }
                    admin_article_form.js:221:25

                 */
                /*
                    And now inside success, instead of console.log(),
                    we'll say referenceList.addReference(data).
                 */
                referenceList.addReference(data);
                /*
                    Cool! Give your page a nice refresh.
                    And... let's see: astronaut.jpg is the last file on the list currently.
                    So let's upload Earth from the Moon.jpeg.
                    It uploads and... boom! So fast!
                    We can even instantly downloaded it.
                    Next: let's keep leveling up: authors need a way to delete existing file references.
                 */
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